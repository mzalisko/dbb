<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use App\Support\AuditAction;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Auth events involve no model CRUD, so owen-it never sees them — log them
 * explicitly into activity_log. No password or credential secret is ever stored;
 * only the attempted identifier on failures.
 */
class LogAuthenticationEvents
{
    public function handleLogin(Login $event): void
    {
        // Stamp last activity for the Team page. saveQuietly + last_login_at is in
        // $auditExclude, so this never creates a spurious user.updated audit.
        if ($event->user) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        }

        ActivityLogService::log('auth.login', $event->user, context: 'web', user: $event->user);
    }

    public function handleLogout(Logout $event): void
    {
        ActivityLogService::log('auth.logout', $event->user, context: 'web', user: $event->user);
    }

    public function handleFailed(Failed $event): void
    {
        ActivityLogService::log('auth.login_failed', null, [
            'email' => $event->credentials['email'] ?? null,
        ], severity: AuditAction::CRITICAL, context: 'web');
    }

    public function handleLockout(Lockout $event): void
    {
        ActivityLogService::log('auth.lockout', null, [
            'email' => $event->request?->input('email'),
        ], severity: AuditAction::CRITICAL, context: 'web');
    }
}
