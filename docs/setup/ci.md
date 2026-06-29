# CI Workflow

## Purpose

The backend CI workflow verifies that the .NET solution restores, builds, and tests successfully.

Workflow file:

```text
.github/workflows/dotnet-ci.yml
```

## Triggers

The workflow runs for:

- Pull requests targeting `develop`
- Pull requests targeting `main`
- Pushes to `develop`
- Pushes to `main`

## Checks

The workflow runs on `ubuntu-latest` and uses the .NET 10 SDK.

Commands:

```powershell
dotnet restore backend/Zinema.sln
dotnet build backend/Zinema.sln --configuration Release --no-restore
dotnet test backend/Zinema.sln --configuration Release --no-build --logger "trx" --results-directory TestResults
```

Test result files are uploaded as a workflow artifact when available.

## External Services

The CI workflow does not start PostgreSQL, Redis, MinIO, Docker, FFmpeg, or media processing tools. The current integration tests use test-safe in-memory dependencies and do not require external services.

## Current Scope

This workflow runs build and tests only. It does not deploy the application, publish Docker images, upload packages, or require repository secrets.
