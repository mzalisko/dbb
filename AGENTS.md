# AGENTS.md — DataBridge CRM v2

> Золоте правило проєкту. Кожен агент читає це першим.
> Помилки → §9 + `C:\Dev\ddbv2-vault\50-mistakes\<date>-<slug>.md`
> Brief: `docs\BRIEF.md` · Vault: `C:\Dev\ddbv2-vault\` · User-doc: `C:\Dev\ddbv2-vault\user-doc\`

---

## 0. Стек (незмінно)

- **Backend:** Laravel 13 + Livewire 3 + Alpine.js + Blade
- **Викидаємо:** React, Inertia, Ziggy
- **WP-інтеграція:** dead-drop · XChaCha20-Poly1305 + Ed25519 · per-site keys · R2/MinIO
- **Хостинг:** Docker Desktop локально → VPS за Tailscale (нуль публічних портів)
- **Runtime:** Windows 11 (без WSL)
- **Проєкт:** `C:\Dev\ddbv2\`
- **Vault:** `C:\Dev\ddbv2-vault\`
- **Worktrees:** `C:\Dev\worktrees\`
- **Git remote:** `https://github.com/mzalisko/dbb.git`

## 1. Правила роботи

1. **Plan Mode першим.** Без затвердженого плану від `@planner` + `@critic` — ніхто не починає.
2. **4–8 worktrees паралельно.** `.\scripts\new-worktree.ps1 <agent> <recipe-id>`
3. **Субагент = вузька роль.** Описи у `.Codex\agents\*.md` (8 агентів).
4. **Opus** для архітектури/безпеки/коду. **Sonnet** для vault/keeper.
5. **Recipes у vault.** `C:\Dev\ddbv2-vault\30-tasks\<phase>\<id>.md`
6. **Помилка → правило.** `@keeper` додає у §9 + vault `50-mistakes\`.

## 2. Захищені файли (sign-off critic)

- `app\Models\**`
- `app\Policies\**`
- `database\migrations\**`
- `database\seeders\**`
- `app\Services\WpFeed\Cipher.php` ← тільки `crypto-eng`
- `AGENTS.md` ← тільки `scribe`

## 3. Команди (PowerShell)

```powershell
.\scripts\new-worktree.ps1 backend PL-T01    # новий worktree для @backend
.\scripts\new-worktree.ps1 ui PH-T01         # новий worktree для @ui
.\scripts\merge-worktree.ps1 wt-backend-pl-t01  # merge
.\scripts\start-dev.ps1                       # запуск сервера
git worktree list                             # активні worktrees
docker compose exec app php artisan <cmd>     # artisan
docker compose logs -f app                    # логи
```

## 4. MCP сервери

```powershell
Codex mcp add playwright -- npx -y @playwright/mcp@latest
Codex mcp add obsidian -- npx -y mcp-remote http://localhost:22360/sse
Codex mcp add github -- npx -y @modelcontextprotocol/server-github
```

## 5. Цикл фази (8-агентний pipeline)

```
@planner → @critic → @team-lead → @ui / @backend / @tester → @reviewer → @keeper
```

1. `@planner` → recipes у vault (`30-tasks/<phase>/<id>.md`)
2. `@critic` → APPROVED або NEEDS_REVISION (max 2 раунди)
3. `@team-lead` → запускає worktrees паралельно (≤4)
4. `@ui` / `@backend` / `@tester` → виконують recipes у worktrees
5. `@reviewer` → APPROVED або правки (1 раунд)
6. `@keeper` → vault sync + git commit/push + kanban
7. `/clear` перед наступною фазою

## 6. Token economy (коротко)

| Агент | Модель |
|---|---|
| @planner, @critic, @team-lead | opus |
| @ui, @backend, @tester, @reviewer | opus |
| @keeper | sonnet |
| ralph-loop | haiku / sonnet |

Повні правила: `C:\Dev\ddbv2-vault\70-token-economy\rules.md`

## 7. Структура

```
C:\Dev\ddbv2\
├── AGENTS.md
├── docs\BRIEF.md
├── .Codex\agents\        ← 8 агентів (planner, critic, team-lead, ui, backend, tester, reviewer, keeper)
├── .Codex\commands\
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

- **`--max-usd` не існує в Codex CLI** → прапор відсутній. Правило: не додавай неіснуючих флагів.
- **Промпт вводити тільки після `>`** → спочатку `Codex --agents ...`, чекай `>`, тоді вставляй. Правило: prompt іде в Codex, не в bash.
- **`spatie/laravel-auditing` не існує** → правильний пакет `owen-it/laravel-auditing`. Правило: перевіряй packagist.org.
- **`;` у PowerShell** → `wt cmd1 ; cmd2` не працює, потрібно `` wt cmd1 `; cmd2 ``. Правило: у PowerShell перед `;` для wt ставити backtick.

---

**Оновлення:** 2026-05-24 — Laravel 12 → 13 (P1-T00). 2026-05-26 — 8-агентний pipeline (planner→critic→team-lead→ui/backend/tester→reviewer→keeper). Далі редагує тільки `@keeper`.
