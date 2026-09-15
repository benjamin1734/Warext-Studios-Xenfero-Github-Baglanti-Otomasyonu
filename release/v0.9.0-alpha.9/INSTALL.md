# Kurulum / Installation — v0.9.0 Alpha 9

## Türkçe

Bu paket **Alpha / pre-release geliştirme kurulum paketidir**.

### Dosya yapısı

```text
upload/
└── src/addons/Warext/GitHubSync/
```

### Kurulum

1. `upload/` klasörünün içeriğini XenForo kök dizinine yükleyin.
2. XenForo development mode'u etkinleştirin.
3. Sunucuda şu komutu çalıştırın:

```bash
php cmd.php xf-dev:import --addon Warext/GitHubSync
```

4. Admin CP → Add-ons bölümünden **Warext GitHub Sync** eklentisini kurun veya yükseltin.
5. GitHub App, webhook secret/private key ve XFRM API key gibi gizli değerleri yalnızca `src/config.php` üzerinden tanımlayın.

> Bu sürüm production ACP archive installer değildir. Nihai paket için gerçek XenForo 2.3 runtime üzerinde `xf-addon:build-release` ile `_data + hashes.json` üretilmelidir.

---

## English

This package is an **Alpha / pre-release development installation package**.

### Layout

```text
upload/
└── src/addons/Warext/GitHubSync/
```

### Installation

1. Upload the contents of `upload/` to the XenForo root directory.
2. Enable XenForo development mode.
3. Run:

```bash
php cmd.php xf-dev:import --addon Warext/GitHubSync
```

4. Install or upgrade **Warext GitHub Sync** from Admin CP → Add-ons.
5. Define sensitive values such as the GitHub App private key, webhook secret and XFRM API key only through `src/config.php`.

> This is not yet a production ACP archive installer. The final package must be built from a real XenForo 2.3 runtime using `xf-addon:build-release` to generate `_data + hashes.json`.
