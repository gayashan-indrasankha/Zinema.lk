# Video Processing Lifecycle

## Purpose

The video processing lifecycle foundation defines safe job state transitions for admin operations and the worker host.

This branch does not execute FFmpeg, generate HLS output, transcode videos, modify uploaded files, or run external media tools.

## Status Flow

Video processing jobs use these status values:

```text
Pending
Queued
Processing
Completed
Failed
Cancelled
```

Allowed transitions:

```text
Pending -> Queued
Pending -> Cancelled
Queued -> Processing
Queued -> Cancelled
Processing -> Completed
Processing -> Failed
```

Blocked transitions include:

```text
Pending -> Completed
Pending -> Failed
Completed -> Processing
Failed -> Processing
Cancelled -> Processing
```

Completed, failed, and cancelled jobs are terminal for this foundation and cannot be claimed again by the worker.

## Lifecycle Timestamps

Existing job fields are used:

- `queuedAt` records queue timing.
- `startedAt` is set when a queued job moves to `Processing`.
- `completedAt` is set when a processing job moves to `Completed` or `Failed`.
- `errorMessage` stores the failure reason for failed jobs.
- `attemptCount` increments when a queued job is claimed.

No table shape change is required for this lifecycle foundation.

## Worker Behavior

The worker can read queued jobs and claim them:

```text
Queued -> Processing
```

After claiming a job, the worker calls `IVideoProcessingService`.

When execution is disabled, the service does not run FFmpeg and the worker marks the claimed job as failed with a clear message. When execution is enabled, the worker marks a job completed only after the processing service reports success.

## Admin Lifecycle Endpoints

Admins can manually move jobs through lifecycle states:

```text
PATCH /api/admin/processing-jobs/{id}/start
PATCH /api/admin/processing-jobs/{id}/complete
PATCH /api/admin/processing-jobs/{id}/fail
```

These endpoints are useful for verifying lifecycle behavior while real media processing remains out of scope.

## Future Work

Later branches can add:

- Worker job claiming with stronger concurrency controls.
- Real media processing execution.
- HLS output generation.
- Retry policies and attempt limits.
- Progress reporting.
- Output storage metadata.
