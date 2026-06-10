<?php

namespace Nwdb\WpFeed;

use App\Models\ContactEntry;
use App\Models\Site;

/**
 * Серіалізує видимі ContactEntry сайту в канонічний JSON-payload v1.
 * Гео-правила (geo_mode + countries) їдуть у payload — плагін фільтрує
 * сам, віддзеркалюючи ContactEntry::visibleForGeo().
 *
 * Порядок ключів і записів фіксований, тому contentHash() детермінований
 * для однакових даних і використовується для дедупу публікацій.
 */
class PayloadBuilder
{
    public const SCHEMA_VERSION = 1;

    public function build(Site $site, int $version): array
    {
        $entries = $site->contactEntries()
            ->where('visible', true)
            ->orderBy('type')
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return [
            'v'            => self::SCHEMA_VERSION,
            'pv'           => $version,
            'generated_at' => now()->toIso8601ZuluString(),
            'contacts'     => $this->contacts($entries),
        ];
    }

    public function toCanonicalJson(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** Детермінований хеш вмісту (без generated_at/pv) для дедупу. */
    public function contentHash(array $payload): string
    {
        return hash('sha256', $this->toCanonicalJson(['v' => $payload['v'], 'contacts' => $payload['contacts']]));
    }

    public function entryCount(array $payload): int
    {
        return collect($payload['contacts'])->sum(fn ($group) => count($group));
    }

    private function contacts($entries): array
    {
        $byType = $entries->groupBy('type');

        return [
            'phones'     => $byType->get('phone', collect())->map(fn ($e) => $this->contactItem($e))->values()->all(),
            'messengers' => $byType->get('messenger', collect())->map(fn ($e) => $this->contactItem($e))->values()->all(),
            'prices'     => $byType->get('price', collect())->map(fn ($e) => $this->priceItem($e))->values()->all(),
        ];
    }

    private function contactItem(ContactEntry $e): array
    {
        return [
            'id'        => $e->id,
            'kind'      => $e->kind,
            'label'     => $e->label,
            'value'     => $e->value,
            'role'      => $e->role,
            'geo_mode'  => $e->geo_mode,
            'countries' => array_values($e->countries ?? []),
            'order'     => $e->order,
            'parent'    => $e->parent_id,
        ];
    }

    private function priceItem(ContactEntry $e): array
    {
        return [
            'id'         => $e->id,
            'sku'        => $e->sku,
            'label'      => $e->label,
            'currency'   => $e->currency,
            'price'      => $e->price,
            'old_price'  => $e->old_price,
            'price_unit' => $e->price_unit,
            'geo_mode'   => $e->geo_mode,
            'countries'  => array_values($e->countries ?? []),
            'order'      => $e->order,
        ];
    }
}
