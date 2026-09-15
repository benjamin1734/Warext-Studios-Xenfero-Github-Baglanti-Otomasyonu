# Warext GitHub Sync for XenForo

[Türkçe](README.tr-TR.md) | **English**

**Warext GitHub Sync** is a modular GitHub ↔ XenForo synchronization add-on for XenForo 2.3+. It is designed as a synchronization engine, not merely a webhook poster.

> Current status: **0.4.0 Alpha 4 — development source, not a production release.**

## What it does

GitHub → XenForo currently supports:

- Releases: create/update mapped XenForo threads or posts.
- Pushes and tags: filtered/aggregated changelog messages.
- Issues: create/update mapped XenForo discussions.
- Issue comments: create/update/delete replies and automatically resolve the parent Issue thread.
- Pull requests, PR reviews and PR review comments: normalize and route them to XenForo.
- Persistent object mapping, payload idempotency, webhook delivery logs and retries.

XenForo → GitHub currently supports:

- Thread + first post → GitHub Issue create/update.
- Thread replies → GitHub Issue comment create/update.
- Reply deletion → GitHub Issue comment deletion.
- Thread deletion → close the mapped GitHub Issue.
- Optional thread open/closed state synchronization.
- Queue-based outbound processing with retry so a GitHub outage does not block normal forum posting.
- Recursion protection to prevent GitHub → XenForo → GitHub loops.

## Architecture

```text
GitHub Webhooks
      │
      ▼
HMAC validation → Delivery log → Queue / retry → Event normalizer
                                             │
                                             ▼
                                   Mapping / filter engine
                                             │
                    ┌────────────────────────┴──────────────────────┐
                    ▼                                               ▼
             XenForo actions                                Sync registry
       thread/post create/edit/delete                  GitHub object ↔ XF object
                    ▲                                               │
                    └────────────────────────┬──────────────────────┘
                                             │
                                   XenForo entity events
                                             │
                                             ▼
                                   Outbound queue / retry
                                             │
                                             ▼
                                     GitHub REST API
```

The synchronization registry stores durable identity links, so editing a GitHub Issue or Release edits the previously-created XenForo content rather than blindly creating another post.

## Repository layout

```text
src/addons/Warext/GitHubSync/   XenForo add-on source
  _output/                      XenForo development output
  _no_upload/                   development notes/tests
docs/                           English/Turkish project docs
```

## Documentation

- [Installation / development setup](docs/INSTALLATION.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Türkçe kurulum](docs/KURULUM.tr-TR.md)
- [Türkçe mimari](docs/MIMARI.tr-TR.md)
- [Changelog](CHANGELOG.md)

## Security

Webhook requests are validated using GitHub `X-Hub-Signature-256` HMAC-SHA256 signatures. GitHub App private keys and webhook secrets are referenced from XenForo configuration and are intentionally not stored as plaintext in the add-on tables.

Never commit your GitHub App private key or real webhook secret to this repository.

## Development status

This repository contains development output (`_output`). A real XenForo 2.3 development installation is still required to import/validate the development data and produce a production package using XenForo's normal add-on build process.

Warext Studios
