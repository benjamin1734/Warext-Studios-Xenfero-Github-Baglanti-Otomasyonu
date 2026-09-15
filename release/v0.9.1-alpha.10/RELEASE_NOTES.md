# Warext GitHub Sync v0.9.1 Alpha 10

## Türkçe

Bu sürüm, **normal XenForo kurulumunda Admin CP menülerinin görünmemesi** sorununu gideren kurulum/paketleme düzeltmesidir.

### Düzeltilenler
- Gerçek XenForo `_data/*.xml` kurulum verileri release sırasında üretilir.
- Admin CP route, navigation, permission, template, phrase ve event listener kayıtları normal kurulumda otomatik import edilir.
- Admin menü phrase adı `admin_navigation.wghGitHubSync` olarak düzeltildi.
- Admin permission phrase adı `admin_permission.wghManage` olarak düzeltildi.
- Release ZIP’inden development-only `_output` kaldırıldı.
- `hashes.json` gerçek release dosyalarından SHA-256 ile yeniden üretilir.
- Var olan v0.9.0 Alpha 9 kurulumları için daha yüksek sürüm numarasıyla upgrade paketi olarak hazırlanmıştır.

### Kurulum / Güncelleme
XenForo Admin CP > **Add-ons > Install/upgrade from archive** alanından `Warext-GitHub-Sync-v0.9.1-Alpha10-install.zip` dosyasını doğrudan yükleyin.

Kurulumdan sonra Admin CP içinde **Tools > Warext GitHub Sync** alanı görünmelidir. Super admin değilseniz yönetici hesabınıza `wghManage` yetkisinin verilmiş olması gerekir.

> Alpha sürümdür; gerçek GitHub/XFRM runtime testleri halen devam etmektedir.

---

## English

This release fixes the packaging issue where the **Warext GitHub Sync Admin CP area did not appear on a normal XenForo installation**.

### Fixed
- Real XenForo `_data/*.xml` installer data is generated during release builds.
- Admin routes, navigation, permissions, templates, phrases and event listeners are imported automatically during normal installation.
- Corrected the admin navigation phrase to `admin_navigation.wghGitHubSync`.
- Corrected the admin permission phrase to `admin_permission.wghManage`.
- Removed development-only `_output` from the release ZIP.
- `hashes.json` is rebuilt with SHA-256 from the actual release files.
- Packaged with a higher version number so existing v0.9.0 Alpha 9 installations can upgrade normally.

### Install / Upgrade
Upload `Warext-GitHub-Sync-v0.9.1-Alpha10-install.zip` directly from XenForo Admin CP > **Add-ons > Install/upgrade from archive**.

After installation, **Tools > Warext GitHub Sync** should appear in Admin CP. Non-super administrators must be granted the `wghManage` administrator permission.

> This remains an Alpha build; full GitHub/XFRM runtime testing is still in progress.
