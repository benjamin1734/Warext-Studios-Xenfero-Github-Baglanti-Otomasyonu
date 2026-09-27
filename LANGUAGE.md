# Language support / Dil desteği

Warext GitHub Sync follows XenForo's native language selection.

- Import `languages/English.xml` into an English XenForo language (or child language).
- Import `languages/Turkish.xml` into a Turkish XenForo language (or child language).
- User-created repository names, issue/PR content, release notes, workflow names, GitHub API errors and stored audit data are external/user content and are not automatically translated.
- Add-on interface text, Admin CP labels, buttons, descriptions and local action/result messages use XenForo phrases.

Translation source files live under `languages/translations/tr-TR/`. The generated packs must keep exact phrase-key parity with `languages/source.en-US.json`.

Yeni kullanıcıya görünen sabit metinler XenForo phrase sistemi kullanılmadan template/PHP içine eklenmemelidir.
