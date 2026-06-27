# Admin Media Assets API

## Overview

The Admin Media Assets API provides protected metadata management endpoints for media linked to the Zinema.lk catalog.

All endpoints require:

```http
Authorization: Bearer <admin-jwt-access-token>
```

Required role:

```text
Admin
```

This API stores metadata only. Use `docs/api/admin-media-upload-api.md` for admin image upload. The metadata endpoints do not replace files, delete physical objects, process videos, create HLS output, or run FFmpeg.

## Endpoints

```text
GET /api/admin/media-assets
GET /api/admin/media-assets/{id}
POST /api/admin/media-assets
PUT /api/admin/media-assets/{id}
DELETE /api/admin/media-assets/{id}
```

## List Query Parameters

```text
page
pageSize
assetType
status
movieId
seriesId
episodeId
```

`page` defaults to `1`. `pageSize` defaults to `20` and is capped at `100`.

`assetType` is normalized to lowercase before filtering.

Supported `status` values come from the backend `MediaStatus` enum:

- `PendingUpload`
- `Uploaded`
- `Processing`
- `Ready`
- `Failed`
- `Archived`

Example:

```http
GET /api/admin/media-assets?page=1&pageSize=20&assetType=poster&status=Ready
```

## Request Body

```json
{
  "title": "Demo Poster",
  "assetType": "poster",
  "contentType": "image/jpeg",
  "fileName": "poster.jpg",
  "storageKey": "movies/demo-action-feature/poster.jpg",
  "publicUrl": "http://localhost:9000/zinema-media/movies/demo-action-feature/poster.jpg",
  "fileSizeBytes": 1200,
  "status": "PendingUpload",
  "movieId": "00000000-0000-0000-0000-000000000001",
  "seriesId": null,
  "episodeId": null,
  "collectionId": null
}
```

Rules:

- `title`, `assetType`, `contentType`, `fileName`, and `storageKey` are required.
- `status` is optional and defaults to `PendingUpload`.
- At least one related catalog ID is required.
- When supplied, `movieId`, `seriesId`, `episodeId`, and `collectionId` must point to existing records.
- `storageKey` must be unique.
- `publicUrl` is optional. When omitted, the storage abstraction can build one from configuration if a base URL is configured.

## Response Body

```json
{
  "id": "00000000-0000-0000-0000-000000000100",
  "title": "Demo Poster",
  "assetType": "poster",
  "contentType": "image/jpeg",
  "fileName": "poster.jpg",
  "storageKey": "movies/demo-action-feature/poster.jpg",
  "publicUrl": "http://localhost:9000/zinema-media/movies/demo-action-feature/poster.jpg",
  "fileSizeBytes": 1200,
  "status": "PendingUpload",
  "movieId": "00000000-0000-0000-0000-000000000001",
  "seriesId": null,
  "episodeId": null,
  "collectionId": null,
  "createdAt": "2026-06-28T00:00:00+00:00",
  "updatedAt": null
}
```

## GET /api/admin/media-assets

Returns a paginated metadata list.

Responses:

- `200 OK` with a paginated response.
- `400 Bad Request` for invalid filter values.

## GET /api/admin/media-assets/{id}

Returns one media asset metadata record.

Responses:

- `200 OK` when the record exists.
- `404 Not Found` when the record does not exist.

## POST /api/admin/media-assets

Creates a media asset metadata record.

Responses:

- `201 Created` when the record is created.
- `400 Bad Request` for validation errors.
- `404 Not Found` when a supplied catalog link does not exist.
- `409 Conflict` for duplicate storage keys.

## PUT /api/admin/media-assets/{id}

Updates a media asset metadata record.

Responses:

- `200 OK` when the record is updated.
- `400 Bad Request` for validation errors.
- `404 Not Found` when the record or a supplied catalog link does not exist.
- `409 Conflict` for duplicate storage keys.

## DELETE /api/admin/media-assets/{id}

Archives the media asset metadata by setting status to `Archived`.

This endpoint does not delete physical objects from storage.

Responses:

- `204 No Content` when the record is archived.
- `404 Not Found` when the record does not exist.

## Error Shape

Validation, missing-record, and conflict responses use Problem Details.

Example:

```json
{
  "type": "about:blank",
  "title": "Storage key 'movies/demo-action-feature/poster.jpg' is already used.",
  "status": 409,
  "detail": "AdminMediaAsset.StorageKeyConflict"
}
```

## Notes

- Controllers return DTOs only.
- EF Core write and read logic lives in Infrastructure.
- Application defines DTOs, commands, queries, validation errors, and service interfaces.
- Domain remains independent from EF Core and API response contracts.
