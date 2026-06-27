# Media Storage

## Purpose

The media storage foundation separates media metadata from physical object storage.

This branch manages database metadata for media assets and supports admin image upload to S3-compatible object storage. It does not upload videos, process videos, generate HLS output, or run FFmpeg.

## Current Model

`MediaAsset` records store:

- Display metadata such as title and asset type.
- File metadata such as file name, content type, file size, and storage key.
- Optional public URL.
- Processing or availability status through `MediaStatus`.
- Optional links to movie, series, episode, or collection records.

At least one catalog link is required when admin users create or update media asset metadata.

## Storage Keys

`storageKey` identifies where the object lives in S3-compatible storage.

Example:

```text
media-assets/2026/06/11111111222233334444555555555555-demo-poster.jpg
```

For direct metadata endpoints, admin users supply the storage key. For upload endpoints, the backend generates the key.

## Object Storage Abstraction

Application defines:

```text
IObjectStorageService
ObjectStorageOptions
ObjectUploadRequest
ObjectUploadResult
```

Infrastructure provides a MinIO/S3-compatible implementation that uploads objects and can build public URLs from configuration:

```text
ObjectStorage__Endpoint
ObjectStorage__AccessKey
ObjectStorage__SecretKey
ObjectStorage__BucketName
ObjectStorage__UseSsl
ObjectStorage__EnsureBucketExists
ObjectStorage__PublicBaseUrl
```

If `PublicBaseUrl` is configured, it is used directly. Otherwise, the implementation combines `Endpoint`, `UseSsl`, and `BucketName`.

When `EnsureBucketExists` is enabled, Infrastructure checks for the bucket and creates it if needed before upload.

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

## Admin Upload Flow

```text
Admin multipart request
  -> API form contract
  -> Application upload command
  -> Infrastructure upload service
  -> MinIO/S3-compatible object storage
  -> PostgreSQL media_assets table
```

The API keeps `IFormFile` in the API layer. Application receives a stream-based command and does not reference ASP.NET Core upload types.

Upload validation checks:

- Empty file.
- Maximum file size.
- Allowed image content types.
- Dangerous file names.
- Required catalog relation.
- Existence of supplied catalog links.

Allowed upload content types:

```text
image/jpeg
image/png
image/webp
```

Uploaded media assets are stored with `MediaStatus.Uploaded`.

## Delete Behavior

Admin delete archives metadata by setting `MediaStatus.Archived`.

Physical objects are not deleted. This avoids data loss while upload and processing flows are still separate future work.

## Future Work

Later branches can add:

- Presigned upload URLs.
- Background job creation.
- FFmpeg processing.
- HLS manifest and segment storage.
- Storage cleanup policies.
