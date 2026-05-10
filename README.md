# Sync Service

Sync Service is a Laravel backend that imports gameplay events from the Game Service and converts them into child-level literacy analytics.

It keeps a local copy of learning events, maintains daily and weekly summaries, and exposes analytics endpoints for frontend and AI consumers.

## What It Does

- Pulls gameplay events from the Game Service on a schedule.
- Stores each event as a local `learning_event`.
- Aggregates events into daily and weekly literacy summaries.
- Calculates accuracy, consistency, skill diversity, and mastery score.
- Generates short human-readable explanations for literacy progress.
- Exposes API endpoints for frontend dashboards and AI feature export.
- Protects selected service-to-service endpoints with JWT authentication.

## Core Flow

1. Laravel scheduler runs `sync:game-events` every minute.
2. `GameServiceClient` requests new or updated events from the Game Service.
3. Events are upserted into the `learning_events` table by external `event_id`.
4. Each synced event dispatches `ProcessLearningEventJob`.
5. The job updates daily and weekly summaries for the child.
6. API consumers fetch the latest analytics summaries or AI export data.

## Main Concepts

### Learning Events

Learning events are raw gameplay records imported from the Game Service. They include:

- child ID
- game type
- content ID
- score
- time spent
- metrics such as total questions and correct answers
- skill breakdown
- original event timestamp
- sync timestamp

### Daily Summaries

Daily summaries aggregate a child's activity for a single date. They track total sessions, questions, correct answers, accuracy, time spent, consistency, skill diversity, mastery score, and an explanation.

### Weekly Summaries

Weekly summaries aggregate the same analytics over a week. The service currently uses Carbon's `startOfWeek()` and `endOfWeek()` behavior for week boundaries.

### AI Feature Snapshot

The AI snapshot endpoint summarizes the latest daily records into a compact feature set, including average accuracy, average mastery score, trend values, consistency, skill diversity, and learning stability.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Queue
- Laravel Scheduler
- SQLite by default for local development
- Firebase PHP-JWT for service-to-service JWTs
- Vite/Tailwind scaffold from the Laravel starter

## Important Files

- `routes/api.php` - public and protected API routes
- `routes/console.php` - scheduled sync command
- `app/Console/Commands/PullGameEvents.php` - imports events from Game Service
- `app/Integrations/GameService/GameServiceClient.php` - Game Service HTTP client
- `app/Jobs/ProcessLearningEventJob.php` - queued aggregation job
- `app/Services/SummaryAggregationService.php` - daily and weekly aggregation logic
- `app/Analytics/LiteracyAnalyticsService.php` - analytics formulas
- `app/Services/ExplanationGeneratorService.php` - summary explanation text
- `app/Services/ServiceJwtService.php` - service JWT generation and validation
- `app/Http/Middleware/VerifyServiceJwt.php` - protected route middleware
- `docs/API.md` - frontend-facing API reference

## Environment

Copy the example environment and set local values:

```bash
cp .env.example .env
php artisan key:generate
```

The service-specific values used by this app are:

```env
GAME_SERVICE_URL=
GAME_SERVICE_TOKEN=

SYNC_SERVICE_SECRET=
GAME_SERVICE_SECRET=
AI_SERVICE_SECRET=
```

`GAME_SERVICE_URL` and `GAME_SERVICE_TOKEN` are used when pulling events from the Game Service.

`SYNC_SERVICE_SECRET`, `GAME_SERVICE_SECRET`, and `AI_SERVICE_SECRET` are used for service-to-service JWT validation.

## Local Setup

Install dependencies:

```bash
composer install
npm install
```

Run migrations:

```bash
php artisan migrate
```

Start the development stack:

```bash
composer run dev
```

The `dev` script starts:

- Laravel server
- queue listener
- Laravel Pail logs
- Vite dev server

## Running the Sync Manually

```bash
php artisan sync:game-events
```

The scheduled version is defined in `routes/console.php` and runs every minute when the Laravel scheduler is active.

## Queues

Synced events dispatch `ProcessLearningEventJob`, so the queue worker must be running for summaries to update asynchronously:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

The default local queue connection is configured as `database`.

## API Documentation

Frontend developers should use:

```text
docs/API.md
```

It documents available endpoints, response shapes, error responses, and authentication expectations.

## Current Notes

- The summary formulas are intentionally simple and may evolve.
- Consistency is currently stored as a placeholder value of `1.0` during aggregation.
- Skill diversity is currently calculated from the event skill breakdown count divided by `10`.
- The regular summary endpoints are currently unprotected.
- The AI feature snapshot endpoint is protected by `service.jwt`.
- Starter Laravel example tests are still present; service-specific tests should be added as the API stabilizes.
