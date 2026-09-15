# Değişiklik Günlüğü

## 0.8.0 Alpha 8
- XFRM senkronizasyon geçmişi/audit kayıtları ve işlem snapshotları eklendi.
- GitHub App API üzerinden güncel Release kaydını yeniden çekerek çalışan manuel XFRM retry eklendi.
- Resource, ResourceVersion ve Resource Update kimlikleri için remote reconcile kontrolü eklendi.
- Uzak XFRM içeriğini sessizce değiştirmeyen güvenli local tracking snapshot restore sistemi eklendi.
- Retry/attempt/success/reconcile zamanları ve remote-state takibi eklendi.
- Sender/delivery metadata gürültüsünün gereksiz işlem oluşturmaması için semantik GitHub Release hash sistemi eklendi.
- Admin CP için ayrı XFRM mapping yöneticisi, kayıt detay ve history ekranları eklendi.
- Webhook backlog/hata, çözülmemiş conflict ve XFRM failed/attention/stale metriklerini gösteren Health ekranı eklendi.
- Reconcile sırasında 404 ile auth/sunucu hatalarını ayıran tipli XFRM API exception katmanı eklendi.
- `xf_wgh_xfrm_history` tablosu ve 0.8 upgrade şeması eklendi.
- Setup tablo kurulum yapısı helper metodlara ayrılarak sadeleştirildi; eski Alpha upgrade adımları korundu.

## 0.7.0 Alpha 7
- GitHub Release → mevcut XFRM resource senkronizasyonu eklendi.
- XenForo REST API üzerinden ResourceVersion oluşturma eklendi.
- Release notlarından Resource Update oluşturma/düzenleme eklendi.
- GitHub release → XFRM version/update kalıcı kimlik takibi eklendi.
- Release asset/source ZIP/release sayfası indirme kaynağı seçimi eklendi.
- Silinen veya yayından kaldırılan release için ignore/soft/hard delete politikaları eklendi.
- Admin CP XFRM durum ekranı ve diagnostics kontrolleri eklendi.
- Gerçek API anahtarlarını DB’ye yazmadan config.php referanslarıyla çalışma eklendi.

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
- GitHub ↔ XenForo karşılaştırmalarında ortak mesaj/başlık/prefix/açık-kapalı hash modeli kullanıldı.
- GitHub label ↔ XenForo konu prefix eşlemesi iki yönde eklendi.
- GitHub Issue açık/kapalı durumu ↔ XenForo konu durumu senkronizasyonu eklendi.
- Repository/connection/global kapsamlı GitHub kullanıcı adı → XenForo kullanıcı ID eşlemeleri eklendi.
- `xf_wgh_user_mapping` ve `xf_wgh_conflict` şemaları ile Admin CP ekranları eklendi.

## 0.4.0 Alpha 4
- GitHub Issues, comments, Pull Requests, PR reviews ve review comment olayları eklendi.
- Dinamik parent yönlendirme ve kalıcı Issue/PR metadata eşlemesi eklendi.
- XenForo Thread/Post eventlerinden GitHub'a outbound queue + retry sistemi eklendi.
- XenForo konusu → GitHub Issue ve XenForo cevapları → Issue comment akışları eklendi.
- `SyncGuard` ile çift yönlü sonsuz döngü koruması eklendi.

Önceki Alpha sürümlerinin ayrıntılı İngilizce geçmişi için [CHANGELOG.md](CHANGELOG.md) dosyasına bakın.
