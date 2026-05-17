# Database Schemas

This service stores frontend-submitted gameplay sessions, derives child-level literacy summaries from those sessions, and keeps operational state for queues, cache, sessions, and service authentication.

The major service-owned schemas are:

- `learning_events` - raw gameplay sessions submitted by the frontend.
- `daily_summaries` - per-child, per-day analytics rollups.
- `weekly_summaries` - per-child, per-week analytics rollups.

Laravel also owns supporting tables for queues, cache, sessions, users, password resets, and Sanctum personal access tokens.

## Data Flow

1. The frontend posts `{ "sessions": [...] }` to the session ingestion endpoint.
2. Each submitted session is normalized and inserted into `learning_events` by stable `event_id`.
3. Duplicate `event_id` values are accepted but do not dispatch aggregation again.
4. A `ProcessLearningEventJob` is dispatched for each newly created event.
5. The job updates one row in `daily_summaries` and one row in `weekly_summaries` for the event's child and event date/week.
6. API endpoints read from summaries for dashboard responses and from `learning_events` for AI event export.

There are no database-level foreign keys between these domain tables. `child_id` is an external string identifier owned by the broader child/account domain.

## `learning_events`

Raw frontend-submitted gameplay records. This table is the local event ledger for analytics and AI export.

Source migration: `database/migrations/2026_05_10_100217_create_learning_events_table.php`  
Model: `app/Models/LearningEvent.php`

| Column | Type | Required | Notes |
| --- | --- | --- | --- |
| `id` | bigint | yes | Internal primary key. |
| `event_id` | string | yes | Stable client-provided session/event ID. Unique. |
| `child_id` | string | yes | External child identifier. Indexed. |
| `game_type` | string | yes | Game/category that produced the event. |
| `content_id` | string | yes | Upstream content identifier. |
| `score` | integer | yes | Raw score supplied by the frontend session payload. |
| `time_spent` | integer | yes | Seconds spent in the session/event. |
| `metrics` | jsonb | no | Flexible event metrics. Aggregation currently reads `total_questions` and `correct_answers`. Cast to array by Eloquent. |
| `skill_breakdown` | jsonb | no | Flexible skill-level values keyed by skill name. Cast to array by Eloquent. |
| `event_created_at` | timestamp | yes | Original session/event creation time. Used for daily and weekly grouping. |
| `last_updated` | timestamp | yes | Client-provided update timestamp or server ingestion time. Indexed. |
| `synced_at` | timestamp | no | Time this service accepted the event. |
| `created_at` | timestamp | no | Laravel row creation timestamp. |
| `updated_at` | timestamp | no | Laravel row update timestamp. |

Indexes and constraints:

- Primary key: `id`.
- Unique: `event_id`.
- Index: `child_id`.
- Index: `last_updated`.
- Composite index: `child_id`, `event_created_at`.

Usage notes:

- Ingestion uses `firstOrCreate(['event_id' => ...], ...)`, so repeated frontend retries with the same session id do not create a second local row.
- The AI event export endpoint returns up to 1000 rows per child ordered by `event_created_at`, optionally filtered with `event_created_at >= since`.
- Summary aggregation is additive. To prevent retry double-counting, duplicate `event_id` submissions are not reprocessed.

## `daily_summaries`

Per-child analytics rollups for one calendar date.

Source migration: `database/migrations/2026_05_10_100226_create_daily_summaries_table.php`  
Model: `app/Models/DailySummary.php`

| Column | Type | Required | Notes |
| --- | --- | --- | --- |
| `id` | bigint | yes | Internal primary key. |
| `child_id` | string | yes | External child identifier. Indexed. |
| `summary_date` | date | yes | Date bucket derived from `learning_events.event_created_at`. Cast to date by Eloquent. |
| `total_sessions` | integer | yes | Number of events counted for the child/date. Defaults to `0`. |
| `total_questions` | integer | yes | Sum of `metrics.total_questions`. Defaults to `0`. |
| `correct_answers` | integer | yes | Sum of `metrics.correct_answers`. Defaults to `0`. |
| `accuracy` | float | yes | Stored as a `0` to `1` ratio. API resources return percentage values. |
| `time_spent` | integer | yes | Sum of event `time_spent`, in seconds. Defaults to `0`. |
| `consistency` | float | yes | Stored as a `0` to `1` ratio. Currently set to placeholder `1.0` during aggregation. |
| `skill_diversity` | float | yes | Stored as a `0` to `1` ratio. Currently calculated as `count(skill_breakdown) / 10` for the processed event. |
| `mastery_score` | float | yes | Stored as a `0` to `1` ratio. Weighted score from accuracy, consistency, and skill diversity. |
| `generated_explanation` | text | no | Human-readable progress explanation. |
| `algorithm_version` | integer | yes | Summary algorithm version. Defaults to `1`. |
| `created_at` | timestamp | no | Laravel row creation timestamp. |
| `updated_at` | timestamp | no | Laravel row update timestamp. |

Indexes and constraints:

- Primary key: `id`.
- Index: `child_id`.
- Unique: `child_id`, `summary_date`.

Aggregation behavior:

- Rows are created with `firstOrCreate(['child_id' => ..., 'summary_date' => ...])`.
- `accuracy = correct_answers / total_questions`, rounded to four decimal places.
- `mastery_score = (0.5 * accuracy) + (0.3 * consistency) + (0.2 * skill_diversity)`, rounded to four decimal places.
- Latest daily summary endpoints order by `summary_date` descending.
- AI feature snapshots average the latest 10 daily rows and compute trend/stability values from stored ratios.

## `weekly_summaries`

Per-child analytics rollups for one week.

Source migration: `database/migrations/2026_05_10_100250_create_weekly_summaries_table.php`  
Model: `app/Models/WeeklySummary.php`

| Column | Type | Required | Notes |
| --- | --- | --- | --- |
| `id` | bigint | yes | Internal primary key. |
| `child_id` | string | yes | External child identifier. Indexed. |
| `week_start_date` | date | yes | Week start derived with Carbon `startOfWeek()` from `event_created_at`. Cast to date by Eloquent. |
| `week_end_date` | date | yes | Week end derived with Carbon `endOfWeek()` from `event_created_at`. Cast to date by Eloquent. |
| `total_sessions` | integer | yes | Number of events counted for the child/week. Defaults to `0`. |
| `total_questions` | integer | yes | Sum of `metrics.total_questions`. Defaults to `0`. |
| `correct_answers` | integer | yes | Sum of `metrics.correct_answers`. Defaults to `0`. |
| `accuracy` | float | yes | Stored as a `0` to `1` ratio. API resources return percentage values. |
| `time_spent` | integer | yes | Sum of event `time_spent`, in seconds. Defaults to `0`. |
| `consistency` | float | yes | Stored as a `0` to `1` ratio. Currently set to placeholder `1.0` during aggregation. |
| `skill_diversity` | float | yes | Stored as a `0` to `1` ratio. Currently calculated as `count(skill_breakdown) / 10` for the processed event. |
| `mastery_score` | float | yes | Stored as a `0` to `1` ratio. Weighted score from accuracy, consistency, and skill diversity. |
| `generated_explanation` | text | no | Human-readable progress explanation. |
| `algorithm_version` | integer | yes | Summary algorithm version. Defaults to `1`. |
| `created_at` | timestamp | no | Laravel row creation timestamp. |
| `updated_at` | timestamp | no | Laravel row update timestamp. |

Indexes and constraints:

- Primary key: `id`.
- Index: `child_id`.
- Unique: `child_id`, `week_start_date`.

Aggregation behavior:

- Rows are created with `firstOrCreate(['child_id' => ..., 'week_start_date' => ...], ['week_end_date' => ...])`.
- Metrics use the same formulas as `daily_summaries`.
- Latest weekly summary endpoints order by `week_start_date` descending.
- Week boundaries follow the runtime Carbon locale/configuration default unless explicitly configured elsewhere.

## Laravel Operational Tables

These tables come from Laravel or first-party packages and support application infrastructure rather than the literacy analytics domain.

### Queue Tables

Source migration: `database/migrations/0001_01_01_000002_create_jobs_table.php`

- `jobs` stores queued jobs for the database queue connection.
- `job_batches` stores Laravel batch metadata.
- `failed_jobs` stores failed queue payloads and exceptions.

`ProcessLearningEventJob` uses this queue path when the queue connection is configured as `database`.

### Cache Tables

Source migration: `database/migrations/0001_01_01_000001_create_cache_table.php`

- `cache` stores cache entries by key.
- `cache_locks` stores cache lock ownership and expiration.

### Auth And Session Tables

Source migrations:

- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2026_05_10_102157_create_personal_access_tokens_table.php`

Tables:

- `users` is the default Laravel user table.
- `password_reset_tokens` stores password reset tokens by email.
- `sessions` stores database-backed session payloads.
- `personal_access_tokens` is the Laravel Sanctum token table.

Current API docs describe service JWT authentication for protected service-to-service routes. Sanctum tables exist in the schema, but the documented API surface does not currently rely on browser-issued Sanctum tokens.

## Ratio And Percentage Conventions

The database stores analytics scores as ratios:

- `accuracy`: `0` to `1`
- `consistency`: `0` to `1`
- `skill_diversity`: `0` to `1`
- `mastery_score`: `0` to `1`

Frontend summary resources multiply these values by `100` before returning them. AI feature snapshot responses expose the stored ratio values directly.
