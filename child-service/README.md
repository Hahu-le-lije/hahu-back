# Child Service

This Laravel service owns child account creation and management for Hahu Lä-Ləje.

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

Internal service token:

- `GET /api/internal/children/{childId}`
- `GET /api/internal/get-child/{childId}`
- `GET /api/internal/subscriptions/children/{subscriptionId}`
- `POST /api/internal/subscriptions/assign-child`

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
INTERNAL_SERVICE_TOKEN=
SUBSCRIPTION_SERVICE_TOKEN=
CLERK_JWT_KEY=
CLERK_ISSUER=
CLERK_AUTHORIZED_PARTIES=
CHILD_TOKEN_TTL_MINUTES=120
CHILD_TOKEN_AUDIENCE=child-service
CHILD_PIN_LENGTH=6
```

`INTERNAL_SERVICE_TOKEN` protects read-only service-to-service child lookup routes. `SUBSCRIPTION_SERVICE_TOKEN` protects subscription assignment writes and falls back to `INTERNAL_SERVICE_TOKEN` if it is not set.

## Local Checks

```bash
php artisan route:list
php artisan test
```

## Google Cloud Run

This service includes a Dockerfile for Cloud Run. Build and deploy the image from this directory:

```bash
export PROJECT_ID=your-gcp-project
export REGION=us-central1
export REPOSITORY=services
export IMAGE_URI=$REGION-docker.pkg.dev/$PROJECT_ID/$REPOSITORY/child-service

gcloud artifacts repositories create $REPOSITORY --repository-format=docker --location=$REGION
gcloud builds submit --tag $IMAGE_URI
gcloud run deploy child-service --image $IMAGE_URI --platform managed --region $REGION
```

Cloud Run provides the `PORT` environment variable automatically. Configure the app secrets and service settings in Cloud Run, including `APP_KEY`, `APP_URL`, `CHILD_SERVICE_JWT_SECRET`, Clerk settings, subscription service token, and database connection variables. Run database migrations as a separate deploy step rather than on container startup.
