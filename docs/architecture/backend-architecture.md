# Backend Architecture

## Overview

The Zinema.lk backend is an ASP.NET Core / .NET 10 solution designed as a Modular Monolith with Clean Architecture boundaries.

The initial backend foundation contains the API host, application layer, domain layer, infrastructure layer, worker host, and test projects. It establishes project references and startup patterns without implementing product features.

## Solution Layout

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

## Dependency Direction

```text
Zinema.Api
  -> Zinema.Application
  -> Zinema.Infrastructure

Zinema.Application
  -> Zinema.Domain

Zinema.Infrastructure
  -> Zinema.Application
  -> Zinema.Domain

Zinema.Worker
  -> Zinema.Application
  -> Zinema.Infrastructure

Zinema.UnitTests
  -> Zinema.Application
  -> Zinema.Domain

Zinema.IntegrationTests
  -> Zinema.Api
  -> Zinema.Application
  -> Zinema.Infrastructure
  -> Zinema.Domain
```

## Layer Responsibilities

### API Layer

`Zinema.Api` owns HTTP delivery concerns:

- Program startup.
- Middleware registration.
- Swagger/OpenAPI.
- Health checks.
- Controllers and API contracts.
- API-level configuration.

The API layer should delegate business workflows to the Application layer.

### Application Layer

`Zinema.Application` owns use cases and orchestration:

- Application services.
- Feature handlers.
- DTOs.
- Validators.
- Interfaces for infrastructure dependencies.
- Result and error abstractions.

The Application layer depends on the Domain layer and exposes interfaces that Infrastructure can implement.

### Domain Layer

`Zinema.Domain` owns core business concepts:

- Entities.
- Value objects.
- Enums.
- Constants.
- Domain exceptions.
- Domain rules.

The Domain layer should remain independent from ASP.NET Core, Entity Framework Core, storage providers, and other infrastructure concerns.

### Infrastructure Layer

`Zinema.Infrastructure` owns technical implementations:

- Database access in later branches.
- Repository implementations in later branches.
- External services in later branches.
- Object storage integrations in later branches.
- Email integrations in later branches.

The Infrastructure layer wires implementations into dependency injection while keeping those details outside the API and Application layers.

### Worker Host

`Zinema.Worker` owns background execution:

- Future video processing jobs.
- Future queue consumers.
- Future scheduled maintenance tasks.

The worker references Application and Infrastructure so background jobs can reuse application use cases and infrastructure adapters.

## Startup Flow

```text
Program.cs
  -> AddApiServices()
  -> AddApplication()
  -> AddInfrastructure()
  -> Build app
  -> UseApiPipeline()
  -> Run app
```

The API pipeline currently includes:

- Global exception handling middleware skeleton.
- Swagger and Swagger UI in development.
- Health check endpoint at `/health`.
- Controller mapping for future endpoints.

## Testing Strategy

`Zinema.UnitTests` will cover domain and application behavior with fast isolated tests.

`Zinema.IntegrationTests` will cover API, infrastructure, and future database flows when those pieces are introduced.

## Current Boundaries

This branch intentionally avoids:

- Database schema.
- Movie management logic.
- Authentication logic.
- Frontend implementation.
- Real media processing logic.

Those concerns will be added in later focused branches.
