# PRESERVED — DataBridge CRM v2

> Збережено: 2026-05-27. Читати перед стартом кожної сесії.

---

## Agent Pipeline (Ralph Loop)

| Агент | Роль | Output |
|---|---|---|
| **Architect** | Проєктує структуру модулів, БД схему, API контракти | `docs/architecture/{module}.md` |
| **Builder** | Пише код за специфікацією Architect (SRP: один файл = одна відповідальність) | PHP/Blade/JS файли |
| **Reviewer** | Безпека, PSR-12, оптимізація. Блокує merge при критичних порушеннях | `docs/reviews/{timestamp}.md` |
| **Tester** | E2E тести Playwright для кожної завершеної сторінки | `tests/e2e/{page}.spec.js` |
| **Vault Keeper** | Контекст < 40% → snapshot в `storage/context/snapshot_{timestamp}.json` | JSON snapshot |

**Ralph Loop цикл:** PLAN → BUILD → REVIEW → TEST → VERIFY → (next page)

---

## Практики оптимізації

- Eager loading (уникати N+1)
- Redis кешування (TTL по типу даних)
- Queue Jobs для bulk операцій (100+ сайтів)
- Chunked processing при масових оновленнях
- DB indexing: `site_id`, `group_id`, `field_type`
- API response compression (gzip)
- Lazy loading для великих датасетів
- Pagination скрізь

---

## Правила безпеки (незмінні)

- **Без 2FA, без підтвердження email** (явна вимога — не змінювати)
- Auth: Laravel Sanctum з API токенами
- WP плагін не знає про існування CRM — pull модель
- WP плагін отримує дані через анонімний endpoint з підписаним токеном
- Токен: HMAC-SHA256, ротація кожні 30 днів
- Rate limiting на всіх API endpoints
- CORS: тільки whitelist доменів
- SQL: виключно prepared statements / Eloquent
- XSS: htmlspecialchars + CSP headers
- Env: тільки через .env
- Логи: не писати паролі/токени
- Middleware auth на всіх захищених маршрутах
- Role-based: Admin > Manager

---

## Vault Keeper Protocol

При СТАРТІ сесії:
1. Перевір `storage/context/snapshot_*.json`
2. Якщо є — завантаж останній за датою
3. Виведи: поточний модуль, останній файл, наступний крок, кількість завершених файлів
4. Продовж звідси

При роботі (після кожного файлу):
- Якщо контекст > 40% → зберегти snapshot → стиснути контекст

Snapshot формат:
```json
{
  "session_date": "...",
  "current_module": "...",
  "current_phase": "...",
  "completed_files": [],
  "next_file": "...",
  "next_step": "...",
  "open_questions": [],
  "important_decisions": [],
  "db_changes_pending": [],
  "test_status": {}
}
```

---

## MVP Scope

**Включає:**
- Авторизація (login/logout)
- Управління сайтами (CRUD)
- Управління групами сайтів
- Дані: телефони та месенджери
- Bulk операції
- API endpoint для WP плагіна
- Базові логи
- Дашборд (базова статистика)

**Після MVP (не торкатись):** ціни, адреси, соцмережі, кастомні поля, детальні логи, управління користувачами, розширений дашборд.

---

## DB Schema (MVP)

```sql
users        — id, name, email, password, role (admin|manager), timestamps
sites        — id, name, url, token, group_id, status, last_sync_at, timestamps
site_groups  — id, name, description, timestamps
data_fields  — id, name, type (phone|messenger|...), label, timestamps
data_values  — id, site_id, field_id, value, timestamps
activity_logs — id, user_id, site_id, action, old_value, new_value, ip, timestamps
```
