<?php

namespace Warext\GitHubSync\Job;

use Warext\GitHubSync\Service\Sync\PushDeliveryAggregator;
use Warext\GitHubSync\Service\Admin\HealthAlertManager;
use XF\Job\AbstractJob;

class ProcessDelivery extends AbstractJob
{
    private const MAX_ATTEMPTS = 5;

    public function run($maxRunTime): \XF\Job\JobResult
    {
        $deliveryId = (int)($this->data['delivery_id'] ?? 0);
        if (!$deliveryId)
        {
            return $this->complete();
        }

        /** @var \Warext\GitHubSync\Entity\Delivery|null $delivery */
        $delivery = \XF::em()->find('Warext\\GitHubSync:Delivery', $deliveryId);
        if (!$delivery || in_array((string)$delivery->status, ['processed', 'ignored', 'grouped'], true))
        {
            return $this->complete();
        }

        if ((string)$delivery->github_event === 'push')
        {
            $config = \XF::config('warextGitHubSync');
            $seconds = is_array($config) ? (int)($config['pushAggregationSeconds'] ?? 60) : 60;
            $seconds = max(0, min(600, $seconds));
            $delivery = (new PushDeliveryAggregator())->aggregate($delivery, $seconds);
        }

        $delivery->attempt_count++;
        $delivery->next_attempt_date = 0;
        $delivery->status = 'processing';
        $delivery->save();

        try
        {
            $dispatcher = new \Warext\GitHubSync\Service\Sync\EventDispatcher();
            $dispatcher->dispatch($delivery);
        }
        catch (\Throwable $e)
        {
            $delivery->last_error = mb_substr($e->getMessage(), 0, 65535);

            if ((int)$delivery->attempt_count < self::MAX_ATTEMPTS)
            {
                $delay = $this->retryDelay((int)$delivery->attempt_count);
                $delivery->status = 'retry_waiting';
                $delivery->next_attempt_date = \XF::$time + $delay;
                $delivery->save();

                \XF::app()->jobManager()->enqueueLater(
                    'warextGitHubSyncDeliveryRetry' . $delivery->delivery_id . '_' . $delivery->attempt_count,
                    $delivery->next_attempt_date,
                    'Warext\\GitHubSync:ProcessDelivery',
                    ['delivery_id' => (int)$delivery->delivery_id]
                );
                \XF::logException($e, false, '[Warext GitHub Sync] Delivery retry scheduled: ');
                (new HealthAlertManager())->evaluate();
                return $this->complete();
            }

            $delivery->status = 'failed';
            $delivery->next_attempt_date = 0;
            $delivery->save();
            \XF::logException($e, false, '[Warext GitHub Sync] Delivery permanently failed: ');
            (new HealthAlertManager())->evaluate();
            return $this->complete();
        }

        (new HealthAlertManager())->evaluate();
        return $this->complete();
    }

    private function retryDelay(int $attempt): int
    {
        return match ($attempt)
        {
            1 => 30,
            2 => 120,
            3 => 600,
            4 => 1800,
            default => 3600
        };
    }

    public function getStatusMessage(): string
    {
        return 'Processing GitHub webhook delivery...';
    }

    public function canCancel(): bool
    {
        return true;
    }

    public function canTriggerByChoice(): bool
    {
        return false;
    }
}
