<?php

namespace Nwdb\WpFeed;

use Nwdb\Models\SitePlugin;

/**
 * Генерує унікальну, нейтральну ідентичність білда: два сайти ніколи
 * не діляться slug-ом, назвою, префіксом, cron-хуком чи шляхом фіду,
 * тож мережу не можна виявити фінгерпринтингом плагіна.
 */
class IdentityFactory
{
    private const WORDS_A = [
        'swift', 'clear', 'simple', 'smart', 'rapid', 'prime', 'solid',
        'bright', 'easy', 'quick', 'neat', 'fresh', 'modern', 'compact',
    ];

    private const WORDS_B = [
        'page', 'site', 'content', 'block', 'widget', 'panel', 'section',
        'layout', 'display', 'info', 'footer', 'sidebar', 'card', 'banner',
    ];

    private const WORDS_C = [
        'toolkit', 'helper', 'manager', 'assist', 'kit', 'tools', 'suite',
        'utils', 'box', 'studio', 'works', 'plus', 'pro', 'lab',
    ];

    /** @return array{slug:string,display_name:string,prefix:string,text_domain:string,feed_token:string,feed_filename:string,cron_hook:string,cron_interval:int,build_meta:array} */
    public function make(): array
    {
        $slug = $this->uniqueSlug();
        $prefix = $this->prefixFor($slug);
        $token = bin2hex(random_bytes(16));

        return [
            'slug'          => $slug,
            'display_name'  => $this->displayNameFor($slug),
            'prefix'        => $prefix,
            'text_domain'   => $slug,
            'feed_token'    => $token,
            'feed_filename' => $token.'.bin',
            'cron_hook'     => $prefix.'sync_event',
            'cron_interval' => random_int(
                (int) config('nwdb.plugin.cron_interval_min'),
                (int) config('nwdb.plugin.cron_interval_max'),
            ),
            'build_meta'    => [
                'builder_version' => config('nwdb.plugin.version'),
                'generated_at'    => now()->toIso8601String(),
            ],
        ];
    }

    private function uniqueSlug(): string
    {
        do {
            $slug = sprintf(
                '%s-%s-%s',
                self::WORDS_A[random_int(0, count(self::WORDS_A) - 1)],
                self::WORDS_B[random_int(0, count(self::WORDS_B) - 1)],
                self::WORDS_C[random_int(0, count(self::WORDS_C) - 1)],
            );
        } while (SitePlugin::withoutGlobalScopes()->where('slug', $slug)->exists());

        return $slug;
    }

    private function displayNameFor(string $slug): string
    {
        return implode(' ', array_map('ucfirst', explode('-', $slug)));
    }

    private function prefixFor(string $slug): string
    {
        $initials = implode('', array_map(fn ($w) => $w[0], explode('-', $slug)));

        return $initials.'_'.bin2hex(random_bytes(2)).'_';
    }
}
