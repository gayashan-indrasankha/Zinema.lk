# Video Processing Pipeline

## Purpose

The video processing foundation prepares job tracking and worker boundaries for later media processing work.

This branch creates API, service, queue, lifecycle, and guarded FFmpeg/HLS processing foundations. FFmpeg execution is disabled by default and only runs when explicitly enabled through configuration.

## Current Flow

```text
Admin source video upload
  -> POST /api/admin/media-assets/upload
  -> MediaAsset metadata is created
  -> Video processing job is created
  -> Existing queue transition moves job to Queued
```

Admins can still create a job manually for an existing media asset:

```text
Admin request
  -> POST /api/admin/media-assets/{id}/processing-jobs
  -> Application command
  -> Infrastructure EF Core service
  -> video_processing_jobs table
```

The job stores:

- `mediaAssetId`
- `sourceStorageKey`
- `status`
- `attemptCount`
- `queuedAt`
- optional output/error/timing fields for later branches

Manual jobs start as:

```text
Pending
```

Source video uploads create the job and enqueue it immediately:

```text
Queued
```

## Status Model

Processing jobs use `VideoProcessingJobStatus`:

```text
Pending
Queued
Processing
Completed
Failed
Cancelled
```

Current allowed transitions:

```text
Pending -> Queued
Pending -> Cancelled
Queued -> Processing
Queued -> Cancelled
Processing -> Completed
Processing -> Failed
```

Other transitions are blocked by application validation.

## Worker Boundary

Application defines lightweight worker-facing interfaces:

```text
IVideoProcessingQueue
IVideoProcessingJobRunner
IVideoProcessingJobExecutionService
IVideoProcessingService
IFfmpegCommandBuilder
IFfmpegAvailabilityChecker
IHlsOutputManifestBuilder
IVideoPlaybackOutputService
```

`Zinema.Worker` registers placeholder implementations. The worker logs a heartbeat and calls the placeholder runner.

The runner reads queued jobs through `IVideoProcessingQueue.GetQueuedJobsAsync` and delegates each job to `IVideoProcessingJobExecutionService`.

The execution service owns the processing flow:

```text
Queued -> Processing -> Completed
Queued -> Processing -> Failed
```

It can also accept a `Pending` job by enqueueing it before starting processing.

When `VideoProcessing__EnableExecution` is `false`, the service returns a disabled result without running FFmpeg and the worker fails the claimed job with a clear message. When execution is enabled, the service checks FFmpeg availability before attempting local HLS output generation.

When local HLS processing succeeds, the processing result can include an `HlsOutputManifest` with the master playlist path, relative playback path, segment pattern, output directory, generated timestamp, and validation state.

## Playback Output Boundary

The public playback output endpoint is:

```text
GET /api/videos/{videoId}/playback
```

For this foundation, `videoId` is the source media asset ID. The endpoint checks existing processing job state and returns safe playback path information only when completed output path data is available.

It does not expose absolute local paths, generate signed URLs, call a CDN, or pretend playback is ready when output metadata is missing.

## Data Boundary

The existing `video_processing_jobs` table is used. The status column remains a string column, so the clearer job status enum does not require a table shape change.

## Future Work

Later branches can add:

- Stronger worker job claiming.
- Retry and attempt tracking.
- Source file staging from object storage.
- HLS output upload to object storage.
- Output storage paths.
- Playback metadata from output manifests.
- Dedicated persisted playback entities.
- Job progress reporting.
- Cleanup and failure recovery.
