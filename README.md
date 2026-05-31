# Hahu Backend Monorepo

This repository contains the Hahu backend microservice stack.
It is structured as a multi-service Laravel/Node.js backend with shared infrastructure managed by Docker Compose for local development, but the codebase is also intended for deployment on Google Cloud (Cloud Run and related GCP services).

## What is included

Top-level services:
- `analysis-recommendation-service` — AI-driven recommendations and learning health insights.
- `child-service` — child profile and parent/child authentication management.
- `subscription-service` — subscription billing and entitlement logic.
- `sync-service` — gameplay ingestion, analytics aggregation, and AI feature export.
- `cms` — content management backend and admin API.
- `gaming-service` — game server backend.

Shared infrastructure in `docker-compose.yml`:
- `postgres` with multiple databases (`cms`, `child`, `subscription`, `sync`, `analysis`)
- `redis`
- `rabbitmq`
- `nginx` gateway for routing service traffic

## Local Docker usage

This repo can be run locally with Docker Desktop:

```powershell
docker compose build
docker compose up
```

Local service URLs via the Nginx gateway:
- `http://localhost/child/`
- `http://localhost/cms/`
- `http://localhost/analysis/`
- `http://localhost/sync/`
- `http://localhost/subscription/`
- `http://localhost/gaming/`

RabbitMQ management UI:
- `http://localhost:15672` (`guest` / `guest`)

## Google Cloud deployment intent

The services are also explicitly documented and configured for Google Cloud Run; e.g.:
- `child-service` has Cloud Run deployment instructions in `child-service/README.md`
- `sync-service` has Cloud Run deployment instructions in `sync-service/README.md`

Other service folders include Cloud Run-friendly Dockerfile conventions, dynamic port binding, and Google Cloud logging-related configuration.

Thus, the primary production target is Google Cloud, with Docker Compose provided primarily for local development and debugging.

## Service-specific docs

See each service folder for details and deployment instructions:
- `analysis-recommendation-service/README.md`
- `child-service/README.md`
- `subscription-service/README.md`
- `sync-service/README.md`
- `cms/README.md`

## Helpful commands

```powershell
docker compose ps
docker compose logs -f
docker compose logs -f cms
docker compose exec cms php artisan route:list
docker compose down
docker compose down -v
docker compose up --build
```

## Notes

- Local Docker is supported and useful for development, but many services are built to run behind Cloud Run with environment-variable configuration.
- Production deployment should use the service-specific Cloud Run/Artifact Registry workflow, not the root Docker Compose stack.
- Service secrets and GCP environment variables are expected to be provided by Cloud Run or Secret Manager in production.
