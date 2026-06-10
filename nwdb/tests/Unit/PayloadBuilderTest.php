<?php

namespace Nwdb\Tests\Unit;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nwdb\WpFeed\PayloadBuilder;
use Tests\TestCase;

class PayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    private PayloadBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new PayloadBuilder();
    }

    private function makeSiteWithEntries(): Site
    {
        $site = Site::factory()->create();

        $site->contactEntries()->create([
            'type' => 'phone', 'value' => '+380501112233', 'label' => 'Гаряча лінія',
            'role' => 'primary', 'geo_mode' => 'only', 'countries' => ['UA', 'PL'],
            'visible' => true, 'order' => 1,
        ]);
        $site->contactEntries()->create([
            'type' => 'messenger', 'kind' => 'telegram', 'value' => '@manager',
            'role' => 'primary', 'geo_mode' => 'all', 'visible' => true, 'order' => 1,
        ]);
        $site->contactEntries()->create([
            'type' => 'price', 'sku' => 'basic', 'label' => 'Базовий', 'value' => '999',
            'currency' => 'UAH', 'price' => 999.0, 'geo_mode' => 'all', 'visible' => true, 'order' => 1,
        ]);
        // Прихований запис не має потрапити у payload
        $site->contactEntries()->create([
            'type' => 'phone', 'value' => '+15550001122',
            'role' => 'hidden', 'geo_mode' => 'all', 'visible' => false, 'order' => 2,
        ]);

        return $site;
    }

    public function test_only_visible_entries_are_serialized(): void
    {
        $payload = $this->builder->build($this->makeSiteWithEntries(), 1);

        $this->assertSame(1, count($payload['contacts']['phones']));
        $this->assertSame(1, count($payload['contacts']['messengers']));
        $this->assertSame(1, count($payload['contacts']['prices']));
        $this->assertSame(3, $this->builder->entryCount($payload));
    }

    public function test_geo_rules_are_preserved(): void
    {
        $payload = $this->builder->build($this->makeSiteWithEntries(), 1);
        $phone = $payload['contacts']['phones'][0];

        $this->assertSame('only', $phone['geo_mode']);
        $this->assertSame(['UA', 'PL'], $phone['countries']);
        $this->assertSame('+380501112233', $phone['value']);
    }

    public function test_content_hash_is_deterministic_and_ignores_timestamp(): void
    {
        $site = $this->makeSiteWithEntries();

        $a = $this->builder->build($site, 1);
        $this->travel(5)->minutes();
        $b = $this->builder->build($site->fresh(), 2);

        $this->assertNotSame($a['generated_at'], $b['generated_at']);
        $this->assertSame($this->builder->contentHash($a), $this->builder->contentHash($b));
    }

    public function test_content_hash_changes_with_data(): void
    {
        $site = $this->makeSiteWithEntries();
        $a = $this->builder->build($site, 1);

        $site->contactEntries()->where('type', 'phone')->where('visible', true)->first()
            ->update(['value' => '+380679998877']);

        $b = $this->builder->build($site->fresh(), 1);

        $this->assertNotSame($this->builder->contentHash($a), $this->builder->contentHash($b));
    }

    public function test_canonical_json_round_trips(): void
    {
        $payload = $this->builder->build($this->makeSiteWithEntries(), 1);
        $json = $this->builder->toCanonicalJson($payload);

        $this->assertEquals($payload, json_decode($json, true));
        $this->assertStringContainsString('Гаряча лінія', $json); // JSON_UNESCAPED_UNICODE
    }
}
