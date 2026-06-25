# Database Design

## Overview

Zinema.lk – Modern Full-Stack Movie Streaming Platform uses PostgreSQL as the primary relational database and Entity Framework Core as the data access foundation.

The first database foundation focuses on catalog and media concepts only. It does not introduce public API CRUD endpoints, authentication implementation, or frontend code.

## Ownership

`Zinema.Domain` owns the entity model and domain enums.

`Zinema.Infrastructure` owns EF Core concerns:

- `AppDbContext`
- Entity configurations
- PostgreSQL registration
- EF Core migrations

The Domain project does not reference EF Core.

## Initial Entities

- `Movie`
- `Series`
- `Episode`
- `Genre`
- `Collection`
- `MovieGenre`
- `MediaAsset`
- `VideoProcessingJob`

## Shared Domain Abstractions

`BaseEntity`

- Provides `Id`.

`AuditableEntity`

- Extends `BaseEntity`.
- Provides `CreatedAt` and `UpdatedAt`.

## Domain Enums

`PublishStatus`

- `Draft`
- `Scheduled`
- `Published`
- `Archived`

`MediaStatus`

- `PendingUpload`
- `Uploaded`
- `Processing`
- `Ready`
- `Failed`
- `Archived`

## Tables

```text
movies
series
episodes
genres
collections
movie_genres
media_assets
video_processing_jobs
```

## Relationship Summary

Movies and genres:

- `Movie` has many `MovieGenre` records.
- `Genre` has many `MovieGenre` records.
- `MovieGenre` creates the many-to-many relationship.
- A unique index prevents duplicate movie and genre pairs.

Collections and movies:

- `Collection` has many `Movie` records.
- `Movie.CollectionId` is optional.
- Deleting a collection leaves movies in place and clears the relationship.

Series and episodes:

- `Series` has many `Episode` records.
- `Episode.SeriesId` is required.
- Episodes are unique per series, season number, and episode number.

Media assets:

- `MediaAsset` can be linked to a movie, series, episode, or collection.
- These relationships are optional.
- Media records remain useful for tracking source and processed files.

Video processing:

- `VideoProcessingJob` belongs to one `MediaAsset`.
- Jobs track status, source storage key, output prefix, attempts, queued time, started time, completed time, and error text.

## Indexes

Slug indexes:

- Unique slug index on `movies`.
- Unique slug index on `series`.
- Unique slug index on `episodes`.
- Unique slug index on `genres`.
- Unique slug index on `collections`.

Search and listing indexes:

- Title indexes on movies, series, episodes, collections, and media assets.
- Publish status indexes on publishable catalog entities.
- Media status index on media assets.
- Processing status and queued time indexes on video processing jobs.

Storage indexes:

- Unique storage key index on media assets.

## EF Core Configuration

Entity configuration classes live in:

```text
backend/src/Zinema.Infrastructure/Persistence/Configurations
```

`AppDbContext` applies configurations from the Infrastructure assembly and updates audit timestamps before save operations.

## Connection String

Development placeholder:

```text
Host=localhost;Port=5432;Database=zinema_db;Username=postgres;Password=postgres
```

The connection string is configured in:

```text
backend/src/Zinema.Api/appsettings.json
```

## Initial Migration

Migration name:

```text
InitialCatalogSchema
```

Migration location:

```text
backend/src/Zinema.Infrastructure/Persistence/Migrations
```
