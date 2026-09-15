<?php

namespace Warext\GitHubSync\Service\Admin;

final class HealthMonitor
{
    public function snapshot(): array
    {
        $now = \XF::$time;
        $hour = $now - 3600;
        $day = $now - 86400;
        $failedHour = \XF::finder('Warext\\GitHubSync:Delivery')->where('status', 'failed')->where('received_date', '>=', $hour)->total();
        $processedHour = \XF::finder('Warext\\GitHubSync:Delivery')->where('status', 'processed')->where('received_date', '>=', $hour)->total();
        $pending = \XF::finder('Warext\\GitHubSync:Delivery')->where('status', ['received','processing'])->total();
        $oldestPending = \XF::finder('Warext\\GitHubSync:Delivery')->where('status', ['received','processing'])->order('received_date')->fetchOne();
        $pendingAge = $oldestPending ? max(0, $now - (int)$oldestPending->received_date) : 0;
        $conflicts = \XF::finder('Warext\\GitHubSync:Conflict')->where('status','pending')->total();
        $xfrmFailed = \XF::finder('Warext\\GitHubSync:XfrmRelease')->where('status','failed')->total();
        $xfrmAttention = \XF::finder('Warext\\GitHubSync:XfrmRelease')->where('status','attention')->total();
        $staleXfrm = \XF::finder('Warext\\GitHubSync:XfrmRelease')->where('status',['synced','attention'])->where('updated_date','<',$day)->where('last_reconcile_date',0)->total();
        $inactiveConnections = \XF::finder('Warext\\GitHubSync:Connection')->where('active',0)->total();

        $severity = 'healthy';
        $reasons = [];
        if ($failedHour > 0 || $xfrmFailed > 0 || $pendingAge > 900)
        {
            $severity = 'critical';
            if ($failedHour) $reasons[] = $failedHour . ' webhook failure(s) in the last hour';
            if ($xfrmFailed) $reasons[] = $xfrmFailed . ' failed XFRM sync record(s)';
            if ($pendingAge > 900) $reasons[] = 'oldest webhook pending for ' . $pendingAge . ' seconds';
        }
        elseif ($conflicts > 0 || $xfrmAttention > 0 || $staleXfrm > 0)
        {
            $severity = 'degraded';
            if ($conflicts) $reasons[] = $conflicts . ' pending conflict(s)';
            if ($xfrmAttention) $reasons[] = $xfrmAttention . ' XFRM record(s) need attention';
            if ($staleXfrm) $reasons[] = $staleXfrm . ' XFRM record(s) have never been reconciled and are older than one day';
        }

        return [
            'severity' => $severity,
            'reasons' => $reasons,
            'failedHour' => $failedHour,
            'processedHour' => $processedHour,
            'pending' => $pending,
            'pendingAge' => $pendingAge,
            'conflicts' => $conflicts,
            'xfrmFailed' => $xfrmFailed,
            'xfrmAttention' => $xfrmAttention,
            'staleXfrm' => $staleXfrm,
            'inactiveConnections' => $inactiveConnections,
            'generatedAt' => $now
        ];
    }
}
