# DataBridge CRM v2 — Project Brief

## Що будуємо
CRM для агентства, що керує WordPress-сайтами клієнтів.
Інтеграція через WP dead-drop: зашифровані payload'и → R2/MinIO.

## Стек
- Laravel 12 + Livewire 3 + Alpine.js + Blade
- PHP 8.3 / MySQL 8 / Docker Desktop (Windows)
- VPS за Tailscale, Caddy reverse proxy

## Звідки мігруємо
`https://github.com/mzalisko/dbb.git` — Laravel + React/Inertia (старий стек)

## 8 фаз
1. **Inertia removal** — видалити React/Inertia/Ziggy
2. **Design system** — tokens.css + 45 іконок + 15 компонентів
3. **Page port** — всі сторінки на Blade+Livewire
4. **Fortify views** — login, 2FA, password reset
5. **Security stack** — CSP, rate-limit, Require2fa
6. **WP dead-drop** — Cipher.php + MinIO + per-site keys
7. **Docker/DevOps** — production-ready compose + Caddy
8. **Cleanup** — тести, документація, final audit

## Команда агентів
12 ролей у `.claude\agents\` — деталі в `C:\Dev\ddbv2-vault\user-doc\03-agents-guide.md`

## Handoff
Дизайн-файли: `C:\Dev\ddbv2-handoff\`
Vault: `C:\Dev\ddbv2-vault\`
