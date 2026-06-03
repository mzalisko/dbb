<?php

namespace Tests\Feature;

use App\Livewire\Users\Index as UsersIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A temporary access password is additive: an admin/manager can sign in as a user
 * with it while the user keeps their own password. It expires, and any real
 * password change wipes it.
 */
class TempPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function target(string $real = 'real-password-123'): User
    {
        return User::factory()->create([
            'role' => 'viewer',
            'email' => 'target@example.com',
            'password' => $real,
        ]);
    }

    public function test_generating_temp_password_keeps_the_real_one(): void
    {
        $user = $this->target();
        $user->setTemporaryPassword('Temp-9999-Pass', now()->addHours(48));
        $user->refresh();

        $this->assertTrue(Hash::check('real-password-123', $user->password), 'real password preserved');
        $this->assertNotNull($user->temp_password);
        $this->assertTrue($user->checkTempPassword('Temp-9999-Pass'));
        $this->assertTrue($user->temp_password_expires_at->isFuture());
    }

    public function test_user_can_log_in_with_their_real_password(): void
    {
        $user = $this->target();
        $user->setTemporaryPassword('Temp-9999-Pass');

        $this->post('/login', ['email' => 'target@example.com', 'password' => 'real-password-123']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_log_in_with_the_temp_password(): void
    {
        $user = $this->target();
        $user->setTemporaryPassword('Temp-9999-Pass');

        $this->post('/login', ['email' => 'target@example.com', 'password' => 'Temp-9999-Pass']);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_log', [
            'action' => 'auth.temp_login',
            'user_id' => $user->id,
        ]);
    }

    public function test_expired_temp_password_is_rejected(): void
    {
        $user = $this->target();
        $user->setTemporaryPassword('Temp-9999-Pass');
        $user->forceFill(['temp_password_expires_at' => now()->subMinute()])->save();

        $this->post('/login', ['email' => 'target@example.com', 'password' => 'Temp-9999-Pass']);

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->target();

        $this->post('/login', ['email' => 'target@example.com', 'password' => 'nope']);

        $this->assertGuest();
    }

    public function test_changing_the_real_password_wipes_the_temp_one(): void
    {
        $admin = User::factory()->create(['role' => 'owner']);
        $user = $this->target();
        $user->setTemporaryPassword('Temp-9999-Pass');

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('viewUser', $user->id)
            ->set('changingPassword', true)
            ->set('newPassword', 'BrandNew-123')
            ->set('confirmPassword', 'BrandNew-123')
            ->call('saveUser');

        $user->refresh();
        $this->assertNull($user->temp_password, 'temp password cleared on real change');
        $this->assertNull($user->temp_password_expires_at);
        $this->assertTrue(Hash::check('BrandNew-123', $user->password));

        // The old temp credential must no longer authenticate.
        $this->assertFalse($user->checkTempPassword('Temp-9999-Pass'), 'old temp credential is dead');
    }
}
