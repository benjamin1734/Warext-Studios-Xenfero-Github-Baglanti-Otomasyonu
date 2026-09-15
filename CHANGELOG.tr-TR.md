# Değişiklik Günlüğü

## 0.4.0 Alpha 4
- GitHub `issues`, `issue_comment`, `pull_request`, `pull_request_review` ve `pull_request_review_comment` olayları eklendi.
- GitHub yorumlarının parent Issue/PR üzerinden doğru XenForo konusunu otomatik bulduğu dinamik yönlendirme eklendi.
- Issue/PR numarası metadata'sı kalıcı sync kayıtlarına eklendi.
- XenForo Thread/Post save/delete event listenerları eklendi.
- XenForo kullanıcı işlemlerini GitHub ağı yüzünden bekletmemek için outbound queue + retry sistemi eklendi.
- XenForo konusu → GitHub Issue oluşturma/güncelleme/kapatma eklendi.
- XenForo cevapları → GitHub Issue comment oluşturma/güncelleme/silme eklendi.
- GitHub → XenForo → GitHub sonsuz döngüsünü engelleyen `SyncGuard` eklendi.
- Mapping ekranına source forum, ilk mesaj, cevap ve durum senkronizasyon seçenekleri eklendi.
- Reverse XenForo lookup ve repository parent lookup özellikleri Sync Registry'ye eklendi.

Önceki Alpha sürümlerinin ayrıntılı İngilizce geçmişi için [CHANGELOG.md](CHANGELOG.md) dosyasına bakın.
