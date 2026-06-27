# Media Storage

## Purpose

The media storage foundation separates media metadata from physical object storage.

This branch manages database metadata for media assets and prepares a small object storage abstraction for future upload flows. It does not upload files, process videos, generate HLS output, or run FFmpeg.

## Current Model

`MediaAsset` records store:

- Display metadata such as title and asset type.
- File metadata such as file name, content type, file size, and storage key.
- Optional public URL.
- Processing or availability status through `MediaStatus`.
- Optional links to movie, series, episode, or collection records.

At least one catalog link is required when admin users create or update media asset metadata.

## Storage Keys

`storageKey` identifies where the object is expected to live in S3-compatible storage.

Example:

```text
movies/demo-action-feature/poster.jpg
```

The API only stores this key. It does not verify that the object exists yet.

## Object Storage Abstraction

Application defines:

```text
IObjectStorageService
ObjectStorageOptions
```

Infrastructure provides a no-network implementation that can build a public URL from configuration:

```text
ObjectStorage__Endpoint
ObjectStorage__BucketName
ObjectStorage__PublicBaseUrl
```

If `PublicBaseUrl` is configured, it is used directly. Otherwise, the implementation can combine `Endpoint` and `BucketName`.

No access keys, secret keys, uploads, deletes, or bucket operations are implemented in this branch.

## Admin Metadata Flow

```text
Admin API request
  -> Application command/query contract
  -> Infrastructure EF Core service
  -> PostgreSQL media_assets table
```

The service validates:

- Required metadata fields.
- Metadata length limits that match EF Core configuration.
- Non-negative file size.
- At least one catalog link.
- Existence of supplied catalog links.
- Unique storage key.

## Delete Behavior

Admin delete archives metadata by setting `MediaStatus.Archived`.

Physical objects are not deleted. This avoids data loss while upload and processing flows are still separate future work.

## Future Work

Later branches can add:

- Presigned upload URLs.
- Direct upload coordination.
- Bucket setup checks.
- Background job creation.
- FFmpeg processing.
- HLS manifest and segment storage.
- Storage cleanup policies.
