<?php

namespace Warext\GitHubSync\Service\Sync\Traits;

use RuntimeException;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;

trait OutboundCommentActionsTrait
{
    private function upsertConversationComment(Mapping $mapping, $thread, $post): void
    {
        $source = $this->source($mapping);
        $parentType = (string)$source['outbound_object'] === 'pull_request' ? 'pull_request' : 'issue';
        $commentType = $parentType === 'pull_request' ? 'pr_comment' : 'issue_comment';

        [$repository, $connection] = $this->repositoryAndConnection($mapping);
        $parentSync = $this->registry->findByXf($mapping, 'post', (int)$thread->first_post_id, $parentType);
        if (!$parentSync) $parentSync = $this->registry->findByXfInRepository($repository, 'post', (int)$thread->first_post_id, $parentType);
        if (!$parentSync) return;

        $parentMeta = is_array($parentSync->metadata) ? $parentSync->metadata : [];
        $number = $parentType === 'pull_request'
            ? (int)($parentMeta['pull_request_number'] ?? 0)
            : (int)($parentMeta['issue_number'] ?? 0);
        if ($number <= 0) return;

        $hash = $this->registry->currentXfHash('post', (int)$post->post_id);
        if ($hash === '') return;
        $sync = $this->registry->findByXf($mapping, 'post', (int)$post->post_id, $commentType);
        if ($sync && (string)$sync->last_xf_hash === $hash) return;

        if (!$sync)
        {
            $result = $this->api->createIssueComment(
                $connection,
                (string)$repository->owner_name,
                (string)$repository->repo_name,
                $number,
                (string)$post->message
            );
            $githubId = (string)($result['id'] ?? '');
            if ($githubId === '') throw new RuntimeException('GitHub create conversation comment response did not contain id.');
            $metadata = ['thread_id' => (int)$thread->thread_id, 'created_from_xf' => true];
            $metadata[$parentType === 'pull_request' ? 'pull_request_number' : 'issue_number'] = $number;
            $this->registry->registerOutbound(
                $mapping, $repository, $commentType, $githubId, 'post', (int)$post->post_id, $hash, $metadata
            );
            return;
        }

        $this->api->updateIssueComment(
            $connection,
            (string)$repository->owner_name,
            (string)$repository->repo_name,
            (int)$sync->github_id,
            (string)$post->message
        );
        $this->registry->registerOutbound(
            $mapping, $repository, $commentType, (string)$sync->github_id, 'post', (int)$post->post_id, $hash,
            is_array($sync->metadata) ? $sync->metadata : []
        );
    }

    private function deletePost(int $postId, array $extra): void
    {
        foreach ($this->outboundMappings() as $mapping)
        {
            foreach (['issue_comment', 'pr_comment'] as $commentType)
            {
                $sync = $this->registry->findByXf($mapping, 'post', $postId, $commentType);
                if (!$sync) continue;
                [$repository, $connection] = $this->repositoryAndConnection($mapping);
                $this->api->deleteIssueComment(
                    $connection,
                    (string)$repository->owner_name,
                    (string)$repository->repo_name,
                    (int)$sync->github_id
                );
                $sync->status = 'local_deleted';
                $sync->origin = 'xenforo';
                $sync->last_sync_date = \XF::$time;
                $sync->save();
            }
        }
    }

    private function deleteThread(int $threadId, array $extra): void
    {
        $firstPostId = (int)($extra['first_post_id'] ?? 0);
        if ($firstPostId <= 0) return;

        foreach ($this->outboundMappings() as $mapping)
        {
            $source = $this->source($mapping);
            $parentType = (string)$source['outbound_object'] === 'pull_request' ? 'pull_request' : 'issue';
            $sync = $this->registry->findByXf($mapping, 'post', $firstPostId, $parentType);
            if (!$sync) continue;
            $metadata = is_array($sync->metadata) ? $sync->metadata : [];
            $number = $parentType === 'pull_request'
                ? (int)($metadata['pull_request_number'] ?? 0)
                : (int)($metadata['issue_number'] ?? 0);
            if ($number <= 0) continue;
            [$repository, $connection] = $this->repositoryAndConnection($mapping);

            if ($parentType === 'pull_request')
            {
                $this->api->updatePullRequest(
                    $connection,
                    (string)$repository->owner_name,
                    (string)$repository->repo_name,
                    $number,
                    ['state' => 'closed']
                );
            }
            else
            {
                $this->api->updateIssue(
                    $connection,
                    (string)$repository->owner_name,
                    (string)$repository->repo_name,
                    $number,
                    ['state' => 'closed', 'state_reason' => 'not_planned']
                );
            }
            $sync->status = 'local_deleted';
            $sync->origin = 'xenforo';
            $sync->last_sync_date = \XF::$time;
            $sync->save();
        }
    }
}
