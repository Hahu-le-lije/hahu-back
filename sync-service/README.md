# Sync Service

Sync Service is a Laravel backend that accepts gameplay sessions from the frontend and converts them into child-level literacy analytics.

It keeps a local copy of learning events, maintains daily and weekly summaries, and exposes analytics endpoints for frontend and AI consumers.

## What It Does

- Accepts frontend-submitted gameplay sessions at `POST /api/sessions`.
- Stores each accepted session as a local `learning_event`.
- Ignores duplicate session ids for aggregation so frontend retries do not double-count summaries.
- Aggregates events into daily and weekly literacy summaries.
- Calculates accuracy, consistency, skill diversity, and mastery score.
- Generates short human-readable explanations for literacy progress.
- Exposes API endpoints for frontend dashboards and AI feature export.
- Protects selected service-to-service endpoints with JWT authentication.

## Core Flow

1. The frontend posts `{ "sessions": [...] }` to `POST /api/sessions`.
2. The service normalizes each session into the `learning_events` table by stable `event_id`.
3. Newly created events dispatch `ProcessLearningEventJob`.
4. The job updates daily and weekly summaries for the child.
5. API consumers fetch the latest analytics summaries or AI export data.

## Main Concepts

### Learning Events

Learning events are raw gameplay records submitted by the frontend. They include:

- child ID
- game type
- content ID
- score
- time spent
- metrics such as total questions and correct answers
- skill breakdown
- original event timestamp
- ingestion timestamp

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
- SQLite by default for local development
- Firebase PHP-JWT for service-to-service JWTs
- Vite/Tailwind scaffold from the Laravel starter

## Important Files

- `routes/api.php` - ingestion, summary, and protected AI routes
- `app/Http/Controllers/Api/LearningSessionController.php` - frontend session ingestion endpoint
- `app/Services/LearningSessionIngestionService.php` - session normalization and idempotent persistence
- `app/Jobs/ProcessLearningEventJob.php` - queued aggregation job
- `app/Services/SummaryAggregationService.php` - daily and weekly aggregation logic
- `app/Analytics/LiteracyAnalyticsService.php` - analytics formulas
- `app/Services/ExplanationGeneratorService.php` - summary explanation text
- `app/Services/ServiceJwtService.php` - service JWT generation and validation
- `app/Http/Middleware/VerifyServiceJwt.php` - protected route middleware
- `docs/API.md` - frontend-facing API reference
- `docs/DATABASE.md` - database schema reference

## Environment

Copy the example environment and set local values:

```bash
cp .env.example .env
php artisan key:generate
```

The service-specific values used by this app are:

```env
SYNC_SERVICE_SECRET=
AI_SERVICE_SECRET=
```

`SYNC_SERVICE_SECRET` and `AI_SERVICE_SECRET` are used for service-to-service JWT validation.

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

## Container / Google Cloud Run

This service includes a production Dockerfile suitable for Cloud Run. The container serves Laravel through Apache, uses `public/` as the document root, builds Vite assets during the image build, and listens on Cloud Run's `PORT` environment variable.

Build locally:

```bash
docker build -t sync-service .
docker run --rm -p 8080:8080 --env-file .env sync-service
```

Deploy with Google Cloud Build and Cloud Run:

```bash
gcloud builds submit --tag gcr.io/PROJECT_ID/sync-service
gcloud run deploy sync-service \
  --image gcr.io/PROJECT_ID/sync-service \
  --region REGION \
  --allow-unauthenticated
```

Set production values with Cloud Run environment variables or Secret Manager. At minimum, configure `APP_KEY`, `APP_URL`, database settings, `SYNC_SERVICE_SECRET`, and `AI_SERVICE_SECRET`.

The HTTP Cloud Run service should not be relied on to run the Laravel queue worker continuously. Run queue work with a dedicated worker/job process such as `php artisan queue:work --tries=1`.

## Queues

Accepted sessions dispatch `ProcessLearningEventJob`, so the queue worker must be running for summaries to update asynchronously:

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
- The session ingestion and regular summary endpoints are currently unprotected.
- The AI feature snapshot endpoint is protected by `service.jwt`.
