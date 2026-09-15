# Warext GitHub Sync – Development status

Version: **0.4.0 Alpha 4**

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

## Next major development block
- Conflict resolver (`github_wins`, `xenforo_wins`, `newest_wins`, manual review queue).
- GitHub labels ↔ XenForo prefixes mapping.
- GitHub issue state ↔ XenForo thread open/close state in both directions with configurable policies.
- GitHub user ↔ XenForo user mapping.
- Pull-request outbound actions and review workflow controls.
- Workflow/Actions event module.
- XFRM integration module.
- Full XenForo 2.3 runtime import/template validation and `xf-addon:build-release` production package.
