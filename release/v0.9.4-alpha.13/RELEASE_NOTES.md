# Warext GitHub Sync v0.9.4 Alpha 13

## Türkçe

Bu sürüm, eklentinin Admin CP sol menüsünde ekran görüntüsündeki diğer sistemler gibi ayrı ve açılır bir kategori olarak görünmemesi sorununu giderir.

### Düzeltilenler
- `Warext GitHub Sync` artık `Tools` altındaki tek link değildir.
- Ayrı bir üst seviye Admin CP navigasyon kategorisi oluşturuldu.
- Altına 13 yönetim bağlantısı eklendi: Dashboard, Connections, Repositories, Mappings, Templates, Actions, PR Tools, XFRM, Health, User Mappings, Conflicts, Webhook Logs, Diagnostics.
- Admin route `section context` değeri `wghGitHubSync` olarak ayarlandı; eklenti sayfalarında doğru sol menü bölümü seçili kalır.
- Her alt menü `wghManage` admin yetkisiyle korunur.
- Release doğrulamasına navigation tree ve route context kontrolleri eklendi.
- Önceki `_data + hashes.json`, template syntax ve hash doğrulama kontrolleri korunur.

### Güncelleme
`Warext-GitHub-Sync-v0.9.4-Alpha13-install.zip` dosyasını Admin CP → Add-ons → Install/upgrade from archive üzerinden mevcut kurulumun üzerine yükleyin.

---

## English

This release fixes the missing dedicated Admin CP sidebar group. Warext GitHub Sync now appears as its own collapsible navigation category, similar to other installed systems.

### Fixed
- Warext GitHub Sync is no longer a single item nested under Tools.
- Added a dedicated top-level Admin CP navigation group.
- Added 13 child navigation entries: Dashboard, Connections, Repositories, Mappings, Templates, Actions, PR Tools, XFRM, Health, User Mappings, Conflicts, Webhook Logs and Diagnostics.
- Set the admin route section context to `wghGitHubSync` so the correct sidebar group remains selected.
- All child entries remain protected by the `wghManage` administrator permission.
- Added release validation for the navigation tree and route context.
- Existing `_data + hashes.json`, template syntax and hash verification gates remain enabled.

### Upgrade
Install `Warext-GitHub-Sync-v0.9.4-Alpha13-install.zip` over the existing add-on through Admin CP → Add-ons → Install/upgrade from archive.
