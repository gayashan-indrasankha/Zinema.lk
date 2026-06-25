# Feature Inventory

## Public Movie Browsing

- Home page with featured content, latest releases, popular titles, and curated rows.
- Movie and series listing pages.
- Genre, type, year, language, and availability filters.
- Public collection pages for curated groups of content.

## Movie Details

- Title, poster, backdrop, description, release year, runtime, language, genres, cast, and rating.
- Trailer placement.
- Related content suggestions.
- Availability and watch action.
- Reviews, ratings, and comments summary.

## Search and Filtering

- Global search across movies, series, trailers, and collections.
- Typeahead or live search support in later phases.
- Filter combinations for genre, release year, content type, and sort order.
- Admin search for catalog management.

## Watch Page

- HLS video playback.
- Playback metadata and title context.
- Resume-watch support in later phases.
- Related content and watchlist actions.
- Secure playback URL handling through backend authorization.

## User Authentication

- Email and password authentication.
- Optional external identity providers in later phases.
- Email verification and password reset.
- User profile management.
- Role-based authorization for viewer and admin access.

## Watchlist

- Add or remove movies and series.
- View saved titles from the user profile.
- Support for future personalized rows and reminders.

## Reviews and Ratings

- User ratings on movies and series.
- Text reviews or comments.
- Moderation support for admin users.
- Aggregated rating display on detail pages.

## Admin Movie Management

- Create, edit, publish, unpublish, and archive catalog items.
- Manage movies, series, episodes, trailers, collections, genres, and metadata.
- Assign posters, backdrops, thumbnails, and media assets.
- Track content readiness before publishing.

## Media Upload

- Upload source media files and artwork.
- Store original files in S3-compatible object storage.
- Record metadata, upload status, and ownership.
- Validate file type, size, and required metadata.

## Video Processing

- Queue processing jobs after upload.
- Use FFmpeg for transcoding and HLS packaging.
- Generate HLS manifests and segments.
- Create thumbnails and optional preview assets.
- Track processing status, errors, retries, and output locations.

## Dashboard

- Admin overview for catalog totals, published content, users, and media status.
- Processing queue visibility.
- Recent activity and operational alerts.
- Quick links for common admin actions.

## Notifications

- Account notifications such as verification and password reset.
- Admin notifications for failed processing jobs.
- Content notifications for watchlist updates in later phases.
- Email and in-app notification channels in future phases.

## Reports and Analytics

- Content views and engagement trends.
- Top movies, series, and collections.
- User activity summaries.
- Processing success and failure rates.
- Storage usage and media pipeline visibility.

## MVP Features

- Public browsing, search, and detail pages.
- User authentication.
- Watchlist basics.
- Ratings and reviews basics.
- Admin catalog management.
- Media upload records.
- Background video processing status.
- HLS watch page.
- Basic dashboard.
- Foundational reports for content and processing.

## Later-Phase Features

- Personalized recommendations.
- Advanced live search.
- Comment moderation workflows.
- Notification preferences.
- Subscription or payment workflows.
- Advanced analytics dashboards.
- Multi-language metadata.
- Mobile-oriented API support.
- Editorial placement tools.
- Automated media quality checks.
