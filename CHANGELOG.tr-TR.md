# Değişiklik Günlüğü

## 0.9.0 Alpha 9
- Webhooklardan yakalanan ve istenirse GitHub'dan yenilenen kalıcı `workflow_run` geçmişi eklendi.
- GitHub Actions run kontrolleri eklendi: yeniden çalıştırma, yalnızca başarısız jobları yeniden çalıştırma ve iptal.
- Admin CP üzerinden kullanıcı loginleri ve organizasyon team slugları için Pull Request reviewer request yönetimi eklendi.
- İşlem başına en fazla 50 seçili kayıt için toplu XFRM retry/reconcile eklendi.
- Inbound/outbound job aktivitesiyle otomatik değerlendirilen kalıcı health alert geçmişi eklendi.
- `warextGitHubSync.healthThresholds` altında ayarlanabilir health eşikleri eklendi.
- Workflow run ve health alert tabloları ile 0.9 upgrade adımları eklendi.
- GitHub REST user-agent 0.9 geliştirme hattına yükseltildi.

## 0.8.0 Alpha 8
- XFRM senkronizasyon geçmişi/audit kayıtları ve işlem snapshotları eklendi.
- GitHub App API üzerinden gerçek Release kaydını tekrar çekerek çalışan manuel XFRM retry eklendi.
- Resource, ResourceVersion ve Resource Update kimlikleri için remote reconcile kontrolü eklendi.
- Uzak XFRM içeriğini sessizce geri almayan güvenli local tracking snapshot restore sistemi eklendi.
- Retry/attempt/success/reconcile zamanları ve remote state takibi eklendi.
- Gereksiz payload/sender değişikliklerinin XFRM işlemi üretmemesi için semantik Release hash sistemi eklendi.
- Admin CP için ayrı XFRM mapping yöneticisi eklendi.
- Webhook backlog/hata, conflict ve XFRM attention metriklerini gösteren Health ekranı eklendi.
- Reconcile sırasında 404 ile auth/sunucu hatalarını ayıran XFRM API exception katmanı eklendi.
- XFRM history tablosu ve 0.8 upgrade şeması eklendi.

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
