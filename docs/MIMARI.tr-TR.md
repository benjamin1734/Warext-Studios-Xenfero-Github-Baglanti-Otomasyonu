# Mimari

Warext GitHub Sync tek bir webhook controller'a yığılmış bir sistem değildir. Yeni GitHub/XenForo modülleri bağımsız eklenebilsin diye katmanlı tasarlanmıştır.

## GitHub → XenForo

1. Public webhook controller ham GitHub isteğini alır.
2. Uygun connection secret adayları bulunur.
3. HMAC-SHA256 imzası doğrulanır.
4. Delivery GUID tekrar kontrolünden geçirilir ve payload kaydedilir.
5. XenForo job queue delivery'yi retry destekli işler.
6. Event Normalizer payload'ı ortak event modeline dönüştürür.
7. Mapping sistemi repo/event/action eşleşmelerini bulur.
8. Filter Engine branch/release/commit/path kurallarını uygular.
9. Message Factory standart XenForo işlemini ve metadata eşlemelerini oluşturur.
10. Conflict Resolver, eşlenen XenForo içeriğinin son senkronizasyondan sonra değişip değişmediğini kontrol eder.
11. XenForo'nun native servisleri konu/mesaj ekler, düzenler, siler ve prefix/açık-kapalı metadata'sını uygular.
12. Sync Registry GitHub nesnesi ↔ XenForo nesnesi kimlik bağını ve hash durumunu saklar.

## Dinamik parent yönlendirme

Issue/PR yorumları için sabit XenForo thread ID zorunlu değildir. Yorumun parent GitHub nesnesi Sync Registry üzerinden bulunur; daha önce o Issue/PR için oluşturulan XenForo konusu tespit edilip yorum doğru konuya gönderilir.

## XenForo → GitHub

1. Thread/Post entity save/delete eventleri outbound job oluşturur.
2. `SyncGuard`, GitHub tarafından uygulanmakta olan XenForo değişikliklerini geri göndermeyi engeller.
3. Outbound processor direction/repository/source forum kriterlerine göre mapping seçer.
4. Gerekirse GitHub Issue oluşturur veya mevcut eşleşmeyi bulur.
5. Konu başlığı + ilk mesaj Issue title/body olur.
6. XenForo cevapları GitHub Issue comment olur.
7. İsteğe bağlı prefix mapping GitHub Issue label setine; konu açık/kapalı durumu Issue durumuna dönüşür.
8. Ortak hash modeli hem gereksiz tekrar yazımları hem conflict tabanını yönetir.
9. GitHub API hataları kullanıcı isteğinin dışında retry edilir.

## Güvenlik ilkeleri

- Secret değerleri DB'de açık metin tutulmaz.
- Webhook HMAC doğrulaması zorunludur.
- Delivery GUID tekrar saldırısı/duplicate için kullanılır.
- Yıkıcı işlemler yalnızca entegrasyonun sahipliği doğrulanabilen içeriklerde uygulanır.
- Mevcut XenForo konu ilk mesajları yanlış remote delete'e karşı korunur.
- GitHub API yazma işlemleri queue içinde çalışır.
- Loop protection çift yönlü sonsuz senkronizasyonu engeller.

## Conflict ve metadata senkronizasyonu

Daha önce eşlenmiş bir nesne GitHub'dan tekrar geldiğinde sistem, son senkronizasyonda kaydedilen XenForo hash'i ile mevcut XenForo içeriğini karşılaştırır. Mapping politikasına göre GitHub, XenForo, en yeni taraf veya yönetici kararı kazanabilir. Manuel conflict kayıtları `xf_wgh_conflict` tablosunda tutulur ve Admin CP'den çözülebilir.

Mapping metadata katmanı GitHub label'larını tek bir XenForo konu prefix'i ile, Issue açık/kapalı durumunu `discussion_open` ile ve GitHub event gönderen hesabını kapsamlı XenForo kullanıcı eşlemeleriyle senkronize edebilir. Label/prefix eşlemeleri `label=prefix_id` biçimindedir ve bidirectional mappinglerde aynı tablo iki yönde kullanılır.

## Admin CP modülerliği

Yönetim işlemleri bağlantı/repository, mapping/template, kullanıcı/conflict ve delivery/diagnostics modüllerine ayrılmıştır. Böylece ileride yeni GitHub nesneleri veya XenForo hedefleri eklenirken tek büyük controller'a bağımlılık oluşmaz.
