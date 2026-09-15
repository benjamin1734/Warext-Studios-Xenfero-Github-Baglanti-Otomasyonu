# Warext GitHub Sync for XenForo

**Türkçe** | [English](README.md)

**Warext GitHub Sync**, XenForo 2.3+ için modüler ve çift yönlü GitHub ↔ XenForo senkronizasyon eklentisidir. Basit bir webhook mesaj botu değildir; GitHub ve XenForo nesneleri arasında kalıcı kimlik bağı tuttuğu için sonraki düzenleme, silme ve durum değişiklikleri mevcut karşılık üzerinde uygulanabilir.

> Güncel durum: **0.8.0 Alpha 8 — geliştirme kaynak kodudur, production sürümü değildir.**

## Mevcut özellikler

GitHub → XenForo tarafında Release, push/tag değişiklik günlükleri, Issues, Issue comments, Pull Requests, PR review/review comments, GitHub Actions `workflow_run` / `workflow_job`, şablonlar, filtreler, queue/retry ve kalıcı nesne eşleme desteği bulunur.

XenForo → GitHub tarafında Issue oluşturma/düzenleme/kapatma, Issue comment, Pull Request oluşturma/düzenleme/kapatma, PR conversation comment, isteğe bağlı prefix → PR review işlemleri, Admin CP üzerinden açıkça tetiklenen `workflow_dispatch`, label ↔ prefix, kullanıcı eşleme, güvenli field mapping ve `github_wins`, `xenforo_wins`, `newest_wins`, manuel inceleme conflict politikaları desteklenir.

Opsiyonel XenForo Resource Manager entegrasyonu GitHub Release → mevcut XFRM ResourceVersion + Resource Update senkronizasyonunu XenForo REST API üzerinden yapar. v0.8 ile XFRM işlem geçmişi, GitHub'dan güncel Release'i tekrar çekerek manuel retry, remote reconcile, güvenli local tracking snapshot restore, retry/attempt/success/reconcile zamanları, remote-state takibi ve semantik Release hash sistemi eklendi.

## Operasyon ve izleme

Admin CP'de Dashboard, Connections, Repositories, Mappings, Templates, Actions, XFRM, Health, User mappings, Conflicts, Webhook logs ve Diagnostics bölümleri bulunur. Health ekranı webhook hata/backlog, çözülmemiş conflict ve XFRM failed/attention/stale durumlarını `healthy`, `degraded` veya `critical` olarak özetler.

Manuel XFRM retry eski webhook payloadını körlemesine tekrar oynatmaz; GitHub App installation bağlantısıyla güncel Release kaydını GitHub'dan yeniden çeker. Reconcile seçilen Resource ile takip edilen ResourceVersion/Resource Update kimliklerini doğrular. HTTP 404 gerçek eksik remote nesne olarak değerlendirilirken yetki ve sunucu hataları gerçek hata olarak bırakılır.

## Mimari

```text
GitHub Webhook
      │
      ▼
HMAC doğrulama → Delivery log → Queue/retry → Event Normalizer
                                           │
                                           ▼
                                 Mapping/Filtre Motoru
                                           │
                    ┌──────────────────────┴──────────────────────┐
                    ▼                                             ▼
             XenForo işlemleri                             Sync Registry
                    ▲                                             │
                    └──────────────────────┬──────────────────────┘
                                           │
                                 XenForo Entity Eventleri
                                           │
                                           ▼
                                     Outbound Queue
                                           │
                                           ▼
                                      GitHub REST API

GitHub Release → opsiyonel XFRM adapter → XenForo REST API → ResourceVersion/Update
```

## Dokümantasyon

- [Türkçe kurulum](docs/KURULUM.tr-TR.md)
- [Türkçe mimari](docs/MIMARI.tr-TR.md)
- [Alan eşleme](docs/ALAN_ESLEME.tr-TR.md)
- [XFRM entegrasyonu](docs/XFRM.tr-TR.md)
- [Health / retry / reconcile](docs/IZLEME.tr-TR.md)
- [English installation](docs/INSTALLATION.md)
- [English architecture](docs/ARCHITECTURE.md)
- [Değişiklik günlüğü](CHANGELOG.tr-TR.md)

## Güvenlik

Webhook istekleri GitHub `X-Hub-Signature-256` HMAC-SHA256 doğrulamasından ve delivery-ID tekrar kontrolünden geçer. GitHub App private key, webhook secret ve XFRM API key değerleri XenForo `config.php` üzerinden referanslanır; gerçek gizli değerler eklenti tablolarına veya repoya yazılmaz.

## Geliştirme durumu

Repo XenForo development output (`_output`) içerir. Production sürümden önce gerçek XenForo 2.3 + XFRM geliştirme kurulumunda import/template/runtime testleri ve XenForo'nun normal `_data` / `xf-addon:build-release` süreci tamamlanmalıdır.

Warext Studios
