<?php

namespace Warext\GitHubSync;

use XF\Mvc\Entity\Entity;
use Warext\GitHubSync\Service\Sync\OutboundQueue;
use Warext\GitHubSync\Service\Sync\SyncGuard;

final class Listener
{
    public static function threadPostSave(Entity $entity): void
    {
        if (SyncGuard::active()) return;
        (new OutboundQueue())->enqueue('thread', (int)$entity->thread_id, 'upsert');
    }

    public static function postPostSave(Entity $entity): void
    {
        if (SyncGuard::active()) return;
        (new OutboundQueue())->enqueue('post', (int)$entity->post_id, 'upsert');
    }

    public static function threadPostDelete(Entity $entity): void
    {
        if (SyncGuard::active()) return;
        (new OutboundQueue())->enqueue('thread', (int)$entity->thread_id, 'delete', [
            'first_post_id' => (int)($entity->first_post_id ?? 0)
        ]);
    }

    public static function postPostDelete(Entity $entity): void
    {
        if (SyncGuard::active()) return;
        (new OutboundQueue())->enqueue('post', (int)$entity->post_id, 'delete', [
            'thread_id' => (int)($entity->thread_id ?? 0)
        ]);
    }
}
