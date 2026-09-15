<?php

namespace Warext\GitHubSync\Service\Sync;

use InvalidArgumentException;
use RuntimeException;
use Warext\GitHubSync\Entity\Conflict;
use Warext\GitHubSync\Entity\Mapping;
use Warext\GitHubSync\Entity\Repository;

final class ConflictResolver
{
    public function allowInbound(Mapping $mapping, Repository $repository, $sync, array $action, string $githubHash): bool
    {
        if (!$sync || (string)$sync->xf_content_type !== 'post') return true;
        if ((string)$sync->last_github_hash === $githubHash) return true;

        $post = \XF::em()->find('XF:Post', (int)$sync->xf_content_id);
        if (!$post) return true;
        $xfHash = $this->postHash($post);
        $lastXfHash = (string)$sync->last_xf_hash;
        if ($lastXfHash === '' || hash_equals($lastXfHash, $xfHash)) return true;

        return match ((string)$mapping->conflict_strategy)
        {
            'github_wins' => true,
            'xenforo_wins' => $this->localWins($mapping, $sync, $githubHash),
            'newest_wins' => $this->remoteTimestamp($action) >= $this->localTimestamp($post)
                ? true
                : $this->localWins($mapping, $sync, $githubHash),
            default => $this->queueConflict($mapping, $repository, $sync, $action, $githubHash, $xfHash)
        };
    }

    public function resolve(Conflict $conflict, string $resolution): void
    {
        if ((string)$conflict->status !== 'pending')
        {
            throw new RuntimeException('Only pending conflicts can be resolved.');
        }
        if (!in_array($resolution, ['github_wins', 'xenforo_wins', 'dismiss'], true))
        {
            throw new InvalidArgumentException('Unknown conflict resolution.');
        }

        $sync = (int)$conflict->sync_id > 0
            ? \XF::em()->find('Warext\\GitHubSync:SyncObject', (int)$conflict->sync_id)
            : null;

        if ($resolution === 'github_wins')
        {
            $mapping = \XF::em()->find('Warext\\GitHubSync:Mapping', (int)$conflict->mapping_id);
            $repository = \XF::em()->find('Warext\\GitHubSync:Repository', (int)$conflict->repository_id);
            if (!$mapping || !$repository)
            {
                throw new RuntimeException('Conflict mapping or repository no longer exists.');
            }
            $action = is_array($conflict->action_payload) ? $conflict->action_payload : [];
            if ($action === [])
            {
                throw new RuntimeException('Conflict action payload is missing.');
            }
            (new XenForoActionExecutor(new SyncRegistry()))->execute(
                $mapping,
                $repository,
                $action,
                (string)$conflict->github_hash
            );
        }
        elseif ($sync && $resolution === 'xenforo_wins')
        {
            $mapping = \XF::em()->find('Warext\\GitHubSync:Mapping', (int)$conflict->mapping_id);
            if (!$mapping) throw new RuntimeException('Conflict mapping no longer exists.');
            $this->localWins($mapping, $sync, (string)$conflict->github_hash);
        }
        elseif ($sync)
        {
            $sync->last_github_hash = (string)$conflict->github_hash;
            $currentHash = (new SyncRegistry())->currentXfHash((string)$sync->xf_content_type, (int)$sync->xf_content_id);
            if ($currentHash !== '') $sync->last_xf_hash = $currentHash;
            $sync->origin = 'xenforo';
            $sync->last_sync_date = \XF::$time;
            $sync->save();
        }

        $conflict->status = $resolution === 'dismiss' ? 'dismissed' : 'resolved';
        $conflict->resolution = $resolution;
        $conflict->resolved_date = \XF::$time;
        $conflict->save();
    }

    public function postHash($post): string
    {
        $thread = $post->Thread;
        $isFirstPost = $thread && (int)$thread->first_post_id === (int)$post->post_id;
        $data = ['message' => (string)$post->message];
        if ($isFirstPost)
        {
            $data['title'] = (string)$thread->title;
            $data['prefix_id'] = (int)$thread->prefix_id;
            $data['discussion_open'] = (bool)$thread->discussion_open;
        }
        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function localWins(Mapping $mapping, $sync, string $githubHash): bool
    {
        $sync->last_github_hash = $githubHash;
        $sync->origin = 'xenforo';
        $sync->last_sync_date = \XF::$time;
        $sync->save();

        if (in_array((string)$mapping->direction, ['xf_to_github', 'bidirectional'], true)
            && (string)$sync->xf_content_type === 'post'
            && (int)$sync->xf_content_id > 0)
        {
            (new OutboundQueue())->enqueue('post', (int)$sync->xf_content_id, 'upsert');
        }
        return false;
    }

    private function queueConflict(Mapping $mapping, Repository $repository, $sync, array $action, string $githubHash, string $xfHash): bool
    {
        $existing = \XF::finder('Warext\\GitHubSync:Conflict')
            ->where('sync_id', (int)$sync->sync_id)
            ->where('github_hash', $githubHash)
            ->where('xf_hash', $xfHash)
            ->where('status', 'pending')
            ->fetchOne();
        if (!$existing)
        {
            $conflict = \XF::em()->create('Warext\\GitHubSync:Conflict');
            $conflict->mapping_id = (int)$mapping->mapping_id;
            $conflict->repository_id = (int)$repository->repository_id;
            $conflict->sync_id = (int)$sync->sync_id;
            $conflict->github_type = (string)$sync->github_type;
            $conflict->github_id = (string)$sync->github_id;
            $conflict->xf_content_type = (string)$sync->xf_content_type;
            $conflict->xf_content_id = (int)$sync->xf_content_id;
            $conflict->github_hash = $githubHash;
            $conflict->xf_hash = $xfHash;
            $conflict->action_payload = $action;
            $conflict->status = 'pending';
            $conflict->created_date = \XF::$time;
            $conflict->save();
        }
        return false;
    }

    private function remoteTimestamp(array $action): int
    {
        $raw = (string)($action['remote_updated_at'] ?? '');
        $ts = $raw !== '' ? strtotime($raw) : false;
        return $ts !== false ? $ts : \XF::$time;
    }

    private function localTimestamp($post): int
    {
        return max((int)($post->last_edit_date ?? 0), (int)($post->post_date ?? 0));
    }
}
