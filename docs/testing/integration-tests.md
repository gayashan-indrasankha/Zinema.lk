# API Integration Tests

## Purpose

The API integration test foundation verifies lightweight HTTP behavior against the ASP.NET Core application host.

The tests use `WebApplicationFactory<Program>` and run with test-safe configuration. The test host replaces PostgreSQL with an EF Core in-memory database, keeps development seeding disabled, and disables video processing execution.

## Current Coverage

The integration test project currently verifies:

- `GET /health` returns a successful response.
- `GET /api/catalog/genres` returns a JSON array.
- `GET /api/admin/ping` requires authentication.

These tests do not require PostgreSQL, Redis, MinIO, FFmpeg, video files, or generated media output.

## Run Commands

Run all backend tests:

```powershell
dotnet test backend/Zinema.sln
```

Run only API integration tests:

```powershell
dotnet test backend/tests/Zinema.IntegrationTests/Zinema.IntegrationTests.csproj
```

## Test Host Notes

`ZinemaApiFactory` configures the API host for the `Testing` environment and supplies local test values for JWT settings. It removes the production EF Core database provider registration and adds an isolated EF Core in-memory database for each factory instance.

Keep integration tests stable and focused on API behavior. Broader database, storage, and media processing tests can be added later with dedicated test infrastructure.
