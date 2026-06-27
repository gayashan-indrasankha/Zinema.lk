# Target Architecture

## Architecture Style

Zinema.lk – Modern Full-Stack Movie Streaming Platform will use a Modular Monolith with Clean Architecture.

The Modular Monolith keeps the application in one deployable backend while organizing the codebase into clear product modules. This supports fast development, simple deployment, shared transactions where appropriate, and easier testing.

Clean Architecture separates domain rules, application use cases, infrastructure concerns, and API delivery. This keeps business behavior independent from frameworks, databases, storage providers, and background processing tools.

DDD-lite will be used where it clarifies the model. Modules should use clear language, aggregate boundaries, value objects, domain events, and application services when those patterns reduce ambiguity.

## System Components

Frontend:

- Next.js application with TypeScript.
- Tailwind CSS and shadcn/ui for interface development.
- Public routes for browsing and watching.
- Protected routes for profiles and admin workflows.

Backend:

- ASP.NET Core / .NET 10 REST API.
- Module-oriented application services.
- Role-based authorization.
- REST endpoints for public, user, admin, and operational flows.

Database:

- PostgreSQL as the primary relational database.
- Entity Framework Core for data access and schema evolution.
- Module-owned tables with explicit relationships.

Cache and Short-Lived State:

- Redis for caching, rate limiting support, session-related state, queues, and future performance patterns.

Storage:

- S3-compatible object storage for source media, processed HLS output, posters, thumbnails, and other assets.

Worker:

- Background worker process for media processing and scheduled jobs.
- FFmpeg for transcoding, thumbnail generation, and HLS packaging.

## Text-Based Architecture Diagram

```text
[Viewer / Admin Browser]
          |
          v
[Next.js + TypeScript Frontend]
          |
          v
[ASP.NET Core / .NET 10 REST API]
          |
          +--> [Catalog Module] --------+
          +--> [Identity Module] -------+
          +--> [Streaming Module] ------+
          +--> [Engagement Module] -----+--> [PostgreSQL]
          +--> [Admin Module] ----------+
          +--> [Analytics Module] ------+
          |
          +--> [Redis]
          |
          +--> [S3-Compatible Object Storage]
          |
          v
[Background Worker] --> [FFmpeg + HLS] --> [S3-Compatible Object Storage]
```

## Module Breakdown

Catalog Module:

- Movies, series, episodes, trailers, collections, genres, metadata, and publish state.
- Public browse, search, filtering, and details.

Identity and Access Module:

- Users, profiles, authentication, roles, permissions, email verification, and password reset.
- Role-based authorization for admin workflows.

Streaming Module:

- Watch page support, playback authorization, HLS manifest access, and viewing sessions.
- Future resume-watch and playback history support.

Media Processing Module:

- Upload records, source file tracking, processing jobs, FFmpeg execution, output assets, and processing status.
- Retry and failure handling for video processing.

Engagement Module:

- Watchlist, ratings, reviews, comments, and user activity.
- Moderation support in later phases.

Admin Module:

- Catalog management, media management, dashboard summaries, settings, and operational views.

Notifications Module:

- Account notifications, processing alerts, and future user content notifications.

Analytics Module:

- View tracking, content performance, user activity summaries, processing reports, and storage visibility.

## Request Flow Examples

Public browsing flow:

1. The viewer opens a browse page in the Next.js app.
2. The frontend calls the REST API with filters and pagination.
3. The API validates query parameters and routes to the Catalog module.
4. The Catalog module queries PostgreSQL through EF Core.
5. The API returns a paginated response.
6. The frontend renders content cards, empty states, and pagination controls.

Authenticated watchlist flow:

1. A signed-in user clicks add to watchlist.
2. The frontend sends an authenticated REST request.
3. The API validates the access token and user identity.
4. The Engagement module applies watchlist rules.
5. PostgreSQL stores the watchlist item.
6. The API returns the updated state to the frontend.

Admin movie publishing flow:

1. An admin creates or edits a movie in the admin workspace.
2. The frontend submits metadata through the REST API.
3. The API enforces role-based authorization.
4. The Catalog module validates required publishing fields.
5. PostgreSQL stores the content record and publish state.
6. The admin dashboard reflects the updated readiness status.

## Video Processing Flow

1. An admin uploads a source video and required metadata.
2. The API stores upload metadata in PostgreSQL.
3. The source file is stored in S3-compatible object storage.
4. A background job is queued for processing.
5. The worker downloads or streams the source file for FFmpeg processing.
6. FFmpeg transcodes the video and packages it as HLS.
7. The worker uploads HLS manifests, segments, thumbnails, and preview assets to object storage.
8. Processing status is updated in PostgreSQL.
9. The admin workspace displays success or failure details.
10. Published content can provide authorized playback access to the HLS manifest.

## API Design Principles

- REST-first endpoints with predictable resource naming.
- Clear public, authenticated user, admin, and operational route boundaries.
- Consistent pagination, filtering, validation, and error response patterns.
- Role-based authorization at API and application service boundaries.
- Explicit DTOs for request and response contracts.
- No direct storage paths exposed without authorization rules.

## Data and Storage Principles

- PostgreSQL stores durable relational data and content metadata.
- Object storage stores binary media and derived assets.
- Redis supports cache and short-lived state patterns.
- Media records should keep source, processing, and published output states separate.
- Background jobs should be idempotent where practical.
