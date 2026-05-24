# CLAUDE.md — DataBridge CRM v2

> Золоте правило проєкту. Кожен агент читає це першим.
> Помилки → §9 + `C:\Dev\ddbv2-vault\50-mistakes\<date>-<slug>.md`
> Brief: `docs\BRIEF.md` · Vault: `C:\Dev\ddbv2-vault\` · User-doc: `C:\Dev\ddbv2-vault\user-doc\`

---

## 0. Стек (незмінно)

- **Backend:** Laravel 12 + Livewire 3 + Alpine.js + Blade
- **Викидаємо:** React, Inertia, Ziggy
- **WP-інтеграція:** dead-drop · XChaCha20-Poly1305 + Ed25519 · per-site keys · R2/MinIO
- **Хостинг:** Docker Desktop локально → VPS за Tailscale (нуль публічних портів)
- **Runtime:** Windows 11 (без WSL)
- **Проєкт:** `C:\Dev\ddbv2\`
- **Vault:** `C:\Dev\ddbv2-vault\`
- **Worktrees:** `C:\Dev\worktrees\`
- **Git remote:** `https://github.com/mzalisko/dbb.git`

## 1. Правила роботи

1. **Plan Mode першим.** Без затвердженого плану від `planner` + `critic` — ніхто не починає.
2. **4–8 worktrees паралельно.** `.\scripts\new-worktree.ps1 <agent> <recipe-id>`
3. **Субагент = вузька роль.** Описи у `.claude\agents\*.md`
4. **Opus+thinking** для архітектури/безпеки. **Haiku** для Ralph-loops.
5. **Recipes у vault.** `C:\Dev\ddbv2-vault\30-tasks\<phase>\<id>.md`
6. **Cost guard:** Ralph `MAX_ITERATIONS=200`. Перевищення → пауза → scribe.
7. **Помилка → правило.** Scribe додає у §9 + vault `50-mistakes\`.

## 2. Захищені файли (sign-off critic)

- `app\Models\**`
- `app\Policies\**`
- `database\migrations\**`
- `database\seeders\**`
- `app\Services\WpFeed\Cipher.php` ← тільки `crypto-eng`
- `CLAUDE.md` ← тільки `scribe`

## 3. Команди (PowerShell)

```powershell
.\scripts\new-worktree.ps1 backend-eng P1-T01   # новий worktree
.\scripts\merge-worktree.ps1 wt-backend-eng-p1-t01  # merge
.\scripts\start-dev.ps1                          # запуск сервера
.\.ralph\icon-port\run.ps1                       # ralph loop
git worktree list                                # активні worktrees
docker compose exec app php artisan <cmd>        # artisan
docker compose logs -f app                       # логи
```

## 4. MCP сервери

```powershell
claude mcp add playwright -- npx -y @playwright/mcp@latest
claude mcp add obsidian -- npx -y mcp-remote http://localhost:22360/sse
claude mcp add github -- npx -y @modelcontextprotocol/server-github
```

## 5. Цикл фази

1. `planner` → recipes у vault
2. `critic` → APPROVED (max 2 раунди)
3. `orchestrator` → worktrees (≤4 паралельно)
4. worktree commit → merge → `tester` smoke
5. `scribe` → vault + kanban
6. `/clear` перед наступною фазою

## 6. Token economy (коротко)

| Роль | Модель |
|---|---|
| orchestrator, planner, critic, security-eng, crypto-eng, tester | opus |
| designer, backend-eng, frontend-eng | opus |
| devops, scribe | sonnet |
| ralph-loop (icons, mistakes) | haiku |
| ralph-loop (tests) | sonnet |

Повні правила: `C:\Dev\ddbv2-vault\70-token-economy\rules.md`

## 7. Структура

```
C:\Dev\ddbv2\
├── CLAUDE.md
├── docs\BRIEF.md
├── .claude\agents\        ← 12 ролей
├── .claude\commands\
├── .ralph\                ← loops
├── scripts\               ← *.ps1
└── [Laravel code]

C:\Dev\ddbv2-vault\        ← Obsidian vault
C:\Dev\worktrees\          ← git worktrees
```

## 8. Як користуватись vault

Відкрий **Obsidian → File → Open vault → `C:\Dev\ddbv2-vault`**  
Агенти читають через MCP `obsidian` (коли Obsidian запущений).  
Точка входу: `C:\Dev\ddbv2-vault\00-meta\index.md`

## 9. Lessons learned

> Кожна помилка → новий пункт. Що зробив → чому неправильно → правило.

- **`--max-usd` не існує в Claude Code CLI** → прапор відсутній. Правило: не додавай неіснуючих флагів.
- **Промпт вводити тільки після `>`** → спочатку `claude --agents ...`, чекай `>`, тоді вставляй. Правило: prompt іде в Claude, не в bash.
- **`spatie/laravel-auditing` не існує** → правильний пакет `owen-it/laravel-auditing`. Правило: перевіряй packagist.org.
- **`;` у PowerShell** → `wt cmd1 ; cmd2` не працює, потрібно `` wt cmd1 `; cmd2 ``. Правило: у PowerShell перед `;` для wt ставити backtick.

---

**Оновлення:** 2026-05-24 — Windows-native setup. Далі редагує тільки `scribe`.
