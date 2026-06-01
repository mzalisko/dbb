<?php

namespace Tests\Feature;

use App\Livewire\Sites\Show;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteContactEntriesTest extends TestCase
{
    use RefreshDatabase;

    private function ownerSite(array $attrs = []): array
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create($attrs);

        return [$user, $site];
    }

    /** Task 1: global numbers (all/except) are never an isolation conflict. */
    public function test_global_numbers_are_not_flagged_as_isolation_conflicts(): void
    {
        [$user, $site] = $this->ownerSite(['geo_tabs' => ['UA', 'RU', 'BY']]);

        $global = ContactEntry::factory()->for($site)->phone()->create([
            'value' => 'GLOBAL', 'geo_mode' => 'all', 'countries' => null,
        ]);
        $except = ContactEntry::factory()->for($site)->phone()->create([
            'value' => 'EXCEPT', 'geo_mode' => 'except', 'countries' => ['PL'],
        ]);
        $restricted = ContactEntry::factory()->for($site)->phone()->create([
            'value' => 'ONLY-UA-RU', 'geo_mode' => 'only', 'countries' => ['UA', 'RU'],
        ]);

        $component = Livewire::actingAs($user)->test(Show::class, ['site' => $site]);
        $conflictIds = $component->viewData('conflictPhoneIds');

        // Default isolation rule UA ↔ RU·BY: only the explicitly restricted UA+RU number clashes.
        $this->assertContains($restricted->id, $conflictIds);
        $this->assertNotContains($global->id, $conflictIds);
        $this->assertNotContains($except->id, $conflictIds);
    }

    /** Task 2.1: a messenger reserve inherits its primary's platform. */
    public function test_messenger_reserve_inherits_primary_kind_via_add_flow(): void
    {
        [$user, $site] = $this->ownerSite();
        $primary = ContactEntry::factory()->for($site)->messenger('telegram')->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addEntry', 'messenger', $primary->id)
            ->assertSet('entryRole', 'backup')
            ->assertSet('entryKind', 'telegram')
            ->set('entryValue', '@reserve_handle')
            ->call('saveEntry');

        $this->assertDatabaseHas('contact_entries', [
            'site_id'   => $site->id,
            'type'      => 'messenger',
            'kind'      => 'telegram',
            'role'      => 'backup',
            'parent_id' => $primary->id,
            'value'     => '@reserve_handle',
        ]);
    }

    /** Task 2.1: even a tampered kind cannot diverge from the primary's platform. */
    public function test_messenger_reserve_kind_cannot_diverge_from_primary(): void
    {
        [$user, $site] = $this->ownerSite();
        $primary = ContactEntry::factory()->for($site)->messenger('telegram')->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addEntry', 'messenger', $primary->id)
            ->set('entryKind', 'viber')        // attempt to switch platform
            ->set('entryValue', '+480000000')
            ->call('saveEntry');

        $reserve = ContactEntry::where('parent_id', $primary->id)->first();
        $this->assertNotNull($reserve);
        $this->assertSame('telegram', $reserve->kind);
    }

    /** Task 2.1: model-level saves also normalize messenger reserve platform. */
    public function test_messenger_reserve_kind_is_normalized_on_model_save(): void
    {
        [, $site] = $this->ownerSite();
        $primary = ContactEntry::factory()->for($site)->messenger('telegram')->create();

        $reserve = ContactEntry::factory()->for($site)->messenger('viber')->create([
            'role' => 'backup',
            'parent_id' => $primary->id,
        ]);

        $this->assertSame('telegram', $reserve->fresh()->kind);
    }

    /** Task 2: a primary messenger can be created with a chosen platform. */
    public function test_primary_messenger_can_be_added(): void
    {
        [$user, $site] = $this->ownerSite();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addEntry', 'messenger')
            ->set('entryKind', 'whatsapp')
            ->set('entryValue', '+48111222333')
            ->call('saveEntry');

        $this->assertDatabaseHas('contact_entries', [
            'site_id' => $site->id,
            'type'    => 'messenger',
            'kind'    => 'whatsapp',
            'role'    => 'primary',
            'value'   => '+48111222333',
        ]);
    }

    /** Task 2: messenger without a platform fails validation. */
    public function test_messenger_requires_a_platform(): void
    {
        [$user, $site] = $this->ownerSite();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addEntry', 'messenger')
            ->set('entryValue', '@no_platform')
            ->call('saveEntry')
            ->assertHasErrors(['entryKind']);
    }

    /** Task 2: clicking edit on a messenger actually opens the drawer (was silently broken). */
    public function test_editing_a_messenger_opens_the_drawer(): void
    {
        [$user, $site] = $this->ownerSite();
        $msg = ContactEntry::factory()->for($site)->messenger('whatsapp')->create([
            'value' => '+48123456789', 'label' => 'Підтримка',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('editEntry', $msg->id)
            ->assertSet('editingEntry', true)
            ->assertSet('entryType', 'messenger')
            ->assertSet('entryKind', 'whatsapp')
            ->assertSee('Редагувати месенджер')
            ->assertSee('+48123456789');
    }

    /** Task 3: optional categories can be toggled and persist; required ones are protected. */
    public function test_data_categories_toggle_and_persist(): void
    {
        [$user, $site] = $this->ownerSite();

        $component = Livewire::actingAs($user)->test(Show::class, ['site' => $site])
            ->assertSet('dataCategories', ['phones', 'messengers', 'prices']);

        // Turn prices off
        $component->call('toggleDataCategory', 'prices')
            ->assertSet('dataCategories', ['phones', 'messengers']);
        $this->assertSame(['phones', 'messengers'], $site->fresh()->data_categories);

        // Turn addresses on (canonical order preserved)
        $component->call('toggleDataCategory', 'addresses')
            ->assertSet('dataCategories', ['phones', 'messengers', 'addresses']);

        // Required category cannot be removed
        $component->call('toggleDataCategory', 'phones')
            ->assertSet('dataCategories', ['phones', 'messengers', 'addresses']);
    }

    /** Task 3: persisted categories rehydrate on mount, with required ones forced in. */
    public function test_data_categories_rehydrate_with_required_forced(): void
    {
        [$user, $site] = $this->ownerSite(['data_categories' => ['prices']]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->assertSet('dataCategories', ['phones', 'messengers', 'prices']);
    }

    /** Task: messenger platform tabs can be managed per-site. */
    public function test_messenger_kind_tabs_can_be_added_and_removed(): void
    {
        [$user, $site] = $this->ownerSite();
        ContactEntry::factory()->for($site)->messenger('telegram')->create();

        $component = Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->assertSet('messengerKinds', ['telegram']);

        $component->set('newMessengerKind', 'Viber')
            ->call('addMessengerKind')
            ->assertSet('messengerKinds', ['telegram', 'viber']);

        $this->assertSame(['telegram', 'viber'], $site->fresh()->messenger_kinds);

        $component->call('removeMessengerKind', 'telegram')
            ->assertSet('messengerKinds', ['viber']);

        $this->assertSame(['viber'], $site->fresh()->messenger_kinds);

        $component->set('newMessengerKind', 'Custom Chat')
            ->call('addMessengerKind')
            ->assertSet('messengerKinds', ['viber', 'custom-chat']);

        $this->assertSame(['viber', 'custom-chat'], $site->fresh()->messenger_kinds);
    }

    public function test_messenger_kind_tab_removal_requires_confirmation(): void
    {
        [$user, $site] = $this->ownerSite(['messenger_kinds' => ['telegram', 'messenger']]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('requestRemoveMessengerKind', 'messenger')
            ->assertSet('confirmingAction', true)
            ->assertSet('confirmAction', 'remove-messenger-kind')
            ->assertSet('confirmMessengerKind', 'messenger')
            ->assertSet('messengerKinds', ['telegram', 'messenger'])
            ->call('confirmPendingAction')
            ->assertSet('confirmingAction', false)
            ->assertSet('messengerKinds', ['telegram']);

        $this->assertSame(['telegram'], $site->fresh()->messenger_kinds);
    }

    public function test_custom_messenger_kind_can_be_used_when_creating_entry(): void
    {
        [$user, $site] = $this->ownerSite(['messenger_kinds' => ['max']]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->assertSee('Max')
            ->call('addEntry', 'messenger')
            ->set('entryKind', 'max')
            ->set('entryValue', '@max_support')
            ->call('saveEntry')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('contact_entries', [
            'site_id' => $site->id,
            'type' => 'messenger',
            'kind' => 'max',
            'value' => '@max_support',
        ]);
    }

    public function test_custom_messenger_kind_renders_short_badge(): void
    {
        [$user, $site] = $this->ownerSite(['messenger_kinds' => ['max']]);
        ContactEntry::factory()->for($site)->messenger('max')->create(['value' => '@max_support']);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->assertSee('MA')
            ->assertDontSee('??');
    }

    /** Task: clicking edit on a price opens the drawer (was silently broken). */
    public function test_editing_a_price_opens_the_drawer(): void
    {
        [$user, $site] = $this->ownerSite();
        $price = ContactEntry::factory()->for($site)->price()->create([
            'label' => 'Преміум', 'sku' => 'SKU-7',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('editEntry', $price->id)
            ->assertSet('editingEntry', true)
            ->assertSet('entryType', 'price')
            ->assertSee('Редагувати ціну')
            ->assertSee('SKU-7');
    }

    /** Task: a price can be added through the drawer. */
    public function test_price_can_be_added(): void
    {
        [$user, $site] = $this->ownerSite();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addEntry', 'price')
            ->assertSet('entryCurrency', 'EUR')
            ->set('entryLabel', 'Базовий')
            ->set('entrySku', 'SKU-100')
            ->set('entryPrice', 100)
            ->set('entryCurrency', 'PLN')
            ->call('saveEntry');

        $this->assertDatabaseHas('contact_entries', [
            'site_id'  => $site->id,
            'type'     => 'price',
            'sku'      => 'SKU-100',
            'currency' => 'PLN',
            'role'     => 'primary',
        ]);
    }

    public function test_price_variant_can_be_added_from_existing_sku_group(): void
    {
        [$user, $site] = $this->ownerSite();
        ContactEntry::factory()->for($site)->price()->create([
            'label' => 'Підписка Standard',
            'sku' => 'WAVE-01',
            'price_unit' => '/міс',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addPriceToSku', 'WAVE-01')
            ->assertSet('addingEntry', true)
            ->assertSet('entryType', 'price')
            ->assertSet('entrySku', 'WAVE-01')
            ->assertSet('entryLabel', 'Підписка Standard')
            ->assertSet('entryPriceUnit', '/міс');
    }

    /** Task: a price requires sku + amount + currency. */
    public function test_price_requires_sku_and_amount(): void
    {
        [$user, $site] = $this->ownerSite();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('addEntry', 'price')
            ->set('entrySku', '')
            ->call('saveEntry')
            ->assertHasErrors(['entryPrice', 'entrySku']);
    }
}
