# Zinema.lk – Modern Full-Stack Movie Streaming Platform

Zinema.lk is a planned full-stack movie streaming platform for discovering, managing, and watching movie and series content through a polished web experience and a robust administration system.

## Product Goal

The goal is to build a reliable streaming platform that supports public content discovery, authenticated user features, administrative catalog management, and a scalable media processing pipeline for HLS playback.

## Target Users

- Viewers who want to browse, search, and watch movie and series content.
- Registered users who want watchlists, ratings, reviews, and personalized activity.
- Content administrators who manage movies, series, media assets, metadata, and publishing status.
- Platform operators who need dashboards, reporting, storage visibility, and operational checks.

## Public Project Description

Zinema.lk – Modern Full-Stack Movie Streaming Platform combines a Next.js user experience with an ASP.NET Core Web API, PostgreSQL data storage, Redis-backed performance patterns, S3-compatible media storage, and FFmpeg-powered HLS video processing.

## Target Tech Stack

- Frontend: Next.js, TypeScript, Tailwind CSS, shadcn/ui
- Backend: ASP.NET Core / .NET 10 Web API
- Data access: Entity Framework Core
- Database: PostgreSQL
- Cache and short-lived state: Redis
- Object storage: S3-compatible storage
- Video processing: FFmpeg + HLS
- Platform tooling: Docker, GitHub Actions

## Planned Modules

- Catalog: movies, series, collections, genres, metadata, and publish status.
- Streaming: watch pages, playback URLs, HLS manifests, and viewing sessions.
- Identity and access: authentication, profiles, roles, and permissions.
- Engagement: watchlist, reviews, ratings, comments, and user activity.
- Admin: content management, media upload, dashboards, settings, and operational tools.
- Media processing: background jobs, transcoding, thumbnails, HLS packaging, and storage publishing.
- Notifications: account, content, and platform alerts.
- Analytics: views, content performance, user activity, and operational reports.

## Architecture Summary

The platform will use a Modular Monolith with Clean Architecture. The backend will be organized around product modules while sharing one deployable API and one primary database. DDD-lite practices will be used where they help model business behavior without adding unnecessary ceremony.

The media pipeline will use a background job pattern: uploaded source files are stored, queued for processing, transcoded with FFmpeg, packaged as HLS, and published to S3-compatible storage for playback.

## Development Workflow

- Start with public planning and architecture documentation.
- Build the application branch by branch with focused scopes.
- Keep commits small and reviewable.
- Add tests with each meaningful behavior change.
- Use GitHub Actions for build, test, and quality checks.
- Keep documentation updated as decisions and modules become concrete.

## Branch Strategy

- `main` represents the stable public project state.
- `feature/00-product-planning` contains the initial planning documentation.
- Future work should use focused `feature/NN-topic-name` branches.
- Pull requests should describe scope, tests, risks, and documentation changes.

## Setup

Setup instructions will be added when the application structure is introduced.

Planned setup coverage:

- Required SDKs and tools
- Environment variables
- Docker services
- Database setup
- Local API startup
- Local frontend startup
- Media storage and FFmpeg configuration

## Status

This repository currently contains public planning documentation for the target product, architecture, and development strategy.
