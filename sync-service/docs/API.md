# Sync Service API

This document is for frontend developers integrating with Sync Service.

Base URL depends on the environment. In local development, Laravel commonly runs at:

```text
http://localhost:8000
```

All API paths below are prefixed with `/api`.

## Data Conventions

- `child_id` is a string identifier owned by the broader child/account domain.
- `time_spent` is measured in seconds.
- Summary percentages are returned as `0` to `100` values.
- Raw AI feature snapshot values are returned as `0` to `1` ratios.
- Dates are returned using Laravel's JSON serialization for date fields.
- Missing summaries return `404` where the endpoint expects one latest summary.

## Authentication

The frontend session ingestion and summary endpoints are currently public inside the service boundary. Session ingestion accepts an `Authorization` header if the frontend provides one, but this service does not currently validate browser-issued child tokens.

The AI feature snapshot endpoint is protected by service JWT middleware:

```http
Authorization: Bearer <token>
```

The token must be generated from the service-auth secrets configured for this backend and must use:

```json
{
  "aud": "sync-service"
}
```

Coordinate with the backend team before calling protected AI routes from a frontend client. These routes are intended for service-to-service traffic, not browser-issued user tokens.

## Endpoints

### Submit Learning Sessions

```http
POST /api/sessions
```

Compatibility alias:

```http
POST /api/learning-events
```

Accepts gameplay sessions from the frontend and stores them as learning events. Newly created events dispatch summary aggregation work; duplicate session ids are accepted without dispatching again so frontend retries do not double-count summaries.

#### Request Body

```json
{
  "sessions": [
    {
      "id": "session_001",
      "childId": "child_123",
      "gameType": "phonics",
      "contentId": "lesson_abc",
      "score": 80,
      "timeSpent": 300,
      "totalQuestions": 10,
      "correctAnswers": 8,
      "skillBreakdown": {
        "letter_sounds": 0.8,
        "blending": 0.7
      },
      "createdAt": "2026-05-10T10:15:00Z"
    }
  ]
}
```

#### Accepted Session Fields

The service accepts both camelCase and snake_case names for common fields.

| Stored Field | Accepted Input Fields | Required | Notes |
| --- | --- | --- | --- |
| `event_id` | `event_id`, `eventId`, `session_id`, `sessionId`, `id` | Yes | Stable unique id for idempotent retries. |
| `child_id` | `child_id`, `childId` | Yes | Child identifier from the child/account domain. |
| `game_type` | `game_type`, `gameType`, `type` | Yes | Game/category that produced the session. |
| `content_id` | `content_id`, `contentId`, `lesson_id`, `lessonId` | Yes | Content or lesson identifier. |
| `score` | `score` | No | Defaults to `0`. |
| `time_spent` | `time_spent`, `timeSpent`, `duration_seconds`, `durationSeconds`, `duration_ms`, `durationMs` | No | Stored in seconds. Milliseconds are converted. Defaults to `0`. |
| `metrics` | `metrics`, plus top-level `totalQuestions`/`correctAnswers` aliases | No | Stored as flexible JSON. |
| `skill_breakdown` | `skill_breakdown`, `skillBreakdown`, `skills` | No | Stored as flexible JSON. |
| `event_created_at` | `event_created_at`, `eventCreatedAt`, `created_at`, `createdAt`, `completed_at`, `completedAt`, `ended_at`, `endedAt`, `timestamp` | No | Defaults to server time when absent or invalid. |
| `last_updated` | `last_updated`, `lastUpdated`, `updated_at`, `updatedAt` | No | Defaults to server time. |

#### Success Response

Status: `202 Accepted`

```json
{
  "message": "Learning sessions accepted.",
  "accepted": 1,
  "created": 1,
  "duplicates": 0,
  "event_ids": ["session_001"],
  "errors": []
}
```

If a batch has both valid and invalid sessions, valid sessions are accepted and validation details are returned in `errors`. If no sessions are accepted, the endpoint returns Laravel validation errors with status `422`.

### Get Latest Daily Summary

```http
GET /api/children/{childId}/daily-summary
```

Returns the latest daily analytics summary for a child.

#### Path Parameters

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `childId` | string | yes | Child identifier. |

#### Example Request

```bash
curl http://localhost:8000/api/children/child_123/daily-summary
```

#### Success Response

```json
{
  "data": {
    "child_id": "child_123",
    "summary_date": "2026-05-10T00:00:00.000000Z",
    "total_sessions": 4,
    "total_questions": 40,
    "correct_answers": 32,
    "accuracy": 80,
    "time_spent": 1200,
    "consistency": 100,
    "skill_diversity": 30,
    "mastery_score": 76,
    "generated_explanation": "Strong literacy performance with steady improvement.",
    "algorithm_version": 1
  }
}
```

#### Error Response

```json
{
  "message": "No daily summary found."
}
```

Status: `404`

### Get Latest Weekly Summary

```http
GET /api/children/{childId}/weekly-summary
```

Returns the latest weekly analytics summary for a child.

#### Path Parameters

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `childId` | string | yes | Child identifier. |

#### Example Request

```bash
curl http://localhost:8000/api/children/child_123/weekly-summary
```

#### Success Response

```json
{
  "data": {
    "child_id": "child_123",
    "week_start_date": "2026-05-04T00:00:00.000000Z",
    "week_end_date": "2026-05-10T00:00:00.000000Z",
    "total_sessions": 18,
    "total_questions": 160,
    "correct_answers": 128,
    "accuracy": 80,
    "time_spent": 5400,
    "consistency": 100,
    "skill_diversity": 40,
    "mastery_score": 78,
    "generated_explanation": "Strong literacy performance with steady improvement.",
    "algorithm_version": 1
  }
}
```

#### Error Response

```json
{
  "message": "No weekly summary found."
}
```

Status: `404`

### Get Analytics Overview

```http
GET /api/children/{childId}/analytics-overview
```

Returns the latest daily and weekly summaries together. Either value can be `null`.

#### Path Parameters

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `childId` | string | yes | Child identifier. |

#### Example Request

```bash
curl http://localhost:8000/api/children/child_123/analytics-overview
```

#### Success Response

```json
{
  "daily_summary": {
    "child_id": "child_123",
    "summary_date": "2026-05-10T00:00:00.000000Z",
    "total_sessions": 4,
    "total_questions": 40,
    "correct_answers": 32,
    "accuracy": 80,
    "time_spent": 1200,
    "consistency": 100,
    "skill_diversity": 30,
    "mastery_score": 76,
    "generated_explanation": "Strong literacy performance with steady improvement.",
    "algorithm_version": 1
  },
  "weekly_summary": {
    "child_id": "child_123",
    "week_start_date": "2026-05-04T00:00:00.000000Z",
    "week_end_date": "2026-05-10T00:00:00.000000Z",
    "total_sessions": 18,
    "total_questions": 160,
    "correct_answers": 128,
    "accuracy": 80,
    "time_spent": 5400,
    "consistency": 100,
    "skill_diversity": 40,
    "mastery_score": 78,
    "generated_explanation": "Strong literacy performance with steady improvement.",
    "algorithm_version": 1
  }
}
```

If no summaries exist:

```json
{
  "daily_summary": null,
  "weekly_summary": null
}
```

Status: `200`

### Export Learning Events For AI

```http
GET /api/ai/children/{childId}/events
```

Returns up to 1000 raw learning events for a child, ordered by `event_created_at`.

This endpoint accepts an optional `since` query parameter.

#### Path Parameters

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `childId` | string | yes | Child identifier. |

#### Query Parameters

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `since` | date/datetime string | no | Only include events where `event_created_at >= since`. |

#### Example Request

```bash
curl "http://localhost:8000/api/ai/children/child_123/events?since=2026-05-01"
```

#### Success Response

```json
{
  "child_id": "child_123",
  "count": 2,
  "events": [
    {
      "event_id": "evt_001",
      "game_type": "phonics",
      "score": 80,
      "time_spent": 300,
      "metrics": {
        "total_questions": 10,
        "correct_answers": 8
      },
      "skill_breakdown": {
        "letter_sounds": 0.8,
        "blending": 0.7
      },
      "event_created_at": "2026-05-10T10:15:00.000000Z"
    },
    {
      "event_id": "evt_002",
      "game_type": "sight_words",
      "score": 90,
      "time_spent": 240,
      "metrics": {
        "total_questions": 10,
        "correct_answers": 9
      },
      "skill_breakdown": {
        "word_recognition": 0.9
      },
      "event_created_at": "2026-05-10T10:25:00.000000Z"
    }
  ]
}
```

### Get AI Feature Snapshot

```http
GET /api/ai/children/{childId}/feature-snapshot
```

Returns a compact feature set derived from the last 10 daily summaries for a child.

This endpoint is protected by service JWT authentication.

#### Headers

| Name | Required | Description |
| --- | --- | --- |
| `Authorization` | yes | `Bearer <service-jwt>` |

#### Path Parameters

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `childId` | string | yes | Child identifier. |

#### Example Request

```bash
curl \
  -H "Authorization: Bearer <token>" \
  http://localhost:8000/api/ai/children/child_123/feature-snapshot
```

#### Success Response

```json
{
  "child_id": "child_123",
  "avg_accuracy": 0.82,
  "avg_mastery_score": 0.79,
  "avg_time_spent": 900,
  "trend_accuracy": 0.08,
  "trend_mastery": 0.06,
  "consistency_score": 1,
  "skill_diversity_avg": 0.35,
  "learning_stability": 0.9986,
  "data_window_days": 10
}
```

#### No Data Response

```json
{
  "message": "No data available for AI export"
}
```

Status: `404`

#### Missing Token Response

```json
{
  "message": "Missing token"
}
```

Status: `401`

#### Invalid Token Response

```json
{
  "message": "Invalid service token",
  "error": "Invalid audience"
}
```

Status: `401`

## Summary Field Reference

| Field | Type | Meaning |
| --- | --- | --- |
| `child_id` | string | Child identifier. |
| `summary_date` | date | Daily summary date. |
| `week_start_date` | date | Start date for weekly summary. |
| `week_end_date` | date | End date for weekly summary. |
| `total_sessions` | integer | Number of gameplay sessions/events counted. |
| `total_questions` | integer | Total questions attempted. |
| `correct_answers` | integer | Total correct answers. |
| `accuracy` | number | Percentage value from `0` to `100`. |
| `time_spent` | integer | Total seconds spent. |
| `consistency` | number | Percentage value from `0` to `100`. Currently based on a placeholder aggregation value. |
| `skill_diversity` | number | Percentage value from `0` to `100`. |
| `mastery_score` | number | Percentage value from `0` to `100`. |
| `generated_explanation` | string | Short readable description of progress. |
| `algorithm_version` | integer | Version of the summary algorithm. |

## Frontend Integration Notes

- Prefer `analytics-overview` when a dashboard needs both daily and weekly widgets.
- Use `daily-summary` or `weekly-summary` when rendering a focused view.
- Treat `daily_summary: null` or `weekly_summary: null` as an empty state, not an error.
- Display `time_spent` as minutes if that is friendlier for the UI.
- Do not multiply summary percentages by 100; they are already returned as display percentages.
- Do multiply AI feature snapshot ratios by 100 if showing them as percentages.
- Be ready for the generated explanation text to change as the scoring model evolves.
