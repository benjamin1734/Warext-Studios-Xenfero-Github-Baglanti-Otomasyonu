<?php

namespace Warext\GitHubSync\Service\Sync;

use RuntimeException;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;

final class XenForoActionExecutor
{
    public function __construct(private SyncRegistry $registry) {}

    public function execute(Mapping $mapping, Repository $repository, array $action, string $payloadHash): array
    {
        return SyncGuard::run(fn() => $this->executeInternal($mapping, $repository, $action, $payloadHash));
    }

    private function executeInternal(Mapping $mapping, Repository $repository, array $action, string $payloadHash): array
    {
        if ($action['message'] === '')
        {
            throw new RuntimeException('Generated XenForo message is empty.');
        }

        $user = \XF::em()->find('XF:User', (int)$action['user_id']);
        if (!$user)
        {
            throw new RuntimeException('Configured XenForo automation user was not found.');
        }

        $existing = $this->registry->find($mapping, $action['object_type'], $action['object_id']);
        if (($action['action_mode'] ?? 'upsert') === 'delete')
        {
            return $this->deleteExisting($existing, $action, $user);
        }
        if ($existing)
        {
            return $this->updateExisting($mapping, $repository, $existing, $action, $user, $payloadHash);
        }

        if ((int)$action['thread_id'] <= 0 && !empty($action['parent_object_type']) && !empty($action['parent_object_id']))
        {
            $parentSync = $this->registry->findInRepository(
                $repository,
                (string)$action['parent_object_type'],
                (string)$action['parent_object_id']
            );
            if ($parentSync && (string)$parentSync->xf_content_type === 'post')
            {
                $parentPost = \XF::em()->find('XF:Post', (int)$parentSync->xf_content_id);
                if ($parentPost && $parentPost->Thread)
                {
                    $action['thread_id'] = (int)$parentPost->thread_id;
                }
            }
        }

        if ((int)$action['thread_id'] > 0)
        {
            $thread = \XF::em()->find('XF:Thread', (int)$action['thread_id']);
            if (!$thread)
            {
                throw new RuntimeException('Configured XenForo target thread was not found.');
            }

            if (!empty($action['update_first_post']))
            {
                $post = $this->editPost($thread->FirstPost, $action['message'], $user);
                if (!empty($action['update_title']) && $action['title'] !== '')
                {
                    $this->editThreadTitle($thread, $action['title'], $user);
                }
            }
            else
            {
                $post = $this->reply($thread, $action['message'], $user);
                if (!empty($action['update_title']) && $action['title'] !== '')
                {
                    $this->editThreadTitle($thread, $action['title'], $user);
                }
            }

            $this->applyThreadMetadata($thread, $action);

            $this->registry->register(
                $mapping, $repository, $action['object_type'], $action['object_id'],
                'post', (int)$post->post_id, $payloadHash,
                array_replace(['thread_id' => (int)$thread->thread_id], (array)($action['sync_metadata'] ?? []))
            );
            return ['type' => 'post', 'id' => (int)$post->post_id, 'thread_id' => (int)$thread->thread_id, 'operation' => 'created'];
        }

        if ((int)$action['node_id'] <= 0)
        {
            throw new RuntimeException('Mapping requires either target thread_id or node_id.');
        }

        $forum = \XF::em()->find('XF:Forum', (int)$action['node_id']);
        if (!$forum)
        {
            throw new RuntimeException('Configured XenForo target forum was not found.');
        }
        if ($action['title'] === '')
        {
            throw new RuntimeException('Generated XenForo thread title is empty.');
        }

        $thread = \XF::asVisitor($user, function() use ($forum, $action)
        {
            /** @var \XF\Service\Thread\Creator $creator */
            $creator = \XF::service('XF:Thread\\Creator', $forum);
            $creator->setContent($action['title'], $action['message']);
            if ((int)$action['prefix_id'] > 0)
            {
                $creator->setPrefix((int)$action['prefix_id']);
            }
            if (in_array((string)($action['remote_state'] ?? ''), ['open', 'closed'], true))
            {
                $creator->setDiscussionOpen((string)$action['remote_state'] === 'open');
            }
            $creator->setIsAutomated();
            return $creator->save();
        });

        $post = $thread->FirstPost;
        $this->applyThreadMetadata($thread, $action);
        $this->registry->register(
            $mapping, $repository, $action['object_type'], $action['object_id'],
            'post', (int)$post->post_id, $payloadHash,
            array_replace(['thread_id' => (int)$thread->thread_id, 'created_thread' => true], (array)($action['sync_metadata'] ?? []))
        );

        return ['type' => 'thread', 'id' => (int)$thread->thread_id, 'post_id' => (int)$post->post_id, 'operation' => 'created'];
    }

    private function deleteExisting($sync, array $action, $user): array
    {
        if (!$sync)
        {
            return ['type' => 'none', 'id' => 0, 'operation' => 'noop'];
        }

        $policy = (string)($action['delete_policy'] ?? 'mark_deleted');
        if ($policy === 'ignore')
        {
            $sync->status = 'remote_deleted';
            $sync->last_sync_date = \XF::$time;
            $sync->save();
            return ['type' => (string)$sync->xf_content_type, 'id' => (int)$sync->xf_content_id, 'operation' => 'ignored_delete'];
        }

        if ((string)$sync->xf_content_type !== 'post')
        {
            throw new RuntimeException('Unsupported synchronized content type for deletion.');
        }

        $post = \XF::em()->find('XF:Post', (int)$sync->xf_content_id);
        if (!$post)
        {
            $sync->status = 'remote_deleted_orphaned';
            $sync->last_sync_date = \XF::$time;
            $sync->save();
            return ['type' => 'post', 'id' => (int)$sync->xf_content_id, 'operation' => 'already_missing'];
        }

        if ($policy === 'mark_deleted')
        {
            $notice = trim((string)($action['message'] ?? ''));
            if ($notice === '')
            {
                $notice = '[B]Bu içerik GitHub tarafında kaldırıldı.[/B]';
            }
            else
            {
                $notice = "[B]GitHub tarafındaki kaynak kaldırıldı.[/B]\n\n" . $notice;
            }
            $this->editPost($post, $notice, $user);
            $sync->status = 'remote_deleted';
            $sync->last_sync_date = \XF::$time;
            $sync->save();
            return ['type' => 'post', 'id' => (int)$post->post_id, 'operation' => 'marked_deleted'];
        }

        if (!in_array($policy, ['soft_delete', 'hard_delete'], true))
        {
            throw new RuntimeException('Unknown delete policy: ' . $policy);
        }

        $metadata = is_array($sync->metadata) ? $sync->metadata : [];
        $thread = $post->Thread;
        $isFirstPost = $thread && (int)$thread->first_post_id === (int)$post->post_id;
        $deleteType = $policy === 'hard_delete' ? 'hard' : 'soft';

        if ($isFirstPost)
        {
            if (empty($metadata['created_thread']))
            {
                throw new RuntimeException('Refusing to delete a pre-existing XenForo thread first post. Use mark_deleted or ignore.');
            }
            \XF::asVisitor($user, function() use ($thread, $deleteType)
            {
                /** @var \XF\Service\Thread\Deleter $deleter */
                $deleter = \XF::service('XF:Thread\\Deleter', $thread);
                $deleter->delete($deleteType, 'GitHub synchronized object was deleted.');
            });
        }
        else
        {
            \XF::asVisitor($user, function() use ($post, $deleteType)
            {
                /** @var \XF\Service\Post\Deleter $deleter */
                $deleter = \XF::service('XF:Post\\Deleter', $post);
                $deleter->delete($deleteType, 'GitHub synchronized object was deleted.');
            });
        }

        $sync->status = 'remote_deleted';
        $sync->last_sync_date = \XF::$time;
        $sync->save();
        return ['type' => 'post', 'id' => (int)$post->post_id, 'operation' => $policy];
    }

    private function updateExisting(Mapping $mapping, Repository $repository, $sync, array $action, $user, string $payloadHash): array
    {
        if ((string)$sync->last_github_hash === $payloadHash)
        {
            return ['type' => (string)$sync->xf_content_type, 'id' => (int)$sync->xf_content_id, 'operation' => 'noop'];
        }

        if ((string)$sync->xf_content_type !== 'post')
        {
            throw new RuntimeException('Unsupported existing XenForo sync content type.');
        }

        $post = \XF::em()->find('XF:Post', (int)$sync->xf_content_id);
        if (!$post)
        {
            $sync->status = 'orphaned';
            $sync->save();
            throw new RuntimeException('Previously synchronized XenForo post no longer exists.');
        }

        $this->editPost($post, $action['message'], $user);
        if (!empty($action['update_title']) && $action['title'] !== '' && $post->Thread)
        {
            $this->editThreadTitle($post->Thread, $action['title'], $user);
        }
        if ($post->Thread)
        {
            $this->applyThreadMetadata($post->Thread, $action);
        }

        $this->registry->register(
            $mapping, $repository, $action['object_type'], $action['object_id'],
            'post', (int)$post->post_id, $payloadHash,
            array_replace(['thread_id' => (int)$post->thread_id], (array)($action['sync_metadata'] ?? []))
        );

        return ['type' => 'post', 'id' => (int)$post->post_id, 'thread_id' => (int)$post->thread_id, 'operation' => 'updated'];
    }

    private function reply($thread, string $message, $user)
    {
        return \XF::asVisitor($user, function() use ($thread, $message)
        {
            /** @var \XF\Service\Thread\Replier $replier */
            $replier = \XF::service('XF:Thread\\Replier', $thread);
            $replier->setMessage($message);
            $replier->setIsAutomated();
            return $replier->save();
        });
    }

    private function editPost($post, string $message, $user)
    {
        if (!$post)
        {
            throw new RuntimeException('Target XenForo post was not found.');
        }
        \XF::asVisitor($user, function() use ($post, $message)
        {
            /** @var \XF\Service\Post\Editor $editor */
            $editor = \XF::service('XF:Post\\Editor', $post);
            $editor->setMessage($message);
            $editor->setIsAutomated();
            $editor->save();
        });
        return $post;
    }

    private function applyThreadMetadata($thread, array $action): void
    {
        $changed = false;
        if (!empty($action['sync_remote_labels']))
        {
            $prefixId = max(0, (int)($action['prefix_id'] ?? 0));
            if ((int)$thread->prefix_id !== $prefixId)
            {
                $thread->prefix_id = $prefixId;
                $changed = true;
            }
        }
        elseif ((int)($action['prefix_id'] ?? 0) > 0 && (int)$thread->prefix_id !== (int)$action['prefix_id'])
        {
            $thread->prefix_id = (int)$action['prefix_id'];
            $changed = true;
        }

        $state = (string)($action['remote_state'] ?? '');
        if (in_array($state, ['open', 'closed'], true))
        {
            $open = $state === 'open';
            if ((bool)$thread->discussion_open !== $open)
            {
                $thread->discussion_open = $open;
                $changed = true;
            }
        }

        if ($changed)
        {
            $thread->save();
        }
    }

    private function editThreadTitle($thread, string $title, $user): void
    {
        \XF::asVisitor($user, function() use ($thread, $title)
        {
            /** @var \XF\Service\Thread\Editor $editor */
            $editor = \XF::service('XF:Thread\\Editor', $thread);
            $editor->setTitle($title);
            $editor->setIsAutomated();
            $editor->save();
        });
    }
}
