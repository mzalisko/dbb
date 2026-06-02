<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class AuditSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_update_is_audited_without_the_secret(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->update(['password' => Hash::make('newsecret123'), 'name' => 'Renamed']);

        $audit = Audit::where('auditable_type', User::class)->where('auditable_id', $user->id)->latest('id')->first();
        $this->assertNotNull($audit);

        $blob = json_encode([$audit->old_values, $audit->new_values]);
        $this->assertStringNotContainsString('password', $blob);
        $this->assertStringNotContainsString('newsecret123', $blob);
        $this->assertStringNotContainsString('remember_token', $blob);
        // The non-secret change is still there.
        $this->assertStringContainsString('Renamed', $blob);
    }

    public function test_force_fill_password_save_does_not_leak(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->forceFill(['password' => Hash::make('topsecret456')])->save();

        $audit = Audit::where('auditable_type', User::class)->where('auditable_id', $user->id)->latest('id')->first();
        if ($audit) {
            $blob = json_encode([$audit->old_values, $audit->new_values]);
            $this->assertStringNotContainsString('password', $blob);
            $this->assertStringNotContainsString('topsecret456', $blob);
        }
        $this->assertTrue(true); // a no-diff save may produce no audit — that's fine
    }
}
