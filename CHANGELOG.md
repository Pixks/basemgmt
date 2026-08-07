# Changelog

## [1.1.0] – 2026-08-07

Renamed plugin to **CampLink**.

### New features

#### Auth & security
- **Staff account lockout** — 3 failed attempts → configurable temp lock (default 15 min); 1 failure after temp-unlock → permanent lock. Admin panel unlocks with forced security code reset. (`RateLimiter` rewritten; `permanent_lock` column added to `bm_staff`)

#### Scheduling
- **Global day plan templates** — `bm_plan_templates` / `bm_plan_template_items` tables; full CRUD panel; "Apply template" widget inline on the plan edit page

#### Meals
- **Predefined diets & serving locations** — `bm_meal_diets` / `bm_meal_locations` tables; admin CRUD panel; selects with "Other – type manually" fallback (JS swaps field name on submit) in the meal item editor
- **Auto-add meal to daily plan** — checkbox on meal item save triggers plan entry creation for the meal's date

#### Notifications & reporting
- **Configurable e-mail notifications** — settings section for per-type recipient addresses; `bm_missing_report_emails` replaces hardcoded `admin_email` in `Scheduler::send_daily_reminders()`
- **Periodic staff count e-mail reports** — `bm_periodic_staff_report` WP-Cron event; configurable interval (`hourly` / `twicedaily` / `daily`) and recipients; `reschedule_staff_report()` called on settings save

#### Audit & transparency
- **Operation logs** — `bm_operation_logs` table; `OperationLogger` class with `ACTION_*` constants; logs logins, failures, unlocks, thread creation; filterable admin panel with optional log pruning

#### UX / admin
- **Create conversation thread from submission** — button on submission detail view; inserts `bm_conv_threads` + system `bm_conv_messages` row
- **Dashboard buttons moved to top** — action buttons rendered above data grids
- **Form builder help text** — `<p>` on every field; dynamic per-type hint shown via JS when type select changes
- **PDF / print export** — print-friendly full-HTML pages (camp headcounts, daily schedule, meal menu) opened in new tab with `window.print()` — no external library

### Bug fixes
- `DailyCountRepository::get_for_date()` → `get_by_date()` (non-existent method call in `PdfPage` and `Scheduler`)
- `OperationLogger::log()` argument order corrected in `FrontendAuth` (was passing `int $staff->id` as `string $action`)
- Duplicate "Last login" `<td>` removed from `staff/list.php` (header/body column mismatch)
- `PlanTemplatesPage::handle_apply()` error redirect now preserves `plan_id` context
