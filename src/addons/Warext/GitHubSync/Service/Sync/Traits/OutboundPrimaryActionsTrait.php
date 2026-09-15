<?php

namespace Warext\GitHubSync\Service\Sync\Traits;

use RuntimeException;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;

trait OutboundPrimaryActionsTrait
{
    private function syncThread(int $threadId): void
    {
        $thread = \XF::em()->find('XF:Thread', $threadId);
        if (!$thread || !$thread->Forum || !$thread->FirstPost) return;

        foreach ($this->mappingsForNode((int)$thread->node_id) as $mapping)
        {
            $source = $this->source($mapping);
            if (empty($source['sync_first_post'])) continue;
            $this->upsertPrimary($mapping, $thread, $thread->FirstPost);
        }
    }

    private function syncPost(int $postId): void
    {
        $post = \XF::em()->find('XF:Post', $postId);
        if (!$post || !$post->Thread || !$post->Thread->Forum) return;
        $thread = $post->Thread;
        $isFirst = (int)$thread->first_post_id === (int)$post->post_id || (int)($post->position ?? -1) === 0;

        foreach ($this->mappingsForNode((int)$thread->node_id) as $mapping)
        {
            $source = $this->source($mapping);
            if ($isFirst)
            {
                if (!empty($source['sync_first_post'])) $this->upsertPrimary($mapping, $thread, $post);
            }
            elseif (!empty($source['sync_replies']))
            {
                $this->upsertConversationComment($mapping, $thread, $post);
            }
        }
    }

    private function upsertPrimary(Mapping $mapping, $thread, $firstPost): void
    {
        $source = $this->source($mapping);
        $object = (string)$source['outbound_object'];
        if ($object === 'pull_request')
        {
            $this->upsertPullRequest($mapping, $thread, $firstPost);
            return;
        }
        $this->upsertIssue($mapping, $thread, $firstPost);
    }

    private function upsertIssue(Mapping $mapping, $thread, $firstPost): void
    {
        [$repository, $connection] = $this->repositoryAndConnection($mapping);
        $source = $this->source($mapping);
        $labels = !empty($source['sync_labels'])
            ? $this->labelPrefixMapper->labelsForPrefix($mapping, (int)$thread->prefix_id)
            : [];
        $hash = $this->registry->currentXfHash('post', (int)$firstPost->post_id);
        if ($hash === '') return;

        $sync = $this->registry->findByXf($mapping, 'post', (int)$firstPost->post_id, 'issue');
        if ($sync && (string)$sync->last_xf_hash === $hash) return;

        $values = $this->fieldMapper->outboundValues($mapping, $thread, $firstPost, [
            'title' => (string)$thread->title,
            'body' => (string)$firstPost->message,
            'state' => $thread->discussion_open ? 'open' : 'closed'
        ]);

        if (!$sync)
        {
            $result = $this->api->createIssue(
                $connection,
                (string)$repository->owner_name,
                (string)$repository->repo_name,
                (string)($values['title'] ?? $thread->title),
                (string)($values['body'] ?? $firstPost->message),
                $labels
            );
            $githubId = (string)($result['id'] ?? '');
            $number = (int)($result['number'] ?? 0);
            if ($githubId === '' || $number <= 0)
            {
                throw new RuntimeException('GitHub create issue response did not contain id/number.');
            }
            $this->registry->registerOutbound(
                $mapping, $repository, 'issue', $githubId, 'post', (int)$firstPost->post_id, $hash,
                ['thread_id' => (int)$thread->thread_id, 'issue_number' => $number, 'created_from_xf' => true]
            );
            return;
        }

        $metadata = is_array($sync->metadata) ? $sync->metadata : [];
        $number = (int)($metadata['issue_number'] ?? 0);
        if ($number <= 0) throw new RuntimeException('Synchronized GitHub issue number is missing.');

        $changes = [
            'title' => (string)($values['title'] ?? $thread->title),
            'body' => (string)($values['body'] ?? $firstPost->message)
        ];
        if (!empty($source['sync_thread_state'])) $changes['state'] = (string)($values['state'] ?? ($thread->discussion_open ? 'open' : 'closed'));
        if (!empty($source['sync_labels'])) $changes['labels'] = $labels;

        $this->api->updateIssue($connection, (string)$repository->owner_name, (string)$repository->repo_name, $number, $changes);
        $this->registry->registerOutbound(
            $mapping, $repository, 'issue', (string)$sync->github_id, 'post', (int)$firstPost->post_id, $hash, $metadata
        );
    }

    private function upsertPullRequest(Mapping $mapping, $thread, $firstPost): void
    {
        [$repository, $connection] = $this->repositoryAndConnection($mapping);
        $source = $this->source($mapping);
        $hash = $this->registry->currentXfHash('post', (int)$firstPost->post_id);
        if ($hash === '') return;

        $sync = $this->registry->findByXf($mapping, 'post', (int)$firstPost->post_id, 'pull_request');
        if ($sync && (string)$sync->last_xf_hash === $hash) return;

        $values = $this->fieldMapper->outboundValues($mapping, $thread, $firstPost, [
            'title' => (string)$thread->title,
            'body' => (string)$firstPost->message,
            'state' => $thread->discussion_open ? 'open' : 'closed',
            'head' => (string)$source['pr_head'],
            'base' => (string)($source['pr_base'] !== '' ? $source['pr_base'] : $repository->default_branch),
            'draft' => (bool)$source['pr_draft'],
            'maintainer_can_modify' => (bool)$source['pr_maintainer_can_modify']
        ]);

        $labels = !empty($source['sync_labels'])
            ? $this->labelPrefixMapper->labelsForPrefix($mapping, (int)$thread->prefix_id)
            : [];

        if (!$sync)
        {
            $head = trim((string)($values['head'] ?? ''));
            $base = trim((string)($values['base'] ?? ''));
            if ($head === '' || $base === '')
            {
                throw new RuntimeException('Pull request mapping requires a head and base branch. Configure PR head/base or outbound field mapping.');
            }

            $result = $this->api->createPullRequest(
                $connection,
                (string)$repository->owner_name,
                (string)$repository->repo_name,
                (string)($values['title'] ?? $thread->title),
                (string)($values['body'] ?? $firstPost->message),
                $head,
                $base,
                (bool)($values['draft'] ?? false),
                (bool)($values['maintainer_can_modify'] ?? true)
            );
            $number = (int)($result['number'] ?? 0);
            if ($number <= 0) throw new RuntimeException('GitHub create pull request response did not contain a PR number.');
            if ($labels !== [])
            {
                $this->api->updateIssue($connection, (string)$repository->owner_name, (string)$repository->repo_name, $number, ['labels' => $labels]);
            }

            $metadata = [
                'thread_id' => (int)$thread->thread_id,
                'pull_request_number' => $number,
                'github_numeric_id' => (string)($result['id'] ?? ''),
                'created_from_xf' => true
            ];
            $metadata = $this->applyReviewPrefix($mapping, $repository, $connection, $thread, $number, $metadata);
            $this->registry->registerOutbound(
                $mapping, $repository, 'pull_request', 'number:' . $number, 'post', (int)$firstPost->post_id, $hash, $metadata
            );
            return;
        }

        $metadata = is_array($sync->metadata) ? $sync->metadata : [];
        $number = (int)($metadata['pull_request_number'] ?? 0);
        if ($number <= 0 && str_starts_with((string)$sync->github_id, 'number:')) $number = (int)substr((string)$sync->github_id, 7);
        if ($number <= 0) throw new RuntimeException('Synchronized GitHub pull request number is missing.');

        $changes = [
            'title' => (string)($values['title'] ?? $thread->title),
            'body' => (string)($values['body'] ?? $firstPost->message)
        ];
        if (!empty($source['sync_thread_state'])) $changes['state'] = (string)($values['state'] ?? ($thread->discussion_open ? 'open' : 'closed'));
        if (trim((string)($values['base'] ?? '')) !== '') $changes['base'] = trim((string)$values['base']);
        if (array_key_exists('maintainer_can_modify', $values)) $changes['maintainer_can_modify'] = (bool)$values['maintainer_can_modify'];

        $this->api->updatePullRequest($connection, (string)$repository->owner_name, (string)$repository->repo_name, $number, $changes);
        if (!empty($source['sync_labels']))
        {
            $this->api->updateIssue($connection, (string)$repository->owner_name, (string)$repository->repo_name, $number, ['labels' => $labels]);
        }

        $metadata = $this->applyReviewPrefix($mapping, $repository, $connection, $thread, $number, $metadata);
        $this->registry->registerOutbound(
            $mapping, $repository, 'pull_request', (string)$sync->github_id, 'post', (int)$firstPost->post_id, $hash, $metadata
        );
    }

    private function applyReviewPrefix(Mapping $mapping, Repository $repository, $connection, $thread, int $number, array $metadata): array
    {
        $source = $this->source($mapping);
        if (empty($source['sync_review_prefix'])) return $metadata;

        $map = is_array($source['review_prefix_map']) ? $source['review_prefix_map'] : [];
        $prefixId = (int)$thread->prefix_id;
        $event = strtoupper(trim((string)($map[$prefixId] ?? '')));
        if (!in_array($event, ['APPROVE', 'REQUEST_CHANGES', 'COMMENT'], true)) return $metadata;
        if ((int)($metadata['last_review_prefix_id'] ?? 0) === $prefixId) return $metadata;

        $body = trim((string)$source['review_body']);
        if ($body === '')
        {
            $body = match ($event)
            {
                'APPROVE' => 'Approved from the linked XenForo discussion.',
                'REQUEST_CHANGES' => 'Changes requested from the linked XenForo discussion.',
                default => 'Review status synchronized from the linked XenForo discussion.'
            };
        }
        $result = $this->api->createPullRequestReview(
            $connection,
            (string)$repository->owner_name,
            (string)$repository->repo_name,
            $number,
            $event,
            $body
        );
        $metadata['last_review_prefix_id'] = $prefixId;
        $metadata['last_review_id'] = (string)($result['id'] ?? '');
        $metadata['last_review_event'] = $event;
        return $metadata;
    }
}
