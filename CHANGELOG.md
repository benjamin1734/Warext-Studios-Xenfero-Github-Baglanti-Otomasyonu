# Changelog

## 0.6.0 Alpha 6
- Added XenForo thread → GitHub Pull Request create/update/close synchronization.
- Added XenForo replies → Pull Request conversation comments.
- Added optional XenForo prefix → GitHub PR review actions (`APPROVE`, `REQUEST_CHANGES`, `COMMENT`) with duplicate-review protection.
- Added GitHub `workflow_run` and `workflow_job` webhook modules.
- Added workflow branch/status/conclusion filters.
- Added Admin CP Actions browser and explicit `workflow_dispatch` execution.
- Added safe inbound/outbound field mapping for selected synchronization fields.
- Added GitHub REST helpers for Pull Requests, PR reviews and Actions workflows.
- Updated REST user agent to the 0.6 development line.

## 0.5.0 Alpha 5
- Added persistent synchronization conflict records and Admin CP conflict review/resolution workflow.
- Added `github_wins`, `xenforo_wins`, `newest_wins`, and `manual` conflict strategies to the active inbound dispatcher.
- Unified XenForo content hashing so inbound/outbound conflict detection uses the same title/message/prefix/open-state model.
- Added GitHub label ↔ XenForo prefix mapping in both directions.
- Added optional GitHub open/closed state → XenForo thread lock state synchronization.
- Added XenForo prefix → GitHub Issue labels synchronization.
- Added scoped GitHub-login → XenForo-user mappings and optional inbound author resolution.
- Added `xf_wgh_user_mapping` and `xf_wgh_conflict` install/upgrade/uninstall schema support.
- Added Admin CP screens for user mappings and conflict queue.
- Added diagnostics/static-integrity coverage for the new modules.
- Updated GitHub REST client user agent for the 0.5 development line.

## 0.4.0 Alpha 4
- Added GitHub `issues`, `issue_comment`, `pull_request`, `pull_request_review`, and `pull_request_review_comment` message/action normalization.
- Added dynamic parent-child routing so GitHub comments can automatically locate the XenForo thread created for their parent Issue/PR.
- Added persistent Issue/PR number metadata to synchronized objects.
- Added XenForo `entity_post_save` and `entity_post_delete` listeners for Thread/Post outbound synchronization.
- Added outbound XenForo job queue with unique jobs and retry delays to avoid blocking forum requests on GitHub network calls.
- Added XenForo → GitHub Issue creation/update/close support.
- Added XenForo reply → GitHub Issue comment create/update/delete support.
- Added `SyncGuard` recursion protection so GitHub-originated XenForo changes do not immediately echo back to GitHub.
- Added mapping options for XenForo source forum, first-post synchronization, reply synchronization, and thread state synchronization.
- Added GitHub API helpers for issue and issue-comment write actions.
- Improved sync registry with reverse XenForo lookup and repository-wide parent lookup.
- Fixed test portability; core tests no longer reference a previous package path.

## 0.3.0 Alpha 3
- Added Admin CP route/controller and navigation for the integration.
- Added dedicated `wghManage` administrator permission.
- Added dashboard with connection/repository/mapping/delivery counters and recent webhook activity.
- Added GitHub connection CRUD, safe secret references and manual repository synchronization action.
- Added repository browser.
- Added mapping CRUD for repository/event/action/direction/target/filter configuration.
- Added reusable message-template entity, repository, Admin CP editor and mapping association.
- Added webhook delivery browser, payload inspector and manual retry action.
- Added diagnostics screen for PHP/OpenSSL/JSON/database/GitHub App connectivity.
- Added delayed push aggregation by repository + ref with commit deduplication.
- Added exponential webhook retry scheduling.
- Added connection deletion guard while synchronized repository records still belong to the connection.

## 0.2.0 Alpha 2
- Added GitHub App JWT authentication and installation access token client.
- Added installation repository discovery and repository upsert/deactivation synchronization.
- Added config-reference based private-key and webhook-secret handling.
- Added per-repository/per-connection webhook secret resolution with global bootstrap fallback.
- Added branch, prerelease/draft, commit-message and path filters.
- Added template renderer and release/push/tag message factory.
- Added persistent object sync registry and payload-hash idempotency.
- Added native XenForo thread creation, reply, first-post edit and thread-title edit actions.
