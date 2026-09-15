# Health, retry and reconciliation

0.8 adds operational controls for long-running GitHub ↔ XenForo synchronization.

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
