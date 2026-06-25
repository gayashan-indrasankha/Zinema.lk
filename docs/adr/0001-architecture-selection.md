# ADR 0001: Architecture Selection

Date: 2026-06-25

Status: Accepted

## Context

Zinema.lk – Modern Full-Stack Movie Streaming Platform needs to support public browsing, authenticated viewer features, admin catalog management, media upload, video processing, and reporting. The architecture must be easy to develop, test, deploy, and evolve while keeping module boundaries clear.

## Decision

The platform will use:

- Modular Monolith as the backend architecture style.
- Clean Architecture for dependency direction and separation of concerns.
- DDD-lite where it improves domain clarity.
- Next.js + TypeScript for the frontend.
- ASP.NET Core / .NET 10 Web API for the backend.
- Entity Framework Core with PostgreSQL for persistence.
- Redis for cache and short-lived state patterns.
- S3-compatible object storage for media assets.
- FFmpeg + HLS for video processing and playback delivery.

## Why Modular Monolith Was Selected

A Modular Monolith provides strong boundaries without the operational complexity of distributed services. It allows the project to move quickly while still organizing the backend into clear modules such as Catalog, Identity, Streaming, Media Processing, Engagement, Admin, Notifications, and Analytics.

This approach supports:

- One deployable backend.
- Clear module ownership.
- Easier local development.
- Simpler testing and debugging.
- Shared database transactions where they are valuable.
- A future path to separate services if a module later needs independent scaling.

## Why Microservices Are Not Used Initially

Microservices add operational requirements that are not necessary for the first product phases. They typically require distributed tracing, network resilience patterns, service discovery, independent deployment pipelines, data synchronization, and more complex production operations.

The initial priority is to deliver a dependable product foundation with clear boundaries. The architecture can still preserve module separation so that future extraction remains possible when there is a proven scaling or team-ownership need.

## Why Clean Architecture Is Used

Clean Architecture keeps domain rules and application use cases independent from delivery and infrastructure details. This helps the project avoid coupling business behavior directly to frameworks, databases, storage providers, or background job tools.

Expected benefits:

- Testable application behavior.
- Framework-independent domain logic.
- Clear dependency direction.
- Replaceable infrastructure adapters.
- More maintainable module boundaries.

## Why PostgreSQL Is Selected

PostgreSQL is a strong fit because the platform needs relational data, structured metadata, search-friendly querying, transactional consistency, and reporting support.

It works well for:

- Movies, series, episodes, collections, genres, and metadata.
- Users, roles, watchlists, reviews, ratings, and comments.
- Media processing records and publishing states.
- Analytics and reporting queries.
- Entity Framework Core integration.

## Why Next.js + ASP.NET Core Is Selected

Next.js provides a strong frontend foundation for public content pages, routing, server-side rendering options, SEO-friendly pages, and a productive TypeScript developer experience.

ASP.NET Core provides a high-performance backend platform with mature REST API support, authentication and authorization patterns, dependency injection, background services, and first-class tooling for .NET development.

Together they provide:

- Strong separation between user interface and API.
- Type-safe frontend development.
- Robust backend APIs.
- Clear deployment boundaries.
- A practical path for web, admin, and future client experiences.

## Why FFmpeg + HLS Is Used

FFmpeg is widely used for video processing, transcoding, thumbnail generation, and media packaging. HLS is a practical streaming format for web playback and broad device support.

This combination supports:

- Adaptive streaming preparation.
- Segment-based video delivery.
- Standard browser playback through HLS-compatible players.
- Background processing after upload.
- Storage of manifests and segments in S3-compatible object storage.

## Consequences

- Module boundaries must be actively maintained inside the backend.
- Shared database access must follow module ownership rules.
- Background processing must be observable and retryable.
- ADRs should be added or updated as important decisions change.
- The architecture should stay simple until product needs justify more complexity.
