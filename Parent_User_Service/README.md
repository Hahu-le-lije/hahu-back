# Parent User Service API

Laravel API backend for Ha-hu Lä-Ləje Parent User Service only. This service uses Clerk JWTs for authentication and PostgreSQL for storage.

## Setup

- Configure database connection in .env (DB_CONNECTION=pgsql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
- Set Clerk environment variables (CLERK_JWKS_URL, CLERK_ISSUER).
- Run migrations.

## API Endpoints

### Parent Profile

- POST /api/parents/sign-up
- GET /api/parents/me
- PUT /api/parents/update
- DELETE /api/parents/delete

## Notes

- All routes return JSON only.
- All protected routes require a valid Clerk JWT.
- No child-related logic is included in this service.
