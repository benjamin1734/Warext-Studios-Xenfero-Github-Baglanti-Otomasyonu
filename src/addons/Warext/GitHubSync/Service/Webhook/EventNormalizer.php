<?php

namespace Warext\GitHubSync\Service\Webhook;

use InvalidArgumentException;
use Warext\GitHubSync\Dto\WebhookEvent;

final class EventNormalizer
{
    public function normalize(string $deliveryGuid, string $event, string $payloadJson): WebhookEvent
    {
        $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload))
        {
            throw new InvalidArgumentException('Webhook payload must decode to an object.');
        }

        $repository = $payload['repository'] ?? [];
        $repositoryId = (int)($repository['id'] ?? 0);
        $repositoryFullName = (string)($repository['full_name'] ?? '');
        $action = (string)($payload['action'] ?? '');

        return new WebhookEvent(
            $deliveryGuid,
            trim($event),
            $action,
            $repositoryId,
            $repositoryFullName,
            $payload
        );
    }
}
