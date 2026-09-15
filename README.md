# Warext GitHub Sync for XenForo

[Türkçe](README.tr-TR.md) | **English**

**Warext GitHub Sync** is a modular, bidirectional GitHub ↔ XenForo synchronization add-on for XenForo 2.3+. It is a synchronization engine rather than a simple webhook poster: GitHub and XenForo objects keep durable identity links so later edits, deletes and state changes can update the original counterpart.

> Current status: **0.8.0 Alpha 8 — development source, not a production release.**

## Current capabilities

GitHub → XenForo supports releases, push/tag changelogs, Issues, Issue comments, Pull Requests, PR reviews/review comments, GitHub Actions `workflow_run` / `workflow_job`, reusable templates, filters, queues/retries and persistent object mapping.

XenForo → GitHub supports Issue create/update/close, Issue comments, Pull Request create/update/close, PR conversation comments, optional prefix → PR review actions, explicit Admin CP `workflow_dispatch`, label ↔ prefix mapping, user mapping, safe field mapping and conflict policies (`github_wins`, `xenforo_wins`, `newest_wins`, manual review).

Optional XenForo Resource Manager integration supports GitHub Release → existing XFRM ResourceVersion + Resource Update synchronization using XenForo's REST API. v0.8 adds XFRM action history, manual retry by refetching the authoritative GitHub Release, remote reconciliation, safe local tracking-snapshot restore, retry/attempt/success/reconcile timestamps, remote-state tracking and semantic release hashing.

## Operations and monitoring

Admin CP contains Dashboard, Connections, Repositories, Mappings, Templates, Actions, XFRM, Health, User mappings, Conflicts, Webhook logs and Diagnostics. The Health monitor summarizes webhook failures/backlog, unresolved conflicts and XFRM failed/attention/staleness as `healthy`, `degraded` or `critical`.

Manual XFRM retry does not blindly replay an old payload: it fetches the current GitHub Release using the configured GitHub App installation. Reconcile checks the configured Resource and tracked ResourceVersion/Resource Update identities. HTTP 404 is treated as a missing remote object; authentication and server failures remain real errors.

## Architecture

```text
GitHub Webhooks
      │
      ▼
HMAC validation → Delivery log → Queue/retry → Event normalizer
                                             │
                                             ▼
                                   Mapping/filter engine
                                             │
                     ┌───────────────────────┴──────────────────────┐
                     ▼                                              ▼
              XenForo actions                               Sync registry
                     ▲                                              │
                     └───────────────────────┬──────────────────────┘
                                             │
                                   XenForo entity events
                                             │
                                             ▼
                                   Outbound queue/retry
                                             │
                                             ▼
                                      GitHub REST API

GitHub Release → optional XFRM adapter → XenForo REST API → ResourceVersion/Update
```

## Documentation

- [Installation / development setup](docs/INSTALLATION.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Field mapping](docs/FIELD_MAPPING.md)
- [XFRM integration](docs/XFRM.md)
- [Health / retry / reconciliation](docs/MONITORING.md)
- [Türkçe kurulum](docs/KURULUM.tr-TR.md)
- [Türkçe mimari](docs/MIMARI.tr-TR.md)
- [Türkçe izleme](docs/IZLEME.tr-TR.md)
- [Changelog](CHANGELOG.md)

## Security

Webhook deliveries require GitHub `X-Hub-Signature-256` HMAC-SHA256 validation and delivery-ID deduplication. GitHub App private keys, webhook secrets and XFRM API keys are referenced from XenForo `config.php`; real secrets are not stored in add-on tables or committed to the repository.

## Development status

The repository contains XenForo development output (`_output`). Before a production release, the add-on still needs import/template/runtime testing on a real XenForo 2.3 + XFRM development installation and XenForo's normal `_data` / `xf-addon:build-release` build process.

Warext Studios
