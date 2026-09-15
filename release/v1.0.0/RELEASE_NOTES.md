# Warext GitHub Sync v1.0.0

## Türkçe

**Warext GitHub Sync v1.0.0**, XenForo 2.3+ için ilk kararlı sürümdür.

Bu sürüm; GitHub ↔ XenForo çift yönlü senkronizasyon motorunu, Issue / Pull Request / Release / yorum / workflow akışlarını, conflict çözümünü, retry-recovery araçlarını, XFRM entegrasyonunu, health monitoring sistemini ve bağımsız Admin CP yönetim alanını tek kararlı paket altında sunar.

### Öne çıkanlar
- GitHub Release / Push / Tag / Issue / Comment / Pull Request / Review / Actions → XenForo.
- XenForo Thread/Post → GitHub Issue / PR / Comment.
- GitHub label ↔ XenForo prefix ve açık/kapalı durum senkronizasyonu.
- GitHub kullanıcı ↔ XenForo kullanıcı eşleme.
- `github_wins`, `xenforo_wins`, `newest_wins` ve manuel conflict çözümü.
- GitHub Actions workflow geçmişi, dispatch, rerun, failed-rerun ve cancel.
- PR reviewer / team reviewer yönetimi.
- GitHub Release → XFRM ResourceVersion + Resource Update.
- XFRM history, retry/reconcile, bulk recovery ve tracking restore.
- Kalıcı health alert geçmişi ve diagnostics.
- GitHub App JWT / installation token ve HMAC webhook doğrulaması.
- Admin CP'de ayrı açılır **Warext GitHub Sync** kategorisi ve 13 alt yönetim sayfası.
- Normal XenForo `_data/*.xml + hashes.json` kurulum paketi.

### Kurulum / Güncelleme
`Warext-GitHub-Sync-v1.0.0-install.zip` dosyasını XenForo Admin CP → Add-ons → **Install/upgrade from archive** üzerinden yükleyin.

Mevcut `v0.9.4 Alpha 13` kurulumu doğrudan `v1.0.0` üzerine yükseltilebilir; kaldırıp yeniden kurmanız gerekmez.

---

## English

**Warext GitHub Sync v1.0.0** is the first stable release for XenForo 2.3+.

This release delivers the complete bidirectional GitHub ↔ XenForo synchronization engine, Issue / Pull Request / Release / comment / workflow flows, conflict handling, retry and recovery tools, XFRM integration, health monitoring and the dedicated Admin CP management area in one stable package.

### Highlights
- GitHub Release / Push / Tag / Issue / Comment / Pull Request / Review / Actions → XenForo.
- XenForo Thread/Post → GitHub Issue / PR / Comment.
- GitHub label ↔ XenForo prefix and open/closed state synchronization.
- GitHub user ↔ XenForo user mapping.
- GitHub-wins, XenForo-wins, newest-wins and manual conflict resolution.
- GitHub Actions workflow history, dispatch, rerun, failed-rerun and cancel.
- Pull Request reviewer / team reviewer management.
- GitHub Release → XFRM ResourceVersion + Resource Update.
- XFRM history, retry/reconcile, bulk recovery and tracking restore.
- Persistent health alert history and diagnostics.
- GitHub App JWT / installation-token flow and HMAC webhook validation.
- Dedicated collapsible **Warext GitHub Sync** Admin CP category with 13 child management pages.
- Normal XenForo `_data/*.xml + hashes.json` installation archive.

### Install / Upgrade
Install `Warext-GitHub-Sync-v1.0.0-install.zip` through XenForo Admin CP → Add-ons → **Install/upgrade from archive**.

Existing `v0.9.4 Alpha 13` installations can be upgraded directly to `v1.0.0`; uninstalling first is not required.
