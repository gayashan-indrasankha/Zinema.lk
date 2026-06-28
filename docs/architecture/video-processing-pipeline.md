# Video Processing Pipeline

## Purpose

The video processing foundation prepares job tracking and worker boundaries for later media processing work.

This branch creates API, service, queue, lifecycle, and guarded FFmpeg/HLS processing foundations. FFmpeg execution is disabled by default and only runs when explicitly enabled through configuration.

## Current Flow

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

New jobs start as:

```text
Pending
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
IVideoProcessingService
IFfmpegCommandBuilder
IFfmpegAvailabilityChecker
```

`Zinema.Worker` registers placeholder implementations. The worker logs a heartbeat and calls the placeholder runner.

The placeholder runner can read queued jobs through `IVideoProcessingQueue.GetQueuedJobsAsync`, claim them through the lifecycle service, and move them from `Queued` to `Processing`. It then calls `IVideoProcessingService`.

When `VideoProcessing__EnableExecution` is `false`, the service returns a disabled result without running FFmpeg and the worker fails the claimed job with a clear message. When execution is enabled, the service checks FFmpeg availability before attempting local HLS output generation.

## Data Boundary

The existing `video_processing_jobs` table is used. The status column remains a string column, so the clearer job status enum does not require a table shape change.

## Future Work

Later branches can add:

- Queue-backed job dispatch.
- Stronger worker job claiming.
- Retry and attempt tracking.
- Source file staging from object storage.
- HLS output upload to object storage.
- Output storage paths.
- Job progress reporting.
- Cleanup and failure recovery.
