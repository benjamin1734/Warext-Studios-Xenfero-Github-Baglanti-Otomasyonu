# Warext GitHub Sync – Development status

Version: **0.8.0 Alpha 8**

## Completed foundation
- Core add-on database/entity/repository structure.
- GitHub App RS256 JWT and installation access-token flow.
- Installation repository discovery/synchronization.
- Config-reference secret/private-key handling.
- HMAC-SHA256 webhook verification and delivery deduplication.
- Persistent GitHub object ↔ XenForo sync registry with reverse/parent lookup.
- GitHub Releases / push / tags / Issues / comments / Pull Requests / reviews / Actions → XenForo routing.
- XenForo → GitHub Issue and Pull Request create/update/close flows.
- Queue-based outbound processing with retry and recursion guard.
- Conflict handling, label ↔ prefix mapping, GitHub ↔ XenForo user mapping and safe field mapping.
- Admin CP dashboard, connection/repository/mapping/template/Actions/XFRM/Health/log/diagnostics modules.

## 0.7–0.8 XFRM layer
- GitHub Release → existing XFRM ResourceVersion + Resource Update through XenForo REST API.
- Persistent release/version/update identity tracking and configurable download/delete behavior.
- Per-action XFRM history snapshots.
- Manual retry by refetching the authoritative GitHub Release through the GitHub App API.
- Remote Resource/ResourceVersion/ResourceUpdate reconciliation.
- Safe local tracking-snapshot restore followed by reconcile.
- Retry/attempt/success/reconcile timestamps and remote-state tracking.
- Semantic release hashing for idempotency.
- Dedicated XFRM mapping manager.
- Synchronization Health monitor with backlog/failure/conflict/XFRM metrics.

## Next major development block
- Bulk XFRM retry/reconcile and notification thresholds.
- More granular PR reviewer/request controls and workflow-run history.
- Runtime smoke-test harness for a real XenForo 2.3 + XFRM installation.
- Production `_data` export / `xf-addon:build-release` once a real XenForo development environment is available.
