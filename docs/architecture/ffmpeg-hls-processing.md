# FFmpeg HLS Processing

## Purpose

The FFmpeg/HLS processing foundation prepares local command planning and guarded execution for video processing jobs.

This branch adds command building, output path planning, FFmpeg availability checks, and a local processing service boundary. It does not upload HLS output to object storage, publish HLS URLs, or make production deployment assumptions.

Successful processing results can include an `HlsOutputManifest` that describes the generated local HLS output.

## Configuration

Configuration section:

```text
VideoProcessing
```

Environment variables:

```text
VideoProcessing__FfmpegPath
VideoProcessing__OutputRoot
VideoProcessing__EnableExecution
VideoProcessing__HlsSegmentDurationSeconds
```

Defaults:

```text
VideoProcessing__FfmpegPath=
VideoProcessing__OutputRoot=media-output/hls
VideoProcessing__EnableExecution=false
VideoProcessing__HlsSegmentDurationSeconds=6
```

`EnableExecution` defaults to `false`. With execution disabled, the service returns a clear disabled result and does not run FFmpeg.

## Output Layout

The command builder plans local HLS output under the configured output root:

```text
media-output/hls/{jobId}/master.m3u8
media-output/hls/{jobId}/segment_%03d.ts
```

Generated media output is ignored by Git and must not be committed.

The output manifest describes the same output with safe relative playback paths:

```text
{jobId}/master.m3u8
{jobId}/segment_%03d.ts
```

## Command Shape

The command builder creates argument lists for process execution instead of composing shell commands.

Planned FFmpeg arguments include:

```text
-hide_banner
-nostdin
-y
-i {source}
-c:v h264
-c:a aac
-f hls
-hls_time {seconds}
-hls_playlist_type vod
-hls_segment_filename {segmentPathPattern}
{masterPlaylistPath}
```

## Availability Check

`IFfmpegAvailabilityChecker` checks only the configured FFmpeg path.

If `VideoProcessing__FfmpegPath` is empty, the checker reports that FFmpeg is not configured. The project does not download FFmpeg automatically and does not assume a global FFmpeg install.

## Worker Behavior

The worker:

1. Reads queued jobs.
2. Delegates each job to `IVideoProcessingJobExecutionService`.
3. Lets the execution service claim the job into `Processing`.
4. Calls `IVideoProcessingService`.
5. Builds an HLS output manifest for successful local output.
6. Completes the job only when processing succeeds.
7. Fails the claimed job with a clear message when execution is disabled, FFmpeg is unavailable, validation fails, or FFmpeg returns a failure.

The worker does not delete source files and does not upload HLS output to MinIO in this branch.

## Diagnostic Endpoint

Admins can check local FFmpeg configuration:

```text
GET /api/admin/processing/ffmpeg/status
```

The endpoint requires the `Admin` role and returns whether the FFmpeg path is configured and available.

## Future Work

Later branches can add:

- Local source file staging from object storage.
- HLS output upload to MinIO/S3-compatible storage.
- Output media asset metadata.
- Playback path publishing.
- Progress reporting.
- Retry policies and attempt limits.
- Stream quality variants and master playlist generation.
