<?php

namespace Warext\GitHubSync\Job;

use Warext\GitHubSync\Service\Sync\OutboundProcessor;
use XF\Job\AbstractJob;

class ProcessOutbound extends AbstractJob
{
    private const MAX_ATTEMPTS = 5;

    public function run($maxRunTime): \XF\Job\JobResult
    {
        $contentType = (string)($this->data['content_type'] ?? '');
        $contentId = (int)($this->data['content_id'] ?? 0);
        $operation = (string)($this->data['operation'] ?? 'upsert');
        $extra = is_array($this->data['extra'] ?? null) ? $this->data['extra'] : [];
        $attempt = (int)($this->data['attempt'] ?? 0) + 1;

        if ($contentType === '' || $contentId <= 0)
        {
            return $this->complete();
        }

        try
        {
            (new OutboundProcessor())->process($contentType, $contentId, $operation, $extra);
        }
        catch (\Throwable $e)
        {
            \XF::logException($e, false, '[Warext GitHub Sync] Outbound synchronization failed: ');
            if ($attempt < self::MAX_ATTEMPTS)
            {
                $delay = match ($attempt)
                {
                    1 => 30,
                    2 => 120,
                    3 => 600,
                    4 => 1800,
                    default => 3600
                };
                $key = 'warextGitHubSyncOutboundRetry_' . $contentType . '_' . $contentId . '_' . $operation . '_' . $attempt;
                \XF::app()->jobManager()->enqueueLater(
                    $key,
                    \XF::$time + $delay,
                    'Warext\\GitHubSync:ProcessOutbound',
                    [
                        'content_type' => $contentType,
                        'content_id' => $contentId,
                        'operation' => $operation,
                        'extra' => $extra,
                        'attempt' => $attempt
                    ]
                );
            }
        }

        return $this->complete();
    }

    public function getStatusMessage(): string
    {
        return 'Synchronizing XenForo content to GitHub...';
    }

    public function canCancel(): bool { return true; }
    public function canTriggerByChoice(): bool { return false; }
}
