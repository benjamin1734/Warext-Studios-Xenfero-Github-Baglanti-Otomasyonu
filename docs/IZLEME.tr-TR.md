# Health, retry ve reconcile

0.8 ile uzun süre çalışan GitHub ↔ XenForo senkronizasyonu için operasyonel yönetim katmanı eklendi.

## Health ekranı

Admin CP → GitHub Sync → Health; webhook işleme/hata sayılarını, bekleyen delivery yaşını, çözülmemiş conflictleri, failed/attention XFRM kayıtlarını ve reconcile edilmemiş eski kayıtları gösterir. Güncel webhook/XFRM hatası veya 15 dakikadan eski backlog `critical`; conflict ya da XFRM attention/stale durumları `degraded` kabul edilir.

## Manuel XFRM retry

Retry eski webhook payloadını körlemesine tekrar oynatmaz. GitHub App installation token ile GitHub'dan güncel ve yetkili Release kaydı yeniden çekilir, normal XFRM senkronizasyon motoruna gönderilir. Retry sayısı ve zamanları saklanır.

## Reconcile

Seçili Resource ile takip edilen ResourceVersion/Resource Update kimlikleri XenForo REST API üzerinden doğrulanır. 404 durumunda kayıt `attention/missing` olur; yetki veya sunucu hataları yanlışlıkla "silinmiş" sayılmaz.

## Tracking snapshot restore

History kayıtları o andaki Version/Update kimliklerini ve hash bilgisini tutar. Snapshot restore yalnızca entegrasyonun local takip kaydını değiştirir ve kaydı `attention` yapar; uzaktaki XFRM içeriğini değiştirmez veya undelete yapmaz. Restore sonrası reconcile çalıştırılmalıdır.

## Semantik Release hash

XFRM idempotency artık tüm webhook gövdesine değil, senkronizasyonu etkileyen Release alanlarına (tag, başlık, notlar, assetler ve indirme ayarları) dayanır. Böylece sender/delivery metadata değişiklikleri gereksiz XFRM işlemi oluşturmaz.
