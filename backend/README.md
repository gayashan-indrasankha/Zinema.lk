# Zinema Backend

Backend foundation for **Zinema.lk – Modern Full-Stack Movie Streaming Platform**.

This backend uses ASP.NET Core / .NET 10 and is organized as a Modular Monolith with Clean Architecture boundaries.

## Solution Structure

```text
backend/
  Zinema.sln
  src/
    Zinema.Api/
    Zinema.Application/
    Zinema.Domain/
    Zinema.Infrastructure/
    Zinema.Worker/
  tests/
    Zinema.UnitTests/
    Zinema.IntegrationTests/
```

## Project Responsibilities

`Zinema.Api`

- ASP.NET Core Web API entry point.
- API startup and middleware registration.
- Swagger/OpenAPI configuration.
- Health check endpoint at `/health`.
- Controller and contract folders for future API endpoints.

`Zinema.Application`

- Application services and use cases.
- DTOs, validators, interfaces, and orchestration logic.
- Result and error abstractions.
- Dependency injection registration for application services.

`Zinema.Domain`

- Core entities, value objects, enums, constants, exceptions, and domain rules.
- Domain behavior should not depend on infrastructure or API concerns.

`Zinema.Infrastructure`

- EF Core database access.
- PostgreSQL persistence configuration.
- Future external service integrations.
- Future object storage, email, and repository implementations.
- Dependency injection registration for infrastructure services.

`Zinema.Worker`

- Background worker host for future video processing jobs.
- Intended for media processing orchestration, queue handling, and scheduled work.
- No FFmpeg processing is implemented in this foundation branch.

`Zinema.UnitTests`

- Future unit tests for domain and application behavior.
- Includes one simple foundation test for the Result placeholder.

`Zinema.IntegrationTests`

- Placeholder project for future API and infrastructure integration tests.

## Build

From the repository root:

```bash
dotnet build backend/Zinema.sln
```

## Database Setup

The API is configured for PostgreSQL through the `DefaultConnection` connection string.

Local development configuration lives in `src/Zinema.Api/appsettings.Development.json`:

```text
Host=localhost;Port=5432;Database=zinema_db;Username=postgres;Password=postgres
```

The base `appsettings.json` keeps its connection string generic. Use environment variables or Development settings for local values.

The EF Core context lives in `Zinema.Infrastructure/Persistence/AppDbContext.cs`.

Current migration:

```text
InitialCatalogSchema
```

Apply migrations from the repository root when a local PostgreSQL instance is available:

```bash
dotnet ef database update --project backend/src/Zinema.Infrastructure --startup-project backend/src/Zinema.Api --context AppDbContext
```

Or use the helper script:

```powershell
.\scripts\database\update-database.ps1
```

## Local Development Services

Start local PostgreSQL, Redis, and MinIO from the repository root:

```bash
docker compose up -d
```

Local service defaults:

- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`
- MinIO API: `http://localhost:9000`
- MinIO console: `http://localhost:9001`

Copy `.env.example` to `.env` if local port or credential overrides are needed. Keep real secrets out of Git.

Full setup instructions live in `docs/setup/local-development.md`.

## Development Seed Data

Development seeding is disabled by default and only runs in the Development environment when explicitly enabled:

```powershell
$env:Database__SeedOnStartup = "true"
dotnet run --project backend/src/Zinema.Api
```

When enabled, startup applies EF Core migrations and seeds neutral demo catalog data. The seed operation is idempotent and checks slugs before inserting records.

Seed summary:

- Genres: Action, Drama, Family, Mystery.
- Published movies: Demo Action Feature, Sample Drama Story, Neutral Family Adventure, Catalog Mystery Sample.
- No real posters or videos are seeded.

## Catalog API

The public catalog API currently exposes read-only endpoints:

```text
GET /api/catalog/movies
GET /api/catalog/movies/{slug}
GET /api/catalog/genres
```

Catalog controllers return DTOs only. EF Core query logic is implemented in Infrastructure through `ICatalogQueryService`, keeping controllers thin and Application independent from Infrastructure.

Supported movie list query parameters:

- `page`
- `pageSize`
- `search`
- `genre`
- `publishStatus`
- `sortBy`

`publishStatus` defaults to published content. `sortBy` supports `latest`, `title`, and `year`.

Catalog endpoint documentation lives in `docs/api/catalog-api.md`.

Local test URLs:

```text
GET http://localhost:5145/health
GET http://localhost:5145/api/catalog/movies
GET http://localhost:5145/api/catalog/genres
```

## Authentication and Authorization

The API uses ASP.NET Core Identity with JWT Bearer authentication.

Roles:

- `User`
- `Admin`

Public auth endpoints:

```text
POST /api/auth/register
POST /api/auth/login
GET /api/auth/me
```

RBAC verification endpoint:

```text
GET /api/admin/ping
```

`/api/auth/me` requires a valid bearer token. `/api/admin/ping` requires the `Admin` role.

JWT settings are configured through the `Jwt` configuration section. The Development settings use a local-only placeholder signing key. Real signing keys should be supplied through environment variables or secret storage.

Development role seeding is idempotent and runs only when startup seeding is explicitly enabled:

```powershell
$env:Database__SeedOnStartup = "true"
dotnet run --project backend/src/Zinema.Api
```

Development admin user seeding is disabled by default. If enabled, credentials must come from configuration or environment variables:

```powershell
$env:Auth__SeedDevelopmentAdmin = "true"
$env:Auth__DevelopmentAdminEmail = "admin@example.test"
$env:Auth__DevelopmentAdminPassword = "<local-dev-password>"
```

Auth endpoint documentation lives in `docs/api/auth-api.md`. Security notes live in `docs/security/authentication.md`.

## Admin Catalog API

Admin catalog endpoints require a JWT access token for a user with the `Admin` role.

Movie management endpoints:

```text
POST /api/admin/movies
PUT /api/admin/movies/{id}
DELETE /api/admin/movies/{id}
PATCH /api/admin/movies/{id}/publish
PATCH /api/admin/movies/{id}/unpublish
```

Genre management endpoints:

```text
POST /api/admin/genres
PUT /api/admin/genres/{id}
DELETE /api/admin/genres/{id}
```

Movie deletes archive the movie by setting its publish status to `Archived`. Genre deletes are blocked when the genre is linked to movies.

Admin catalog endpoint documentation lives in `docs/api/admin-catalog-api.md`.

## Admin Media Assets API

Admin media asset endpoints require a JWT access token for a user with the `Admin` role.

Media asset metadata endpoints:

```text
GET /api/admin/media-assets
GET /api/admin/media-assets/{id}
POST /api/admin/media-assets
PUT /api/admin/media-assets/{id}
DELETE /api/admin/media-assets/{id}
```

This API manages metadata only. It records fields such as title, asset type, content type, file name, storage key, optional public URL, file size, status, and catalog links.

The metadata endpoints do not upload files, replace files, delete physical objects, run FFmpeg, or generate HLS output. `DELETE` archives metadata by setting media status to `Archived`.

Admin media asset endpoint documentation lives in `docs/api/admin-media-assets-api.md`. Storage architecture notes live in `docs/architecture/media-storage.md`.

## Admin Media Upload API

Admin media upload requires a JWT access token for a user with the `Admin` role.

Upload endpoint:

```text
POST /api/admin/media-assets/upload
```

This endpoint accepts `multipart/form-data`, uploads an image to MinIO/S3-compatible object storage, and creates the related media asset metadata record after upload succeeds.

Allowed upload content types:

```text
image/jpeg
image/png
image/webp
```

Default max upload size:

```text
5242880 bytes
```

Local MinIO defaults are configured in `src/Zinema.Api/appsettings.Development.json` and can be overridden with environment variables:

```text
ObjectStorage__Endpoint=localhost:9000
ObjectStorage__AccessKey=minioadmin
ObjectStorage__SecretKey=minioadmin
ObjectStorage__BucketName=zinema-media
ObjectStorage__UseSsl=false
ObjectStorage__EnsureBucketExists=true
ObjectStorage__PublicBaseUrl=http://localhost:9000/zinema-media
```

When `ObjectStorage__EnsureBucketExists` is `true`, the API creates the bucket if it is missing. This branch does not upload videos, run FFmpeg, transcode media, or generate HLS output.

Admin media upload endpoint documentation lives in `docs/api/admin-media-upload-api.md`.

## Current Scope

This foundation currently includes:

- .NET solution and project structure.
- Clean Architecture project references.
- Dependency injection extension methods.
- Result/Error placeholders.
- Global exception handling middleware skeleton.
- Swagger/OpenAPI setup.
- `/health` endpoint.
- Worker project skeleton.
- Unit and integration test project structure.
- Domain entities for the initial catalog model.
- EF Core `AppDbContext`.
- PostgreSQL registration through Infrastructure.
- Initial catalog migration.
- Public catalog read APIs.
- Application-layer catalog query contracts and DTOs.
- Local Docker Compose services for PostgreSQL, Redis, and MinIO.
- Explicit Development-only demo database seeding.
- ASP.NET Core Identity with Guid-based users and roles.
- JWT Bearer authentication.
- Role-based authorization foundation.
- Admin-only catalog management APIs for movies and genres.
- Admin-only media asset metadata management APIs.
- Object storage URL abstraction foundation.
- Admin-only image upload to MinIO/S3-compatible object storage.

Series, episode, collection management, video upload, watchlist features, review features, payment features, video processing, and frontend implementation are intentionally out of scope for this branch.
