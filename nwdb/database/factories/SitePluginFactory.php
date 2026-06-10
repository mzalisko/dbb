<?php

namespace Nwdb\Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Nwdb\Models\SitePlugin;
use Nwdb\WpFeed\Cipher;

class SitePluginFactory extends Factory
{
    protected $model = SitePlugin::class;

    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(3);
        $prefix = substr(str_replace('-', '', $slug), 0, 3).'_'.bin2hex(random_bytes(2)).'_';
        $token = bin2hex(random_bytes(16));

        return [
            'site_id'         => Site::factory(),
            'slug'            => $slug,
            'display_name'    => implode(' ', array_map('ucfirst', explode('-', $slug))),
            'prefix'          => $prefix,
            'text_domain'     => $slug,
            'feed_token'      => $token,
            'feed_filename'   => $token.'.bin',
            'sym_key'         => base64_encode(Cipher::generateSymmetricKey()),
            'cron_hook'       => $prefix.'sync_event',
            'cron_interval'   => $this->faker->numberBetween(3300, 4500),
            'status'          => SitePlugin::STATUS_DRAFT,
            'payload_version' => 1,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn () => [
            'status' => SitePlugin::STATUS_ENABLED,
            'connected_at' => now(),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn () => ['status' => SitePlugin::STATUS_PAUSED]);
    }
}
