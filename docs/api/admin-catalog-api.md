# Admin Catalog API

## Overview

The Admin Catalog API provides protected movie and genre management endpoints for Zinema.lk – Modern Full-Stack Movie Streaming Platform.

All endpoints require:

```http
Authorization: Bearer <admin-jwt-access-token>
```

Required role:

```text
Admin
```

This API does not include frontend code, video upload, FFmpeg processing, watchlist features, review features, payment features, or admin media processing workflows.

## Endpoints

Movie endpoints:

```text
POST /api/admin/movies
PUT /api/admin/movies/{id}
DELETE /api/admin/movies/{id}
PATCH /api/admin/movies/{id}/publish
PATCH /api/admin/movies/{id}/unpublish
```

Genre endpoints:

```text
POST /api/admin/genres
PUT /api/admin/genres/{id}
DELETE /api/admin/genres/{id}
```

## Movie Request

```json
{
  "title": "Demo Action Feature",
  "slug": "demo-action-feature",
  "description": "Neutral admin catalog entry.",
  "releaseYear": 2026,
  "runtimeMinutes": 100,
  "language": "English",
  "genreIds": [
    "00000000-0000-0000-0000-000000000010"
  ],
  "publishStatus": "Draft"
}
```

`slug` is optional on create. When omitted, the API generates a safe slug from the title.

On update, omitting `slug` keeps the current slug.

Supported publish status values come from the backend `PublishStatus` enum:

- `Draft`
- `Scheduled`
- `Published`
- `Archived`

## Movie Response

```json
{
  "id": "00000000-0000-0000-0000-000000000001",
  "title": "Demo Action Feature",
  "slug": "demo-action-feature",
  "description": "Neutral admin catalog entry.",
  "releaseYear": 2026,
  "runtimeMinutes": 100,
  "language": "English",
  "publishStatus": "Draft",
  "genres": [
    {
      "id": "00000000-0000-0000-0000-000000000010",
      "name": "Action",
      "slug": "action"
    }
  ]
}
```

## POST /api/admin/movies

Creates a movie and assigns genre links.

Responses:

- `201 Created` when the movie is created.
- `400 Bad Request` for validation errors.
- `409 Conflict` for duplicate slug conflicts.

## PUT /api/admin/movies/{id}

Updates movie metadata and replaces genre links.

Responses:

- `200 OK` when the movie is updated.
- `400 Bad Request` for validation errors.
- `404 Not Found` when the movie does not exist.
- `409 Conflict` for duplicate slug conflicts.

## DELETE /api/admin/movies/{id}

Archives a movie by setting publish status to `Archived`.

The endpoint avoids hard deletion so catalog history and future media-processing relationships stay safe.

Responses:

- `204 No Content` when the movie is archived.
- `404 Not Found` when the movie does not exist.

## PATCH /api/admin/movies/{id}/publish

Sets the movie publish status to `Published`.

Responses:

- `200 OK` with the updated movie.
- `404 Not Found` when the movie does not exist.

## PATCH /api/admin/movies/{id}/unpublish

Sets the movie publish status to `Draft`.

Responses:

- `200 OK` with the updated movie.
- `404 Not Found` when the movie does not exist.

## Genre Request

```json
{
  "name": "Action",
  "slug": "action",
  "description": "Action catalog genre."
}
```

`slug` is optional on create. When omitted, the API generates a safe slug from the name.

On update, omitting `slug` keeps the current slug.

## Genre Response

```json
{
  "id": "00000000-0000-0000-0000-000000000010",
  "name": "Action",
  "slug": "action",
  "description": "Action catalog genre."
}
```

## POST /api/admin/genres

Creates a genre.

Responses:

- `201 Created` when the genre is created.
- `400 Bad Request` for validation errors.
- `409 Conflict` for duplicate name or slug conflicts.

## PUT /api/admin/genres/{id}

Updates a genre.

Responses:

- `200 OK` when the genre is updated.
- `400 Bad Request` for validation errors.
- `404 Not Found` when the genre does not exist.
- `409 Conflict` for duplicate name or slug conflicts.

## DELETE /api/admin/genres/{id}

Deletes a genre only when it is not linked to movies.

Responses:

- `204 No Content` when the genre is deleted.
- `404 Not Found` when the genre does not exist.
- `409 Conflict` when the genre is used by movies.

## Error Shape

Validation, missing-record, and conflict responses use Problem Details.

Example:

```json
{
  "type": "about:blank",
  "title": "Movie slug 'demo-action-feature' is already used.",
  "status": 409,
  "detail": "AdminCatalog.MovieSlugConflict"
}
```

## Notes

- Controllers return DTOs only.
- EF Core write logic lives in Infrastructure.
- Application defines commands, DTOs, validation error codes, and service interfaces.
- Domain remains independent from EF Core and API response contracts.
