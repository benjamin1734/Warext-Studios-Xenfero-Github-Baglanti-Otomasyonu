# Warext GitHub Sync v0.9.0 Alpha 9

## Türkçe

Bu sürüm **Alpha / pre-release** geliştirme sürümüdür. GitHub ↔ XenForo çift yönlü senkronizasyon çekirdeği, Issues, Pull Requests, Actions, XFRM, conflict/retry/recovery ve health monitoring modülleri bu sürümde bir araya gelmiştir.

### Öne çıkanlar

- GitHub Release / Push / Tag / Issue / Comment / Pull Request / Review / Actions → XenForo senkronizasyonu.
- XenForo Thread/Post → GitHub Issue / PR / Comment senkronizasyonu.
- GitHub label ↔ XenForo prefix ve durum senkronizasyonu.
- Kullanıcı eşleme ve çift yönlü conflict çözüm motoru.
- PR reviewer / team reviewer yönetimi.
- Workflow geçmişi, dispatch, rerun, failed-rerun ve cancel kontrolleri.
- GitHub Release → XFRM ResourceVersion + Resource Update senkronizasyonu.
- XFRM history, manuel ve toplu retry/reconcile, tracking restore.
- Health monitor ve kalıcı alert geçmişi.
- GitHub App JWT / installation token altyapısı ve HMAC webhook doğrulaması.

### Kurulum paketi

Release asset olarak `Warext-GitHub-Sync-v0.9.0-Alpha9-install.zip` sağlanır.

**Önemli:** Bu Alpha paket geliştirme kurulumu içindir ve `_output` verisi içerir. XenForo development mode üzerinde `xf-dev:import` ile kullanılabilir. Gerçek production ACP archive installer için XenForo runtime üzerinde `xf-addon:build-release` çalıştırılarak `_data + hashes.json` üretilmesi gerekir.

### Kurulum özeti

1. ZIP içindeki `upload/` klasörü içeriğini XenForo kök dizinine yükleyin.
2. XenForo development mode'u açın.
3. `php cmd.php xf-dev:import --addon Warext/GitHubSync` çalıştırın.
4. Admin CP > Add-ons üzerinden Warext GitHub Sync eklentisini kurun/yükseltin.

---

## English

This is an **Alpha / pre-release** development build. It combines the bidirectional GitHub ↔ XenForo synchronization core with Issues, Pull Requests, Actions, XFRM, conflict/retry/recovery and health-monitoring modules.

### Highlights

- GitHub Release / Push / Tag / Issue / Comment / Pull Request / Review / Actions → XenForo synchronization.
- XenForo Thread/Post → GitHub Issue / PR / Comment synchronization.
- GitHub label ↔ XenForo prefix and state synchronization.
- User mappings and bidirectional conflict-resolution engine.
- Pull Request reviewer / team reviewer management.
- Workflow history, dispatch, rerun, failed-rerun and cancel controls.
- GitHub Release → XFRM ResourceVersion + Resource Update synchronization.
- XFRM history, manual/bulk retry-reconcile and tracking restore.
- Health monitor and persistent alert history.
- GitHub App JWT / installation-token flow and HMAC webhook validation.

### Installation package

The release provides `Warext-GitHub-Sync-v0.9.0-Alpha9-install.zip` as an asset.

**Important:** This Alpha package is intended for a development installation and includes `_output` data. It can be used with `xf-dev:import` while XenForo development mode is enabled. A true production ACP archive installer requires running `xf-addon:build-release` in a XenForo runtime to generate `_data + hashes.json`.

### Installation summary

1. Upload the contents of the ZIP's `upload/` directory to the XenForo root.
2. Enable XenForo development mode.
3. Run `php cmd.php xf-dev:import --addon Warext/GitHubSync`.
4. Install/upgrade Warext GitHub Sync from Admin CP > Add-ons.
