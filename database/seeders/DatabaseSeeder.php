<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data (maintain FK order)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ContactEntry::truncate();
        ActivityLog::truncate();
        DB::table('sites')->truncate();
        DB::table('clients')->truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Team ─────────────────────────────────────────────
        $owner = User::create([
            'name'              => 'Mykola Zalisko',
            'email'             => 'zaliskomykola@gmail.com',
            'password'          => Hash::make('admin123'),
            'role'              => 'owner',
            'organization_name' => 'DataBridge Agency',
            'email_verified_at' => now(),
        ]);

        $ivan = User::create([
            'name'              => 'Іван Петренко',
            'email'             => 'ivan@databridge.app',
            'password'          => Hash::make('password'),
            'role'              => 'admin',
            'organization_name' => 'DataBridge Agency',
            'email_verified_at' => now(),
        ]);

        $olha = User::create([
            'name'              => 'Olha Boyko',
            'email'             => 'olha@databridge.app',
            'password'          => Hash::make('password'),
            'role'              => 'member',
            'organization_name' => 'DataBridge Agency',
            'email_verified_at' => now(),
        ]);

        $sam = User::create([
            'name'              => 'Sam Cooper',
            'email'             => 'sam@databridge.app',
            'password'          => Hash::make('password'),
            'role'              => 'member',
            'organization_name' => 'DataBridge Agency',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name'              => 'Дмитро К.',
            'email'             => 'dmytro@databridge.app',
            'password'          => Hash::make('password'),
            'role'              => 'member',
            'organization_name' => 'DataBridge Agency',
            'email_verified_at' => now(),
        ]);

        // ── Clients ──────────────────────────────────────────
        $nordwave = Client::create([
            'user_id'       => $owner->id,
            'company_name'  => 'NordWave Digital',
            'contact_name'  => 'Oksana Nordström',
            'contact_email' => 'oksana@nordwave.com',
            'contact_phone' => '+48 22 100 20 30',
            'status'        => 'active',
            'notes'         => "Клієнт з 2022 р. Основний контакт — Oksana.\n\nMessengers:\n• Telegram: @nordwave_oksana (головний, UA+World)\n• WhatsApp: +48 22 100 20 30 (PL резерв)\n• Viber: +38 097 100 20 30 (UA резерв)\n\nSocial:\n• Instagram: @nordwave.digital\n• Facebook: fb.com/nordwavedigital\n• LinkedIn: linkedin.com/company/nordwave",
        ]);

        $apex = Client::create([
            'user_id'       => $ivan->id,
            'company_name'  => 'Apex Commerce GmbH',
            'contact_name'  => 'Mark Schmidt',
            'contact_email' => 'mark@apex-shop.com',
            'contact_phone' => '+49 30 555 88 99',
            'status'        => 'active',
            'notes'         => "E-commerce клієнт, Берлін. Sync проблеми — Connection refused на основному сайті.\n\nMessengers:\n• Telegram: @apex_mark\n• WhatsApp: +49 30 555 88 99\n\nSocial:\n• Instagram: @apexcommerce.de\n• Facebook: fb.com/apexcommerce",
        ]);

        $lumen = Client::create([
            'user_id'       => $olha->id,
            'company_name'  => 'Lumen Digital Solutions',
            'contact_name'  => 'Andriy Lumen',
            'contact_email' => 'andriy@lumen-io.com',
            'contact_phone' => '+38 044 111 22 33',
            'status'        => 'active',
            'notes'         => "SaaS компанія, Київ.\n\nMessengers:\n• Telegram: @lumen_andriy (головний)\n• Viber: +38 044 111 22 33\n\nSocial:\n• Twitter/X: @lumenio\n• LinkedIn: linkedin.com/company/lumen-digital",
        ]);

        $kestrel = Client::create([
            'user_id'       => $owner->id,
            'company_name'  => 'Kestrel Systems Ltd',
            'contact_name'  => 'Kim Kestrel',
            'contact_email' => 'kim@kestrel.so',
            'contact_phone' => '+44 20 7946 0100',
            'status'        => 'active',
            'notes'         => "UK клієнт, Лондон. B2B SaaS.\n\nMessengers:\n• Telegram: @kim_kestrel\n• WhatsApp: +44 20 7946 0100\n\nSocial:\n• LinkedIn: linkedin.com/company/kestrel-systems\n• Twitter/X: @kestrel_so",
        ]);

        $northgate = Client::create([
            'user_id'       => $sam->id,
            'company_name'  => 'NorthGate Retail',
            'contact_name'  => 'Serhiy Boyko',
            'contact_email' => 'serhiy@northgate.shop',
            'contact_phone' => '+48 71 200 10 10',
            'status'        => 'active',
            'notes'         => "Retail мережа, PL + UA.\n\nТелефони:\n• +48 71 200 10 10 — PL головний\n• +48 71 299 99 88 — PL резерв\n• +38 044 200 10 10 — UA головний\n\nMessengers:\n• Telegram: @northgate_support (UA+World)\n• WhatsApp: +48 71 200 10 10 (PL)\n\nSocial:\n• Facebook: fb.com/northgateshop\n• Instagram: @northgate.shop",
        ]);

        $democorp = Client::create([
            'user_id'       => $owner->id,
            'company_name'  => 'Demo Client Corp',
            'contact_name'  => 'Demo Contact',
            'contact_email' => 'demo@demo-site.example',
            'contact_phone' => '+48 00 000 00 00',
            'status'        => 'active',
            'notes'         => "Демонстраційний клієнт для тестування.\n\nТелефони:\n• +48 00 000 00 00 — PL головний\n• +48 99 999 99 99 — PL резерв\n• 11111111111 — UA+World головний\n• +099 11 22 33 — UA резерв\n\nMessengers:\n• Telegram: @demo_pl (PL)\n• WhatsApp: +48 22 555 33 11 (PL резерв)\n• Telegram: @demo_main (UA+World)\n• Viber: +38 099 11 22 33 (UA)\n\nSocial:\n• Instagram: @democlient\n• Facebook: fb.com/democlientcorp",
        ]);

        $voltway = Client::create([
            'user_id'       => $ivan->id,
            'company_name'  => 'VoltWay Innovations',
            'contact_name'  => 'Volt Founder',
            'contact_email' => 'hello@voltway.pro',
            'contact_phone' => '+38 093 300 44 55',
            'status'        => 'inactive',
            'notes'         => "Стартап, на паузі з Q1 2026. Staging сайт активний.\n\nMessengers:\n• Telegram: @voltway_ceo\n\nSocial:\n• LinkedIn: linkedin.com/company/voltway",
        ]);

        $stone = Client::create([
            'user_id'       => $olha->id,
            'company_name'  => 'StoneWorks Creative',
            'contact_name'  => 'Petro Stone',
            'contact_email' => 'petro@stoneworks.dev',
            'contact_phone' => '+48 22 999 88 77',
            'status'        => 'active',
            'notes'         => "Дизайн-студія, Варшава. Staging сайт для нового проекту.\n\nMessengers:\n• Telegram: @petro_stone\n• WhatsApp: +48 22 999 88 77\n\nSocial:\n• Instagram: @stoneworks.creative\n• Behance: behance.net/stoneworks",
        ]);

        // ── Sites ─────────────────────────────────────────────
        Site::create([
            'client_id'      => $democorp->id,
            'name'           => 'demo-site.example',
            'url'            => 'https://demo-site.example',
            'wp_version'     => '6.5.4',
            'php_version'    => '8.3',
            'status'         => 'active',
            'group'          => 'production',
            'group_color'    => '#5a8a3c',
            'last_checked_at'=> now()->subMinutes(5),
            'is_favourite'   => true,
            'notes'          => "Основний демо-сайт.\n\nТелефони: 7 номерів (PL+UA geo)\nMessengers: 3 (Telegram, WhatsApp, FB Messenger)",
        ]);

        Site::create([
            'client_id'      => $nordwave->id,
            'name'           => 'nordwave.com',
            'url'            => 'https://nordwave.com',
            'wp_version'     => '6.5.3',
            'php_version'    => '8.2',
            'status'         => 'active',
            'group'          => 'production',
            'group_color'    => '#5a8a3c',
            'last_checked_at'=> now()->subMinutes(20),
            'is_favourite'   => true,
            'notes'          => "Головний сайт NordWave Digital.\n\nТелефони: 12 номерів\nMessengers: 2 (Telegram, WhatsApp)",
        ]);

        Site::create([
            'client_id'      => $apex->id,
            'name'           => 'apex-shop.com',
            'url'            => 'https://apex-shop.com',
            'wp_version'     => '6.4.4',
            'php_version'    => '8.1',
            'status'         => 'offline',
            'group'          => 'production',
            'group_color'    => '#5a8a3c',
            'last_checked_at'=> now()->subMinutes(12),
            'notes'          => "⚠️ Помилка sync: Connection refused.\nПотрібна перевірка хостингу.\n\nТелефони: 8 номерів\nMessengers: 4",
        ]);

        Site::create([
            'client_id'      => $lumen->id,
            'name'           => 'lumen-io.com',
            'url'            => 'https://lumen-io.com',
            'wp_version'     => '6.5.2',
            'php_version'    => '8.2',
            'status'         => 'active',
            'group'          => 'production',
            'group_color'    => '#5a8a3c',
            'last_checked_at'=> now()->subHours(2),
            'notes'          => "SaaS лендінг + блог.\n\nТелефони: 6 номерів\nMessengers: 1 (Telegram)",
        ]);

        Site::create([
            'client_id'      => $kestrel->id,
            'name'           => 'kestrel.so',
            'url'            => 'https://kestrel.so',
            'wp_version'     => '6.5.4',
            'php_version'    => '8.3',
            'status'         => 'active',
            'group'          => 'production',
            'group_color'    => '#5a8a3c',
            'last_checked_at'=> now()->subHours(3),
            'notes'          => "B2B SaaS landing.\n\nТелефони: 4 номери\nMessengers: 2 (Telegram, WhatsApp)",
        ]);

        Site::create([
            'client_id'      => $northgate->id,
            'name'           => 'northgate.shop',
            'url'            => 'https://northgate.shop',
            'wp_version'     => '6.5.1',
            'php_version'    => '8.2',
            'status'         => 'active',
            'group'          => 'production',
            'group_color'    => '#5a8a3c',
            'last_checked_at'=> now()->subDay(),
            'notes'          => "WooCommerce store. PL + UA market.\n\nТелефони: 9 номерів (PL + UA geo)\nMessengers: 3 (Telegram, WhatsApp, Viber)",
        ]);

        Site::create([
            'client_id'      => $voltway->id,
            'name'           => 'voltway.pro',
            'url'            => 'https://voltway.pro',
            'wp_version'     => '6.4.3',
            'php_version'    => '8.1',
            'status'         => 'maintenance',
            'group'          => 'staging',
            'group_color'    => '#b87a1c',
            'last_checked_at'=> now()->subDays(2),
            'notes'          => "Staging. Клієнт на паузі з Q1 2026.\n\nТелефони: 5 номерів\nMessengers: 2",
        ]);

        Site::create([
            'client_id'      => $stone->id,
            'name'           => 'stoneworks.dev',
            'url'            => 'https://stoneworks.dev',
            'wp_version'     => '6.4.4',
            'php_version'    => '8.1',
            'status'         => 'active',
            'group'          => 'staging',
            'group_color'    => '#b87a1c',
            'last_checked_at'=> now()->subDay(),
            'notes'          => "Dev/staging середовище нового проекту StoneWorks.\n\nТелефони: 3 номери\nMessengers: 1 (Telegram)",
        ]);

        // ── Activity Log ──────────────────────────────────────
        $logs = [
            ['user_id' => $owner->id,  'action' => 'phone updated',  'subject_type' => Site::class,   'subject_id' => 1, 'created_at' => now()->subMinutes(6)],
            ['user_id' => null,        'action' => 'sync failed',    'subject_type' => Site::class,   'subject_id' => 3, 'created_at' => now()->subMinutes(18)],
            ['user_id' => $owner->id,  'action' => 'push success',   'subject_type' => Site::class,   'subject_id' => 2, 'created_at' => now()->subMinutes(30)],
            ['user_id' => null,        'action' => 'sync ok',        'subject_type' => Site::class,   'subject_id' => 7, 'created_at' => now()->subMinutes(45)],
            ['user_id' => $olha->id,   'action' => 'push success',   'subject_type' => Site::class,   'subject_id' => 4, 'created_at' => now()->subHours(1)],
            ['user_id' => null,        'action' => 'push success',   'subject_type' => Site::class,   'subject_id' => 5, 'created_at' => now()->subHours(1)->subMinutes(6)],
            ['user_id' => null,        'action' => 'sync timeout',   'subject_type' => Site::class,   'subject_id' => 3, 'created_at' => now()->subHours(1)->subMinutes(12)],
            ['user_id' => $ivan->id,   'action' => 'client created', 'subject_type' => Client::class, 'subject_id' => 2, 'created_at' => now()->subHours(2)],
            ['user_id' => $owner->id,  'action' => 'site created',   'subject_type' => Site::class,   'subject_id' => 1, 'created_at' => now()->subHours(3)],
            ['user_id' => $olha->id,   'action' => 'client created', 'subject_type' => Client::class, 'subject_id' => 3, 'created_at' => now()->subHours(4)],
            ['user_id' => $owner->id,  'action' => 'push success',   'subject_type' => Site::class,   'subject_id' => 6, 'created_at' => now()->subHours(5)],
            ['user_id' => $sam->id,    'action' => 'client created', 'subject_type' => Client::class, 'subject_id' => 5, 'created_at' => now()->subHours(6)],
        ];

        foreach ($logs as $log) {
            ActivityLog::create($log);
        }

        // ── Contact Entries (demo-site.example) ───────────────
        $demoSite = Site::where('name', 'demo-site.example')->firstOrFail();

        // Phones — PL pool (primary + 2 backups)
        $plPhone = ContactEntry::create([
            'site_id'  => $demoSite->id, 'type' => 'phone',
            'value'    => '+48 00 000 00 00', 'label' => 'Польща · головний',
            'role'     => 'primary', 'geo_mode' => 'only', 'countries' => ['PL'],
            'visible'  => true, 'order' => 1,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'phone',
            'value'     => '+48 99 999 99 99', 'label' => 'PL резерв',
            'role'      => 'backup', 'geo_mode' => 'only', 'countries' => ['PL'],
            'visible'   => true, 'order' => 1, 'parent_id' => $plPhone->id,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'phone',
            'value'     => '+48 71 222 11 00', 'label' => 'PL резерв · 2',
            'role'      => 'backup', 'geo_mode' => 'only', 'countries' => ['PL'],
            'visible'   => true, 'order' => 2, 'parent_id' => $plPhone->id,
        ]);

        // Phones — UA + World pool (primary + 3 backups)
        $worldPhone = ContactEntry::create([
            'site_id'  => $demoSite->id, 'type' => 'phone',
            'value'    => '11111111111', 'label' => 'Головний (UA + Світ)',
            'role'     => 'primary', 'geo_mode' => 'except', 'countries' => ['PL'],
            'visible'  => true, 'order' => 1,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'phone',
            'value'     => '+099 11 22 33', 'label' => 'UA резерв',
            'role'      => 'backup', 'geo_mode' => 'only', 'countries' => ['UA'],
            'visible'   => true, 'order' => 1, 'parent_id' => $worldPhone->id,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'phone',
            'value'     => '073 111-22-33', 'label' => 'UA резерв · 2',
            'role'      => 'backup', 'geo_mode' => 'only', 'countries' => ['UA'],
            'visible'   => true, 'order' => 2, 'parent_id' => $worldPhone->id,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'phone',
            'value'     => '+48 22 555 33 11', 'label' => 'Універсальний резерв',
            'role'      => 'backup', 'geo_mode' => 'all', 'countries' => [],
            'visible'   => true, 'order' => 3, 'parent_id' => $worldPhone->id,
        ]);

        // Phones — hidden (archived)
        ContactEntry::create([
            'site_id'  => $demoSite->id, 'type' => 'phone',
            'value'    => '063 000-00-00', 'label' => 'Колишній головний',
            'role'     => 'hidden', 'geo_mode' => 'all', 'countries' => [],
            'visible'  => false, 'order' => 5,
        ]);

        // Messengers — PL pool
        $plTg = ContactEntry::create([
            'site_id'  => $demoSite->id, 'type' => 'messenger', 'kind' => 'telegram',
            'value'    => '@demo_pl', 'label' => 'PL · Telegram головний',
            'role'     => 'primary', 'geo_mode' => 'only', 'countries' => ['PL'],
            'visible'  => true, 'order' => 1,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'messenger', 'kind' => 'whatsapp',
            'value'     => '+48 22 555 33 11', 'label' => 'PL · WhatsApp резерв',
            'role'      => 'backup', 'geo_mode' => 'only', 'countries' => ['PL'],
            'visible'   => true, 'order' => 1, 'parent_id' => $plTg->id,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'messenger', 'kind' => 'messenger',
            'value'     => 'm.me/demoPL', 'label' => 'PL · Facebook Messenger',
            'role'      => 'backup', 'geo_mode' => 'only', 'countries' => ['PL'],
            'visible'   => true, 'order' => 2, 'parent_id' => $plTg->id,
        ]);

        // Messengers — UA + World pool
        $mainTg = ContactEntry::create([
            'site_id'  => $demoSite->id, 'type' => 'messenger', 'kind' => 'telegram',
            'value'    => '@demo_main', 'label' => 'Головний (UA + Світ)',
            'role'     => 'primary', 'geo_mode' => 'except', 'countries' => ['PL'],
            'visible'  => true, 'order' => 1,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'messenger', 'kind' => 'viber',
            'value'     => '+38 099 11 22 33', 'label' => 'UA · Viber резерв',
            'role'      => 'backup', 'geo_mode' => 'only', 'countries' => ['UA'],
            'visible'   => true, 'order' => 1, 'parent_id' => $mainTg->id,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'messenger', 'kind' => 'whatsapp',
            'value'     => '+38 073 000 11 22', 'label' => 'UA · WhatsApp резерв',
            'role'      => 'backup', 'geo_mode' => 'only', 'countries' => ['UA'],
            'visible'   => true, 'order' => 2, 'parent_id' => $mainTg->id,
        ]);
        ContactEntry::create([
            'site_id'   => $demoSite->id, 'type' => 'messenger', 'kind' => 'telegram',
            'value'     => '@demo_support', 'label' => 'Універсальний резерв',
            'role'      => 'backup', 'geo_mode' => 'all', 'countries' => [],
            'visible'   => true, 'order' => 3, 'parent_id' => $mainTg->id,
        ]);

        // Messengers — hidden
        ContactEntry::create([
            'site_id'  => $demoSite->id, 'type' => 'messenger', 'kind' => 'skype',
            'value'    => 'live:demo.old', 'label' => 'Старий Skype',
            'role'     => 'hidden', 'geo_mode' => 'all', 'countries' => [],
            'visible'  => false, 'order' => 9,
        ]);

        // Prices — 3 SKUs, multi-currency geo-targeted
        ContactEntry::create([
            'site_id' => $demoSite->id, 'type' => 'price',
            'value'   => 'WAVE-01', 'label' => 'Підписка · Standard',
            'role'    => 'primary', 'geo_mode' => 'only', 'countries' => ['PL'],
            'visible' => true, 'order' => 1, 'sku' => 'WAVE-01',
            'currency' => 'PLN', 'price' => 149, 'old_price' => 199, 'price_unit' => '/міс',
        ]);
        ContactEntry::create([
            'site_id' => $demoSite->id, 'type' => 'price',
            'value'   => 'WAVE-01', 'label' => 'Підписка · Standard',
            'role'    => 'primary', 'geo_mode' => 'only', 'countries' => ['UA'],
            'visible' => true, 'order' => 2, 'sku' => 'WAVE-01',
            'currency' => 'UAH', 'price' => 1290, 'old_price' => 1490, 'price_unit' => '/міс',
        ]);
        ContactEntry::create([
            'site_id' => $demoSite->id, 'type' => 'price',
            'value'   => 'WAVE-01', 'label' => 'Підписка · Standard',
            'role'    => 'primary', 'geo_mode' => 'except', 'countries' => ['PL', 'UA'],
            'visible' => true, 'order' => 3, 'sku' => 'WAVE-01',
            'currency' => 'EUR', 'price' => 39, 'old_price' => null, 'price_unit' => '/mo',
        ]);
        ContactEntry::create([
            'site_id' => $demoSite->id, 'type' => 'price',
            'value'   => 'WAVE-02', 'label' => 'Підписка · Pro',
            'role'    => 'primary', 'geo_mode' => 'only', 'countries' => ['PL'],
            'visible' => true, 'order' => 1, 'sku' => 'WAVE-02',
            'currency' => 'PLN', 'price' => 299, 'old_price' => null, 'price_unit' => '/міс',
        ]);
        ContactEntry::create([
            'site_id' => $demoSite->id, 'type' => 'price',
            'value'   => 'WAVE-02', 'label' => 'Підписка · Pro',
            'role'    => 'primary', 'geo_mode' => 'only', 'countries' => ['UA'],
            'visible' => true, 'order' => 2, 'sku' => 'WAVE-02',
            'currency' => 'UAH', 'price' => 2490, 'old_price' => null, 'price_unit' => '/міс',
        ]);
        ContactEntry::create([
            'site_id' => $demoSite->id, 'type' => 'price',
            'value'   => 'WAVE-02', 'label' => 'Підписка · Pro',
            'role'    => 'primary', 'geo_mode' => 'except', 'countries' => ['PL', 'UA'],
            'visible' => true, 'order' => 3, 'sku' => 'WAVE-02',
            'currency' => 'USD', 'price' => 79, 'old_price' => null, 'price_unit' => '/mo',
        ]);
        ContactEntry::create([
            'site_id' => $demoSite->id, 'type' => 'price',
            'value'   => 'ONBOARD', 'label' => 'Setup · одноразово',
            'role'    => 'primary', 'geo_mode' => 'all', 'countries' => [],
            'visible' => true, 'order' => 1, 'sku' => 'ONBOARD',
            'currency' => 'EUR', 'price' => 0, 'old_price' => 149, 'price_unit' => 'одноразово',
        ]);
    }
}
