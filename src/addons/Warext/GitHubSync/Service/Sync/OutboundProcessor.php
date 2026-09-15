<?php

namespace Warext\GitHubSync\Service\Sync;

use RuntimeException;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;
use Warext\GitHubSync\Service\GitHub\ApiClient;
use Warext\GitHubSync\Service\GitHub\CredentialProvider;
use Warext\GitHubSync\Service\GitHub\JwtFactory;

final class OutboundProcessor
{
    private SyncRegistry $registry;
    private ApiClient $api;
    private LabelPrefixMapper $labelPrefixMapper;

    public function __construct()
    {
        $this->registry = new SyncRegistry();
        $this->api = new ApiClient(new JwtFactory(), new CredentialProvider());
        $this->labelPrefixMapper = new LabelPrefixMapper();
    }

    public function process(string $contentType, int $contentId, string $operation, array $extra = []): void
    {
        if (SyncGuard::active()) return;

        SyncGuard::run(function() use ($contentType, $contentId, $operation, $extra)
        {
            if ($contentType === 'thread')
            {
                $operation === 'delete'
                    ? $this->deleteThread($contentId, $extra)
                    : $this->syncThread($contentId);
                return;
            }
            if ($contentType === 'post')
            {
                $operation === 'delete'
                    ? $this->deletePost($contentId, $extra)
                    : $this->syncPost($contentId);
            }
        });
    }

    private function syncThread(int $threadId): void
    {
        $thread = \XF::em()->find('XF:Thread', $threadId);
        if (!$thread || !$thread->Forum || !$thread->FirstPost) return;

        foreach ($this->mappingsForNode((int)$thread->node_id) as $mapping)
        {
            $source = $this->source($mapping);
            if (empty($source['sync_first_post'])) continue;
            $this->upsertIssue($mapping, $thread, $thread->FirstPost);
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
                if (!empty($source['sync_first_post'])) $this->upsertIssue($mapping, $thread, $post);
            }
            elseif (!empty($source['sync_replies']))
            {
                $this->upsertIssueComment($mapping, $thread, $post);
            }
        }
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

        if (!$sync)
        {
            $result = $this->api->createIssue(
                $connection,
                (string)$repository->owner_name,
                (string)$repository->repo_name,
                (string)$thread->title,
                (string)$firstPost->message,
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
            'title' => (string)$thread->title,
            'body' => (string)$firstPost->message
        ];
        if (!empty($source['sync_thread_state']))
        {
            $changes['state'] = $thread->discussion_open ? 'open' : 'closed';
        }
        if (!empty($source['sync_labels']))
        {
            $changes['labels'] = $labels;
        }
        $this->api->updateIssue($connection, (string)$repository->owner_name, (string)$repository->repo_name, $number, $changes);
        $this->registry->registerOutbound(
            $mapping, $repository, 'issue', (string)$sync->github_id, 'post', (int)$firstPost->post_id, $hash, $metadata
        );
    }

    private function upsertIssueComment(Mapping $mapping, $thread, $post): void
    {
        [$repository, $connection] = $this->repositoryAndConnection($mapping);
        $issueSync = $this->registry->findByXf($mapping, 'post', (int)$thread->first_post_id, 'issue');
        if (!$issueSync) $issueSync = $this->registry->findByXfInRepository($repository, 'post', (int)$thread->first_post_id, 'issue');
        if (!$issueSync) return;

        $issueMeta = is_array($issueSync->metadata) ? $issueSync->metadata : [];
        $number = (int)($issueMeta['issue_number'] ?? 0);
        if ($number <= 0) return;

        $hash = $this->registry->currentXfHash('post', (int)$post->post_id);
        if ($hash === '') return;
        $sync = $this->registry->findByXf($mapping, 'post', (int)$post->post_id, 'issue_comment');
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
            if ($githubId === '') throw new RuntimeException('GitHub create comment response did not contain id.');
            $this->registry->registerOutbound(
                $mapping, $repository, 'issue_comment', $githubId, 'post', (int)$post->post_id, $hash,
                ['thread_id' => (int)$thread->thread_id, 'issue_number' => $number, 'created_from_xf' => true]
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
            $mapping, $repository, 'issue_comment', (string)$sync->github_id, 'post', (int)$post->post_id, $hash,
            is_array($sync->metadata) ? $sync->metadata : []
        );
    }

    private function deletePost(int $postId, array $extra): void
    {
        foreach ($this->outboundMappings() as $mapping)
        {
            $sync = $this->registry->findByXf($mapping, 'post', $postId, 'issue_comment');
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

    private function deleteThread(int $threadId, array $extra): void
    {
        $firstPostId = (int)($extra['first_post_id'] ?? 0);
        if ($firstPostId <= 0) return;

        foreach ($this->outboundMappings() as $mapping)
        {
            $sync = $this->registry->findByXf($mapping, 'post', $firstPostId, 'issue');
            if (!$sync) continue;
            $metadata = is_array($sync->metadata) ? $sync->metadata : [];
            $number = (int)($metadata['issue_number'] ?? 0);
            if ($number <= 0) continue;
            [$repository, $connection] = $this->repositoryAndConnection($mapping);
            $this->api->updateIssue(
                $connection,
                (string)$repository->owner_name,
                (string)$repository->repo_name,
                $number,
                ['state' => 'closed', 'state_reason' => 'not_planned']
            );
            $sync->status = 'local_deleted';
            $sync->origin = 'xenforo';
            $sync->last_sync_date = \XF::$time;
            $sync->save();
        }
    }

    /** @return array<int, Mapping> */
    private function mappingsForNode(int $nodeId): array
    {
        $out = [];
        foreach ($this->outboundMappings() as $mapping)
        {
            $source = $this->source($mapping);
            $mappedNode = (int)($source['xf_node_id'] ?? 0);
            if ($mappedNode > 0 && $mappedNode !== $nodeId) continue;
            if ((string)($source['outbound_object'] ?? 'issue') !== 'issue') continue;
            $out[] = $mapping;
        }
        return $out;
    }

    /** @return array<int, Mapping> */
    private function outboundMappings(): array
    {
        $collection = \XF::finder('Warext\\GitHubSync:Mapping')
            ->where('enabled', 1)
            ->where('direction', ['xf_to_github', 'bidirectional'])
            ->where('repository_id', '>', 0)
            ->order('priority', 'ASC')
            ->fetch();
        $out = [];
        foreach ($collection as $mapping) $out[] = $mapping;
        return $out;
    }

    private function source(Mapping $mapping): array
    {
        $source = is_array($mapping->source_config) ? $mapping->source_config : [];
        return array_replace([
            'xf_node_id' => 0,
            'outbound_object' => 'issue',
            'sync_first_post' => true,
            'sync_replies' => true,
            'sync_thread_state' => true,
            'sync_labels' => false
        ], $source);
    }

    /** @return array{0:Repository,1:mixed} */
    private function repositoryAndConnection(Mapping $mapping): array
    {
        $repository = \XF::em()->find('Warext\\GitHubSync:Repository', (int)$mapping->repository_id);
        if (!$repository || !$repository->active)
        {
            throw new RuntimeException('Outbound mapping repository is missing or inactive.');
        }
        $connection = \XF::em()->find('Warext\\GitHubSync:Connection', (int)$repository->connection_id);
        if (!$connection || !$connection->active)
        {
            throw new RuntimeException('Outbound mapping GitHub connection is missing or inactive.');
        }
        return [$repository, $connection];
    }

}
