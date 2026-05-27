# CMS Docker Setup

This guide explains how to run the CMS with the root `docker-compose.yml` in this repository.

## Quick Start

### Build and Run

```bash
docker compose up -d --build cms
```

The CMS is available at `http://localhost:8080`.

### Configure Environment

Copy the Docker example environment:

```bash
cp cms/.env.docker cms/.env
```

Update the required variables in `.env`:
- `CLERK_SECRET_KEY`
- `CLERK_JWKS_URL`
- `CLERK_ISSUER`
- `INTERNAL_SERVICE_TOKEN`

### Initialize Database

Migrations run automatically on startup, but you can run them manually:

```bash
docker compose exec cms php artisan migrate
```

Seed the database if needed:

```bash
docker compose exec cms php artisan db:seed
```

### Verify Health

```bash
curl http://localhost:8080/up
docker compose logs -f cms
```

## Services

### cms
- Built from `cms/Dockerfile`
- HTTP port: `8080`
- Health check endpoint: `/up`

### postgres
- Image: `postgres:16-alpine`
- Database name: `hahu`
- User: `hahu`

### redis
- Image: `redis:7-alpine`

### rabbitmq
- Image: `rabbitmq:3-management-alpine`

## Environment Variables

### App Configuration
| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `local` | Environment |
| `APP_DEBUG` | `true` | Enable/disable debug mode |
| `APP_URL` | `http://localhost:8080` | Application URL |
| `PORT` | `8080` | HTTP port inside container |

### Database Configuration
| Variable | Default | Description |
|----------|---------|-------------|
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_HOST` | `postgres` | Database hostname |
| `DB_PORT` | `5432` | Database port |
| `DB_DATABASE` | `hahu` | Database name |
| `DB_USERNAME` | `hahu` | Database user |
| `DB_PASSWORD` | `secret` | Database password |

### Service Integration
| Variable | Default | Description |
|----------|---------|-------------|
| `CHILD_SERVICE_URL` | `http://child-service:8080` | Child Service URL |
| `SYNC_SERVICE_URL` | `http://sync-service:8080` | Sync Service URL |
| `INTERNAL_SERVICE_TOKEN` | - | Shared token for internal service calls |
| `JWT_SECRET` | - | Shared JWT secret for CMS content endpoints |

### Authentication
| Variable | Default | Description |
|----------|---------|-------------|
| `CLERK_SECRET_KEY` | - | Clerk API secret key |
| `CLERK_JWKS_URL` | - | Clerk JWKS URL |
| `CLERK_ISSUER` | - | Clerk issuer URL |

### Docker-Specific
| Variable | Default | Description |
|----------|---------|-------------|
| `RUN_MIGRATIONS` | `true` | Run migrations on startup |
| `CACHE_ON_BOOT` | `true` | Cache config, routes, and views on startup |

## Common Commands

```bash
docker compose logs -f cms
docker compose logs --tail=100 cms
docker compose ps
docker compose restart cms
```

```bash
docker compose exec cms php artisan tinker
docker compose exec cms php artisan migrate:status
docker compose exec cms php artisan cache:clear
docker compose exec cms php artisan config:clear
docker compose exec cms php artisan route:clear
docker compose exec cms php artisan view:clear
```

```bash
docker compose exec postgres psql -U hahu -d hahu
docker compose exec postgres pg_dump -U hahu hahu > backup.sql
docker compose exec -T postgres psql -U hahu hahu < backup.sql
```

## Troubleshooting

### Service Won't Start
```bash
docker compose logs cms
docker compose ps
docker compose restart
```

### Database Connection Issues
```bash
docker compose ps postgres
docker compose logs postgres
docker compose exec cms nc -v postgres 5432
```

### Cache or Permission Issues
```bash
docker compose exec cms php artisan cache:clear
docker compose exec cms chmod -R 775 storage bootstrap/cache
```

## Production Considerations

- Set `APP_DEBUG=false` in production
- Use a strong `DB_PASSWORD`
- Rotate `INTERNAL_SERVICE_TOKEN` regularly
- Rotate `JWT_SECRET` regularly and keep it separate from `INTERNAL_SERVICE_TOKEN`
- Use HTTPS and set `APP_URL=https://...`
- Monitor the `/up` endpoint

## Multi-Service Setup

The repository already includes all services in the root `docker-compose.yml`, so you normally do not need a separate CMS compose file.

## File Structure

```text
cms/
├── Dockerfile
├── .dockerignore
├── .env.docker
├── app/
├── routes/
├── config/
├── resources/
├── database/
└── ...
```
