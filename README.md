# Warext Studios | XenForo GitHub Connection Automation

## English

**Warext GitHub Sync** is a modular, bidirectional GitHub ↔ XenForo synchronization add-on for XenForo 2.3+. It keeps durable identity mappings between GitHub and XenForo objects and manages Issues, Pull Requests, Releases, comments, workflows, conflicts, retry/recovery and XFRM flows.

### Download / Installation

- **Download:** Use the latest package from [GitHub Releases](https://github.com/benjamin1734/Warext-Studios-Xenfero-Github-Baglanti-Otomasyonu/releases).
- Install or upgrade directly from XenForo Admin CP → Add-ons → **Install/upgrade from archive**.
- Existing `v0.9.4 Alpha 13` installations can be upgraded directly to v1.0.0 without uninstalling first.

### Admin CP area

Warext GitHub Sync appears as its own **collapsible Admin CP navigation group**. The group contains Dashboard, Connections, Repositories, Mappings, Templates, Actions, PR Tools, XFRM, Health, User Mappings, Conflicts, Webhook Logs and Diagnostics.

Non-super administrators must be granted the custom `wghManage` administrator permission.

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
- Normal XenForo `_data/*.xml + hashes.json` release package.

## Support

For questions, bug reports, installation support, and help with Warext Studios XenForo add-ons, you can join our support Discord server:

**Discord:** https://discord.gg/tgsV5XMcFS

---

## Türkçe

**Warext GitHub Sync**, XenForo 2.3+ için modüler ve çift yönlü GitHub ↔ XenForo senkronizasyon eklentisidir. GitHub ve XenForo nesneleri arasında kalıcı kimlik bağı tutar; Issue, Pull Request, Release, yorum, workflow, conflict, retry/recovery ve XFRM akışlarını yönetir.

### İndir / Kurulum

- **İndirme:** En güncel paketi [GitHub Releases](https://github.com/benjamin1734/Warext-Studios-Xenfero-Github-Baglanti-Otomasyonu/releases) sayfasından kullanın.
- XenForo Admin CP → Add-ons → **Install/upgrade from archive** üzerinden doğrudan kurulabilir veya güncellenebilir.
- `v0.9.4 Alpha 13` kullananlar eklentiyi kaldırmadan doğrudan v1.0.0'a yükseltebilir.

### Admin CP alanı

Warext GitHub Sync, Admin CP sol menüsünde **bağımsız açılır bir kategori** olarak görünür. Kategori altında şu sayfalar bulunur:

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

Super admin olmayan yöneticilere `wghManage` admin yetkisi verilmelidir.

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
- Normal XenForo `_data/*.xml + hashes.json` release paketi.

## Destek

Sorularınız, hata bildirimleriniz, kurulum desteği ve Warext Studios XenForo eklentileriyle ilgili yardım için destek Discord sunucumuza katılabilirsiniz:

**Discord:** https://discord.gg/tgsV5XMcFS
