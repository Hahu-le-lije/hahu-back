# External Service Dependencies: User Service Contract

This document outlines the API endpoints that the **Subscription Service** expects the **User Service** (or the service defined by `USER_SERVICE_URL`) to provide. These endpoints are critical for validating user relationships and child metadata.

## Authentication

All requests from the Subscription Service include a Bearer token for authentication, managed via the `INTERNAL_SERVICE_TOKEN` environment variable.

```http
Authorization: Bearer <INTERNAL_SERVICE_TOKEN>
Content-Type: application/json
```

---

## 1. Get Parent Details

Retrieves basic information about a parent user to verify their existence and status.

- **URL**: `/get-parent`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "parent_id": "string|integer"
  }
  ```
- **Expected Success Response**:
  - **Code**: 200 OK
  - **Content**:
    ```json
    {
      "status": "success",
      "data": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com",
        ...
      }
    }
    ```

---

## 2. Get Child Details

Retrieves metadata for a specific child, including their current subscription status and parent association.

- **URL**: `/get-child`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "child_id": "string|integer"
  }
  ```
- **Expected Success Response**:
  - **Code**: 200 OK
  - **Content**:
    ```json
    {
      "status": "success",
      "data": {
        "id": "child_uuid",
        "parent_id": 123,
        "name": "Jane Doe",
        "subscription_id": 45, // Currently associated subscription ID
        "birth_date": "2020-01-01",
        ...
      }
    }
    ```

---

## 3. Link Child to Subscription (Deprecated/Legacy)

*Note: While previously handled via HTTP, this action is now primarily dispatched as an asynchronous job. However, the User Service should be prepared to handle updates to a child's `subscription_id` field.*

- **Expected Action**: Update the `subscription_id` for the specified `child_id` in the User Service database.

---

## Error Handling

The Subscription Service expects standard HTTP status codes and a consistent JSON error format:

- **404 Not Found**: If the parent or child does not exist.
  ```json
  {
    "status": "failed",
    "error": "User not found"
  }
  ```
- **401 Unauthorized**: If the `INTERNAL_SERVICE_TOKEN` is invalid or missing.
- **500 Internal Server Error**: For unexpected service failures.
