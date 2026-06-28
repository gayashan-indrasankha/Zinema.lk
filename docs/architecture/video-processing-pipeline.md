# Video Processing Pipeline

## Purpose

The video processing foundation prepares job tracking and worker boundaries for later media processing work.

This branch creates API, service, queue, and lifecycle foundations only. It does not run FFmpeg, transcode video, generate HLS manifests, generate HLS segments, or start operating-system media processes.

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
```

`Zinema.Worker` registers placeholder implementations. The worker logs a heartbeat and calls the placeholder runner.

The placeholder runner can read queued jobs through `IVideoProcessingQueue.GetQueuedJobsAsync`, claim them through the lifecycle service, and move them from `Queued` to `Processing`. It then logs that real processing is not implemented. It does not run commands, process files, write output, or mark jobs completed automatically.

## Data Boundary

The existing `video_processing_jobs` table is used. The status column remains a string column, so the clearer job status enum does not require a table shape change.

## Future Work

Later branches can add:

- Queue-backed job dispatch.
- Stronger worker job claiming.
- Retry and attempt tracking.
- FFmpeg command execution.
- HLS manifest and segment creation.
- Output storage paths.
- Job progress reporting.
- Cleanup and failure recovery.
