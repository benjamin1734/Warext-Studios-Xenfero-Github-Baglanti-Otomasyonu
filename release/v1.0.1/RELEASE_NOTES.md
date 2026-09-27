# Warext GitHub Sync v1.0.1

## Türkçe

Bu sürüm, GitHub Sync eklentisine tam **Türkçe / İngilizce XenForo phrase desteği** ekler.

### Değişiklikler
- Admin CP arayüzündeki sabit metinler XenForo phrase sistemine taşındı.
- Controller işlem, hata ve başarı mesajları phrase sistemine taşındı.
- 402 phrase için birebir İngilizce ve Türkçe dil kapsamı eklendi.
- `languages/English.xml` ve `languages/Turkish.xml` dil paketleri eklendi.
- Dil paketlerinin phrase anahtar eşitliğini doğrulayan otomatik kontrol eklendi.
- Release oluşturucudaki kritik phrase paketleme hatası düzeltildi: artık yalnız noktalı phrase adları değil, tüm eklenti phrase'leri `_data/phrases.xml` içine aktarılıyor.
- Kurulum ZIP'i artık dışarıdan içe aktarılabilir TR/EN dil paketlerini de içeriyor.

Kullanıcı tarafından oluşturulan içerikler, GitHub Issue/PR metinleri, release notları, workflow adları ve harici GitHub API hata metinleri otomatik çevrilmez.

---

## English

This release adds complete **Turkish / English XenForo phrase support** to GitHub Sync.

### Changes
- Hard-coded Admin CP interface text was moved to XenForo phrases.
- Controller action, error and success messages were moved to phrases.
- Exact English/Turkish coverage was added for all 402 add-on phrases.
- `languages/English.xml` and `languages/Turkish.xml` were added.
- Automated phrase-key parity validation was added.
- Fixed a critical release-builder issue where only dotted phrase names were exported. All add-on phrases are now included in `_data/phrases.xml`.
- The installation ZIP now includes importable TR/EN language packs.

User-created content, GitHub Issue/PR text, release notes, workflow names and external GitHub API error text are not automatically translated.
