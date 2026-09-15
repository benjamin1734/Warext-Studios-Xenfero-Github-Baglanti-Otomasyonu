# XFRM entegrasyonu

0.7 ile **XenForo Resource Manager 2.3+** için opsiyonel adapter eklendi. İlk sürüm özellikle güvenli tutuldu: sistem yeni resource'u kendiliğinden üretmek yerine **önceden var olan ResourceItem** kaydını hedefler.

## GitHub Release akışı

`release` mappinginde XFRM açıldığında:

1. XFRM'nin yüklü olduğu ve seçilen resource'un gerçekten var olduğu doğrulanır.
2. `config.php` içindeki XenForo API key referansı çözülür.
3. GitHub release tag'inden tek bir XFRM ResourceVersion oluşturulur.
4. İndirme adresi eşleşen release asset, GitHub source ZIP veya release sayfasından seçilir.
5. Release adı/gövdesinden Resource Update oluşturulur. Bu işlem XFRM'nin normal update/discussion akışını kullanır.
6. GitHub release ID ↔ ResourceVersion/ResourceUpdate ID'leri `xf_wgh_xfrm_release` tablosunda saklanır.
7. Release sonradan düzenlenirse yeni sürüm spamı oluşturmak yerine mevcut Resource Update düzenlenir.

XFRM yazma işlemleri XenForo'nun resmi REST API'si üzerinden yapılır. Böylece belgelenmemiş ResourceVersion servis metodlarına bağımlı kırılgan kod yazılmaz.

## API key yapılandırması

Yalnızca gereken Resource Manager yetkilerini (normalde `resource:write`) içeren ayrı bir XenForo API key oluşturun ve `src/config.php` içine yazın:

```php
$config['warextGitHubSync']['xfrmApiKeys'] = [
    'github_xfrm' => 'GERCEK-XENFORO-API-KEY'
];
```

Mapping yalnızca `github_xfrm` referansını saklar; gerçek key eklenti tablolarına yazılmaz.

## Mapping seçenekleri

- Mevcut XFRM resource ID.
- API key referansı ve isteğe bağlı API context user ID.
- `alongside`: XFRM + normal forum hedefi birlikte.
- `only`: release yalnızca XFRM tarafından işlenir.
- ResourceVersion oluşturma aç/kapat.
- Resource Update oluşturma/düzenleme aç/kapat.
- İndirme kaynağı: release asset, source ZIP veya release sayfası.
- Asset wildcard: ör. `Warext-*.zip`.
- Sayısal tag başındaki `v` harfini kaldırma.
- Silinen/yayından kaldırılan release: ignore, soft delete veya hard delete.

## Düzenleme güvenliği

XenForo'nun public API'sinde ResourceVersion için oluşturma/getirme/silme işlemleri belgelenmiştir. Bu nedenle 0.7, sürüm oluşturulduktan sonra GitHub tag veya external download URL değişirse mevcut ResourceVersion'ı sessizce bozmaz. Kayıt `attention` durumuna alınır; bağlı Resource Update ise release notlarına göre düzenlenmeye devam eder.

## Şimdiki sınır

0.7 otomatik yeni ResourceItem/kategori oluşturmaz. Bu özellik gerçek XFRM runtime üzerinde test edildikten sonra ayrı modül olarak eklenebilir.
