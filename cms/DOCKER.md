# CMS Docker Setup

This guide explains how to run the CMS service using Docker locally and in production.

## Quick Start

### 1. Build and Run

```bash
# From the project root
docker-compose -f docker-compose.cms.yml up -d
```

The CMS will be available at `http://localhost:8080`

### 2. Configure Environment

Copy the Docker example environment:

```bash
cp cms/.env.docker cms/.env
```

Update the required variables in `.env`:
- `CLERK_SECRET_KEY` - Your Clerk API key
- `CLERK_JWKS_URL` - Your Clerk JWKS URL
- `CLERK_ISSUER` - Your Clerk issuer
- `INTERNAL_SERVICE_TOKEN` - Shared token for inter-service communication

### 3. Initialize Database

Migrations run automatically on startup, but you can manually run them:

```bash
docker-compose -f docker-compose.cms.yml exec cms php artisan migrate
```

Seed the database (if seeders exist):

```bash
docker-compose -f docker-compose.cms.yml exec cms php artisan db:seed
```

### 4. Verify Health

```bash
# Check if service is healthy
curl http://localhost:8080/health

# View logs
docker-compose -f docker-compose.cms.yml logs -f cms
```

## Docker Compose Services

### cms
- **Image**: Built from `cms/Dockerfile`
- **Port**: 8080 (configurable via `CMS_PORT`)
- **Database**: PostgreSQL 15 (cms-db)
- **Volumes**:
  - `app/`, `routes/`, `config/`, `resources/`, `database/` (for development)
  - Named volumes for logs and cache
- **Health Check**: HTTP GET to `/health` every 30s

### cms-db
- **Image**: `postgres:15-alpine`
- **Port**: 5432 (configurable via `CMS_DB_PORT`)
- **Volumes**: `cms_db_data` for data persistence
- **Health Check**: PostgreSQL connection check every 10s

## Environment Variables

### App Configuration
| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `local` | Environment (local, staging, production) |
| `APP_DEBUG` | `true` | Enable/disable debug mode |
| `APP_URL` | `http://localhost:8080` | Application URL |
| `PORT` | `8080` | HTTP port inside container |

### Database Configuration
| Variable | Default | Description |
|----------|---------|-------------|
| `CMS_DB_CONNECTION` | `pgsql` | Database driver |
| `CMS_DB_HOST` | `cms-db` | Database hostname |
| `CMS_DB_PORT` | `5432` | Database port |
| `CMS_DB_DATABASE` | `cms_db` | Database name |
| `CMS_DB_USERNAME` | `cms_user` | Database user |
| `CMS_DB_PASSWORD` | `cms_password` | Database password |

### Service Integration
| Variable | Default | Description |
|----------|---------|-------------|
| `CHILD_SERVICE_URL` | `http://child-service:8001` | Child Service URL |
| `SYNC_SERVICE_URL` | `http://sync-service:8002` | Sync Service URL |
| `INTERNAL_SERVICE_TOKEN` | - | Inter-service authentication token |
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
| `LARAVEL_OPTIMIZE` | `true` | Cache config/routes/views on startup |
| `RUN_MIGRATIONS` | `true` | Run migrations on startup |

## Common Commands

### View Logs
```bash
# Real-time logs
docker-compose -f docker-compose.cms.yml logs -f cms

# Last 100 lines
docker-compose -f docker-compose.cms.yml logs --tail=100 cms
```

### Execute Artisan Commands
```bash
# General command
docker-compose -f docker-compose.cms.yml exec cms php artisan <command>

# Examples
docker-compose -f docker-compose.cms.yml exec cms php artisan tinker
docker-compose -f docker-compose.cms.yml exec cms php artisan make:migration <name>
docker-compose -f docker-compose.cms.yml exec cms php artisan db:seed --class=ContentSeeder
```

### Database Management
```bash
# Run migrations
docker-compose -f docker-compose.cms.yml exec cms php artisan migrate

# Rollback migrations
docker-compose -f docker-compose.cms.yml exec cms php artisan migrate:rollback

# Refresh database
docker-compose -f docker-compose.cms.yml exec cms php artisan migrate:fresh --seed

# Access PostgreSQL CLI
docker-compose -f docker-compose.cms.yml exec cms-db psql -U cms_user -d cms_db
```

### Clear Cache
```bash
# Laravel cache
docker-compose -f docker-compose.cms.yml exec cms php artisan cache:clear

# Config cache
docker-compose -f docker-compose.cms.yml exec cms php artisan config:clear

# All caches
docker-compose -f docker-compose.cms.yml exec cms php artisan cache:clear && \
docker-compose -f docker-compose.cms.yml exec cms php artisan config:clear && \
docker-compose -f docker-compose.cms.yml exec cms php artisan route:clear && \
docker-compose -f docker-compose.cms.yml exec cms php artisan view:clear
```

### Rebuild Docker Image
```bash
# Without cache
docker-compose -f docker-compose.cms.yml build --no-cache cms

# And start
docker-compose -f docker-compose.cms.yml up -d --build
```

## Development Workflow

### 1. Start Services
```bash
docker-compose -f docker-compose.cms.yml up -d
```

### 2. Install Dependencies (if not in Docker)
```bash
# PHP dependencies
docker-compose -f docker-compose.cms.yml exec cms composer install

# Node dependencies (if Vite assets)
docker-compose -f docker-compose.cms.yml exec cms npm install
docker-compose -f docker-compose.cms.yml exec cms npm run dev
```

### 3. Run Migrations
```bash
docker-compose -f docker-compose.cms.yml exec cms php artisan migrate
```

### 4. Access Application
- Web: `http://localhost:8080`
- Database: `localhost:5432` (from host machine)

### 5. Stop Services
```bash
docker-compose -f docker-compose.cms.yml down
```

### 6. Clean Up (remove volumes)
```bash
docker-compose -f docker-compose.cms.yml down -v
```

## Troubleshooting

### Service Won't Start
```bash
# Check logs
docker-compose -f docker-compose.cms.yml logs cms

# Verify database is running
docker-compose -f docker-compose.cms.yml ps

# Restart everything
docker-compose -f docker-compose.cms.yml restart
```

### Database Connection Issues
```bash
# Verify database is healthy
docker-compose -f docker-compose.cms.yml ps cms-db

# Check database logs
docker-compose -f docker-compose.cms.yml logs cms-db

# Test connection from app container
docker-compose -f docker-compose.cms.yml exec cms nc -v cms-db 5432
```

### Cache/Permission Issues
```bash
# Clear application cache
docker-compose -f docker-compose.cms.yml exec cms php artisan cache:clear

# Fix storage permissions
docker-compose -f docker-compose.cms.yml exec cms chmod -R 775 storage bootstrap/cache
```

### Port Already in Use
```bash
# Change port in .env or compose file
# Then restart:
docker-compose -f docker-compose.cms.yml down
docker-compose -f docker-compose.cms.yml up -d
```

## Production Considerations

### Security
- Set `APP_DEBUG=false` in production
- Use strong `DB_PASSWORD`
- Rotate `INTERNAL_SERVICE_TOKEN` regularly
- Rotate `JWT_SECRET` regularly and keep it separate from `INTERNAL_SERVICE_TOKEN`
- Set `SANCTUM_ENCRYPT_COOKIES=true`
- Use HTTPS (set `APP_URL=https://...`)

### Performance
- Set `LARAVEL_OPTIMIZE=true` (caches config, routes, views)
- Use Redis for cache/sessions instead of database
- Enable OPcache in PHP
- Consider using CDN for static assets

### Monitoring
- Mount `/var/www/html/storage/logs` to host for log aggregation
- Set up health check monitoring on `/health` endpoint
- Use container restart policies: `restart: unless-stopped`

### Database Backups
```bash
# Backup
docker-compose -f docker-compose.cms.yml exec cms-db pg_dump \
  -U cms_user cms_db > backup.sql

# Restore
docker-compose -f docker-compose.cms.yml exec -T cms-db psql \
  -U cms_user cms_db < backup.sql
```

## Multi-Service Setup

To run CMS with other services (Child Service, Sync Service, etc.):

1. Create a root `docker-compose.yml` that includes all services
2. Use `docker-compose.cms.yml` for CMS-only development
3. Example with other services:

```yaml
version: '3.9'

services:
  # Include the CMS compose file
  include:
    - docker-compose.cms.yml

  # Add other services here
  child-service:
    build: child-service
    ports:
      - "8001:8080"
    # ... configuration
```

## File Structure

```
cms/
├── Dockerfile                 # Multi-stage build (assets, vendor, runtime)
├── .dockerignore              # Files to exclude from Docker build
├── .env.docker                # Example Docker environment
├── app/
├── routes/
├── config/
├── resources/
├── database/
└── ...

docker/
├── cms-entrypoint.sh          # Container startup script
└── apache-start.sh

docker-compose.cms.yml        # Compose file with CMS + PostgreSQL
```
