# Architecture

Warext GitHub Sync is split into independent layers so new GitHub/XenForo modules can be added without coupling everything to a single webhook controller.

## Inbound path

1. Public webhook controller receives the raw GitHub delivery.
2. Candidate connection secrets are resolved.
3. HMAC-SHA256 signature is validated.
4. Delivery GUID is deduplicated and payload is persisted.
5. XenForo job queue processes the delivery with retry.
6. Event normalizer converts the payload into a common event DTO.
7. Mapping repository finds repository/event/action mappings.
8. Filter engine applies branch/release/commit/path rules.
9. Message factory creates a normalized XenForo action.
10. Native XenForo services create/edit/delete thread/post content.
11. Sync registry stores GitHub object ↔ XenForo object identity.

## Dynamic parent routing

Issue/PR comments do not require a hard-coded target thread. The comment carries its parent GitHub object identity; the sync registry resolves that parent to the XenForo post/thread created earlier and routes the new comment there.

## Outbound path

1. XenForo Thread/Post entity save/delete listeners enqueue an outbound job.
2. `SyncGuard` ignores changes that are currently being applied from GitHub.
3. Outbound processor selects mappings by direction/repository/source forum.
4. It resolves or creates the parent GitHub Issue.
5. Thread title/first post become Issue title/body.
6. Replies become Issue comments.
7. Hashes prevent redundant writes.
8. GitHub REST failures are retried outside the user request.

## Safety principles

- Secrets are configuration references, not plaintext database values.
- Incoming webhook payloads require GitHub HMAC validation.
- Delivery GUIDs provide replay/duplicate protection.
- Integration-created content is tracked before destructive delete policies are allowed.
- Existing XenForo thread first posts are protected from unsafe remote deletion.
- Outbound API writes run in queue jobs instead of blocking normal forum requests.
- Loop protection prevents a synchronized write from immediately echoing back to its source.
