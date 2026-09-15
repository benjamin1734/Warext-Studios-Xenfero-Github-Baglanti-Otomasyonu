<?php

namespace Warext\GitHubSync\Service\Sync;

use RuntimeException;
use Warext\GitHubSync\Entity\Delivery;
use Warext\GitHubSync\Service\Webhook\EventNormalizer;
use Warext\GitHubSync\Service\XFRM\ReleaseSynchronizer;
use Warext\GitHubSync\Service\GitHub\WorkflowRunTracker;

final class EventDispatcher
{
    public function dispatch(Delivery $delivery): void
    {
        $normalizer = new EventNormalizer();
        $event = $normalizer->normalize(
            (string)$delivery->github_delivery_guid,
            (string)$delivery->github_event,
            (string)$delivery->payload
        );

        if ($event->repositoryId <= 0)
        {
            $this->finish($delivery, 'ignored', 'Webhook has no repository context.');
            return;
        }

        $repository = \XF::finder('Warext\\GitHubSync:Repository')
            ->where('github_repository_id', $event->repositoryId)
            ->where('active', 1)
            ->fetchOne();
        if (!$repository)
        {
            $this->finish($delivery, 'ignored', 'Repository is not registered or is inactive.');
            return;
        }

        (new WorkflowRunTracker())->capture($repository, $event);

        /** @var \Warext\GitHubSync\Repository\Mapping $mappingRepo */
        $mappingRepo = \XF::repository('Warext\\GitHubSync:Mapping');
        $mappings = $mappingRepo->findApplicable(
            (int)$repository->repository_id,
            $event->event,
            $event->action
        )->fetch();

        if (count($mappings) === 0)
        {
            $this->finish($delivery, 'ignored', 'No matching synchronization mapping.');
            return;
        }

        $filter = new FilterMatcher();
        $registry = new SyncRegistry();
        $messageFactory = new MessageFactory(new TemplateRenderer());
        $executor = new XenForoActionExecutor($registry);
        $conflictResolver = new ConflictResolver();
        $xfrm = new ReleaseSynchronizer();
        $processed = 0;
        $messages = [];
        $conflicts = 0;

        foreach ($mappings as $mapping)
        {
            if (!in_array((string)$mapping->direction, ['github_to_xf', 'bidirectional'], true))
            {
                continue;
            }
            if (!$filter->matches($mapping, $event))
            {
                continue;
            }

            if ($xfrm->enabled($mapping, $event))
            {
                $xfrmResult = $xfrm->synchronize($mapping, $repository, $event, (string)$delivery->payload_hash);
                if (!empty($xfrmResult['processed']))
                {
                    $processed++;
                    if (!empty($xfrmResult['message'])) $messages[] = (string)$xfrmResult['message'];
                }
                if ($xfrm->mode($mapping) === 'only') continue;
            }

            $action = $messageFactory->build($mapping, $repository, $event);
            $existing = $registry->find($mapping, (string)$action['object_type'], (string)$action['object_id']);
            if (($action['action_mode'] ?? 'upsert') !== 'delete'
                && !$conflictResolver->allowInbound($mapping, $repository, $existing, $action, (string)$delivery->payload_hash))
            {
                if ((string)$mapping->conflict_strategy === 'manual') $conflicts++;
                continue;
            }

            $executor->execute($mapping, $repository, $action, (string)$delivery->payload_hash);
            $processed++;
        }

        if ($processed === 0)
        {
            if ($conflicts > 0)
            {
                $this->finish($delivery, 'conflict', sprintf('%d synchronization conflict(s) queued for review.', $conflicts));
                return;
            }
            $this->finish($delivery, 'ignored', 'Mappings matched but all were filtered or directional.');
            return;
        }

        if ($conflicts > 0) $messages[] = sprintf('%d conflict(s) queued.', $conflicts);
        $this->finish($delivery, 'processed', implode(' ', array_values(array_unique($messages))));
    }

    private function finish(Delivery $delivery, string $status, string $message): void
    {
        $delivery->status = $status;
        $delivery->processed_date = \XF::$time;
        $delivery->last_error = $message;
        $delivery->save();
    }
}
