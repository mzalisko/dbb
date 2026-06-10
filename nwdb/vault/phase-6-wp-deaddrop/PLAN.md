# Фаза 6 — WP dead-drop (модуль `nwdb`)

> Доставка контактів сайтів на клієнтські WordPress через зашифрований dead-drop.
> Увесь код фази ізольований у папці `nwdb/` (namespace `Nwdb\`), щоб не змішувати з ядром CRM.

## Архітектура

```
CRM (Laravel, за Tailscale)                      Нейтральний статик-домен (Caddy/VPS)
┌──────────────────────────┐   rsync/mount      ┌───────────────────────────┐
│ ContactEntry (контакти)  │   ───────────────▶ │ /assets/<token>.bin        │
│ PayloadBuilder → JSON    │                    │ (opaque зашифр. конверт)   │
│ Cipher.seal (XChaCha20   │                    └───────────────────────────┘
│   + Ed25519 підпис)      │                                 │ HTTPS GET (ETag)
│ FeedManager / Publisher  │                                 ▼
│ PluginBuilder → per-site │                    ┌───────────────────────────┐
│   унікальний ZIP         │   ручна установка  │ WP-плагін (унікальний білд)│
└──────────────────────────┘   ───────────────▶ │ verify → decrypt → кеш     │
                                                 │ шорткод/віджет + гео-фільтр│
                                                 └───────────────────────────┘
```

## Крипто-конверт (encrypt-then-sign)

`WPF1`(4) · version(1) · flags(1) · nonce(24) · ct_len uint32(4) · ciphertext(AEAD, AD=header) · Ed25519 signature(64)

Плагін **спочатку верифікує підпис** над усім blob-ом, потім AEAD-дешифрує. Один центральний підписний ключ (публічний вбудований у кожен білд), per-site симетричний ключ.

## Анти-фінгерпринтинг

`IdentityFactory` дає кожному сайту унікальні slug / display_name / prefix / feed_token / cron_hook з різних пулів. Два білди не діляться жодною сигнатурою (перевірено тестом `test_two_builds_share_no_identity`).

## Що зроблено (12 тасок, 1 хвиля в цій сесії)

| id | компонент | файли |
|---|---|---|
| WP-T01 | config + artisan-команда + диск | `nwdb/config/nwdb.php`, `nwdb/src/Console/MakeSigningKey.php`, `NwdbServiceProvider` |
| WP-T02 | Cipher (крипто-ядро) | `nwdb/src/WpFeed/Cipher.php`, `CipherException.php` |
| WP-T03 | міграції + моделі | `nwdb/database/migrations/*`, `SitePlugin.php`, `FeedPublication.php` |
| WP-T04 | CipherTest | `nwdb/tests/Unit/CipherTest.php` |
| WP-T05 | PayloadBuilder + IdentityFactory | `nwdb/src/WpFeed/{PayloadBuilder,IdentityFactory}.php` |
| WP-T06 | Publisher | `nwdb/src/WpFeed/Publisher/*` |
| WP-T07 | шаблон плагіна | `nwdb/resources/wp-plugin-template/*` |
| WP-T08 | PluginBuilder (ZIP) | `nwdb/src/WpFeed/PluginBuilder.php` |
| WP-T09 | FeedManager + Job + Observer | `nwdb/src/WpFeed/FeedManager.php`, `Jobs/PublishSiteFeed.php`, `Observers/ContactEntryObserver.php` |
| WP-T10 | PluginPanel + route | `nwdb/src/Livewire/PluginPanel.php`, `nwdb/routes/web.php` |
| WP-T11 | blade-вкладка | `nwdb/resources/views/{plugin-panel,tab-plugin}.blade.php` + інтеграція у `sites/show.blade.php` |
| WP-T12 | feature-тести + factory | `nwdb/tests/Feature/*`, `nwdb/database/factories/SitePluginFactory.php` |

## Тести

`vendor/bin/phpunit --testsuite Nwdb` → **37 passed, 111 assertions**.

## Налаштування перед prod

1. `php artisan nwdb:make-signing-key` → вставити `NWDB_SIGN_PUBLIC`/`NWDB_SIGN_SECRET` у `.env`
2. `NWDB_DEADDROP_PATH` = директорія, яку rsync/mount доставляє на Caddy-хост
3. `NWDB_BASE_URL` = публічний URL нейтрального статик-домену
4. чергу запустити (`php artisan queue:work`) для автопублікації

## Ризики (відкриті питання)

- **WP < 5.2**: sodium_compat у ядрі WP лише з 5.2; для старіших — бандлити paragonie/sodium_compat у ZIP.
- **Витік центрального Ed25519-секрету** = компрометація всього флоту → секрет лише в `.env` за Tailscale; ротація = ребілд усіх плагінів.
- **Втрата `APP_KEY`** = втрата всіх sym-ключів (стандартний Laravel-ризик).
- **Caddy-кеш**: для `.bin` потрібні ETag + короткий TTL (фаза devops).
- **BRIEF.md/PRESERVED.md** містять застарілу схему (HMAC/R2/Sanctum) — замінено цим планом.
