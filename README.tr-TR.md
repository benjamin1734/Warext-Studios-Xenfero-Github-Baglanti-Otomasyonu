# Warext GitHub Sync for XenForo

**Türkçe** | [English](README.md)

**Warext GitHub Sync**, XenForo 2.3+ için modüler ve çift yönlü bir GitHub ↔ XenForo senkronizasyon eklentisidir. Sistem yalnızca “webhook geldi, foruma mesaj at” mantığında değildir; GitHub ve XenForo nesneleri arasında kalıcı kimlik bağı tutar.

> Güncel durum: **0.9.0 Alpha 9 — geliştirme kaynak kodudur, production sürümü değildir.**

## Şu anda neler yapıyor?

GitHub → XenForo:

- Release oluşturma/düzenleme/silme olaylarını eşlenen konu veya mesaja uygular.
- Push ve tag olaylarını filtreleyip/gruplayıp değişiklik günlüğüne dönüştürür.
- GitHub Issue'larını XenForo konularına oluşturabilir ve sonradan aynı konuyu güncelleyebilir.
- Issue yorumlarını doğru parent konuya otomatik bağlayıp cevap olarak oluşturabilir, düzenleyebilir veya silebilir.
- Pull Request, PR review ve PR review comment olaylarını normalize edip XenForo'ya yönlendirebilir.
- GitHub Actions `workflow_run` ve `workflow_job` olaylarını durum/sonuç filtreleriyle işleyebilir.
- İsteğe bağlı GitHub Release → mevcut XFRM ResourceVersion + Resource Update senkronizasyonu yapabilir.
- Kalıcı nesne eşlemesi, tekrar eden webhook koruması, log ve retry sistemi içerir.

XenForo → GitHub:

- XenForo konusu + ilk mesaj → GitHub Issue oluşturma/güncelleme.
- Konu cevapları → GitHub Issue comment oluşturma/güncelleme.
- XenForo cevabı silinirse eşlenen GitHub yorumu silme.
- XenForo konusu silinirse eşlenen GitHub Issue'yu kapatma.
- İstenirse konu açık/kapalı durumunu GitHub Issue durumuna aktarma.
- GitHub erişim problemi olduğunda forum gönderimini bekletmemek için queue + retry kullanma.
- GitHub → XenForo → GitHub sonsuz döngüsünü önleyen senkronizasyon kilidi.
- Mapping bazlı GitHub label ↔ XenForo konu prefix eşlemesi.
- Repository/connection/global kapsamlı GitHub hesabı → XenForo kullanıcı eşleme sistemi.
- Issue tabanlı konularda iki yönlü açık/kapalı durum senkronizasyonu.
- GitHub kazanır, XenForo kazanır, en yeni kazanır ve manuel inceleme conflict stratejileri.
- Manuel GitHub/XenForo çözüm seçeneklerine sahip Admin CP conflict kuyruğu.
- XenForo konusu → GitHub Pull Request oluşturma/güncelleme/kapatma ve cevap → PR conversation comment senkronizasyonu.
- İsteğe bağlı XenForo prefix → PR review (`APPROVE`, `REQUEST_CHANGES`, `COMMENT`) otomasyonu.
- Admin CP üzerinden açıkça tetiklenen `workflow_dispatch` Actions ekranı.
- Seçili alanlar ve branch değerleri için güvenli inbound/outbound field mapping sistemi.
- XFRM sürüm/update kimlik takibi, idempotent oluşturma, düzenleme senkronizasyonu, indirme kaynağı ve silme politikası.
- Kalıcı GitHub Actions workflow-run geçmişi; manuel yenileme, yeniden çalıştırma, yalnızca başarısız jobları yeniden çalıştırma ve iptal kontrolleri.
- Admin CP üzerinden Pull Request kullanıcı/team reviewer request yönetimi.
- Inbound/outbound senkronizasyon aktivitesiyle otomatik güncellenen, ayarlanabilir eşikli health alert kayıtları.
- Seçilen en fazla 50 XFRM kaydı için toplu retry/reconcile.
- XFRM history, manuel retry, remote reconcile, güvenli tracking restore ve health monitoring.

## Mimari

```text
GitHub Webhook
      │
      ▼
HMAC doğrulama → Delivery log → Queue / Retry → Event Normalizer
                                            │
                                            ▼
                                  Mapping / Filtre Motoru
                                            │
                    ┌───────────────────────┴──────────────────────┐
                    ▼                                              ▼
             XenForo işlemleri                              Sync Registry
       konu/mesaj ekle-düzenle-sil                    GitHub ↔ XenForo kimlik bağı
                    ▲                                              │
                    └───────────────────────┬──────────────────────┘
                                            │
                                  XenForo Entity Eventleri
                                            │
                                            ▼
                                      Outbound Queue
                                            │
                                            ▼
                                      GitHub REST API
```

GitHub'daki aynı Issue veya Release sonradan değişirse yeni bir forum mesajı açmak zorunda değildir; registry üzerinden daha önce oluşturduğu XenForo içeriğini bulup düzenler.

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

GitHub webhook istekleri `X-Hub-Signature-256` HMAC-SHA256 doğrulamasından geçer. GitHub App private key ve webhook secret değerleri eklenti tablolarında açık metin olarak tutulmaz; XenForo `config.php` üzerinden referans edilir.

Gerçek private key veya webhook secret değerini repoya kesinlikle yüklemeyin.

## Geliştirme durumu

Repo `_output` geliştirme verisini içerir. Nihai production ZIP'i üretmeden önce gerçek XenForo 2.3 geliştirme ortamında import/template/runtime testleri yapılmalı ve XenForo'nun normal add-on build süreciyle paket oluşturulmalıdır.

Warext Studios
