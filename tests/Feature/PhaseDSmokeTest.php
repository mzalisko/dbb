<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PhaseDSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'owner']);
    }

    // ─── Security headers ────────────────────────────────────────────────────

    public function test_security_headers_present_on_authenticated_page(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_csp_header_present_on_authenticated_page(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertHeader('Content-Security-Policy');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_security_headers_present_on_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    // ─── Auth guard ──────────────────────────────────────────────────────────

    public function test_all_crm_routes_require_auth(): void
    {
        $routes = [
            '/dashboard', '/clients', '/sites', '/team',
            '/activity', '/settings', '/data', '/groups',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/login');
        }
    }

    // ─── Login rate limiting ─────────────────────────────────────────────────

    public function test_login_throttled_after_five_attempts(): void
    {
        $email = 'nobody@example.com';
        $throttleKey = strtolower($email).'|127.0.0.1';
        RateLimiter::clear($throttleKey);

        $payload = ['email' => $email, 'password' => 'wrong-password'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $payload);
        }

        $response = $this->post('/login', $payload);
        $response->assertStatus(429);
    }

    // ─── Session security ────────────────────────────────────────────────────

    public function test_session_serialization_is_json(): void
    {
        $this->assertSame('json', config('session.serialization'));
    }

    public function test_session_http_only_is_enabled(): void
    {
        $this->assertTrue((bool) config('session.http_only'));
    }

    public function test_session_same_site_is_lax_or_strict(): void
    {
        $sameSite = config('session.same_site');
        $this->assertContains($sameSite, ['lax', 'strict']);
    }

    // ─── CSRF protection ────────────────────────────────────────────────────

    public function test_login_page_has_csrf_meta_tag(): void
    {
        $response = $this->get('/login');
        $response->assertSee('name="csrf-token"', false);
    }

    public function test_authenticated_page_has_csrf_meta_tag(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertSee('name="csrf-token"', false);
    }

    // ─── Rate limiter config ─────────────────────────────────────────────────

    public function test_login_rate_limiter_is_registered(): void
    {
        $this->assertNotNull(RateLimiter::limiter('login'));
    }

    public function test_forgot_password_rate_limiter_is_registered(): void
    {
        $this->assertNotNull(RateLimiter::limiter('forgot-password'));
    }
}
