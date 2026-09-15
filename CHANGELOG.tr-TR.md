# Değişiklik Günlüğü

## 0.6.0 Alpha 6
- XenForo konusu → GitHub Pull Request oluşturma/güncelleme/kapatma desteği eklendi.
- XenForo cevapları → Pull Request conversation comment senkronizasyonu eklendi.
- Tekrarlı review engellemesiyle prefix → `APPROVE`, `REQUEST_CHANGES`, `COMMENT` PR review otomasyonu eklendi.
- GitHub `workflow_run` ve `workflow_job` webhook modülleri eklendi.
- Workflow branch/durum/sonuç filtreleri eklendi.
- Admin CP Actions ekranı ve açıkça tetiklenen `workflow_dispatch` desteği eklendi.
- Seçili senkronizasyon alanları için güvenli inbound/outbound field mapping katmanı eklendi.

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
