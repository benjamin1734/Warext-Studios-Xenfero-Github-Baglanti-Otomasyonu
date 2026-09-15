# Field mapping

0.6 adds an optional safe field-mapping layer. It does **not** allow arbitrary PHP/entity writes; only explicitly supported synchronization fields are accepted.

## Inbound: GitHub → XenForo

Configure `XenForo action field=GitHub payload path` in a mapping.

Supported action fields:

- `title`
- `message`
- `remote_state`
- `prefix_id`

Examples:

```text
title=workflow_run.name
message=workflow_run.display_title
remote_state=issue.state
prefix_id=repository.custom_properties.xf_prefix
```

Payload paths are dot-separated and start at the webhook payload root. Invalid/missing paths are ignored.

## Outbound: XenForo → GitHub

Configure `GitHub field=XenForo field path`.

Supported GitHub fields:

- `title`
- `body`
- `state`
- `head`
- `base`
- `draft`
- `maintainer_can_modify`

Examples:

```text
title=thread.title
body=post.message
head=thread.custom_fields.github_branch
base=thread.custom_fields.github_base
```

This makes Pull Request mappings useful without hard-coding every branch in the mapping itself. A fixed PR head/base can still be configured as a fallback.

## Safety

The mapper is deliberately allow-listed. Unknown destinations are ignored, and it never evaluates PHP or template expressions.
