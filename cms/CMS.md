# CMS Implementation — Overview & Developer Guide

This document describes how the CMS backend (`hahu-back/cms`) and the admin frontend (`admin-app`) interact, where to find the key components, and how to operate and extend the system.

## High-level summary
- Backend: Laravel app at `hahu-back/cms` exposing API endpoints for content packs (admin and public), child subject preferences, recommendations and parent-assigned tasks.
- Admin UI: Next.js app at `admin-app` using `admin-app/lib/cmsApi.ts` to call backend admin endpoints and a few new pages for managing children, subjects and assigned tasks.

## Key backend files
- Routes: `routes/api.php` — public and admin routes. Notable additions:
  - `GET /api/subjects?child_id={id}` — get per-child subject status (no auth by default).
  - `PUT /api/subjects` — update per-child subject status.
  - `GET /api/content/packs`, `GET /api/content/packs/{slug}/manifest`, `GET /api/content/packs/{slug}/download` — content delivery routes protected by shared JWT auth (`aud=cms`, `scope=content:read`).
  - `GET /api/children/{child_id}/tasks/recommendations` — returns recommendations (requires `auth:sanctum`).
  - `POST /api/children/{child_id}/tasks/assign` — parent assigns a task to a child (requires `auth:sanctum`).
  - Admin helpers (protected by `auth:sanctum` + `admin` middleware):
    - `GET /api/admin/children` — list child ids with assignments.
    - `GET /api/admin/children/{child_id}/assigned-tasks` — list assigned tasks with content.

- Controllers:
  - `app/Http/Controllers/ContentPackController.php` and `ContentPackVersionController.php` — manage content packs and versions.
  - `app/Support/ContentPayloadFormatter.php` — normalizes legacy/misspelled payload keys into a consistent `payload.contents` structure. Important: it intentionally maps several legacy forms (e.g., `pronouncation` → `pronunciation`) and unifies voice/image/audio keys.
  - `app/Http/Controllers/SubjectController.php` — per-child subject status API; auto-initializes defaults for missing subjects.
  - `app/Http/Controllers/TaskRecommendationController.php` — recommendation logic and assign endpoint. Recommendation flow:
    1. Try external sync service (config `services.sync_service.url` or `SYNC_SERVICE_URL`) GET `/api/children/{child}/summaries/latest`.
    2. Fallback to local `daily_summaries` table if available.
    3. Final fallback: top active content per game type.

- Inter-service communication in CMS includes:
  - Child Service and Sync Service calls for recommendations/ownership checks.
  - Shared-JWT content delivery for other backend services that need to read CMS content packs.

## Database additions
- New migrations (files added):
  - `create_child_subjects_table` — stores per-child game type status (`child_subjects`).
  - `create_assigned_tasks_table` — stores parent-assigned tasks (`assigned_tasks`) with `content_id` FK → `content.id`.

## Admin frontend (`admin-app`) changes
- API client: `admin-app/lib/cmsApi.ts` now contains methods:
  - `getChildSubjects(childId, token)`, `updateChildSubjects(payload, token)`
  - `getRecommendations(childId, token)`, `assignTask(childId, data, token)`
  - `listAdminChildren(token)`, `getAssignedTasks(childId, token)`

- Pages (minimal scaffolds were added):
  - `/admin/children` — list / lookup children.
  - `/admin/children/[childId]/subjects` — list and toggle subjects (calls `PUT /api/subjects`).
  - `/admin/children/[childId]/tasks` — show recommendations and assigned tasks; allow assigning a recommended content.

## Auth and tokens
- Backend uses Laravel Sanctum for API authentication. The assign/recommendation endpoints are protected with `auth:sanctum` so the server can derive `assigned_by` from `request()->user()`.
- Content delivery routes use a shared HS256 JWT signed with `JWT_SECRET`. The token must include `aud=cms` and `scope=content:read`.
- Admin app stores the admin token in `localStorage` (key: `cms_admin_token`) via its login flow — existing login page uses `cmsApi.login` and stores token.

## Contracts — endpoints
- Content packs
  - Request: GET `/api/content/packs` (requires `Authorization: Bearer <shared-jwt>`)
  - Request: GET `/api/content/packs/{slug}/manifest` (requires the same shared JWT)
  - Request: GET `/api/content/packs/{slug}/download` (requires the same shared JWT)
  - Response: active, published content packs only

- Recommendations
  - Request: GET `/api/children/{child_id}/tasks/recommendations` (requires `Authorization: Bearer <token>`)
  - Response: 200 { recommendations: [ { game_type_id, content_id, title, reason } ] }

- Assign
  - Request: POST `/api/children/{child_id}/tasks/assign` (requires `Authorization: Bearer <token>`)
    - Body: { content_id: number, game_type_id: number, reason?: string }
  - Response: 201 { success: true, assigned_task: { id, child_id, content_id, game_type_id, status, assigned_by, assigned_at, reason } }

## Notable implementation details & recommendations
- Content normalization: `ContentPayloadFormatter` contains many backward-compatibility mappings. It's permissive but the key naming is inconsistent (spaces, typos). Consider normalizing keys to stable canonical names before storing.
- Game type mapping: `TaskRecommendationController::mapGameTypeToContentType()` maps numeric `game_type_id` → `content.type` strings. Verify that `content.type` values in DB match these strings; adjust map or seeders if mismatches occur.
- Auth boundaries: the `subjects` endpoints are public (no middleware) by design for parent-app usage; recommendations/assign require `auth:sanctum`. Confirm parent clients obtain and send Sanctum tokens correctly.
- Admin UI: the scaffolded pages are intentionally minimal. Consider adding:
  - search/pagination for children
  - audit trail for assignments
  - validations and confirmation modal before assigning

## How to operate locally
1. Apply migrations:
```bash
cd "hahu-back/cms"
php artisan migrate
php artisan db:seed   # if you want seeded content
```
2. Set environment variables:
- `APP_URL`, `DB_*`, and `SANCTUM_STATEFUL_DOMAINS` / `SESSION_DOMAIN` per Sanctum docs.
- `SYNC_SERVICE_URL` to enable recommendation via external sync service.
- `JWT_SECRET` to let other backend services read CMS content using shared JWTs.
3. Start admin app:
```bash
cd admin-app
npm install
npm run dev
```

## Where to improve / follow-ups
- Clean up and unify keys in `ContentPayloadFormatter` and add unit tests against representative legacy payloads.
- Add API docs (OpenAPI) for the new endpoints and example request/response bodies.
- Harden authorization (policies) for who can assign tasks and whether admins should see parent-only data.
- Add integration tests for recommendation and assign flows (mock sync service).

---

If you want, I can:
- convert the scaffolded admin UI pages into production-ready views (search, pagination, modals),
- add OpenAPI documentation for the CMS endpoints, or
- run migrations and smoke tests in the workspace now.
