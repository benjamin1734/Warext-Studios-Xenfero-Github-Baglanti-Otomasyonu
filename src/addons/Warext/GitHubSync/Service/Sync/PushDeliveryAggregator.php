<?php

namespace Warext\GitHubSync\Service\Sync;

use Warext\GitHubSync\Entity\Delivery;

final class PushDeliveryAggregator
{
    public function aggregate(Delivery $primary, int $windowSeconds): Delivery
    {
        if ((string)$primary->github_event !== 'push' || $windowSeconds <= 0 || (string)$primary->source_ref === '')
        {
            return $primary;
        }

        $from = max(0, (int)$primary->received_date - 2);
        $to = (int)$primary->received_date + $windowSeconds;

        $candidates = \XF::finder('Warext\\GitHubSync:Delivery')
            ->where('delivery_id', '!=', (int)$primary->delivery_id)
            ->where('github_repository_id', (int)$primary->github_repository_id)
            ->where('github_event', 'push')
            ->where('source_ref', (string)$primary->source_ref)
            ->where('status', 'received')
            ->where('received_date', '>=', $from)
            ->where('received_date', '<=', $to)
            ->order('received_date', 'ASC')
            ->fetch();

        if (count($candidates) === 0)
        {
            return $primary;
        }

        $all = [$primary];
        foreach ($candidates as $candidate)
        {
            $all[] = $candidate;
        }
        usort($all, static fn($a, $b) => ((int)$a->received_date <=> (int)$b->received_date) ?: ((int)$a->delivery_id <=> (int)$b->delivery_id));

        $base = json_decode((string)$all[0]->payload, true);
        if (!is_array($base))
        {
            return $primary;
        }

        $commits = [];
        $commitKeys = [];
        $latest = $base;
        foreach ($all as $delivery)
        {
            $payload = json_decode((string)$delivery->payload, true);
            if (!is_array($payload))
            {
                continue;
            }
            $latest = $payload;
            foreach ((array)($payload['commits'] ?? []) as $commit)
            {
                if (!is_array($commit))
                {
                    continue;
                }
                $key = (string)($commit['id'] ?? hash('sha256', json_encode($commit)));
                if (isset($commitKeys[$key]))
                {
                    continue;
                }
                $commitKeys[$key] = true;
                $commits[] = $commit;
            }
        }

        $base['after'] = (string)($latest['after'] ?? ($base['after'] ?? ''));
        $base['head_commit'] = $latest['head_commit'] ?? ($base['head_commit'] ?? null);
        $base['compare'] = (string)($latest['compare'] ?? ($base['compare'] ?? ''));
        $base['commits'] = $commits;
        $base['size'] = count($commits);
        $base['distinct_size'] = count($commits);
        $base['created'] = !empty($base['created']);
        $base['deleted'] = !empty($latest['deleted']);
        $base['forced'] = !empty($latest['forced']);

        $json = json_encode($base, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json))
        {
            return $primary;
        }

        $primary->payload = $json;
        $primary->payload_hash = hash('sha256', $json);
        $primary->save();

        foreach ($candidates as $candidate)
        {
            $candidate->status = 'grouped';
            $candidate->grouped_into_delivery_id = (int)$primary->delivery_id;
            $candidate->processed_date = \XF::$time;
            $candidate->last_error = 'Grouped into delivery #' . (int)$primary->delivery_id;
            $candidate->save();
        }

        return $primary;
    }
}
