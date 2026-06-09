<?php

namespace Tests\Feature;

use App\Livewire\DataBrowser;
use App\Livewire\Sites\Show;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSocialAddressTest extends TestCase
{
    use RefreshDatabase;

    private function ownerSite(array $attrs = []): array
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create($attrs);

        return [$user, $site];
    }

    public function test_social_can_be_added_with_a_platform(): void
    {
        [$user, $site] = $this->ownerSite();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addEntry', 'social')
            ->assertSet('entryType', 'social')
            ->assertSet('entryKind', 'facebook') // first platform, picker not blank
            ->set('entryKind', 'instagram')
            ->set('entryValue', 'https://instagram.com/brand')
            ->set('entryLabel', 'Бренд')
            ->call('saveEntry');

        $this->assertDatabaseHas('contact_entries', [
            'site_id' => $site->id,
            'type'    => 'social',
            'kind'    => 'instagram',
            'value'   => 'https://instagram.com/brand',
            'role'    => 'primary',
        ]);
    }

    public function test_social_requires_platform_and_value(): void
    {
        [$user, $site] = $this->ownerSite();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addEntry', 'social')
            ->set('entryKind', '')
            ->set('entryValue', '')
            ->call('saveEntry')
            ->assertHasErrors(['entryKind', 'entryValue']);
    }

    public function test_address_can_be_added(): void
    {
        [$user, $site] = $this->ownerSite();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addEntry', 'address')
            ->assertSet('entryType', 'address')
            ->set('entryValue', 'вул. Тестова 1, Київ')
            ->set('entryLabel', 'Головний офіс')
            ->call('saveEntry');

        $this->assertDatabaseHas('contact_entries', [
            'site_id' => $site->id,
            'type'    => 'address',
            'value'   => 'вул. Тестова 1, Київ',
            'role'    => 'primary',
        ]);
    }

    public function test_social_and_kind_filter_surface_in_data_browser(): void
    {
        // Type tabs follow enabled site categories, so the site must have socials on.
        [$user, $site] = $this->ownerSite(['data_categories' => ['phones', 'messengers', 'socials']]);
        ContactEntry::factory()->for($site)->social('instagram')->create(['value' => 'IG-PROFILE']);
        ContactEntry::factory()->for($site)->social('facebook')->create(['value' => 'FB-PAGE']);

        Livewire::actingAs($user)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'social', 'kindFilter' => 'instagram'])
            ->assertSee('Соцмережі')      // the type tab is registered
            ->assertSee('IG-PROFILE')
            ->assertDontSee('FB-PAGE');   // kind sub-filter scopes the list
    }

    public function test_address_type_surfaces_in_data_browser(): void
    {
        [$user, $site] = $this->ownerSite(['data_categories' => ['phones', 'messengers', 'addresses']]);
        ContactEntry::factory()->for($site)->address()->create(['value' => 'MY-ADDRESS-STREET']);

        Livewire::actingAs($user)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'address'])
            ->assertSee('Адреси')
            ->assertSee('MY-ADDRESS-STREET');
    }

    public function test_address_reads_geo_label_directly_no_reserve_inheritance(): void
    {
        [$user, $site] = $this->ownerSite();
        $addr = ContactEntry::factory()->for($site)->address()->create([
            'geo_mode' => 'only', 'countries' => ['PL'],
        ]);

        // Plain primary (no parent) → owns its own geo.
        $this->assertStringContainsString('PL', $addr->geo_label);
        $this->assertTrue($addr->visibleForGeo('PL'));
        $this->assertFalse($addr->visibleForGeo('UA'));
    }

    public function test_disabling_a_category_with_rows_trashes_them_after_confirm(): void
    {
        [$user, $site] = $this->ownerSite(['data_categories' => ['phones', 'messengers', 'prices']]);
        $price = ContactEntry::factory()->for($site)->price()->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('toggleDataCategory', 'prices')
            ->assertSet('confirmingAction', true)            // непорожня → питає
            ->assertSet('confirmAction', 'disable-category')
            ->assertSet('confirmCategory', 'prices')
            ->call('confirmPendingAction')
            ->assertSet('confirmingAction', false);

        $this->assertSoftDeleted('contact_entries', ['id' => $price->id]);
        $this->assertNotContains('prices', $site->fresh()->data_categories);
    }

    public function test_disabling_an_empty_category_skips_confirm(): void
    {
        [$user, $site] = $this->ownerSite(['data_categories' => ['phones', 'messengers', 'prices']]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('toggleDataCategory', 'prices')
            ->assertSet('confirmingAction', false);          // порожня → одразу

        $this->assertNotContains('prices', $site->fresh()->data_categories);
    }
}
