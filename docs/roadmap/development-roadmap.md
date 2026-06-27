# Development Roadmap

## Phase 0: Product Planning

- Define product scope, MVP scope, and future scope.
- Document the target architecture and development strategy.
- Capture initial ADRs.
- Establish branch and commit conventions.

## Phase 1: Solution Foundation

Backend:

- Create ASP.NET Core / .NET 10 Web API solution structure.
- Establish Clean Architecture projects and module boundaries.
- Add Entity Framework Core and PostgreSQL configuration.
- Add Redis integration points for future caching and short-lived state.
- Define base API response, error, validation, and logging patterns.

Frontend:

- Create Next.js + TypeScript application structure.
- Add Tailwind CSS and shadcn/ui foundation.
- Define route groups, layout strategy, and shared UI primitives.
- Prepare API client patterns.

DevOps:

- Add Docker Compose for local PostgreSQL, Redis, and S3-compatible storage.
- Add GitHub Actions for build and test checks.
- Add environment variable documentation.

Testing:

- Add backend unit test project.
- Add frontend component and utility testing foundation.
- Add API contract testing approach.

Documentation:

- Document local setup.
- Document coding standards and contribution workflow.

## Phase 2: Catalog and Public Browsing

Backend:

- Implement catalog entities for movies, series, episodes, genres, collections, and media metadata.
- Add REST endpoints for public browse, detail, search, and filtering.
- Add pagination, sorting, and publish-state rules.

Frontend:

- Build home, browse, search results, movie detail, series detail, and collection pages.
- Add responsive layouts and loading states.
- Add empty and error states.

Testing:

- Add catalog service tests.
- Add API integration tests for browse and detail flows.
- Add frontend rendering tests for core pages.

Documentation:

- Document catalog API endpoints.
- Document catalog module decisions.

## Phase 3: Identity, Access, and User Features

Backend:

- Add authentication, profile, role, and permission flows.
- Add email verification and password reset.
- Add watchlist, reviews, ratings, and comments.
- Enforce role-based authorization for admin endpoints.

Frontend:

- Build login, signup, reset password, profile, watchlist, review, and rating flows.
- Add protected route handling.
- Add form validation and user feedback states.

Testing:

- Add authentication and authorization tests.
- Add user feature integration tests.
- Add frontend form and protected route tests.

Documentation:

- Document identity and role model.
- Document user feature API contracts.

## Phase 4: Media and Video Pipeline

Backend:

- Add media asset records and upload lifecycle.
- Add object storage integration.
- Add background job pattern for processing.
- Add processing status, retry, and error tracking.

Media:

- Use FFmpeg for transcoding.
- Package video output as HLS.
- Generate manifests, segments, thumbnails, and optional preview assets.
- Store processed output in S3-compatible object storage.

Frontend:

- Build admin upload and processing status views.
- Build watch page with HLS playback.
- Add playback authorization and failure states.

Testing:

- Add media service tests.
- Add processing job tests with sample fixtures.
- Add watch page integration checks.

Documentation:

- Document media lifecycle.
- Document FFmpeg and HLS requirements.

## Phase 5: Admin Workspace

Backend:

- Add admin endpoints for catalog, media, settings, reports, and dashboard summaries.
- Add audit-friendly update patterns for important admin actions.
- Add validation and publishing readiness checks.

Frontend:

- Build admin dashboard.
- Build movie, series, episode, collection, trailer, and media management screens.
- Add tables, filters, forms, confirmations, and status indicators.

Testing:

- Add admin authorization tests.
- Add admin workflow tests.
- Add frontend tests for high-value forms and tables.

Documentation:

- Document admin workflows.
- Document publishing checklist.

## Phase 6: Analytics, Notifications, and Operations

Backend:

- Add view tracking and engagement metrics.
- Add reports for content performance and media processing.
- Add notification dispatch patterns.
- Add operational health endpoints.

Frontend:

- Build analytics and reports pages.
- Add notification surfaces for admins and users.
- Add dashboard improvements.

DevOps:

- Add deployment-oriented Docker configuration.
- Add CI quality gates.
- Add artifact and environment checks.

Testing:

- Add reporting query tests.
- Add notification tests.
- Add smoke tests for critical flows.

Documentation:

- Document operational checks.
- Document reporting definitions.

## Phase 7: Release Readiness

Backend:

- Harden validation, authorization, logging, and error handling.
- Review database indexes and query performance.
- Review API consistency and versioning needs.

Frontend:

- Polish accessibility, responsive behavior, and loading states.
- Verify cross-browser behavior.
- Improve SEO metadata for public pages.

Media:

- Verify HLS output quality and storage structure.
- Add retry and cleanup routines for failed jobs.

DevOps:

- Finalize deployment checklist.
- Confirm GitHub Actions pipeline.
- Confirm backup and restore expectations for data and media metadata.

Testing:

- Run full regression checks.
- Add end-to-end tests for browse, auth, admin upload, processing status, and watch page flows.

Documentation:

- Complete setup and release documentation.
- Update ADRs when decisions change.
