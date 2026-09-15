# Warext GitHub Sync – Development status

Version: **0.9.0 Alpha 9**

## Completed foundation
- Core add-on database/entity/repository structure.
- GitHub App RS256 JWT and installation access-token flow.
- Installation repository discovery/synchronization.
- Config-reference secret/private-key handling.
- HMAC-SHA256 webhook verification and delivery deduplication.
- Persistent GitHub object ↔ XenForo sync registry with reverse/parent lookup.
- Release / push / tag GitHub → XenForo create-update-delete path.
- GitHub Issues / Issue comments / Pull Requests / PR reviews / PR review comments → XenForo normalization and routing.
- Dynamic GitHub parent object → XenForo thread resolution.
- XenForo → GitHub Issue and Pull Request create/update/close flows.
- XenForo replies → GitHub Issue/PR conversation comments.
- Queue-based outbound processing with retry and recursion guard.
- Mapping filters, reusable templates and safe field mapping.
- Push aggregation and webhook delivery retry.
- Admin CP dashboard, connections, repositories, mappings, templates, Actions, webhook logs and diagnostics.

## Completed in 0.5.0 Alpha 5
- Conflict resolver (`github_wins`, `xenforo_wins`, `newest_wins`, manual review queue).
- GitHub labels ↔ XenForo prefixes mapping.
- GitHub Issue state ↔ XenForo thread open/close synchronization.
- Scoped GitHub user → XenForo user mapping.

## Completed in 0.6.0 Alpha 6
- XenForo thread → GitHub Pull Request create/update/close.
- Optional XenForo prefix → GitHub PR review actions.
- `workflow_run` / `workflow_job` inbound handling.
- Admin CP Actions browser and explicit `workflow_dispatch`.
- Safe inbound/outbound field mapping.

## Completed in 0.7.0 Alpha 7
- GitHub Release → existing XFRM ResourceVersion + Resource Update synchronization.
- Persistent release/version/update identity tracking.
- Download source selection and delete policies.
- XFRM Admin CP status and diagnostics.

## Completed in 0.8.0 Alpha 8
- XFRM action history/audit snapshots.
- Manual retry by refetching the current GitHub Release through the GitHub App API.
- Remote Resource/ResourceVersion/ResourceUpdate reconciliation.
- Safe local tracking-snapshot restore followed by reconcile.
- Retry/attempt/success/reconcile timestamps and remote-state tracking.
- Semantic release hashing for XFRM idempotency.
- Dedicated XFRM mapping manager.
- Synchronization Health monitor with backlog/failure/conflict/XFRM metrics.

## Completed in 0.9.0 Alpha 9
- Persistent workflow-run history and explicit run controls.
- Pull Request reviewer/team request management.
- Automatic persisted health alerts with configurable thresholds.
- Bulk XFRM retry/reconcile.
- 0.9 workflow-run and health-alert schema.

## Next major development block
- Runtime smoke-test harness for a real XenForo 2.3 + XFRM installation.
- Production `_data` export / `xf-addon:build-release` package once a real XenForo development environment is available.
