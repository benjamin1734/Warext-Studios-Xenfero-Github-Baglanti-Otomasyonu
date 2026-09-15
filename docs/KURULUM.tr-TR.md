# Geliştirme kurulumu

> Bu sürüm Alpha geliştirme kaynak kodudur. Henüz production XenForo kurulum ZIP'i olarak kullanılmamalıdır.

## Gereksinimler

- XenForo 2.3+
- Hedef XenForo sürümünün desteklediği PHP sürümü
- OpenSSL
- JSON
- GitHub'ın erişebildiği HTTPS forum adresi
- Senkronize edilecek repolara kurulmuş GitHub App

## Dosyalar

Repodaki:

```text
src/addons/Warext/GitHubSync
```

klasörünü XenForo kurulumunda aynı konuma yerleştirin. XenForo development mode açıldıktan sonra `_output` geliştirme verisini normal XenForo geliştirme akışınızla import edin.

## Gizli bilgiler

Gerçek secret/private key değerlerini repoya veya eklenti tablolarına yazmayın. `src/config.php` içinde tutun:

```php
$config['warextGitHubSync'] = [
    'webhookSecret' => 'opsiyonel-bootstrap-secret',

    'webhookSecrets' => [
        'warext_app' => 'gercek-uzun-rastgele-webhook-secret'
    ],

    'privateKeys' => [
        'warext_app' => <<<'PEM'
-----BEGIN PRIVATE KEY-----
...
-----END PRIVATE KEY-----
PEM
    ],

    'pushAggregationSeconds' => 60
];
```

Admin CP içinden GitHub connection oluşturup private key reference ve webhook secret reference alanlarına `warext_app` yazın.

## GitHub App

Sadece kullandığınız modüllerin ihtiyaç duyduğu yetkileri verin. Çift yönlü Issue senkronizasyonu için Issues read/write gerekir. Pull Request webhook modülleri için uygun Pull Requests read yetkisi gerekir. Installation repository keşfi için repository metadata erişimi gerekir.

Webhook URL örneği:

```text
https://forum.example.com/github-sync-webhook
```

Kullandığınız event'leri seçin. Mevcut modüller `release`, `push`, `issues`, `issue_comment`, `pull_request`, `pull_request_review` ve `pull_request_review_comment` olaylarını anlayabilir.

## Örnek mapping

### GitHub Issue → XenForo

- Direction: `GitHub → XenForo` veya `Bidirectional`
- Event: `issues`
- Event action: `*`
- XenForo target forum node ID: hata bildirim forumunuz
- Automation user ID: bot hesabınız

### XenForo → GitHub Issue

- Direction: `XenForo → GitHub` veya `Bidirectional`
- Repository: belirli bir repo seçin
- XenForo source forum node ID: hata bildirim forumunuz
- GitHub object: `Issue`
- İlk mesaj, cevap ve durum senkronizasyonunu ihtiyacınıza göre açın

Gerçek çift yönlü kullanımda inbound target forum ile outbound source forum aynı iş akışını temsil etmelidir.

## Çift yönlü Issue senkronizasyonu için GitHub App yetkileri

Mevcut özellikler için GitHub App repository izinlerinde **Metadata: Read-only** ve **Issues: Read and write** yetkilerini verin. Yalnızca mappinglerde kullanacağınız webhook olaylarına abone olun (örneğin Releases, Push, Issues, Issue comments, Pull requests ve Pull request review olayları).

## Opsiyonel XFRM API key

GitHub Release → XFRM senkronizasyonu için Resource Manager yazma kapsamına sahip ayrı bir XenForo API key oluşturup config üzerinden referanslayın:

```php
$config['warextGitHubSync']['xfrmApiKeys'] = [
    'github_xfrm' => 'GERCEK-XENFORO-API-KEY'
];
```

Mapping yalnızca `github_xfrm` değerini tutar; gerçek API key eklenti veritabanına kaydedilmez.

## 0.9 araçları için ek GitHub App yetkileri

- Pull Request reviewer görüntüleme için **Pull requests: read**, reviewer ekleme/kaldırma için **Pull requests: write** gerekir.
- Workflow run geçmişini GitHub'dan yenilemek için **Actions: read**, yeniden çalıştırma/iptal işlemleri için **Actions: write** gerekir.
- Bu Admin CP araçlarını kullanmıyorsanız ilgili yazma yetkilerini açmayın.
