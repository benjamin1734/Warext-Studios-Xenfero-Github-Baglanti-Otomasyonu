# Warext GitHub Sync v0.9.3 Alpha 12

## Türkçe

Bu sürüm, `v0.9.2 Alpha 11` kurulumu sırasında görülen `Template name: admin:wgh_mappings` syntax hatasını giderir.

### Düzeltilenler
- `wgh_mappings` içindeki PHP tarzı `$repositories[$mapping.repository_id]` erişimi XenForo template sözdizimine çevrildi.
- Aynı hatanın bulunduğu `wgh_user_mappings` da aynı anda düzeltildi.
- Dinamik dizi anahtarları artık XenForo biçimi `{$array.{$key}}` ile okunuyor.
- Tüm 27 Admin template üzerinde PHP tarzı dinamik array-index kullanımı tarandı; başka aynı tip kullanım kalmadı.
- Release workflow'una bu desen yeniden eklenirse build'i durduran statik kontrol eklendi.
- `_data + hashes.json` normal XenForo archive paketleme yapısı korunuyor.

### Güncelleme
`Warext-GitHub-Sync-v0.9.3-Alpha12-install.zip` dosyasını Admin CP → Add-ons → Install/upgrade from archive üzerinden mevcut kurulumun üzerine yükleyin.

---

## English

This release fixes the `Template name: admin:wgh_mappings` syntax error encountered while installing `v0.9.2 Alpha 11`.

### Fixed
- Replaced PHP-style `$repositories[$mapping.repository_id]` lookup in `wgh_mappings` with XenForo template syntax.
- Fixed the same issue in `wgh_user_mappings` proactively.
- Dynamic keys now use XenForo's `{$array.{$key}}` form.
- All 27 Admin templates were scanned for the same PHP-style dynamic array-index pattern; no other occurrences remain.
- Added a release gate that rejects builds if this pattern is reintroduced.
- Normal XenForo `_data + hashes.json` archive packaging is retained.

### Upgrade
Install `Warext-GitHub-Sync-v0.9.3-Alpha12-install.zip` over the existing add-on through Admin CP → Add-ons → Install/upgrade from archive.
