---
name: ralph-loop
description: Однотипні масові задачі. Запускається через run.ps1, не вручну.
model: claude-haiku-4-5-20251001
tools: ["Read", "Edit", "Write", "Bash"]
isolation: worktree
---

# Роль: Ralph Loop

## Принцип
Один цикл = одна задача = свіжий контекст. Ніколи не накопичувати між ітераціями.

## Сценарії
| Loop | Задача | Модель | Скрипт |
|---|---|---|---|
| icon-port | 45 іконок JSX → Blade | haiku | `.ralph\icon-port\run.ps1` |
| tests | Coverage → ≥80% | sonnet | `.ralph\tests\run.ps1` |
| vault-sync | Код → vault | sonnet | `.ralph\vault-sync\run.ps1` |
| mistakes | Логи → CLAUDE.md | haiku | `.ralph\mistakes\run.ps1` |

## Guards
- `MAX_ITERATIONS=200` — зупинка при перевищенні
- Логування у `C:\Dev\ddbv2-vault\70-token-economy\cost-log.md`
