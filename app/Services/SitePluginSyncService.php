<?php

namespace App\Services;

use App\Models\ContactEntry;
use App\Models\Site;
use App\Support\PriceHtml;
use Illuminate\Support\Facades\Http;

class SitePluginSyncService
{
    public function syncMany(array $siteIds): array
    {
        $result = ['ok' => 0, 'failed' => 0, 'skipped' => 0];
        $siteIds = array_values(array_unique(array_filter(array_map('intval', $siteIds))));
        if (empty($siteIds)) {
            return $result;
        }

        Site::query()
            ->whereIn('id', $siteIds)
            ->get()
            ->each(function (Site $site) use (&$result) {
                if (! $site->url || ! $site->api_key) {
                    $result['skipped']++;
                    return;
                }

                $this->sync($site) ? $result['ok']++ : $result['failed']++;
            });

        return $result;
    }

    public function sync(Site $site): bool
    {
        if ($site->status === 'maintenance') {
            $this->pushStatus($site);

            ActivityLogService::log('site.sync.blocked', $site, [
                'reason' => 'site_maintenance',
                'status' => $site->status,
            ], context: 'sync');

            return false;
        }

        if (! $site->url || ! $site->api_key) {
            return false;
        }

        $pushUrl = $this->pluginEndpoint($site, 'push');
        $payload = $this->buildPayload($site);

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['X-DataBridge-Key' => $site->api_key])
                ->post($pushUrl, $payload);
        } catch (\Throwable $e) {
            ActivityLogService::log('site.sync.failed', $site, [
                'endpoint' => $pushUrl,
                'error' => $e->getMessage(),
            ], context: 'sync');

            return false;
        }

        if (! $response->successful() || ! ($response->json('ok') ?? false)) {
            ActivityLogService::log('site.sync.failed', $site, [
                'endpoint' => $pushUrl,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ], context: 'sync');

            return false;
        }

        $this->refreshSnapshot($site);

        ActivityLogService::log('site.synced', $site, [
            'endpoint' => $pushUrl,
            'counts' => $response->json('counts') ?? [],
            'version' => $payload['version'],
        ], context: 'sync');

        return true;
    }

    public function pushStatus(Site $site): bool
    {
        if (! $site->url || ! $site->api_key) {
            return false;
        }

        $statusUrl = $this->pluginEndpoint($site, 'status');
        $payload = [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'domain' => parse_url((string) $site->url, PHP_URL_HOST) ?: $site->name,
                'status' => $site->status,
            ],
            'version' => 'crm-site-status-' . $site->id . '-' . now()->format('YmdHis'),
        ];

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['X-DataBridge-Key' => $site->api_key])
                ->post($statusUrl, $payload);
        } catch (\Throwable $e) {
            ActivityLogService::log('site.status_sync.failed', $site, [
                'endpoint' => $statusUrl,
                'error' => $e->getMessage(),
            ], context: 'sync');

            return false;
        }

        if (! $response->successful() || ! ($response->json('ok') ?? false)) {
            ActivityLogService::log('site.status_sync.failed', $site, [
                'endpoint' => $statusUrl,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ], context: 'sync');

            return false;
        }

        ActivityLogService::log('site.status_synced', $site, [
            'endpoint' => $statusUrl,
            'status' => $site->status,
            'version' => $payload['version'],
        ], context: 'sync');

        return true;
    }

    private function buildPayload(Site $site): array
    {
        $entries = $site->contactEntries()
            ->orderBy('type')
            ->orderBy('order')
            ->get();

        return [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'domain' => parse_url((string) $site->url, PHP_URL_HOST) ?: $site->name,
                'status' => $site->status,
            ],
            'workspace' => [
                'name' => config('app.name', 'DataBridge'),
            ],
            'contacts' => [
                'phones' => $entries->where('type', 'phone')->map(fn (ContactEntry $entry) => $this->entryPayload($entry))->values()->all(),
                'messengers' => $entries->where('type', 'messenger')->map(fn (ContactEntry $entry) => $this->entryPayload($entry))->values()->all(),
            ],
            'prices' => $entries->where('type', 'price')->map(fn (ContactEntry $entry) => $this->entryPayload($entry))->values()->all(),
            'settings' => [
                'geo_tabs' => $site->geo_tabs ?? ['UA', 'RU', 'BY'],
                'data_categories' => $site->data_categories ?? ['phones', 'messengers', 'prices'],
            ],
            'version' => 'crm-site-' . $site->id . '-' . now()->format('YmdHis'),
        ];
    }

    private function entryPayload(ContactEntry $entry): array
    {
        $geoOwner = $entry->geoOwner();
        $countries = collect($geoOwner->countries ?? [])
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->values()
            ->all();
        if (empty($countries) && $geoOwner->geo_tag) {
            $countries[] = strtoupper((string) $geoOwner->geo_tag);
        }
        $geoMode = $geoOwner->geo_mode ?: 'all';

        $payload = [
            'id' => $entry->id,
            'type' => $entry->type,
            'kind' => $entry->kind,
            'value' => trim((string) $entry->value),
            'text' => trim((string) $entry->value),
            'label' => (string) ($entry->label ?? ''),
            'role' => $entry->role,
            'reserve' => $entry->role === 'backup' || $entry->parent_id !== null,
            'parent_id' => $entry->parent_id,
            'order' => $entry->order,
            'visible' => (bool) $entry->visible,
            'failover_down' => (bool) $entry->failover_down,
            'geoMode' => $geoMode,
            'geo_mode' => $geoMode,
            'countries' => $countries,
            'geoLabel' => $geoOwner->geo_label,
        ];

        if ($entry->type === 'messenger') {
            $payload['link'] = $this->messengerLink($entry);
        }

        if ($entry->type === 'price') {
            $priceHtml = PriceHtml::clean((string) $entry->value);
            $priceText = PriceHtml::text($priceHtml);
            $payload['value'] = $priceHtml;
            $payload['text'] = $priceHtml;
            $payload['plain_text'] = $priceText;
            $payload['code'] = $entry->sku ?: $entry->label ?: $priceText;
            $payload['sku'] = $entry->sku ?: $entry->label ?: $priceText;
            $payload['currency'] = $entry->currency;
            $payload['amount'] = $entry->price;
            $payload['old_amount'] = $entry->old_price;
            $payload['unit'] = $entry->price_unit;
        }

        return $payload;
    }

    private function messengerLink(ContactEntry $entry): string
    {
        $value = trim((string) $entry->value);
        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }

        return match ($entry->kind) {
            'telegram' => 'https://t.me/' . ltrim($value, '@'),
            'whatsapp' => 'https://wa.me/' . preg_replace('/\D+/', '', $value),
            'viber' => 'viber://chat?number=' . preg_replace('/\D+/', '', $value),
            'messenger' => str_starts_with($value, 'm.me/') ? 'https://' . $value : 'https://m.me/' . ltrim($value, '@/'),
            default => $value,
        };
    }

    private function pluginEndpoint(Site $site, string $route): string
    {
        $base = rtrim($this->serverReachableSiteUrl($site), '/');

        return $base . '/wp-json/databridge/v1/' . ltrim($route, '/');
    }

    private function serverReachableSiteUrl(Site $site): string
    {
        $url = trim((string) $site->url);
        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $host = strtolower((string) $parts['host']);
        if (! in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $url;
        }

        $scheme = $parts['scheme'] ?? 'http';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = rtrim($parts['path'] ?? '', '/');

        return "{$scheme}://host.docker.internal{$port}{$path}";
    }

    private function refreshSnapshot(Site $site): void
    {
        try {
            $snapshot = Http::timeout(8)
                ->acceptJson()
                ->withHeaders(['X-DataBridge-Key' => $site->api_key])
                ->get($this->pluginEndpoint($site, 'snapshot'));
        } catch (\Throwable) {
            $site->forceFill(['last_checked_at' => now()])->save();
            return;
        }

        $data = $snapshot->json();
        $site->forceFill([
            'wp_version' => $data['wordpress']['version'] ?? $site->wp_version,
            'php_version' => $data['wordpress']['php_version'] ?? $site->php_version,
            'status' => $snapshot->successful() ? 'active' : $site->status,
            'last_checked_at' => now(),
        ])->save();
    }
}
