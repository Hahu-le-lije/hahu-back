# CMS Backend API

Laravel backend for the CMS admin and content delivery workflow. This service uses Sanctum bearer tokens for authentication and SQLite for storage.

## Setup

- Configure database connection in `.env`.
- Run migrations and seeders.
- Start the Laravel server from this directory.

## API Endpoints

### Authentication

- POST `/api/auth/login`
- POST `/api/auth/logout`
- GET `/api/auth/user`

### Public Content Delivery

- GET `/api/content/packs`
- GET `/api/content/packs/{slug}/manifest`
- GET `/api/content/packs/{slug}/download`

### Admin CMS

- GET `/api/admin/content-packs`
- POST `/api/admin/content-packs`
- PUT `/api/admin/content-packs/{id}`
- DELETE `/api/admin/content-packs/{id}`
- GET `/api/admin/content-pack-versions`
- POST `/api/admin/content-pack-versions`
- PUT `/api/admin/content-pack-versions/{id}`
- DELETE `/api/admin/content-pack-versions/{id}`

## Notes

- All routes return JSON only.
- Admin routes require a valid Sanctum token and the `admin` role.
