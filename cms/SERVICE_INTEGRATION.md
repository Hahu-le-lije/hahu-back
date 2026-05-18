# CMS Service Integration Implementation

## Overview

The CMS has been updated with proper service-to-service integration to:
1. **Verify parent-child relationships** via Child Service
2. **Get performance recommendations** via Sync Service  
3. **Track task assignments** for downstream services

## Services Integrated

### 1. Child Service (`App\Services\ChildServiceClient`)
**Purpose**: Verify that a parent actually owns a child before allowing task assignments or recommendations.

**Methods**:
- `verifyParentOwnsChild(childId, parentId)` - Returns true if parent owns child
- `getChildDetails(childId)` - Get child profile info

**Configuration**:
```
CHILD_SERVICE_URL=http://localhost:8001
CHILD_SERVICE_TOKEN=<internal-service-token>
```

### 2. Sync Service (`App\Services\SyncServiceClient`)
**Purpose**: Retrieve child performance data for generating personalized recommendations.

**Methods**:
- `getLatestDailySummary(childId)` - Get daily performance summary
- `getLatestSummaries(childId)` - Get per-game-type summaries
- `getFeatureSnapshot(childId)` - Get AI-analyzed feature breakdown
- `getRecentEvents(childId, sinceDate)` - Get learning events since date

**Configuration**:
```
SYNC_SERVICE_URL=http://localhost:8002
SYNC_SERVICE_TOKEN=<internal-service-token>
```

## Updated Endpoints

### GET `/api/children/{child_id}/tasks/recommendations`
**Changes**:
- Now requires authentication (Bearer token from Sanctum)
- Verifies parent owns the child (via Child Service)
- Uses Sync Service to get performance data
- Returns 403 if child not owned by parent
- Returns 401 if not authenticated

**Response** (200 OK):
```json
{
  "recommendations": [
    {
      "game_type_id": 1,
      "content_id": 42,
      "title": "Fidel Tracing Level 2",
      "reason": "Mastery score below 0.7"
    }
  ]
}
```

**Errors**:
- `401` - Not authenticated
- `403` - Child not owned by parent
- `500` - Server error during recommendation generation

### POST `/api/children/{child_id}/tasks/assign`
**Changes**:
- Now requires authentication (Bearer token from Sanctum)
- Verifies parent owns the child (via Child Service)
- Logs assignment for Analysis Service consumption
- Validates content_id exists before creating task

**Request Body**:
```json
{
  "content_id": 42,
  "game_type_id": 1,
  "reason": "Parent requested this content"
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "assigned_task": {
    "id": 1,
    "child_id": "child_123",
    "content_id": 42,
    "game_type_id": 1,
    "status": "pending",
    "assigned_by": "parent_456",
    "assigned_at": "2026-05-17T10:30:00Z",
    "reason": "Parent requested this content",
    "created_at": "2026-05-17T10:30:00Z",
    "updated_at": "2026-05-17T10:30:00Z"
  }
}
```

**Errors**:
- `401` - Not authenticated
- `403` - Child not owned by parent
- `422` - Validation failed (invalid content_id, missing fields)
- `500` - Server error during assignment

## Environment Configuration

Add these to your `.env` file:

```bash
# CMS Service Authentication
INTERNAL_SERVICE_TOKEN=your_internal_service_token_here
CMS_SERVICE_SECRET=cms_secret_key

# Child Service
CHILD_SERVICE_URL=http://localhost:8001
CHILD_SERVICE_TOKEN=${INTERNAL_SERVICE_TOKEN}

# Sync Service
SYNC_SERVICE_URL=http://localhost:8002
SYNC_SERVICE_TOKEN=${INTERNAL_SERVICE_TOKEN}
```

## Error Handling & Fallbacks

### Sync Service Unavailable
If Sync Service is down:
1. Try to read from local `daily_summaries` table
2. Fall back to returning all active content
3. Log warning but continue (graceful degradation)

### Child Service Unavailable
If Child Service is down:
- Return 403 Forbidden (fail secure)
- Log the error
- Parent must wait for service recovery

## Service-to-Service Authentication

All internal service calls use bearer tokens configured via:
```
INTERNAL_SERVICE_TOKEN=<shared-secret-between-services>
```

Each service expects this token in the `Authorization: Bearer <token>` header.

## Task Assignment Propagation

When a task is assigned to a child:

1. **Logged Locally** - Assignment stored in `assigned_tasks` table
2. **Logged for Services** - Event logged for Analysis Service consumption
3. **Future**: Could dispatch RabbitMQ job to notify Sync Service

Currently, the system logs the assignment. To propagate to other services:
```php
// In TaskRecommendationController::notifyServicesOfTaskAssignment()
// Dispatch a job like:
// PublishTaskAssignmentEvent::dispatch($task, $childId);
```

## Testing

### Test Parent-Child Verification
```bash
# This should fail with 403 if parent doesn't own child
curl -X GET http://localhost/api/children/child_of_other_parent/tasks/recommendations \
  -H "Authorization: Bearer <parent_token>"
```

### Test Valid Request
```bash
# This should succeed if parent owns child
curl -X GET http://localhost/api/children/child_123/tasks/recommendations \
  -H "Authorization: Bearer <valid_parent_token>"
```

## Database Migrations

The following migrations have been cleaned up:
- ✅ Kept: `2026_05_16_000002_create_child_subjects_table.php` (safe, idempotent)
- ✅ Kept: `2026_05_16_000003_create_assigned_tasks_table.php` (safe, idempotent)
- ❌ Removed: Duplicate versions that lacked safety checks

Run migrations:
```bash
php artisan migrate
```

## Provider Registration

The service clients are registered in `app/Providers/ServiceClientsProvider.php` and automatically available for dependency injection in controllers.

## Next Steps

1. ✅ Configure all service URLs in `.env`
2. ✅ Distribute `INTERNAL_SERVICE_TOKEN` to all services
3. ⏳ Test inter-service communication
4. ⏳ Set up monitoring/alerting for service failures
5. ⏳ Consider caching child ownership checks (short TTL)
6. ⏳ Add OpenAPI documentation for endpoints
7. ⏳ Implement RabbitMQ task assignment propagation
