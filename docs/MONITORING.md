# Health, retry and reconciliation

0.8 introduced operational controls; 0.9 extends them for long-running GitHub ↔ XenForo synchronization.

## Health monitor

Admin CP → GitHub Sync → Health summarizes webhook throughput/failures, pending delivery age, unresolved conflicts, failed/attention XFRM records and stale XFRM reconciliation state. `critical` is used for current webhook/XFRM failures or a webhook backlog older than 15 minutes; `degraded` is used for unresolved conflicts or XFRM attention/staleness.

## XFRM manual retry

Retry does not replay an old stored webhook body. The integration fetches the current authoritative GitHub Release with the configured GitHub App installation token, builds a new release event and passes it through the normal XFRM synchronizer. Retry count and timestamps are persisted.

## Reconcile

Reconcile verifies the configured Resource and any tracked ResourceVersion/Resource Update through XenForo's REST API. A 404 marks the tracking record `attention`/`missing`; authentication and server failures are surfaced as actual errors instead of being mislabeled as missing content.

## Tracking snapshot restore

History entries contain the tracked XFRM IDs/hash at that point in time. Restoring a snapshot changes only the integration's local tracking record and marks it `attention`. It does not modify or undelete remote XFRM content. Run reconcile immediately after restoring.

## Semantic release hashing

XFRM idempotency is based on the GitHub Release fields that affect synchronization (tag, title, notes, assets and download configuration), not the complete webhook delivery. This prevents unrelated sender/delivery metadata from creating duplicate work.

## Automatic health alerts

0.9 persists health alerts in `xf_wgh_health_alert`. Inbound and outbound synchronization jobs evaluate health automatically. New critical states are also written to the XenForo error log once when the alert is created. Open alerts are resolved automatically when the monitored state returns to healthy, or may be resolved manually in Admin CP.

Optional thresholds can be set in `src/config.php`:

```php
$config['warextGitHubSync']['healthThresholds'] = [
    'failedPerHourCritical' => 1,
    'pendingAgeCritical' => 900,
    'conflictsDegraded' => 1,
    'xfrmAttentionDegraded' => 1
];
```

## Workflow-run history

`workflow_run` webhooks are stored even when no forum mapping consumes that event. Admin CP → GitHub Sync → Actions can also import the latest 50 runs from GitHub. Run controls are explicit administrator actions: re-run, re-run failed jobs or cancel.

## Pull Request review requests

Admin CP → GitHub Sync → PR tools loads a pull request and its currently requested user/team reviewers. Review requests may be added or removed by GitHub login/team slug. These operations require GitHub App Pull requests write permission.

## Bulk XFRM recovery

Up to 50 tracked XFRM release records can be selected for bulk retry or reconcile. Each record remains isolated: one failure does not abort the remaining selected records.
