# Video Processing Jobs API

## Overview

The Video Processing Jobs API provides protected admin endpoints for creating and tracking media processing job records.

All endpoints require:

```http
Authorization: Bearer <admin-jwt-access-token>
```

Required role:

```text
Admin
```

This API creates job records only. It does not run FFmpeg, transcode video, generate HLS output, or start operating-system processes.

## Endpoints

```text
POST /api/admin/media-assets/{id}/processing-jobs
GET /api/admin/processing-jobs
GET /api/admin/processing-jobs/{id}
POST /api/admin/processing-jobs/{id}/enqueue
PATCH /api/admin/processing-jobs/{id}/start
PATCH /api/admin/processing-jobs/{id}/complete
PATCH /api/admin/processing-jobs/{id}/fail
PATCH /api/admin/processing-jobs/{id}/cancel
GET /api/admin/processing/ffmpeg/status
```

## Job Status Values

Supported status values:

- `Pending`
- `Queued`
- `Processing`
- `Completed`
- `Failed`
- `Cancelled`

New jobs are created with status:

```text
Pending
```

## POST /api/admin/media-assets/{id}/processing-jobs

Creates a video processing job record for an existing media asset.

The endpoint copies the media asset storage key into `sourceStorageKey`. It does not inspect the object, execute media tools, or create output files.

Responses:

- `201 Created` when the job is created.
- `400 Bad Request` when the media asset ID is invalid.
- `404 Not Found` when the media asset does not exist.

Example response:

```json
{
  "id": "00000000-0000-0000-0000-000000000200",
  "mediaAssetId": "00000000-0000-0000-0000-000000000100",
  "mediaAssetTitle": "Demo Trailer Source",
  "sourceStorageKey": "media-assets/2026/06/source-file.mp4",
  "outputStoragePrefix": null,
  "status": "Pending",
  "errorMessage": null,
  "attemptCount": 0,
  "queuedAt": "2026-06-28T00:00:00+00:00",
  "startedAt": null,
  "completedAt": null,
  "createdAt": "2026-06-28T00:00:00+00:00",
  "updatedAt": null
}
```

## GET /api/admin/processing-jobs

Returns a paginated job list.

Query parameters:

```text
page
pageSize
status
mediaAssetId
```

`page` defaults to `1`. `pageSize` defaults to `20` and is capped at `100`.

Example:

```http
GET /api/admin/processing-jobs?page=1&pageSize=20&status=Pending
```

Responses:

- `200 OK` with a paginated response.
- `400 Bad Request` for invalid status filters.

## GET /api/admin/processing-jobs/{id}

Returns one processing job.

Responses:

- `200 OK` when the job exists.
- `404 Not Found` when the job does not exist.

## POST /api/admin/processing-jobs/{id}/enqueue

Moves a waiting job into the queued state.

Allowed enqueue status:

- `Pending`

When enqueue succeeds, the job status becomes `Queued` and `queuedAt` is refreshed.

Responses:

- `200 OK` when the job is enqueued.
- `400 Bad Request` when the job is already queued, processing, completed, failed, or cancelled.
- `404 Not Found` when the job does not exist.

## PATCH /api/admin/processing-jobs/{id}/start

Claims a queued job and moves it into the processing state.

Allowed start status:

- `Queued`

When start succeeds, the job status becomes `Processing`, `startedAt` is set, `attemptCount` is incremented, and any previous error message is cleared.

Responses:

- `200 OK` when the job is started.
- `400 Bad Request` when the job is pending, already processing, completed, failed, or cancelled.
- `404 Not Found` when the job does not exist.

## PATCH /api/admin/processing-jobs/{id}/complete

Marks a processing job as completed.

Allowed complete status:

- `Processing`

When completion succeeds, the job status becomes `Completed`, `completedAt` is set, and any error message is cleared.

Responses:

- `200 OK` when the job is completed.
- `400 Bad Request` when the job is pending, queued, completed, failed, or cancelled.
- `404 Not Found` when the job does not exist.

## PATCH /api/admin/processing-jobs/{id}/fail

Marks a processing job as failed.

Allowed fail status:

- `Processing`

Request body:

```json
{
  "errorMessage": "Placeholder processing failed."
}
```

When failure succeeds, the job status becomes `Failed`, `completedAt` is set, and `errorMessage` stores the failure reason.

Responses:

- `200 OK` when the job is failed.
- `400 Bad Request` when the error message is missing or the job is pending, queued, completed, failed, or cancelled.
- `404 Not Found` when the job does not exist.

## PATCH /api/admin/processing-jobs/{id}/cancel

Cancels a waiting job.

Allowed cancel statuses:

- `Pending`
- `Queued`

When cancellation succeeds, the job status becomes `Cancelled` and `completedAt` is set.

Responses:

- `200 OK` when the job is cancelled.
- `400 Bad Request` when the job is already processing, completed, failed, or cancelled.
- `404 Not Found` when the job does not exist.

## Error Shape

Validation, missing-record, and invalid-transition responses use Problem Details.

Example:

```json
{
  "type": "about:blank",
  "title": "Cannot cancel a video processing job with status 'Processing'.",
  "status": 400,
  "detail": "VideoProcessingJob.InvalidStateTransition"
}
```

## GET /api/admin/processing/ffmpeg/status

Returns local FFmpeg configuration status for admins.

The endpoint checks whether `VideoProcessing__FfmpegPath` is configured and whether the configured path can run an FFmpeg version check.

Example response when FFmpeg is not configured:

```json
{
  "isPathConfigured": false,
  "isAvailable": false,
  "ffmpegPath": null,
  "version": null,
  "message": "FFmpeg path is not configured."
}
```

## Notes

- Controllers return DTOs only.
- EF Core job read/write logic lives in Infrastructure.
- Application defines DTOs, commands, queries, validation helpers, and interfaces.
- Worker interfaces are placeholders for later processing branches.
- The worker can claim queued jobs into `Processing` and call the video processing service.
- FFmpeg execution is disabled by default and controlled by configuration.
