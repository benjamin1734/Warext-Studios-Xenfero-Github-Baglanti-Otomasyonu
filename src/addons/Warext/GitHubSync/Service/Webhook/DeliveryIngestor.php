<?php

namespace Warext\GitHubSync\Service\Webhook;

use JsonException;
use RuntimeException;

final class DeliveryIngestor
{
    public const MAX_PAYLOAD_BYTES = 5_242_880; // 5 MiB bootstrap safety limit

    public function __construct(
        private SignatureVerifier $signatureVerifier,
        private EventNormalizer $normalizer
    ) {}

    /**
     * @return array{duplicate: bool, delivery_id: int, event: string, action: string}
     */
    public function ingest(string $payload, string $deliveryGuid, string $event, string $signature, array $secrets): array
    {
        if ($deliveryGuid === '' || $event === '')
        {
            throw new RuntimeException('Missing required GitHub delivery headers.');
        }

        if (strlen($payload) > self::MAX_PAYLOAD_BYTES)
        {
            throw new RuntimeException('Webhook payload exceeds the bootstrap safety limit.');
        }

        if (!$this->signatureVerifier->verifyAny($payload, $signature, $secrets))
        {
            throw new RuntimeException('Invalid GitHub webhook signature.');
        }

        $existing = \XF::finder('Warext\\GitHubSync:Delivery')
            ->where('github_delivery_guid', $deliveryGuid)
            ->fetchOne();
        if ($existing)
        {
            return [
                'duplicate' => true,
                'delivery_id' => (int)$existing->delivery_id,
                'event' => (string)$existing->github_event,
                'action' => (string)$existing->event_action
            ];
        }

        try
        {
            $normalized = $this->normalizer->normalize($deliveryGuid, $event, $payload);
        }
        catch (JsonException $e)
        {
            throw new RuntimeException('Invalid JSON webhook payload.', 0, $e);
        }

        /** @var \Warext\GitHubSync\Entity\Delivery $delivery */
        $delivery = \XF::em()->create('Warext\\GitHubSync:Delivery');
        $delivery->github_delivery_guid = $normalized->deliveryGuid;
        $delivery->github_event = $normalized->event;
        $delivery->event_action = $normalized->action;
        $delivery->github_repository_id = $normalized->repositoryId;
        $delivery->payload_hash = hash('sha256', $payload);
        $delivery->source_ref = (string)($normalized->payload['ref'] ?? '');
        $delivery->payload = $payload;
        $delivery->signature_valid = true;
        $delivery->status = 'received';
        $delivery->attempt_count = 0;
        $delivery->next_attempt_date = 0;
        $delivery->grouped_into_delivery_id = 0;
        $delivery->received_date = \XF::$time;
        $delivery->save();

        if ($normalized->event === 'push')
        {
            $config = \XF::config('warextGitHubSync');
            $delay = is_array($config) ? (int)($config['pushAggregationSeconds'] ?? 60) : 60;
            $delay = max(0, min(600, $delay));
            if ($delay > 0)
            {
                \XF::app()->jobManager()->enqueueLater(
                    'warextGitHubSyncDelivery' . $delivery->delivery_id,
                    \XF::$time + $delay,
                    'Warext\\GitHubSync:ProcessDelivery',
                    ['delivery_id' => (int)$delivery->delivery_id]
                );
            }
            else
            {
                \XF::app()->jobManager()->enqueueUnique(
                    'warextGitHubSyncDelivery' . $delivery->delivery_id,
                    'Warext\\GitHubSync:ProcessDelivery',
                    ['delivery_id' => (int)$delivery->delivery_id]
                );
            }
        }
        else
        {
            \XF::app()->jobManager()->enqueueUnique(
                'warextGitHubSyncDelivery' . $delivery->delivery_id,
                'Warext\\GitHubSync:ProcessDelivery',
                ['delivery_id' => (int)$delivery->delivery_id]
            );
        }

        return [
            'duplicate' => false,
            'delivery_id' => (int)$delivery->delivery_id,
            'event' => $normalized->event,
            'action' => $normalized->action
        ];
    }
}
