# Alan eşleme

0.6 ile isteğe bağlı güvenli bir alan eşleme katmanı eklendi. Sistem keyfi PHP veya entity alanı yazımına izin vermez; yalnızca açıkça izin verilen senkronizasyon alanları kullanılabilir.

## GitHub → XenForo

Mapping içinde `XenForo işlem alanı=GitHub payload yolu` biçimi kullanılır.

Desteklenen hedefler:

- `title`
- `message`
- `remote_state`
- `prefix_id`

Örnek:

```text
title=workflow_run.name
message=workflow_run.display_title
remote_state=issue.state
```

## XenForo → GitHub

`GitHub alanı=XenForo alan yolu` biçimi kullanılır.

Desteklenen GitHub alanları:

- `title`
- `body`
- `state`
- `head`
- `base`
- `draft`
- `maintainer_can_modify`

Örnek:

```text
title=thread.title
body=post.message
head=thread.custom_fields.github_branch
base=thread.custom_fields.github_base
```

Böylece bir XenForo custom field içinde tutulan branch adı PR'ın `head` veya `base` değeri olarak kullanılabilir. Mapping içindeki sabit head/base değerleri fallback olarak kalır.

## Güvenlik

Alan eşleme allow-list mantığında çalışır. Bilinmeyen hedefler yok sayılır; PHP kodu veya keyfi ifade çalıştırılmaz.
