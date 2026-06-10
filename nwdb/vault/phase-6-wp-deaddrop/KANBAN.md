# Kanban — Фаза 6 (WP dead-drop)

## Done ✅
- [x] WP-T01 config + artisan-команда + диск deaddrop
- [x] WP-T02 Cipher (XChaCha20-Poly1305 + Ed25519, encrypt-then-sign)
- [x] WP-T03 міграції site_plugins / feed_publications + моделі
- [x] WP-T04 CipherTest (round-trip + tamper + nonce)
- [x] WP-T05 PayloadBuilder + IdentityFactory
- [x] WP-T06 FeedPublisher (Local + S3-stub) + binding
- [x] WP-T07 шаблон WP-плагіна (.stub)
- [x] WP-T08 PluginBuilder (per-site ZIP)
- [x] WP-T09 FeedManager + PublishSiteFeed job + ContactEntryObserver
- [x] WP-T10 PluginPanel (Livewire) + signed download route
- [x] WP-T11 blade-вкладка «Плагін» на Sites/Show
- [x] WP-T12 feature-тести + SitePluginFactory

## Перевірено
- [x] `phpunit --testsuite Nwdb` → 37/37 green
- [x] `nwdb:make-signing-key` друкує валідну пару
- [x] повний набір тестів: 161/167 (3 падіння — pre-existing Vite/route, не пов'язані з nwdb)

## Backlog (наступні фази)
- [ ] S3FeedPublisher (R2/MinIO) — додати aws sdk
- [ ] Самооновлення плагіна через dead-drop (зараз вручну)
- [ ] Caddy-конфіг для ETag/TTL на `.bin` (devops)
- [ ] Fleet-wide індекс плагінів (зараз тільки per-site)
- [ ] Оновити BRIEF.md/PRESERVED.md (застаріла крипто-схема)
