<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4SmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulate browser-like POST with Sec-Fetch-Site header.
     * Laravel 13 PreventRequestForgery middleware allows same-origin requests.
     */
    protected function browserPost(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['Sec-Fetch-Site' => 'same-origin'])
            ->post($uri, $data);
    }

    // --- Auth views render (GET) ---

    public function test_login_page_returns_200(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_register_is_disabled(): void
    {
        // Public self-registration is off (team CRM, invite-only via /team).
        $this->get('/register')->assertStatus(404);
    }

    public function test_forgot_password_page_returns_200(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_confirm_password_page_returns_200(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/user/confirm-password')
            ->assertStatus(200);
    }

    // --- Login flow ---

    public function test_login_form_submits_successfully(): void
    {
        // Factory default password is 'password' (Hash::make('password'))
        $user = User::factory()->create();

        $this->browserPost('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->browserPost('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors();
    }

    public function test_login_page_has_email_and_password_fields(): void
    {
        $response = $this->get('/login');
        $content = $response->getContent();

        $response->assertStatus(200);
        $this->assertStringContainsString('type="email"', $content);
        $this->assertStringContainsString('type="password"', $content);
    }

    public function test_login_page_has_remember_me_checkbox(): void
    {
        $content = $this->get('/login')->getContent();

        $this->assertStringContainsString('name="remember"', $content);
        $this->assertStringContainsString('Remember me', $content);
    }

    public function test_login_page_has_forgot_password_link(): void
    {
        $content = $this->get('/login')->getContent();

        $this->assertStringContainsString('Forgot password?', $content);
        $this->assertStringContainsString('forgot-password', $content);
    }

    // --- Registration flow (disabled — invite-only team CRM) ---

    public function test_register_post_is_disabled(): void
    {
        $this->browserPost('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'securepassword1',
            'password_confirmation' => 'securepassword1',
        ])->assertStatus(404);

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    // --- Guest redirect ---

    public function test_guest_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_redirected_from_protected_pages(): void
    {
        $pages = ['/dashboard', '/clients', '/sites', '/team', '/settings'];
        foreach ($pages as $page) {
            $this->get($page)
                ->assertRedirect('/login');
        }
    }

    // --- Authenticated user can access protected pages ---

    public function test_authenticated_user_accesses_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200);
    }

    public function test_authenticated_user_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    // --- Logout ---

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders(['Sec-Fetch-Site' => 'same-origin'])
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_authenticated_layout_has_logout_button(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('method="POST"', false)
            ->assertSee('/logout', false)
            ->assertSee('title="Вийти"', false);
    }

    // --- Design system / layout ---

    public function test_login_view_uses_guest_layout(): void
    {
        $content = $this->get('/login')->getContent();

        // Guest layout wraps auth views and includes app name
        $this->assertStringContainsString(config('app.name'), $content);
    }

    public function test_login_page_has_design_system_button(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('btn', false);
    }

    // --- Confirm password ---

    public function test_confirm_password_page_has_password_field(): void
    {
        $user = User::factory()->create();

        $content = $this->actingAs($user)
            ->get('/user/confirm-password')
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('type="password"', $content);
        $this->assertStringContainsString('Confirm', $content);
    }

    // --- Rate limiting ---

    public function test_login_rate_limited_after_five_attempts(): void
    {
        $user = User::factory()->create();

        // Exhaust the 5-per-minute limit with wrong passwords
        for ($i = 0; $i < 5; $i++) {
            $this->browserPost('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->browserPost('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    // --- Forgot password form ---

    public function test_forgot_password_page_has_email_field(): void
    {
        $content = $this->get('/forgot-password')->getContent();

        $this->assertStringContainsString('type="email"', $content);
        $this->assertStringContainsString('Send Reset Link', $content);
    }
}
