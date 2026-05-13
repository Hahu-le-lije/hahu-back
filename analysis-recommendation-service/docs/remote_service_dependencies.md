# Remote Service Dependencies

## Overview

The Analysis & Recommendation Service (ARS) relies on a distributed ecosystem of microservices to aggregate child learning data and verify user permissions. This document outlines the expected contracts for all external service dependencies.

---

## 1. Sync Service

The Sync Service is the primary source of truth for child activity and learning metrics.

### Analytics Overview

Retrieves high-level performance metrics.

- **Endpoint:** `GET /api/children/{childId}/analytics-overview`
- **Expected Response:**
    ```json
    {
        "daily_summary": { "time_spent": 3600, "mastery_gain": 5 },
        "weekly_summary": { "mastery_score": 82, "accuracy": 0.88 }
    }
    ```

### Feature Snapshot

Retrieves a breakdown of performance across different learning features.

- **Endpoint:** `GET /api/ai/children/{childId}/feature-snapshot`
- **Auth:** Service Bearer Token
- **Expected Response:**
    ```json
    {
        "consistency_score": 0.85,
        "top_performing_features": ["math", "logic"]
    }
    ```

### Recent Events

Retrieves granular event logs since a specific date.

- **Endpoint:** `GET /api/ai/children/{childId}/events`
- **Params:** `since` (ISO 8601 string)
- **Auth:** Service Bearer Token
- **Expected Response:**
    ```json
    {
        "events": [
            {
                "type": "lesson_completed",
                "timestamp": "...",
                "metadata": { "score": 90 }
            }
        ]
    }
    ```

---

## 2. User Service

The User Service provides child metadata and system-wide status.

### Child Metadata

- **Endpoint:** `GET /api/children/{childId}`
- **Expected Response:**
    ```json
    // writen as the child-service api
    ```

---

## 3. Child Service

Used primarily for ownership verification and subscription linking.

### Child Profile Verification

- **Endpoint:** `GET /api/server/children/{childId}`
- **Auth:** Forwarded Server Bearer Token
- **Expected Response:**
    ```json
    {
        "id": "c123",
        "parent_id": "p987"
    }
    ```

### Subscription Linking

- **Endpoint:** `GET /api/server/subscriptions/children/{childId}`
- **Auth:** Forwarded Server Bearer Token
- **Expected Response:**
    ```json
    [
        {
            "id": "c123",
            "parent_id": "p987"
        }
    ]
    ```

---

## 4. External Dependencies

### Clerk API

Used for JWT validation and public key retrieval.

- **Endpoint:** `GET https://api.clerk.com/v1/jwks`
- **Auth:** Clerk Secret Key (Bearer)
- **Purpose:** Retrieves the JSON Web Key Set to verify incoming parent sessions.

### AI Provider (Gemini AI)

Utilized via the `laravel/ai` SDK for natural language generation.

- **Role:** Processes aggregated metrics (JSON) into parent-facing recommendations.
- **Model Requirement:** `gemini-2-flash-preview` or equivalent for complex educational analysis.
- **Data Input:** Aggregated JSON from Sync & User services.
