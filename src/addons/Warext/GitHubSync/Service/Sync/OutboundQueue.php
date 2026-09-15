<?php

namespace Warext\GitHubSync\Service\Sync;

final class OutboundQueue
{
    public function enqueue(string $contentType, int $contentId, string $operation = 'upsert', array $extra = []): void
    {
        if (SyncGuard::active() || $contentId <= 0)
        {
            return;
        }

        $key = 'warextGitHubSyncOutbound_' . preg_replace('/[^a-z0-9_]+/i', '_', $contentType) . '_' . $contentId . '_' . $operation;
        \XF::app()->jobManager()->enqueueUnique(
            $key,
            'Warext\\GitHubSync:ProcessOutbound',
            [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'operation' => $operation,
                'extra' => $extra,
                'attempt' => 0
            ]
        );
    }
}
