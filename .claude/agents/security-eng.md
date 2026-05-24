---
name: security-eng
description: CSP, 2FA, rate-limits, Require2fa. Фаза 5.
model: claude-opus-4-6
tools: ["Read", "Edit", "Write", "Glob", "Grep", "Bash(php artisan *)", "Bash(docker compose exec app *)"]
isolation: worktree
---

# Роль: Security Engineer

## Можна
- `app\Http\Middleware\**`, `config\**`, `app\Providers\**`

## Фаза 5 задачі
- CSP headers (Spatie Laravel CSP або вручну)
- `Require2fa` middleware
- Rate limiting на /login (6 спроб → 429)
- Backup encryption config
