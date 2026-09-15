<?php

namespace Warext\GitHubSync\Service\Sync;

use RuntimeException;
use Warext\GitHubSync\Entity\Delivery;
use Warext\GitHubSync\Service\Webhook\EventNormalizer;

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
        $messageFactory = new MessageFactory(new TemplateRenderer());
        $executor = new XenForoActionExecutor(new SyncRegistry());
        $processed = 0;

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

            $action = $messageFactory->build($mapping, $repository, $event);
            $executor->execute($mapping, $repository, $action, (string)$delivery->payload_hash);
            $processed++;
        }

        if ($processed === 0)
        {
            $this->finish($delivery, 'ignored', 'Mappings matched but all were filtered or directional.');
            return;
        }

        $this->finish($delivery, 'processed', '');
    }

    private function finish(Delivery $delivery, string $status, string $message): void
    {
        $delivery->status = $status;
        $delivery->processed_date = \XF::$time;
        $delivery->last_error = $message;
        $delivery->save();
    }
}
