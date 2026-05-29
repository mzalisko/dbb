<?php

namespace Tests\Feature;

use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsersPermissionsTest extends TestCase
{
    use RefreshDatabase;

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
}
