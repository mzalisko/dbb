<?php

namespace Nwdb;

use App\Models\ContactEntry;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Nwdb\Console\MakeSigningKey;
use Nwdb\Livewire\PluginPanel;
use Nwdb\Observers\ContactEntryObserver;
use Nwdb\WpFeed\Publisher\FeedPublisher;
use Nwdb\WpFeed\Publisher\LocalPathPublisher;
use Nwdb\WpFeed\Publisher\S3FeedPublisher;

class NwdbServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nwdb.php', 'nwdb');

        // Диск dead-drop реєструється модулем, щоб не чіпати config/filesystems.php
        config([
            'filesystems.disks.deaddrop' => [
                'driver' => 'local',
                'root'   => config('nwdb.deaddrop.path'),
                'throw'  => true,
            ],
        ]);

        $this->app->bind(FeedPublisher::class, function () {
            return match (config('nwdb.publisher')) {
                's3'    => new S3FeedPublisher(),
                default => new LocalPathPublisher(config('nwdb.deaddrop.disk')),
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nwdb');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Livewire::component('nwdb.plugin-panel', PluginPanel::class);

        ContactEntry::observe(ContactEntryObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([MakeSigningKey::class]);
        }
    }
}
