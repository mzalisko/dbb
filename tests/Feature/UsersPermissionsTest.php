<?php

namespace Tests\Feature;

use App\Livewire\Users\Index;
use App\Models\User;
use App\Services\AuditFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UsersPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_a_member_permission_is_audited(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $target = User::factory()->create(['role' => 'viewer']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('viewUser', $target->id)
            ->call('togglePerm', 'sites', 'create')
            ->call('saveUser');

        $event = AuditFeed::collect(['domain' => 'user'])
            ->first(fn ($e) => $e->subjectId === $target->id && $e->old !== []);

        $this->assertNotNull($event, 'a member permission change must be audited');
        $this->assertTrue(
            collect($event->humanChanges())->contains('field', 'Дозволи'),
            'and shown as a readable permission diff'
        );
    }

    public function test_owner_can_remove_an_admin(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($owner)->test(Index::class)->call('removeUser', $admin->id);

        $this->assertNull(User::find($admin->id), 'owner (super user) can delete an admin');
    }

    public function test_admin_cannot_manage_another_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(Index::class)
            ->call('removeUser', $other->id)
            ->assertStatus(403);

        $this->assertNotNull(User::find($other->id), 'an admin must not delete another admin');
    }

    public function test_nobody_can_remove_the_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'owner']);

        Livewire::actingAs($admin)->test(Index::class)
            ->call('removeUser', $owner->id)
            ->assertStatus(403);

        $this->assertNotNull(User::find($owner->id), 'the owner is untouchable');
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('viewUser', $admin->id)
            ->call('selectRole', 'viewer')
            ->call('saveUser');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_can_change_another_users_role(): void
    {
        $admin  = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'viewer']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('viewUser', $target->id)
            ->call('selectRole', 'manager')
            ->call('saveUser');

        $this->assertSame('manager', $target->fresh()->role);
    }

    public function test_custom_permission_matrix_is_stored_only_when_it_diverges(): void
    {
        $admin  = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'manager']);

        // Saving the manager unchanged keeps permissions null (equals role defaults).
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('viewUser', $target->id)
            ->call('saveUser');

        $this->assertNull($target->fresh()->permissions);

        // Toggling a permission away from the manager defaults persists the override.
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('viewUser', $target->id)
            ->call('togglePerm', 'api', 'read')
            ->call('saveUser');

        $target->refresh();
        $this->assertIsArray($target->permissions);
        $this->assertTrue($target->permissions['api']['read']);
    }

    public function test_limited_scope_persists_group_and_site_access(): void
    {
        $admin  = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'manager']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('viewUser', $target->id)
            ->call('setAccessScope', 'limited')
            ->call('toggleGroup', 'Europe')
            ->call('saveUser');

        $target->refresh();
        $this->assertSame('limited', $target->access_scope);
        $this->assertContains('Europe', $target->group_access);
    }

    public function test_admin_can_generate_temporary_password_for_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'viewer']);

        $component = Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('viewUser', $target->id)
            ->call('generateTemporaryPassword')
            ->assertSet('changingPassword', false);

        $password = $component->get('generatedPassword');

        $this->assertNotSame('', $password);

        // The generated value is an additive *temporary* credential — the user's
        // real password must stay intact, the temp one must match and be live.
        $fresh = $target->fresh();
        $this->assertTrue(Hash::check('password', $fresh->password), 'real password untouched');
        $this->assertTrue($fresh->checkTempPassword($password), 'temp password is live');
    }
}
