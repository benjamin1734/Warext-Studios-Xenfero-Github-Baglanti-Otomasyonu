<?php

namespace Warext\GitHubSync\Service\Admin;

final class HealthAlertManager
{
    public function evaluate(?array $snapshot = null): array
    {
        $snapshot ??= (new HealthMonitor())->snapshot();
        $severity = (string)($snapshot['severity'] ?? 'healthy');
        $message = implode('; ', array_map('strval', (array)($snapshot['reasons'] ?? [])));
        $now = \XF::$time;

        if ($severity === 'healthy')
        {
            foreach (\XF::finder('Warext\\GitHubSync:HealthAlert')->where('status','open')->fetch() as $alert)
            {
                $alert->status = 'resolved'; $alert->resolved_date = $now; $alert->last_seen_date = $now; $alert->save();
            }
            return $snapshot;
        }

        $fingerprint = hash('sha256', $severity . '|' . $message);
        $alert = \XF::finder('Warext\\GitHubSync:HealthAlert')->where('fingerprint',$fingerprint)->where('status','open')->fetchOne();
        if (!$alert)
        {
            foreach (\XF::finder('Warext\\GitHubSync:HealthAlert')->where('status','open')->fetch() as $open)
            {
                $open->status = 'resolved'; $open->resolved_date = $now; $open->save();
            }
            $alert = \XF::em()->create('Warext\\GitHubSync:HealthAlert');
            $alert->fingerprint = $fingerprint;
            $alert->severity = $severity;
            $alert->status = 'open';
            $alert->message = mb_substr($message !== '' ? $message : ucfirst($severity) . ' synchronization health state.', 0, 2000);
            $alert->occurrence_count = 1;
            $alert->first_seen_date = $now;
            if ($severity === 'critical') \XF::logError('[Warext GitHub Sync] Critical health alert: ' . $alert->message, false);
        }
        else
        {
            $alert->occurrence_count = (int)$alert->occurrence_count + 1;
        }
        $alert->last_seen_date = $now;
        $alert->save();
        return $snapshot;
    }
}
