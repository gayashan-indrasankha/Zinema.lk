# Video Processing Job Execution

## Purpose

The video processing job execution foundation centralizes the worker job flow in one service.

The worker remains small: it reads available jobs and delegates each job to `IVideoProcessingJobExecutionService`.

## Execution Flow

```text
Zinema.Worker
  -> IVideoProcessingQueue.GetQueuedJobsAsync
  -> IVideoProcessingJobExecutionService.ExecuteAsync
  -> IVideoProcessingJobLifecycleService.StartProcessingJobAsync
  -> IVideoProcessingService.ProcessAsync
  -> IVideoProcessingJobLifecycleService.CompleteProcessingJobAsync
```

Failure flow:

```text
Processing result is disabled, unavailable, invalid, or failed
  -> IVideoProcessingJobLifecycleService.FailProcessingJobAsync
```

## Pending Jobs

The execution service can accept a `Pending` job. It first calls `IVideoProcessingQueue.EnqueueAsync`, then starts the job through the lifecycle service.

The current worker still reads queued jobs from the queue service. This keeps background execution safe and predictable while allowing the execution service to support pending jobs for future fetch strategies.

## Completion Rules

A job is marked `Completed` only when `IVideoProcessingService` returns a successful processing result.

A job is marked `Failed` when:

- processing execution is disabled
- FFmpeg is unavailable
- processing validation fails
- FFmpeg execution fails
- the processing service returns an error result

Failure messages are normalized before being stored on the job.

## Configuration Safety

FFmpeg execution remains disabled by default:

```text
VideoProcessing__EnableExecution=false
```

The execution service does not bypass `IVideoProcessingService`; local FFmpeg execution still depends on the existing processing configuration.

## Current Boundary

This branch does not add object storage upload for HLS output, playback publishing, new database columns, or a new queue backend.
