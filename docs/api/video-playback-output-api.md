# Video Playback Output API

## Overview

The Video Playback Output API provides a public foundation endpoint for checking whether processed video output is available for playback.

This branch does not add a frontend player, CDN integration, signed URLs, DRM, subscription rules, or production playback publishing.

## Endpoint

```text
GET /api/videos/{videoId}/playback
```

Current foundation meaning:

```text
videoId = source media asset ID
```

A later playback model can move this endpoint to a dedicated persisted video/playback entity without changing the basic response concept.

## Response Shape

```json
{
  "videoId": "00000000-0000-0000-0000-000000000100",
  "isPlayable": true,
  "status": "Completed",
  "playlistPath": "media-output/hls/00000000-0000-0000-0000-000000000200/master.m3u8",
  "playbackUrl": "/media-output/hls/00000000-0000-0000-0000-000000000200/master.m3u8",
  "message": null
}
```

When playback is not available:

```json
{
  "videoId": "00000000-0000-0000-0000-000000000100",
  "isPlayable": false,
  "status": "Processing",
  "playlistPath": null,
  "playbackUrl": null,
  "message": "Video processing is still running."
}
```

## Behavior

The endpoint:

- verifies that the source media asset exists
- checks the latest processing job for that media asset
- returns not playable when processing has not started
- returns not playable when processing is pending, queued, processing, failed, or cancelled
- returns not playable when a completed job does not yet have stored output information
- returns playable only when a completed job has a safe relative output path

The endpoint does not expose absolute local machine paths. Unsafe rooted paths, absolute URLs, and traversal paths are not returned.

## Status Codes

- `200 OK` when the source media asset exists, even if playback is not currently available.
- `404 Not Found` when the source media asset does not exist.

## Current Persistence Boundary

The output manifest itself is not persisted in this branch. The playback output service can use existing processing job output path information when it is present.

Future branches can persist output manifests, upload HLS files to object storage, and replace local playback paths with published object-storage paths.
