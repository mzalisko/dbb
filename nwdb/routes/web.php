<?php

use App\Models\Site;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Nwdb\Models\SitePlugin;
use Nwdb\WpFeed\PluginBuilder;

/*
 * Завантаження per-site ZIP білда плагіна. Підписаний тимчасовий URL
 * (генерується панеллю) + auth + авторизація update на сайт.
 * Жодних публічних ендпоінтів: dead-drop живе на зовнішньому статик-хості.
 */
Route::middleware(['web', 'auth', 'signed'])->group(function () {
    Route::get('/sites/{site}/plugin/download', function (Site $site, PluginBuilder $builder) {
        Gate::authorize('update', $site);

        $plugin = SitePlugin::where('site_id', $site->id)->firstOrFail();

        return $builder->streamZip($plugin);
    })->name('nwdb.plugin.download');
});
