# Catalog API

## Overview

The Catalog API provides public read-only access to published movie catalog data for Zinema.lk – Modern Full-Stack Movie Streaming Platform.

This API currently supports:

- Paginated movie browsing.
- Movie detail lookup by slug.
- Genre listing.

It does not include create, update, delete, authentication, admin authorization, video upload, or FFmpeg processing.

## Base Route

```text
/api/catalog
```

## Endpoints

```text
GET /api/catalog/movies
GET /api/catalog/movies/{slug}
GET /api/catalog/genres
```

## GET /api/catalog/movies

Returns a paginated list of movies.

### Query Parameters

`page`

- Optional integer.
- Defaults to `1`.
- Values less than `1` are normalized to `1`.

`pageSize`

- Optional integer.
- Defaults to `20`.
- Maximum value is `100`.
- Values less than `1` are normalized to `20`.

`search`

- Optional text search value.
- Searches movie title and description.

`genre`

- Optional genre name or slug filter.

`publishStatus`

- Optional publish status filter.
- Defaults to `Published`.
- Supported values come from the backend `PublishStatus` enum.

`sortBy`

- Optional sort value.
- Supported values: `latest`, `title`, `year`.
- Defaults to `latest`.

### Example Request

```http
GET /api/catalog/movies?page=1&pageSize=12&search=catalog&genre=drama&sortBy=latest
```

### Example Response

```json
{
  "items": [
    {
      "id": "00000000-0000-0000-0000-000000000001",
      "title": "Sample Catalog Title",
      "slug": "sample-catalog-title",
      "description": "Short public catalog description.",
      "releaseYear": 2026,
      "runtimeMinutes": 120,
      "language": "English",
      "genres": [
        {
          "id": "00000000-0000-0000-0000-000000000010",
          "name": "Drama",
          "slug": "drama"
        }
      ],
      "poster": {
        "id": "00000000-0000-0000-0000-000000000020",
        "title": "Poster",
        "assetType": "poster",
        "contentType": "image/jpeg",
        "publicUrl": "https://cdn.example.com/poster.jpg",
        "fileSizeBytes": 100000
      }
    }
  ],
  "page": 1,
  "pageSize": 12,
  "totalCount": 1,
  "totalPages": 1,
  "hasPreviousPage": false,
  "hasNextPage": false
}
```

## GET /api/catalog/movies/{slug}

Returns a single published movie by slug.

### Route Parameters

`slug`

- Required movie slug.

### Example Request

```http
GET /api/catalog/movies/sample-catalog-title
```

### Example Response

```json
{
  "id": "00000000-0000-0000-0000-000000000001",
  "title": "Sample Catalog Title",
  "slug": "sample-catalog-title",
  "description": "Detailed public catalog description.",
  "releaseYear": 2026,
  "runtimeMinutes": 120,
  "language": "English",
  "genres": [
    {
      "id": "00000000-0000-0000-0000-000000000010",
      "name": "Drama",
      "slug": "drama"
    }
  ],
  "poster": {
    "id": "00000000-0000-0000-0000-000000000020",
    "title": "Poster",
    "assetType": "poster",
    "contentType": "image/jpeg",
    "publicUrl": "https://cdn.example.com/poster.jpg",
    "fileSizeBytes": 100000
  },
  "backdrop": {
    "id": "00000000-0000-0000-0000-000000000021",
    "title": "Backdrop",
    "assetType": "backdrop",
    "contentType": "image/jpeg",
    "publicUrl": "https://cdn.example.com/backdrop.jpg",
    "fileSizeBytes": 200000
  },
  "mediaAssets": [
    {
      "id": "00000000-0000-0000-0000-000000000020",
      "title": "Poster",
      "assetType": "poster",
      "contentType": "image/jpeg",
      "publicUrl": "https://cdn.example.com/poster.jpg",
      "fileSizeBytes": 100000
    }
  ]
}
```

### Not Found Response

When a published movie cannot be found, the API returns a `404` Problem Details response.

```json
{
  "type": "about:blank",
  "title": "Movie was not found.",
  "status": 404,
  "detail": "Catalog.MovieNotFound"
}
```

## GET /api/catalog/genres

Returns genres used by published movie catalog items, sorted by name.

### Example Request

```http
GET /api/catalog/genres
```

### Example Response

```json
[
  {
    "id": "00000000-0000-0000-0000-000000000010",
    "name": "Drama",
    "slug": "drama"
  }
]
```

## Response Shape

Controllers return DTOs only. Domain entities are not exposed directly.

Movie list responses use `PagedResultDto<T>`:

```json
{
  "items": [],
  "page": 1,
  "pageSize": 20,
  "totalCount": 0,
  "totalPages": 0,
  "hasPreviousPage": false,
  "hasNextPage": false
}
```

## Implementation Notes

- Controllers are thin and delegate to `ICatalogQueryService`.
- EF Core read queries live in Infrastructure.
- Read-only queries use `AsNoTracking`.
- Public movie queries default to published content.
- Application DTOs and query models live in `Zinema.Application`.
- Integration tests that execute catalog queries will need a PostgreSQL test database in a later branch.
