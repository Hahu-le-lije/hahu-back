# Docker Desktop

This repository can be run as a local microservice stack with Docker Desktop from the repository root.

## Prerequisites

- Docker Desktop with Docker Compose v2
- Ports `80`, `5432`, `6379`, `5672`, and `15672` available, or edit `docker-compose.yml`
- API keys exported in your shell if you want the AI-backed endpoints to work:

```powershell
$env:GEMINI_API_KEY="..."
$env:HUG_FACE="..."
```

Protected Clerk/Chapa flows also need their real secrets exported or added to `docker-compose.yml`.

## First Run

```powershell
docker compose build
docker compose up
```

The first Postgres boot creates separate databases for:

- `cms`
- `child`
- `subscription`
- `sync`
- `analysis`

Laravel service containers run migrations on startup through `RUN_MIGRATIONS=true`.

## URLs

The Nginx gateway listens on port `80`:

- `http://localhost/child/`
- `http://localhost/cms/`
- `http://localhost/analysis/`
- `http://localhost/sync/`
- `http://localhost/subscription/`
- `http://localhost/gaming/`

RabbitMQ management UI is available at `http://localhost:15672` with `guest` / `guest`.

## Useful Commands

```powershell
docker compose ps
docker compose logs -f
docker compose logs -f cms
docker compose exec cms php artisan route:list
docker compose exec sync-service php artisan sync:game-events
docker compose down
```

To reset local databases completely:

```powershell
docker compose down -v
docker compose up --build
```
