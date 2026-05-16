# Child Service

This Laravel service owns child account creation and management for Hahu La-Ləje.

Note that the broader product uses a family/household account model:

- the parent service owns parent users, billing authority, and household administration
- the subscription service owns purchased subscriptions and assignment rules
- this child service owns child profiles, generated child login credentials, and child-scoped authentication

Child accounts are intentionally limited. Children can log in and read their own profile, but currently they cannot edit their account, change billing, leave a household, or manage subscriptions.

## API Shape

Parent service JWT required:

- `GET /api/parents/children`
- `POST /api/parents/children`
- `GET /api/parents/children/{child}`
- `PATCH /api/parents/children/{child}`
- `DELETE /api/parents/children/{child}`
- `POST /api/parents/children/{child}/credentials`

Child credentials/JWT:

- `POST /api/children/login`
- `GET /api/children/me`
- `POST /api/children/logout`

When a parent creates a child, the service generates a unique username and PIN. The PIN is only returned in the create/reset response and is stored hashed.

For request/response examples, validation rules, auth errors, and frontend implementation notes, see [docs/api.md](docs/api.md).

For table definitions, indexes, ownership notes, and operational schema details, see [docs/database-schema.md](docs/database-schema.md).

## Authentication

This service verifies Clerk session tokens for parent-owned routes and issues its own stateless HS256 JWTs for child sessions.

Expected Clerk parent token claim:

- `sub`: Clerk user id for the parent

Issued child token claims:

- `sub`: child id
- `parent_id`: external parent id
- `role`: `child`
- `aud`: `child-service` by default

Configure secrets with:

```env
CHILD_SERVICE_JWT_SECRET=
CLERK_JWT_KEY=
CLERK_ISSUER=
CLERK_AUTHORIZED_PARTIES=
CHILD_TOKEN_TTL_MINUTES=120
CHILD_TOKEN_AUDIENCE=child-service
CHILD_PIN_LENGTH=6
```

## Local Checks

```bash
php artisan route:list
php artisan test
```
