# Child Service API Reference

This document describes the JSON API exposed by the child service for frontend and service-integration work.

All routes are prefixed with `/api`. Requests and responses use JSON unless noted otherwise.

## Authentication

The service uses bearer JWTs:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

There are two token contexts:

- Parent endpoints require a parent JWT issued by the parent service.
- Child endpoints require either child login credentials or a child JWT issued by this service.
- Internal service endpoints require shared service bearer tokens configured on this service.

Parent JWTs must contain:

| Claim | Required | Notes |
| --- | --- | --- |
| `sub` or `parent_id` | Yes | External parent id. Used as the account owner id for child records. |
| `role` | Yes | Must be `parent`. |
| `aud` | Optional | If configured with `PARENT_TOKEN_AUDIENCE`, it must match. |

Child JWTs issued by this service contain:

| Claim | Notes |
| --- | --- |
| `sub` | Child id. |
| `parent_id` | External parent id that owns the child account. |
| `role` | Always `child`. |
| `aud` | Defaults to `child-service`. |
| `iat` | Issued-at timestamp. |
| `exp` | Expiration timestamp. |

## Child Object

Child objects are returned without the password hash.

```json
{
  "id": 1,
  "parent_id": "parent-123",
  "first_name": "Lina",
  "last_name": "Reader",
  "username": "lina_reader_a1b2c",
  "avatar": "https://example.com/avatar.png",
  "subscription_id": "sub_123",
  "age": 8,
  "birthdate": "2018-04-12T00:00:00.000000Z",
  "skill_level": "beginner",
  "status": "active",
  "last_login_at": "2026-05-10T11:00:00.000000Z",
  "credentials_rotated_at": "2026-05-10T10:30:00.000000Z",
  "created_at": "2026-05-10T10:30:00.000000Z",
  "updated_at": "2026-05-10T11:00:00.000000Z"
}
```

Nullable fields may be returned as `null`. Date and datetime values are serialized by Laravel.

## Endpoint Summary

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| `POST` | `/api/children/login` | None | Exchange child username and PIN for a child JWT. |
| `GET` | `/api/children/me` | Child JWT | Read the current child profile. |
| `POST` | `/api/children/logout` | Child JWT | Client-side logout acknowledgement. |
| `GET` | `/api/parents/children` | Parent JWT | List children owned by the parent. |
| `POST` | `/api/parents/children` | Parent JWT | Create a child and generate credentials. |
| `GET` | `/api/parents/children/{child}` | Parent JWT | Read one owned child. |
| `PUT/PATCH` | `/api/parents/children/{child}` | Parent JWT | Update one owned child. |
| `DELETE` | `/api/parents/children/{child}` | Parent JWT | Delete one owned child. |
| `POST` | `/api/parents/children/{child}/credentials` | Parent JWT | Rotate a child's PIN. |
| `GET` | `/api/internal/children/{childId}` | Internal service token | Read child profile details for service-to-service integrations. |
| `GET` | `/api/internal/get-child/{childId}` | Internal service token | Alias for child profile lookup, retained for subscription service compatibility. |
| `GET` | `/api/internal/subscriptions/children/{subscriptionId}` | Internal service token | List children linked to an external subscription id. |
| `POST` | `/api/internal/subscriptions/assign-child` | Subscription service token | Queue subscription assignment for a child. |

## Child Auth Endpoints

### `POST /api/children/login`

Logs an active child in with their generated username and PIN.

Request body:

| Field | Type | Required | Rules |
| --- | --- | --- | --- |
| `username` | string | Yes | Existing child username. |
| `password` | string | Yes | Current child PIN. |

Example request:

```json
{
  "username": "lina_reader_a1b2c",
  "password": "123456"
}
```

Success response: `200 OK`

```json
{
  "access_token": "<jwt>",
  "token_type": "Bearer",
  "expires_in": 7200,
  "child": {
    "id": 1,
    "parent_id": "parent-123",
    "first_name": "Lina",
    "last_name": "Reader",
    "username": "lina_reader_a1b2c",
    "avatar": null,
    "subscription_id": "sub_123",
    "age": 8,
    "birthdate": null,
    "skill_level": "beginner",
    "status": "active",
    "last_login_at": "2026-05-10T11:00:00.000000Z",
    "credentials_rotated_at": "2026-05-10T10:30:00.000000Z",
    "created_at": "2026-05-10T10:30:00.000000Z",
    "updated_at": "2026-05-10T11:00:00.000000Z"
  }
}
```

Notes:

- `expires_in` is seconds.
- Default expiry is 120 minutes unless configured differently.
- Suspended children cannot log in.

Common errors:

| Status | Body | Meaning |
| --- | --- | --- |
| `422` | `{ "message": "Invalid credentials." }` | Username, PIN, or active status check failed. |
| `422` | Laravel validation error object | Required field missing or invalid type. |

### `GET /api/children/me`

Returns the current authenticated child profile.

Auth: child JWT.

Success response: `200 OK`

```json
{
  "id": 1,
  "parent_id": "parent-123",
  "first_name": "Lina",
  "last_name": "Reader",
  "username": "lina_reader_a1b2c",
  "avatar": null,
  "subscription_id": "sub_123",
  "age": 8,
  "birthdate": null,
  "skill_level": "beginner",
  "status": "active",
  "last_login_at": "2026-05-10T11:00:00.000000Z",
  "credentials_rotated_at": "2026-05-10T10:30:00.000000Z",
  "created_at": "2026-05-10T10:30:00.000000Z",
  "updated_at": "2026-05-10T11:00:00.000000Z"
}
```

Common errors:

| Status | Body | Meaning |
| --- | --- | --- |
| `401` | `{ "message": "Missing bearer token." }` | No token was sent. |
| `401` | `{ "message": "Invalid child token." }` | Token failed verification, expired, or audience check failed. |
| `401` | `{ "message": "Child account is unavailable." }` | Child was deleted, suspended, or otherwise inactive. |
| `403` | `{ "message": "Token is not a child token." }` | Token verified but does not carry child claims. |

### `POST /api/children/logout`

Acknowledges child logout.

Auth: child JWT.

Success response: `204 No Content`

This service is stateless and does not revoke tokens on logout. Frontends should remove the stored child token locally.

## Parent Child Management Endpoints

All endpoints in this section require a parent JWT.

Children are scoped to the parent id in the token. A parent trying to read, update, delete, or rotate credentials for another parent's child receives `404 Not Found`.

### `GET /api/parents/children`

Lists children owned by the authenticated parent, ordered by `first_name`.

Success response: `200 OK`

```json
[
  {
    "id": 1,
    "parent_id": "parent-123",
    "first_name": "Lina",
    "last_name": "Reader",
    "username": "lina_reader_a1b2c",
    "avatar": null,
    "subscription_id": "sub_123",
    "age": 8,
    "birthdate": null,
    "skill_level": "beginner",
    "status": "active",
    "last_login_at": null,
    "credentials_rotated_at": "2026-05-10T10:30:00.000000Z",
    "created_at": "2026-05-10T10:30:00.000000Z",
    "updated_at": "2026-05-10T10:30:00.000000Z"
  }
]
```

### `POST /api/parents/children`

Creates a child under the authenticated parent and generates child login credentials.

Request body:

| Field | Type | Required | Rules |
| --- | --- | --- | --- |
| `first_name` | string | Yes | Max 100 characters. |
| `last_name` | string or null | No | Max 100 characters. |
| `avatar` | string or null | No | Max 2048 characters. |
| `subscription_id` | string or null | No | Max 100 characters. |
| `age` | integer or null | No | Minimum 1, maximum 18. |
| `birthdate` | date or null | No | Any Laravel-parseable date. |
| `skill_level` | string or null | No | Max 50 characters. |

Example request:

```json
{
  "first_name": "Lina",
  "last_name": "Reader",
  "age": 8,
  "skill_level": "beginner",
  "subscription_id": "sub_123"
}
```

Success response: `201 Created`

```json
{
  "child": {
    "id": 1,
    "parent_id": "parent-123",
    "first_name": "Lina",
    "last_name": "Reader",
    "username": "lina_reader_a1b2c",
    "avatar": null,
    "subscription_id": "sub_123",
    "age": 8,
    "birthdate": null,
    "skill_level": "beginner",
    "status": "active",
    "last_login_at": null,
    "credentials_rotated_at": "2026-05-10T10:30:00.000000Z",
    "created_at": "2026-05-10T10:30:00.000000Z",
    "updated_at": "2026-05-10T10:30:00.000000Z"
  },
  "credentials": {
    "username": "lina_reader_a1b2c",
    "pin": "123456"
  }
}
```

Important frontend note: `credentials.pin` is only returned when credentials are created or reset. The service stores the PIN hashed and cannot show the existing PIN later.

Generated credential behavior:

- `username` is generated from first and last name plus a random suffix.
- `pin` defaults to 6 numeric digits unless `CHILD_PIN_LENGTH` is configured differently.
- New children are created with `status: "active"`.

### `GET /api/parents/children/{child}`

Returns one child owned by the authenticated parent.

Path parameters:

| Parameter | Type | Notes |
| --- | --- | --- |
| `child` | integer | Child id. |

Success response: `200 OK`

```json
{
  "id": 1,
  "parent_id": "parent-123",
  "first_name": "Lina",
  "last_name": "Reader",
  "username": "lina_reader_a1b2c",
  "avatar": null,
  "subscription_id": "sub_123",
  "age": 8,
  "birthdate": null,
  "skill_level": "beginner",
  "status": "active",
  "last_login_at": null,
  "credentials_rotated_at": "2026-05-10T10:30:00.000000Z",
  "created_at": "2026-05-10T10:30:00.000000Z",
  "updated_at": "2026-05-10T10:30:00.000000Z"
}
```

### `PUT/PATCH /api/parents/children/{child}`

Updates one child owned by the authenticated parent.

Request body:

| Field | Type | Required | Rules |
| --- | --- | --- | --- |
| `first_name` | string | No | Max 100 characters. |
| `last_name` | string or null | No | Max 100 characters. |
| `avatar` | string or null | No | Max 2048 characters. |
| `subscription_id` | string or null | No | Max 100 characters. |
| `age` | integer or null | No | Minimum 1, maximum 18. |
| `birthdate` | date or null | No | Any Laravel-parseable date. |
| `skill_level` | string or null | No | Max 50 characters. |
| `status` | string | No | Must be `active` or `suspended`. |

Example request:

```json
{
  "skill_level": "intermediate",
  "status": "active"
}
```

Success response: `200 OK`

Returns the updated child object.

Frontend notes:

- Children cannot update their own profile through `/api/children/me`.
- Send `PATCH` for partial updates.
- Setting `status` to `suspended` prevents child login and makes existing child-token profile requests fail once middleware checks the account status.

### `DELETE /api/parents/children/{child}`

Deletes one child owned by the authenticated parent.

Success response: `204 No Content`

### `POST /api/parents/children/{child}/credentials`

Rotates the child's PIN while keeping the username the same.

Success response: `200 OK`

```json
{
  "child": {
    "id": 1,
    "parent_id": "parent-123",
    "first_name": "Lina",
    "last_name": "Reader",
    "username": "lina_reader_a1b2c",
    "avatar": null,
    "subscription_id": "sub_123",
    "age": 8,
    "birthdate": null,
    "skill_level": "beginner",
    "status": "active",
    "last_login_at": null,
    "credentials_rotated_at": "2026-05-10T12:15:00.000000Z",
    "created_at": "2026-05-10T10:30:00.000000Z",
    "updated_at": "2026-05-10T12:15:00.000000Z"
  },
  "credentials": {
    "username": "lina_reader_a1b2c",
    "pin": "789012"
  }
}
```

Important frontend note: show or store the new PIN immediately if the parent needs to share it. It cannot be retrieved again.

## Common Parent Auth Errors

| Status | Body | Meaning |
| --- | --- | --- |
| `401` | `{ "message": "Missing bearer token." }` | No token was sent. |
| `401` | `{ "message": "Invalid parent token." }` | Token failed verification, expired, or audience check failed. |
| `403` | `{ "message": "Token is not a parent token." }` | Token verified but does not carry parent claims. |
| `404` | Laravel not found response | Child id does not exist or belongs to another parent. |
| `422` | Laravel validation error object | Request body failed validation. |

Laravel validation errors use the standard shape:

```json
{
  "message": "The first name field is required.",
  "errors": {
    "first_name": [
      "The first name field is required."
    ]
  }
}
```

## Internal Service Endpoints

Internal read endpoints are intended for other backend services such as CMS, Analysis, and Subscription Service. They are not browser/client endpoints.

Auth: bearer token matching `INTERNAL_SERVICE_TOKEN`. If `INTERNAL_SERVICE_TOKEN` is not configured, the service falls back to `SUBSCRIPTION_SERVICE_TOKEN` for compatibility.

Common errors:

| Status | Body | Meaning |
| --- | --- | --- |
| `401` | `{ "message": "Invalid internal service token." }` | Missing or incorrect bearer token. |
| `503` | `{ "message": "Internal service token is not configured." }` | No internal token is configured. |

### `GET /api/internal/children/{childId}`

Returns child profile details in the service-to-service envelope expected by CMS and Analysis Service.

Path parameters:

| Parameter | Type | Notes |
| --- | --- | --- |
| `childId` | integer or string | Child id. |

Success response: `200 OK`

```json
{
  "status": "success",
  "data": {
    "id": "1",
    "child_id": "1",
    "parent_id": "parent-123",
    "first_name": "Lina",
    "last_name": "Reader",
    "child_name": "Lina Reader",
    "username": "lina_reader_a1b2c",
    "avatar": null,
    "subscription_id": "sub_123",
    "age": 8,
    "birthdate": null,
    "skill_level": "beginner",
    "status": "active",
    "last_login_at": null,
    "credentials_rotated_at": "2026-05-10T10:30:00.000000Z",
    "created_at": "2026-05-10T10:30:00.000000Z",
    "updated_at": "2026-05-10T10:30:00.000000Z"
  }
}
```

The password hash is never returned.

Not found response: `404 Not Found`

```json
{
  "status": "error",
  "message": "Child not found."
}
```

### `GET /api/internal/get-child/{childId}`

Compatibility alias for `GET /api/internal/children/{childId}`. It returns the same response shape and is provided for services that already call `/api/internal/get-child/{childId}`.

### `GET /api/internal/subscriptions/children/{subscriptionId}`

Lists child accounts currently linked to an external subscription id.

Path parameters:

| Parameter | Type | Notes |
| --- | --- | --- |
| `subscriptionId` | string | External subscription id stored on child records. |

Success response: `200 OK`

```json
{
  "status": "success",
  "data": [
    {
      "child_id": "1",
      "child_name": "Lina Reader",
      "parent_id": "parent-123",
      "status": "active"
    }
  ]
}
```

If no children are linked to the subscription, `data` is an empty array.

## Internal Subscription Service Endpoint

### `POST /api/internal/subscriptions/assign-child`

Queues a job that assigns an external subscription id to a child account.

Auth: bearer token matching `SUBSCRIPTION_SERVICE_TOKEN`. If `SUBSCRIPTION_SERVICE_TOKEN` is not configured, the service falls back to `INTERNAL_SERVICE_TOKEN` for compatibility.

Request body:

| Field | Type | Required | Rules |
| --- | --- | --- | --- |
| `child_id` | integer | Yes | Minimum 1. |
| `subscription_id` | string | Yes | Max 100 characters. |

Example request:

```json
{
  "child_id": 1,
  "subscription_id": "sub_123"
}
```

Success response: `202 Accepted`

```json
{
  "message": "Subscription assignment queued."
}
```

The queued `AssignSubscriptionToChild` job writes `subscription_id` on the
matching row in `children`. If the child cannot be found when the job runs,
Laravel retries the job according to queue worker settings and then records it
as failed.

Common errors:

| Status | Body | Meaning |
| --- | --- | --- |
| `401` | `{ "message": "Invalid subscription service token." }` | Missing or incorrect bearer token. |
| `503` | `{ "message": "Subscription service token is not configured." }` | `SUBSCRIPTION_SERVICE_TOKEN` is not set. |
| `422` | Laravel validation error object | Request body failed validation. |

## Frontend Implementation Notes

- Store parent tokens separately from child tokens if the app supports both roles in one browser session.
- Treat child logout as a local token-clearing action.
- Design create/reset credential flows so the generated PIN is clearly visible once, with a confirmation step before leaving the screen if needed.
- Use `404` on parent child-detail routes as "not found or not yours"; the API intentionally does not reveal ownership.
- Use `status: "suspended"` when a parent needs to disable a child account without deleting it.
- Do not expect pagination on `GET /api/parents/children`; the endpoint currently returns the full owned list.
