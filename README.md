# Warext GitHub Sync for XenForo

## Türkçe

**Warext GitHub Sync**, XenForo 2.3+ için modüler ve çift yönlü GitHub ↔ XenForo senkronizasyon eklentisidir. GitHub ve XenForo nesneleri arasında kalıcı kimlik bağı tutar; Issue, Pull Request, Release, yorum, workflow, conflict, retry/recovery ve XFRM akışlarını yönetir.

> Güncel sürüm: **v0.9.3 Alpha 12 — pre-release / test sürümü.**

### İndir / Kurulum

- **Güncel Release:** [v0.9.3 Alpha 12](https://github.com/benjamin1734/Warext-Studios-Xenfero-Github-Baglanti-Otomasyonu/releases/tag/v0.9.3-alpha.12)
- Release asset: `Warext-GitHub-Sync-v0.9.3-Alpha12-install.zip`
- XenForo Admin CP > **Add-ons > Install/upgrade from archive** üzerinden doğrudan kurulabilir veya güncellenebilir.
- Kurulumdan sonra Admin CP > **Tools > Warext GitHub Sync** alanı görünmelidir.

### Son kurulum düzeltmeleri

- v0.9.1 ile normal XenForo kurulumunda gerekli `_data/*.xml + hashes.json` yapısı eklendi.
- v0.9.2 ile geçersiz `<xf:submit>` template etiketleri temizlendi.
- v0.9.3 ile `wgh_mappings` ve `wgh_user_mappings` içindeki PHP tarzı dinamik array erişimleri XenForo template sözdizimine çevrildi.
- Release workflow'u artık bu iki hata sınıfını statik olarak kontrol eder ve hatalı paketi yayınlamaz.

### Özellikler

- GitHub Release / Push / Tag / Issue / Comment / Pull Request / Review / Actions → XenForo.
- XenForo Thread/Post → GitHub Issue / PR / Comment.
- GitHub label ↔ XenForo prefix ve açık/kapalı durum senkronizasyonu.
- GitHub kullanıcı ↔ XenForo kullanıcı eşleme.
- `github_wins`, `xenforo_wins`, `newest_wins` ve manuel conflict çözümü.
- PR reviewer / team reviewer yönetimi.
- Workflow dispatch, geçmiş, rerun, failed-rerun ve cancel.
- GitHub Release → XFRM ResourceVersion + Resource Update.
- XFRM history, manuel/toplu retry-reconcile ve tracking restore.
- Health monitor ve kalıcı alert geçmişi.
- GitHub App JWT / installation token ve HMAC webhook doğrulaması.

### Yönetim paneli

Admin CP altında Warext GitHub Sync; Dashboard, Connections, Repositories, Mappings, Templates, Actions, PR Tools, XFRM, Health, User Mappings, Conflicts, Webhook Logs ve Diagnostics modüllerini içerir.

Super admin olmayan yöneticilere özel `wghManage` admin yetkisi verilmelidir.

---

## English

**Warext GitHub Sync** is a modular, bidirectional GitHub ↔ XenForo synchronization add-on for XenForo 2.3+. It keeps durable identity mappings between GitHub and XenForo objects and manages Issues, Pull Requests, Releases, comments, workflows, conflicts, retry/recovery and XFRM flows.

> Current version: **v0.9.3 Alpha 12 — pre-release / testing build.**

### Download / Installation

- **Latest Release:** [v0.9.3 Alpha 12](https://github.com/benjamin1734/Warext-Studios-Xenfero-Github-Baglanti-Otomasyonu/releases/tag/v0.9.3-alpha.12)
- Release asset: `Warext-GitHub-Sync-v0.9.3-Alpha12-install.zip`
- Install or upgrade directly from XenForo Admin CP > **Add-ons > Install/upgrade from archive**.
- After installation, **Tools > Warext GitHub Sync** should appear in Admin CP.

### Recent installer fixes

- v0.9.1 added the normal XenForo `_data/*.xml + hashes.json` release structure.
- v0.9.2 removed invalid `<xf:submit>` template tags.
- v0.9.3 converts PHP-style dynamic array access in `wgh_mappings` and `wgh_user_mappings` to XenForo template syntax.
- The release workflow now statically rejects both error classes before publishing an archive.

### Features

- GitHub Release / Push / Tag / Issue / Comment / Pull Request / Review / Actions → XenForo.
- XenForo Thread/Post → GitHub Issue / PR / Comment.
- GitHub label ↔ XenForo prefix and open/closed state synchronization.
- GitHub user ↔ XenForo user mapping.
- GitHub-wins, XenForo-wins, newest-wins and manual conflict resolution.
- Pull Request reviewer / team reviewer management.
- Workflow dispatch, history, rerun, failed-rerun and cancel controls.
- GitHub Release → XFRM ResourceVersion + Resource Update.
- XFRM history, manual/bulk retry-reconcile and tracking restore.
- Health monitor and persistent alert history.
- GitHub App JWT / installation-token flow and HMAC webhook validation.

### Admin CP

Warext GitHub Sync provides Dashboard, Connections, Repositories, Mappings, Templates, Actions, PR Tools, XFRM, Health, User Mappings, Conflicts, Webhook Logs and Diagnostics.

Non-super administrators must be granted the custom `wghManage` administrator permission.

---

Warext Studios
