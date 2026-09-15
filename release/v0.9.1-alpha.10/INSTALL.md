# Kurulum / Installation — v0.9.1 Alpha 10

## Türkçe

Bu sürüm **normal XenForo archive installer yapısına** göre paketlenir. Development mode veya `xf-dev:import` gerektirmez.

### Kurulum / güncelleme
1. GitHub Releases bölümünden `Warext-GitHub-Sync-v0.9.1-Alpha10-install.zip` dosyasını indirin.
2. XenForo Admin CP > **Add-ons > Install/upgrade from archive** bölümünü açın.
3. ZIP dosyasını doğrudan yükleyin.
4. Mevcut v0.9.0 Alpha 9 kuruluysa aynı işlem upgrade olarak uygulanır.
5. Kurulum tamamlanınca Admin CP > **Tools > Warext GitHub Sync** alanını açın.

ZIP içinde XenForo’nun normal add-on yükleyicisinin kullandığı `_data/*.xml` ve `hashes.json` bulunur. `_output` release arşivine dahil edilmez.

Super admin olmayan yönetici hesaplarında özel `wghManage` admin yetkisi verilmelidir.

---

## English

This build uses the **normal XenForo archive-installer layout**. Development mode and `xf-dev:import` are not required.

### Install / upgrade
1. Download `Warext-GitHub-Sync-v0.9.1-Alpha10-install.zip` from GitHub Releases.
2. Open XenForo Admin CP > **Add-ons > Install/upgrade from archive**.
3. Upload the ZIP directly.
4. If v0.9.0 Alpha 9 is already installed, XenForo will process this as an upgrade.
5. After installation, open Admin CP > **Tools > Warext GitHub Sync**.

The ZIP contains `_data/*.xml` and `hashes.json` used by XenForo’s normal add-on installer. Development-only `_output` is excluded from the release archive.

Non-super administrators must be granted the custom `wghManage` administrator permission.
