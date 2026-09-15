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

## Otomatik health alert kayıtları

0.9 ile health alert kayıtları `xf_wgh_health_alert` tablosunda kalıcı tutulur. Inbound ve outbound senkronizasyon jobları health durumunu otomatik değerlendirir. Yeni bir kritik durum ilk oluştuğunda XenForo error loguna da tek seferlik kayıt düşülür. Sistem yeniden healthy olduğunda açık alertler otomatik resolved olur; istenirse Admin CP'den manuel de kapatılabilir.

Eşikler `src/config.php` üzerinden ayarlanabilir:

```php
$config['warextGitHubSync']['healthThresholds'] = [
    'failedPerHourCritical' => 1,
    'pendingAgeCritical' => 900,
    'conflictsDegraded' => 1,
    'xfrmAttentionDegraded' => 1
];
```

## Workflow run geçmişi

`workflow_run` webhookları, bu eventi kullanan forum mappingi olmasa bile yerel geçmişe kaydedilir. Admin CP → GitHub Sync → Actions üzerinden GitHub'daki son 50 run ayrıca içe aktarılabilir. Run yeniden çalıştırma, yalnızca başarısız jobları yeniden çalıştırma ve iptal işlemleri açık yönetici eylemidir.

## Pull Request reviewer request yönetimi

Admin CP → GitHub Sync → PR tools üzerinden Pull Request ve mevcut kullanıcı/team reviewer requestleri yüklenebilir. GitHub login veya team slug verilerek reviewer request eklenebilir veya kaldırılabilir. Bu işlemler GitHub App için Pull requests write yetkisi gerektirir.

## Toplu XFRM recovery

En fazla 50 XFRM takip kaydı aynı işlemde retry veya reconcile edilebilir. Bir kaydın hatası diğer seçili kayıtların işlenmesini durdurmaz.
