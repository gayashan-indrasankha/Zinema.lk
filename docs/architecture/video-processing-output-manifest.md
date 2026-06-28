# Video Processing Output Manifest

## Purpose

The video processing output manifest describes generated HLS output in a structured, safe shape.

It prepares the backend for future playback and publishing work without adding database persistence, CDN logic, or object storage upload in this branch.

## Manifest Shape

`HlsOutputManifest` records:

- processing job ID
- output root
- output directory
- master playlist file name
- master playlist path
- relative playback path
- segment file pattern
- segment path pattern
- segment relative path pattern
- generated timestamp
- validation status and validation errors

Example relative playback shape:

```text
{jobId}/master.m3u8
```

Example segment relative path pattern:

```text
{jobId}/segment_%03d.ts
```

## Builder Flow

```text
HlsOutputPlan
  -> IHlsOutputManifestBuilder
  -> HlsOutputManifest
  -> VideoProcessingExecutionResult.OutputManifest
```

The builder creates relative playback paths from the existing output plan and verifies that output paths remain under the configured output root.

## Playback API Position

The playback output API can expose safe playback information once processed output information is available:

```text
GET /api/videos/{videoId}/playback
  -> IVideoPlaybackOutputService
  -> VideoPlaybackOutputDto
```

The current playback endpoint does not persist or load `HlsOutputManifest` records yet. It uses existing processing job output path information when present and returns a not-playable response when output metadata is not available.

## Validation Rules

The manifest foundation validates that:

- processing job ID is present
- output root and output paths are present
- master playlist is an `.m3u8` file
- relative playback paths are not rooted
- relative playback paths do not contain traversal segments
- playlist and segment paths stay inside the output root

Invalid output plans are rejected before FFmpeg execution.

## Current Boundary

This branch does not persist the manifest in the database, publish production playback URLs, or upload generated files to object storage.

Later branches can use the manifest to create playback metadata, upload HLS files to object storage, or generate public playback paths.
