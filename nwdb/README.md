# nwdb — WP dead-drop модуль

Ізольований модуль фази 6: доставка контактів сайтів на клієнтські WordPress
через зашифрований dead-drop. Весь код тут, namespace `Nwdb\`, щоб не
змішуватися з ядром CRM.

## Структура

```
nwdb/
├── config/nwdb.php                 конфіг (publisher, deaddrop, signing keys)
├── database/
│   ├── migrations/                 site_plugins, feed_publications
│   └── factories/SitePluginFactory.php
├── resources/
│   ├── views/                      blade: plugin-panel, tab-plugin
│   └── wp-plugin-template/         .stub-шаблон WP-плагіна
├── routes/web.php                  signed download маршрут
├── src/
│   ├── NwdbServiceProvider.php      реєстрація (provider у bootstrap/providers.php)
│   ├── Console/MakeSigningKey.php   artisan nwdb:make-signing-key
│   ├── Models/                      SitePlugin, FeedPublication
│   ├── Livewire/PluginPanel.php
│   ├── Jobs/PublishSiteFeed.php
│   ├── Observers/ContactEntryObserver.php
│   └── WpFeed/                      Cipher, PayloadBuilder, IdentityFactory,
│                                    FeedManager, PluginBuilder, Publisher/
├── tests/                          Unit + Feature (37 тестів)
└── vault/phase-6-wp-deaddrop/      план + kanban
```

## Autoload (composer.json)

```
"Nwdb\\": "nwdb/src/"
"Nwdb\\Database\\Factories\\": "nwdb/database/factories/"
"Nwdb\\Tests\\": "nwdb/tests/" (dev)
```

## Запуск тестів

```
vendor/bin/phpunit --testsuite Nwdb
```

## Документація

Деталі архітектури, крипто-конверта і ризиків — `vault/phase-6-wp-deaddrop/PLAN.md`.
Скопіюй папку `vault/phase-6-wp-deaddrop/` у `C:\Dev\ddbv2-vault\30-tasks\` локально.
