<?php

namespace Warext\GitHubSync\Dto;

final class WebhookEvent
{
    public function __construct(
        public readonly string $deliveryGuid,
        public readonly string $event,
        public readonly string $action,
        public readonly int $repositoryId,
        public readonly string $repositoryFullName,
        public readonly array $payload
    ) {}
}
