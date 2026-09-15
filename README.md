# Warext GitHub Sync for XenForo

## Türkçe

**Warext GitHub Sync**, XenForo 2.3+ için modüler ve çift yönlü bir GitHub ↔ XenForo senkronizasyon eklentisidir. Sistem yalnızca webhook mesajı göndermek yerine GitHub ve XenForo nesneleri arasında kalıcı kimlik bağı tutar; düzenleme, silme, durum değişikliği, conflict, retry ve recovery işlemlerini yönetir.

> Güncel sürüm: **v0.9.0 Alpha 9 — pre-release / geliştirme sürümü.**

### İndir / Kurulum

- **GitHub Releases:** [v0.9.0 Alpha 9](https://github.com/benjamin1734/Warext-Studios-Xenfero-Github-Baglanti-Otomasyonu/releases/tag/v0.9.0-alpha.9)
- Release içerisinde `Warext-GitHub-Sync-v0.9.0-Alpha9-install.zip` kurulum paketi bulunur.
- Bu Alpha paket, XenForo geliştirme kurulumu için `upload/` yapısı ve `_output` geliştirme verisini içerir.
- Nihai production ACP archive paketi, gerçek XenForo 2.3 runtime üzerinde `xf-addon:build-release` ile `_data + hashes.json` üretildikten sonra hazırlanacaktır.

### Özellikler

GitHub → XenForo:

- Release oluşturma/düzenleme/silme → XenForo konu/mesaj senkronizasyonu.
- Push ve tag olaylarını filtreleme, gruplama ve changelog mesajına dönüştürme.
- GitHub Issue → XenForo konu oluşturma/güncelleme.
- Issue comment → doğru konuya cevap oluşturma/güncelleme/silme.
- Pull Request, PR review ve review comment olaylarını XenForo'ya yönlendirme.
- GitHub Actions `workflow_run` / `workflow_job` takibi ve filtreleri.
- GitHub Release → mevcut XFRM ResourceVersion + Resource Update senkronizasyonu.
- Kalıcı nesne eşleme, delivery deduplication, queue ve retry sistemi.

XenForo → GitHub:

- XenForo konusu + ilk mesaj → GitHub Issue oluşturma/güncelleme/kapatma.
- XenForo cevapları → GitHub Issue / PR conversation comment.
- Label ↔ prefix ve açık/kapalı durum senkronizasyonu.
- GitHub hesabı ↔ XenForo kullanıcı eşleme sistemi.
- `github_wins`, `xenforo_wins`, `newest_wins` ve manuel conflict çözümü.
- XenForo konusu → GitHub Pull Request oluşturma/güncelleme/kapatma.
- Prefix → `APPROVE`, `REQUEST_CHANGES`, `COMMENT` PR review otomasyonu.
- PR kullanıcı/team reviewer request yönetimi.
- GitHub Actions workflow dispatch, geçmiş, rerun, failed-rerun ve cancel kontrolleri.
- Güvenli inbound/outbound field mapping.
- XFRM history, manuel/bulk retry, reconcile ve tracking restore.
- Health monitoring ve kalıcı health alert kayıtları.

### Yönetim paneli

Admin CP içerisinde Dashboard, Connections, Repositories, Mappings, Templates, Actions, PR Tools, XFRM, Health, User Mappings, Conflicts, Webhook Logs ve Diagnostics modülleri bulunur.

### Güvenlik

Webhook istekleri `X-Hub-Signature-256` HMAC-SHA256 ile doğrulanır. GitHub App private key, webhook secret ve XFRM API key gibi hassas değerler eklenti tablolarına açık metin olarak yazılmaz; `config.php` üzerinden referanslanır.

### Türkçe dokümantasyon

- [Kurulum](docs/KURULUM.tr-TR.md)
- [Mimari](docs/MIMARI.tr-TR.md)
- [Alan eşleme](docs/ALAN_ESLEME.tr-TR.md)
- [XFRM entegrasyonu](docs/XFRM.tr-TR.md)
- [Health / retry / reconcile](docs/IZLEME.tr-TR.md)
- [Değişiklik günlüğü](CHANGELOG.tr-TR.md)

---

## English

**Warext GitHub Sync** is a modular, bidirectional GitHub ↔ XenForo synchronization add-on for XenForo 2.3+. Rather than acting as a simple webhook poster, it maintains durable identity links between GitHub and XenForo objects and manages edits, deletions, state changes, conflicts, retries and recovery.

> Current version: **v0.9.0 Alpha 9 — pre-release / development build.**

### Download / Installation

- **GitHub Releases:** [v0.9.0 Alpha 9](https://github.com/benjamin1734/Warext-Studios-Xenfero-Github-Baglanti-Otomasyonu/releases/tag/v0.9.0-alpha.9)
- The release contains `Warext-GitHub-Sync-v0.9.0-Alpha9-install.zip`.
- This Alpha package contains the `upload/` layout and XenForo development `_output` data for a development installation.
- A final production ACP archive will be generated from a real XenForo 2.3 runtime using `xf-addon:build-release`, which produces `_data + hashes.json`.

### Features

GitHub → XenForo:

- Release create/update/delete → XenForo thread/post synchronization.
- Filtered and aggregated push/tag changelogs.
- GitHub Issue → XenForo discussion create/update.
- Issue comments → create/update/delete replies in the correct discussion.
- Pull Request, PR review and review-comment routing.
- GitHub Actions `workflow_run` / `workflow_job` history and filters.
- GitHub Release → existing XFRM ResourceVersion + Resource Update synchronization.
- Persistent object mapping, delivery deduplication, queueing and retry.

XenForo → GitHub:

- Thread + first post → GitHub Issue create/update/close.
- Replies → GitHub Issue / PR conversation comments.
- Label ↔ prefix and open/closed state synchronization.
- GitHub account ↔ XenForo user mappings.
- `github_wins`, `xenforo_wins`, `newest_wins` and manual conflict resolution.
- Thread → GitHub Pull Request create/update/close.
- Prefix → `APPROVE`, `REQUEST_CHANGES`, `COMMENT` PR review automation.
- Pull Request user/team reviewer request management.
- GitHub Actions dispatch, history, rerun, failed-rerun and cancel controls.
- Safe inbound/outbound field mapping.
- XFRM history, manual/bulk retry, reconcile and tracking restore.
- Health monitoring and persistent health-alert records.

### Admin CP

The Admin CP includes Dashboard, Connections, Repositories, Mappings, Templates, Actions, PR Tools, XFRM, Health, User Mappings, Conflicts, Webhook Logs and Diagnostics.

### Security

Webhook requests are validated with `X-Hub-Signature-256` HMAC-SHA256. Sensitive values such as the GitHub App private key, webhook secret and XFRM API key are not stored in plaintext add-on tables; they are referenced through `config.php`.

### English documentation

- [Installation](docs/INSTALLATION.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Field mapping](docs/FIELD_MAPPING.md)
- [XFRM integration](docs/XFRM.md)
- [Health / retry / reconciliation](docs/MONITORING.md)
- [Changelog](CHANGELOG.md)

---

Warext Studios
