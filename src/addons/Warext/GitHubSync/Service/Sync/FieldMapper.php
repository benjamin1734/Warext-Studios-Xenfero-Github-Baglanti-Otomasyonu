<?php

namespace Warext\GitHubSync\Service\Sync;

use Warext\GitHubSync\Dto\WebhookEvent;
use Warext\GitHubSync\Entity\Mapping;

final class FieldMapper
{
    private const INBOUND_FIELDS = ['title', 'message', 'remote_state', 'prefix_id'];
    private const OUTBOUND_FIELDS = [
        'title', 'body', 'state', 'base', 'head', 'draft', 'maintainer_can_modify'
    ];

    public function applyInbound(Mapping $mapping, WebhookEvent $event, array $action): array
    {
        $target = is_array($mapping->target_config) ? $mapping->target_config : [];
        $map = is_array($target['field_map'] ?? null) ? $target['field_map'] : [];
        if ($map === []) return $action;

        foreach ($map as $destination => $source)
        {
            $destination = trim((string)$destination);
            if (!in_array($destination, self::INBOUND_FIELDS, true)) continue;
            $value = $this->readArrayPath($event->payload, (string)$source);
            if ($value === null) continue;

            $action[$destination] = match ($destination)
            {
                'prefix_id' => max(0, (int)$value),
                'remote_state' => $this->normalizeState($value),
                default => is_scalar($value) ? trim((string)$value) : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            };
        }

        return $action;
    }

    public function outboundValues(Mapping $mapping, $thread, $post, array $defaults = []): array
    {
        $sourceConfig = is_array($mapping->source_config) ? $mapping->source_config : [];
        $map = is_array($sourceConfig['field_map'] ?? null) ? $sourceConfig['field_map'] : [];
        if ($map === []) return $defaults;

        $values = $defaults;
        $context = ['thread' => $thread, 'post' => $post];
        foreach ($map as $destination => $source)
        {
            $destination = trim((string)$destination);
            if (!in_array($destination, self::OUTBOUND_FIELDS, true)) continue;
            $value = $this->readMixedPath($context, (string)$source);
            if ($value === null) continue;

            $values[$destination] = match ($destination)
            {
                'state' => $this->normalizeState($value),
                'draft', 'maintainer_can_modify' => (bool)$value,
                default => is_scalar($value) ? trim((string)$value) : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            };
        }

        return $values;
    }

    private function readArrayPath(array $data, string $path)
    {
        $path = trim($path);
        if (str_starts_with($path, 'payload.')) $path = substr($path, 8);
        if ($path === '') return null;

        $value = $data;
        foreach (explode('.', $path) as $segment)
        {
            if (!is_array($value) || !array_key_exists($segment, $value)) return null;
            $value = $value[$segment];
        }
        return $value;
    }

    private function readMixedPath(array $context, string $path)
    {
        $parts = array_values(array_filter(explode('.', trim($path)), static fn($v) => $v !== ''));
        if ($parts === []) return null;

        $value = $context;
        foreach ($parts as $segment)
        {
            if (is_array($value))
            {
                if (!array_key_exists($segment, $value)) return null;
                $value = $value[$segment];
                continue;
            }

            if ($value instanceof \ArrayAccess)
            {
                if (!$value->offsetExists($segment)) return null;
                $value = $value[$segment];
                continue;
            }

            if (is_object($value))
            {
                try
                {
                    $value = $value->{$segment};
                    continue;
                }
                catch (\Throwable $e)
                {
                    if (method_exists($value, 'get'))
                    {
                        try { $value = $value->get($segment); continue; } catch (\Throwable $ignored) {}
                    }
                    return null;
                }
            }

            return null;
        }

        return $value;
    }

    private function normalizeState($value): string
    {
        if (is_bool($value)) return $value ? 'open' : 'closed';
        $state = mb_strtolower(trim((string)$value));
        return match ($state)
        {
            'open', 'opened', 'reopened', 'true', '1' => 'open',
            'closed', 'close', 'false', '0' => 'closed',
            default => $state
        };
    }
}
