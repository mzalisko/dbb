<?php

namespace App\Support;

/**
 * Central catalog of audit action codes (domain.object.verb) with their
 * human (UA) label, severity and icon.
 *
 * Single source of truth that de-noises the activity feed: AuditFeed resolves
 * owen-it audit events and activity_log rows into these codes, and the UI
 * renders label()/severity()/icon() instead of raw strings like "site updated".
 */
final class AuditAction
{
    public const INFO = 0;
    public const WARN = 1;
    public const CRITICAL = 2;

    /** code => [label (UA), severity, icon] */
    private const CATALOG = [
        // ── ContactEntry ──────────────────────────────────────────────
        'entry.created'          => ['Запис створено',           self::INFO,     'plus'],
        'entry.updated'          => ['Запис змінено',            self::INFO,     'edit'],
        'entry.deleted'          => ['Запис видалено',           self::WARN,     'trash'],
        'entry.restored'         => ['Запис відновлено',         self::INFO,     'refresh'],
        'entry.purged'           => ['Запис видалено остаточно', self::CRITICAL, 'trash'],
        'entry.price.changed'    => ['Ціну змінено',             self::INFO,     'edit'],
        'entry.messenger.added'  => ['Месенджер додано',         self::INFO,     'plus'],

        // ── Bulk (one summary row per batch) ─────────────────────────
        'entry.bulk.deleted'     => ['Масове видалення',          self::WARN,     'trash'],
        'entry.bulk.visibility'  => ['Масова зміна видимості',    self::INFO,     'edit'],
        'entry.bulk.restored'    => ['Масове відновлення',        self::INFO,     'refresh'],
        'entry.bulk.updated'     => ['Масова зміна',              self::INFO,     'edit'],
        'entry.bulk.role'        => ['Масова зміна стану',        self::INFO,     'bolt'],
        'entry.bulk.geo'         => ['Масова зміна гео',          self::INFO,     'globe'],
        'entry.bulk.price'       => ['Масова зміна цін',          self::INFO,     'tag'],
        'entry.bulk.created'     => ['Масове створення',          self::INFO,     'plus'],
        'entry.bulk.moved'       => ['Масове переміщення',        self::INFO,     'share'],
        'entry.bulk.attached'    => ['Масове приєднання резерву', self::INFO,     'link'],
        'entry.bulk.purged'      => ['Масове видалення назавжди', self::CRITICAL, 'trash'],

        // ── Site ──────────────────────────────────────────────────────
        'site.created'           => ['Сайт створено',            self::INFO,     'plus'],
        'site.settings.updated'  => ['Налаштування оновлено',    self::INFO,     'edit'],
        'site.geo.updated'       => ['Гео-видимість змінено',    self::INFO,     'edit'],
        'site.categories.updated'=> ['Категорії змінено',        self::INFO,     'edit'],
        'site.status.changed'    => ['Статус змінено',           self::INFO,     'edit'],
        'site.failover.toggled'  => ['Failover перемкнено',      self::INFO,     'bolt'],
        'site.failover.triggered' => ['Failover спрацював',       self::WARN,     'bolt'],
        'site.failover.restored'  => ['Failover відновлено',      self::INFO,     'refresh'],
        'site.deleted'           => ['Сайт видалено',            self::WARN,     'trash'],

        // ── SiteGroup ────────────────────────────────────────────────
        'group.created'          => ['Групу створено',           self::INFO,     'plus'],
        'group.deleted'          => ['Групу видалено',           self::WARN,     'trash'],
        'group.sites.reassigned' => ['Сайти перегруповано',      self::WARN,     'edit'],

        // ── User / team ──────────────────────────────────────────────
        'user.invited'           => ['Користувача запрошено',    self::INFO,     'plus'],
        'user.role.changed'      => ['Роль змінено',             self::CRITICAL, 'edit'],
        'user.suspended'         => ['Користувача призупинено',  self::WARN,     'bolt'],
        'user.removed'           => ['Користувача видалено',     self::WARN,     'trash'],

        // ── Auth / system ────────────────────────────────────────────
        'auth.login'             => ['Вхід',                     self::INFO,     'edit'],
        'auth.temp_login'        => ['Тимчасовий вхід (адмін-доступ)', self::WARN, 'bolt'],
        'auth.logout'            => ['Вихід',                    self::INFO,     'edit'],
        'auth.login_failed'      => ['Невдалий вхід',            self::CRITICAL, 'bolt'],
        'auth.lockout'           => ['Блокування входу',         self::CRITICAL, 'bolt'],
        'system.audit.pruned'    => ['Логи очищено',             self::INFO,     'trash'],
    ];

    public static function label(string $code): string
    {
        return self::CATALOG[$code][0] ?? $code;
    }

    public static function severity(string $code): int
    {
        return self::CATALOG[$code][1] ?? self::INFO;
    }

    public static function icon(string $code): string
    {
        return self::CATALOG[$code][2] ?? 'edit';
    }

    /** Domain prefix (entry|site|group|user|auth|system) — used by filter pills. */
    public static function domain(string $code): string
    {
        return strstr($code, '.', true) ?: $code;
    }

    public static function exists(string $code): bool
    {
        return isset(self::CATALOG[$code]);
    }

    /** @return array<string> all known codes */
    public static function codes(): array
    {
        return array_keys(self::CATALOG);
    }
}
