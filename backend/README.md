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

The API is configured for PostgreSQL through the `DefaultConnection` connection string in `src/Zinema.Api/appsettings.json`.

Development placeholder:

```text
Host=localhost;Port=5432;Database=zinema_db;Username=postgres;Password=postgres
```

The EF Core context lives in `Zinema.Infrastructure/Persistence/AppDbContext.cs`.

Current migration:

```text
InitialCatalogSchema
```

Apply migrations from the repository root when a local PostgreSQL instance is available:

```bash
dotnet ef database update --project backend/src/Zinema.Infrastructure --startup-project backend/src/Zinema.Api --context AppDbContext
```

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

Create, update, delete, movie management workflows, authentication implementation, video processing, and frontend implementation are intentionally out of scope for this branch.
