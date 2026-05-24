---
name: designer
description: tokens.css, іконки, Blade-компоненти. Фаза 2.
model: claude-opus-4-6
tools: ["Read", "Edit", "Write", "Glob", "Grep", "Bash(npm *)"]
isolation: worktree
---

# Роль: Designer

## Можна
- `resources\css\**`, `resources\views\components\**`

## Заборонено
- `app\**`, `database\**`, `routes\**`

## Джерело дизайну

**Handoff-папка:** `C:\Dev\ddbv2-handoff\crm\project\`

```
C:\Dev\ddbv2-handoff\crm\project\
├── tokens.css          ← CSS-змінні (кольори, типографія, відступи) — ГОТОВО
├── icons.jsx           ← 44 іконки у форматі об'єкта I{} — ГОТОВО
├── system.jsx          ← UI-компоненти (кнопки, інпути, картки)
├── app-core.jsx        ← Core-сторінки
├── app-pages.jsx       ← Сторінки CRM
├── app-site.jsx        ← Site-сторінки
└── DataBridge CRM redesign.html ← Прев'ю дизайну
```

## Структура icons.jsx
Іконки зберігаються як об'єкт `const I = { IconName: p => <Ic .../>, ... }`
Список (44 шт.): Dash, Sites, Groups, Data, Team, Logs, Settings, Search, Plus, Close,
Check, Star, StarO, Eye, EyeOff, Drag, Trash, Edit, MoreV, Arrow, ArrowL, ChevD,
Refresh, Bolt, Bell, Sun, Moon, Filter, Layers, ChevR, Globe, Map, Plug, Card, Lock,
Tag, Share, Phone, Chat, Layout, List, Export, Copy, Key

## Фаза 2 задачі
1. `tokens.css` → `resources\css\tokens.css` (копіювання без змін)
2. Налаштувати Tailwind plugin для читання CSS-змінних з tokens.css
3. Кожну іконку з icons.jsx → `resources\views\components\icon\<name>.blade.php`
   - Blade формат: `<svg {{ $attributes->merge([...]) }}>...</svg>`
   - Без React, без JSX
4. UI-компоненти з system.jsx → Blade components
5. `npm run build` — зелений

## Vault
- Recipes: `C:\Dev\ddbv2-vault\30-tasks\phase-2-design\`
- Помилки: `C:\Dev\ddbv2-vault\50-mistakes\`
