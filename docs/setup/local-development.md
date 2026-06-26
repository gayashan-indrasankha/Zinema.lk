# Local Development Setup

## Overview

This guide starts the local services needed by Zinema.lk – Modern Full-Stack Movie Streaming Platform and prepares a safe development database with neutral demo catalog data.

Local services:

- PostgreSQL for application data.
- Redis for future cache and short-lived state work.
- MinIO for S3-compatible local object storage.

No frontend, authentication, admin write endpoints, video upload, or FFmpeg processing is included in this setup branch.

## Prerequisites

- Docker Desktop or Docker Engine with Compose support.
- .NET 10 SDK.
- EF Core CLI.

Install EF Core CLI if needed:

```powershell
dotnet tool install --global dotnet-ef
```

## Environment File

Copy `.env.example` to `.env` for local Docker Compose overrides.

```powershell
Copy-Item .env.example .env
```

The checked-in `.env.example` uses safe local defaults only. Keep real secrets out of Git.

## Start Local Services

From the repository root:

```powershell
docker compose up -d
```

Services:

- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`
- MinIO API: `http://localhost:9000`
- MinIO console: `http://localhost:9001`

Default MinIO local credentials:

```text
minioadmin / minioadmin
```

## Database Configuration

Development connection string:

```text
Host=localhost;Port=5432;Database=zinema_db;Username=postgres;Password=postgres
```

The local connection string is configured in:

```text
backend/src/Zinema.Api/appsettings.Development.json
```

The base `appsettings.json` keeps its connection string generic.

## Apply Migrations

From the repository root:

```powershell
dotnet ef database update --project backend/src/Zinema.Infrastructure --startup-project backend/src/Zinema.Api
```

Or use the helper script:

```powershell
.\scripts\database\update-database.ps1
```

## Optional Demo Seed Data

Demo seeding is Development-only and disabled by default.

To run migrations and seed neutral demo data on API startup:

```powershell
$env:Database__SeedOnStartup = "true"
dotnet run --project backend/src/Zinema.Api
```

Seed data is safe to run repeatedly. It checks existing slugs before inserting records.

Seed summary:

- Genres: Action, Drama, Family, Mystery.
- Movies: Demo Action Feature, Sample Drama Story, Neutral Family Adventure, Catalog Mystery Sample.
- Seed movies are published so public catalog APIs can return local data.
- No real posters, videos, or copyrighted movie names are seeded.

To keep startup seeding disabled:

```powershell
$env:Database__SeedOnStartup = "false"
```

## Run the API

```powershell
dotnet run --project backend/src/Zinema.Api
```

Development URL:

```text
http://localhost:5145
```

## Test URLs

Health:

```http
GET http://localhost:5145/health
```

Movie list:

```http
GET http://localhost:5145/api/catalog/movies
```

Movie search:

```http
GET http://localhost:5145/api/catalog/movies?search=demo&sortBy=latest
```

Genre list:

```http
GET http://localhost:5145/api/catalog/genres
```

Movie detail:

```http
GET http://localhost:5145/api/catalog/movies/demo-action-feature
```

## Stop Local Services

```powershell
docker compose down
```

To remove local service volumes:

```powershell
docker compose down -v
```

## Drop Local Database

The drop helper requires an explicit confirmation switch:

```powershell
.\scripts\database\drop-database.ps1 -ConfirmDrop
```
