# Değişiklik Günlüğü

## 0.5.0 Alpha 5
- Kalıcı conflict kayıtları ve Admin CP conflict inceleme/çözüm akışı eklendi.
- `github_wins`, `xenforo_wins`, `newest_wins` ve `manual` stratejileri gerçek inbound işlem motoruna bağlandı.
- GitHub ↔ XenForo karşılaştırmalarında aynı mesaj/başlık/prefix/açık-kapalı hash modeli kullanılacak şekilde hash sistemi birleştirildi.
- GitHub label ↔ XenForo konu prefix eşlemesi iki yönde eklendi.
- GitHub Issue açık/kapalı durumu → XenForo konu açık/kapalı durumu senkronizasyonu eklendi.
- XenForo konu prefix → GitHub Issue label senkronizasyonu eklendi.
- Repository/connection/global kapsamlı GitHub kullanıcı adı → XenForo kullanıcı ID eşlemeleri eklendi.
- `xf_wgh_user_mapping` ve `xf_wgh_conflict` kurulum/yükseltme/kaldırma şemaları eklendi.
- Kullanıcı eşlemeleri ve conflict kuyruğu için Admin CP ekranları eklendi.
- Admin CP; bağlantı/repo, mapping/template, conflict/kullanıcı eşleme ve delivery/diagnostics modüllerine ayrıldı.
- Repo tarafında eksik kalan branch/commit/path FilterMatcher yeniden eklendi ve diagnostics tüm güncel tabloları kontrol edecek şekilde genişletildi.
- Mapping kaynak forum alanındaki `xf_node_id` kayıt uyuşmazlığı düzeltildi.

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
