# Analysis & Recommendation Service API Documentation

## Overview
This service provides AI-driven recommendations and learning health insights for parents regarding their children's progress. It operates as a Backend-for-Frontend (BFF), aggregating data from various microservices (Sync Service, User Service) to provide a unified dashboard experience.

---

## Authentication & Authorization

### Clerk Authentication
All requests must include a valid Clerk JWT in the `Authorization` header.

*   **Header:** `Authorization: Bearer <clerk_jwt_token>`
*   **Validation:** Tokens are validated against Clerk's JWKS.

### Guardian Verification
The service enforces strict ownership checks. An authenticated guardian can only access data for children they are explicitly authorized to manage. Unauthorized attempts will result in a `403 Forbidden` response.

---

## API Endpoints

### 1. Get Latest Recommendation
Retrieves the most recent AI-generated recommendation for a specific child.

- **URL:** `/api/parents/children/{childId}/recommendation`
- **Method:** `GET`
- **URL Params:** `childId=[string]`
- **Success Response:**
  - **Code:** `200 OK`
  - **Content:**
    ```json
    {
      "child_id": "c123",
      "tier": "Premium",
      "generated_at": "2026-05-13T10:00:00.000000Z",
      "recommendation_text": "Focus on spatial reasoning exercises this week...",
      "next_update_expected_at": "2026-05-20T10:00:00.000000Z"
    }
    ```
- **Error Responses:**
  - **Code:** `401 Unauthorized` (Invalid or missing token)
  - **Code:** `403 Forbidden` (Guardian not authorized for this child)
  - **Code:** `404 Not Found` (No recommendations generated yet)

---

### 2. Get Dashboard Status
Retrieves a summary of learning metrics, including health score, time spent, and consistency.

- **URL:** `/api/parents/children/{childId}/dashboard-status`
- **Method:** `GET`
- **URL Params:** `childId=[string]`
- **Success Response:**
  - **Code:** `200 OK`
  - **Content:**
    ```json
    {
      "learning_health_score": "Excellent",
      "time_spent_today_minutes": 45,
      "weekly_accuracy": 0.85,
      "consistency_status": "Highly Consistent"
    }
    ```
- **Error Responses:**
  - **Code:** `404 Not Found` (Not enough data to generate status)

---

### 3. Get Recommendation History
Retrieves the history of past recommendations. 
**Note:** This endpoint is restricted to users on a non-Basic subscription tier.

- **URL:** `/api/parents/children/{childId}/recommendation/history`
- **Method:** `GET`
- **URL Params:** `childId=[string]`
- **Success Response:**
  - **Code:** `200 OK`
  - **Content:**
    ```json
    {
      "history": [
        {
          "generated_at": "2026-05-13T10:00:00.000000Z",
          "recommendation_text": "..."
        },
        {
          "generated_at": "2026-05-06T10:00:00.000000Z",
          "recommendation_text": "..."
        }
      ]
    }
    ```
- **Error Responses:**
  - **Code:** `403 Forbidden` (Access denied for 'Basic' tier)

---

## Response Codes Summary

| Status Code | Description |
|:------------|:------------|
| `200 OK` | The request was successful. |
| `400 Bad Request` | Missing required parameters. |
| `401 Unauthorized` | Invalid or missing authentication token. |
| `403 Forbidden` | You do not have permission to access this resource or feature. |
| `404 Not Found` | The requested resource or data could not be found. |
| `500 Internal Server Error` | An unexpected error occurred on the server. |

---

## Local Development

### Prerequisites
- PHP 8.2+
- Composer
- SQLite (or preferred database)

### Setup
1.  Clone the repository.
2.  Install dependencies: `composer install`.
3.  Copy `.env.example` to `.env` and configure your Clerk and Service keys.
4.  Run migrations: `php artisan migrate`.
5.  Start the server: `php artisan serve`.
