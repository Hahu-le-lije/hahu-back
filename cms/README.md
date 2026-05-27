# CMS Backend API

Laravel backend for the CMS admin and content delivery workflow. This service uses Sanctum bearer tokens for authentication and PostgreSQL for storage in development/production.

## Setup

- Install PHP and the `pdo_pgsql` extension (Postgres PDO driver).
- Copy `.env.example` to `.env` and set the Postgres connection variables:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

- Create the database in Postgres and ensure the configured user has privileges.
- Run migrations and seeders:

```bash
php artisan migrate --seed
```

- Start the Laravel server from this directory:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

## API Endpoints

### Parent App

- POST `/api/parents/sign-up`
- GET `/api/parents/me` (requires `clerk.auth`)
- PUT `/api/parents/update` (requires `clerk.auth`)
- DELETE `/api/parents/delete` (requires `clerk.auth`)

### Authentication

- POST `/api/auth/login`
- POST `/api/auth/logout` (requires `auth:sanctum`)
- GET `/api/auth/user` (requires `auth:sanctum`)

### Child App Content Delivery

- GET `/api/content/packs` (requires a shared JWT signed with `JWT_SECRET`)
- GET `/api/content/packs/{slug}/manifest` (requires the same shared JWT)
- GET `/api/content/packs/{slug}/download` (requires the same shared JWT)
- GET `/api/subjects?child_id={childId}`
- PUT `/api/subjects`

### Parent Tasking

- GET `/api/children/{child_id}/tasks/recommendations` (requires `auth:sanctum`)
- POST `/api/children/{child_id}/tasks/assign` (requires `auth:sanctum`)

### Admin CMS

- GET `/api/admin/content-packs`
- GET `/api/admin/content-packs/{id}`
- POST `/api/admin/content-packs`
- PUT `/api/admin/content-packs/{id}`
- DELETE `/api/admin/content-packs/{id}`
- GET `/api/admin/content-pack-versions`
- POST `/api/admin/content-pack-versions`
- PUT `/api/admin/content-pack-versions/{id}`
- DELETE `/api/admin/content-pack-versions/{id}`
- GET `/api/admin/children`
- GET `/api/admin/children/{child_id}/assigned-tasks`

## Notes

- All routes return JSON only.
- CMS inter-service communication now uses shared HS256 JWTs for content delivery.
- Services should send `Authorization: Bearer <jwt>` to content endpoints.
- The JWT must be signed with `JWT_SECRET` (or the configured fallback secret) and include a valid subject claim such as `sub` or `user_id`.
- Admin routes require a valid Sanctum token and the `admin` role.
