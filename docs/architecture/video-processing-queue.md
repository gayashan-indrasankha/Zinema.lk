# Video Processing Queue

## Purpose

The video processing queue foundation lets admins move waiting processing job records into a queue-ready state and lets the worker observe queued jobs.

This branch does not execute FFmpeg, generate HLS files, transcode videos, modify uploaded files, or require Redis.

## Queue Model

The current queue is EF Core backed:

```text
video_processing_jobs.status
```

Enqueue changes a job from:

```text
Pending -> Queued
```

Only `Pending` jobs can be enqueued.

These statuses cannot be enqueued:

```text
Queued
Processing
Completed
Failed
Cancelled
```

## Admin Flow

```text
POST /api/admin/processing-jobs/{id}/enqueue
  -> IVideoProcessingJobService
  -> IVideoProcessingQueue
  -> VideoProcessingQueueService
  -> video_processing_jobs table
```

The endpoint returns the updated job DTO.

## Worker Flow

```text
Zinema.Worker
  -> IVideoProcessingJobRunner
  -> IVideoProcessingQueue.GetQueuedJobsAsync
  -> queued job DTOs
  -> IVideoProcessingJobLifecycleService.StartProcessingJobAsync
  -> Processing status
```

The current worker runner is placeholder-only. It reads queued jobs, claims them by moving them from `Queued` to `Processing`, and logs that real processing is not implemented.

It does not run FFmpeg, write output files, generate HLS output, or mark jobs completed automatically.

## Redis

Redis is available in local Docker Compose for later branches, but this branch does not require Redis. The queue foundation uses PostgreSQL job status transitions so it remains simple and easy to test.

## Future Work

Later branches can add:

- Redis-backed dispatch.
- Stronger worker job claiming.
- Retry and attempt handling.
- FFmpeg execution.
- HLS manifest and segment output.
- Progress reporting.
- Failure recovery.
