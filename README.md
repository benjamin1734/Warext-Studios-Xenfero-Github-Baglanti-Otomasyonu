# Warext GitHub Sync for XenForo

## Türkçe

**Warext GitHub Sync**, XenForo 2.3+ için modüler ve çift yönlü GitHub ↔ XenForo senkronizasyon eklentisidir. GitHub ve XenForo nesneleri arasında kalıcı kimlik bağı tutar; Issue, Pull Request, Release, yorum, workflow, conflict, retry/recovery ve XFRM akışlarını yönetir.

> Güncel sürüm: **v0.9.4 Alpha 13 — pre-release / test sürümü.**

### İndir / Kurulum

- **Güncel Release:** [v0.9.4 Alpha 13](https://github.com/benjamin1734/Warext-Studios-Xenfero-Github-Baglanti-Otomasyonu/releases/tag/v0.9.4-alpha.13)
- Release asset: `Warext-GitHub-Sync-v0.9.4-Alpha13-install.zip`
- XenForo Admin CP > **Add-ons > Install/upgrade from archive** üzerinden doğrudan kurulabilir veya güncellenebilir.

### Admin CP alanı

v0.9.4 ile Warext GitHub Sync artık `Tools` altındaki tek bir link olarak değil, Admin CP sol menüsünde **bağımsız açılır bir kategori** olarak tanımlanır. Kategori altında şu sayfalar bulunur:

- Dashboard
- Connections
- Repositories
- Mappings
- Templates
- Actions
- PR Tools
- XFRM
- Health
- User Mappings
- Conflicts
- Webhook Logs
- Diagnostics

Admin route section context değeri de `wghGitHubSync` olarak bağlanmıştır; eklenti sayfalarında sol menü grubu doğru seçili kalır. Super admin olmayan yöneticilere `wghManage` admin yetkisi verilmelidir.

### Son kurulum düzeltmeleri

- v0.9.1: normal XenForo kurulumu için `_data/*.xml + hashes.json` release yapısı.
- v0.9.2: geçersiz `<xf:submit>` template etiketleri kaldırıldı.
- v0.9.3: PHP tarzı dinamik array erişimleri XenForo template sözdizimine çevrildi.
- v0.9.4: bağımsız Admin CP kategori ağacı ve route section context eklendi.

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

---

## English

**Warext GitHub Sync** is a modular, bidirectional GitHub ↔ XenForo synchronization add-on for XenForo 2.3+. It keeps durable identity mappings between GitHub and XenForo objects and manages Issues, Pull Requests, Releases, comments, workflows, conflicts, retry/recovery and XFRM flows.

> Current version: **v0.9.4 Alpha 13 — pre-release / testing build.**

### Download / Installation

- **Latest Release:** [v0.9.4 Alpha 13](https://github.com/benjamin1734/Warext-Studios-Xenfero-Github-Baglanti-Otomasyonu/releases/tag/v0.9.4-alpha.13)
- Release asset: `Warext-GitHub-Sync-v0.9.4-Alpha13-install.zip`
- Install or upgrade directly from XenForo Admin CP > **Add-ons > Install/upgrade from archive**.

### Admin CP area

Starting with v0.9.4, Warext GitHub Sync is defined as its own **collapsible Admin CP navigation group** instead of a single link nested below Tools. The group contains Dashboard, Connections, Repositories, Mappings, Templates, Actions, PR Tools, XFRM, Health, User Mappings, Conflicts, Webhook Logs and Diagnostics.

The admin route section context is also set to `wghGitHubSync` so the correct navigation group remains selected while browsing the add-on. Non-super administrators must be granted the custom `wghManage` administrator permission.

### Recent installer fixes

- v0.9.1: normal XenForo `_data/*.xml + hashes.json` archive layout.
- v0.9.2: removed invalid `<xf:submit>` tags.
- v0.9.3: converted PHP-style dynamic array access to XenForo template syntax.
- v0.9.4: added a dedicated Admin CP navigation tree and route section context.

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

---

Warext Studios
