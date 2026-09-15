# Warext GitHub Sync – Development status

Version: **0.6.0 Alpha 6**

## Completed
- Core add-on database/entity/repository structure.
- GitHub App RS256 JWT and installation access-token flow.
- Installation repository discovery/synchronization.
- Config-reference secret/private-key handling.
- HMAC-SHA256 webhook verification and delivery deduplication.
- Persistent GitHub object ↔ XenForo sync registry with reverse/parent lookup.
- Release / push / tag GitHub → XenForo create-update-delete path.
- GitHub Issues / Issue comments / Pull Requests / PR reviews / PR review comments → XenForo normalization and routing.
- Dynamic GitHub parent object → XenForo thread resolution.
- XenForo → GitHub Issue create/update/close flow.
- XenForo replies → GitHub Issue comments create/update/delete flow.
- Queue-based outbound processing with retry.
- Bidirectional recursion guard.
- Native XenForo thread/post create-edit-delete services.
- Mapping filters and reusable templates.
- Push aggregation and delivery retry.
- Admin CP dashboard, connections, repositories, mappings, templates, webhook logs and diagnostics.
- Dedicated Admin CP permission.

## Completed in 0.5.0 Alpha 5
- Active conflict resolver (`github_wins`, `xenforo_wins`, `newest_wins`, manual review queue).
- GitHub labels ↔ XenForo prefixes mapping in both directions.
- GitHub Issue state ↔ XenForo thread open/close synchronization.
- Scoped GitHub user → XenForo user mapping.
- Admin CP conflict and user-mapping screens.
- Unified XenForo hash model for reliable conflict detection.

## Completed in 0.6.0 Alpha 6
- XenForo thread → GitHub Pull Request create/update/close support.
- XenForo replies → Pull Request conversation comment create/update/delete support.
- Optional XenForo prefix → GitHub PR review actions (`APPROVE`, `REQUEST_CHANGES`, `COMMENT`).
- `workflow_run` and `workflow_job` inbound event rendering and parent routing.
- Workflow branch/status/conclusion filters.
- Admin CP GitHub Actions browser + explicit `workflow_dispatch` action.
- Safe inbound/outbound field mapping layer for selected synchronization fields.

## Next major development block
- XFRM integration module for GitHub releases ↔ resource versions/discussion threads.
- More granular PR reviewer/request controls and workflow run monitoring.
- Full XenForo 2.3 runtime import/template validation and `xf-addon:build-release` production package.
