# XFRM integration

0.7 adds an optional adapter for **XenForo Resource Manager 2.3+**. The first supported workflow deliberately targets an **existing ResourceItem** instead of creating resources automatically.

## GitHub Release flow

A `release` mapping can enable XFRM synchronization:

1. Validate that XFRM is loaded and the configured resource exists.
2. Resolve the configured local XenForo API key from `config.php`.
3. Create one XFRM ResourceVersion from the GitHub release tag.
4. Resolve an external download URL from a matching release asset, GitHub source ZIP, or release page.
5. Create one Resource Update from the GitHub release name/body. This uses XFRM's normal resource-update/discussion workflow.
6. Persist the GitHub release ID ↔ ResourceVersion/ResourceUpdate IDs in `xf_wgh_xfrm_release`.
7. Later release edits update the existing Resource Update instead of creating duplicate versions.

The official XenForo REST API is used for XFRM writes. This avoids depending on undocumented internal ResourceVersion service signatures.

## API key configuration

Create a dedicated XenForo API key with the minimum Resource Manager scope required by the operations you enable (normally `resource:write`). Store it in `src/config.php`:

```php
$config['warextGitHubSync']['xfrmApiKeys'] = [
    'github_xfrm' => 'REAL-XENFORO-API-KEY'
];
```

The mapping stores only `github_xfrm`, not the real key.

## Mapping options

- Existing XFRM resource ID.
- API key reference and optional API context user.
- `alongside`: XFRM + normal forum target.
- `only`: process the release only through XFRM.
- Create ResourceVersion toggle.
- Create Resource Update toggle.
- Download source: matching release asset, source ZIP, or release page.
- Asset wildcard, e.g. `Warext-*.zip`.
- Strip leading `v` from numeric tags.
- Deleted/unpublished release policy: ignore, soft delete, hard delete.

## Edit safety

XenForo's public API exposes create/get/delete operations for ResourceVersion. Therefore 0.7 does **not** silently mutate an existing version when a GitHub release tag or external download URL changes after version creation. The sync record is marked `attention`; release notes can still update the linked Resource Update. This avoids destructive version recreation.

## Current boundary

0.7 does not automatically create a new ResourceItem/category. That belongs in a later module after live XFRM runtime testing. Existing resources are the safe, predictable first target.
