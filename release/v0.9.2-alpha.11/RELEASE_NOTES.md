# Warext GitHub Sync v0.9.2 Alpha 11

## Türkçe

Bu sürüm, v0.9.1 Alpha 10 kurulumunda XenForo template derleyicisinin verdiği `Unknown tag submit` hatasını giderir.

### Düzeltilenler
- `admin:wgh_actions` içindeki geçersiz `<xf:submit>` kaldırıldı.
- `admin:wgh_health` içindeki geçersiz `<xf:submit>` kaldırıldı.
- `admin:wgh_xfrm` içindeki geçersiz `<xf:submit>` kaldırıldı.
- Bu üç işlem geçerli `<xf:button type="submit">...</xf:button>` yapısına çevrildi.
- Release workflow'una ham `<xf:submit>` etiketi kalırsa paketi yayınlamadan önce build'i durduran statik kontrol eklendi.
- Normal XenForo `_data + hashes.json` archive paketleme sistemi korunuyor.

### Güncelleme
`Warext-GitHub-Sync-v0.9.2-Alpha11-install.zip` dosyasını Admin CP > Add-ons > Install/upgrade from archive üzerinden mevcut eklentinin üzerine yükseltme olarak kurun.

---

## English

This release fixes the XenForo template compiler error `Unknown tag submit` encountered while installing v0.9.1 Alpha 10.

### Fixed
- Removed invalid `<xf:submit>` from `admin:wgh_actions`.
- Removed invalid `<xf:submit>` from `admin:wgh_health`.
- Removed invalid `<xf:submit>` from `admin:wgh_xfrm`.
- Replaced all three with valid `<xf:button type="submit">...</xf:button>` controls.
- Added a release gate that rejects a build if a raw `<xf:submit>` tag remains in exported templates.
- Retained the normal XenForo `_data + hashes.json` archive packaging flow.

### Upgrade
Install `Warext-GitHub-Sync-v0.9.2-Alpha11-install.zip` over the existing add-on through Admin CP > Add-ons > Install/upgrade from archive.
