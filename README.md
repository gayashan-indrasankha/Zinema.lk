# CineDrive — PHP Movie Site Starter

This is a small starter structure added to your existing project to demonstrate:

- Separate pages: `pages/movie-detail.php`, `pages/download.php`, `pages/trailers.php`
- Includes for reusable templates: `includes/header.php`, `includes/footer.php`, `includes/functions.php`
- Assets organized under `assets/css`, `assets/js`, `assets/media`
- Tailwind CDN for mobile-responsive styling
- JS for download countdown and TikTok-style trailer viewer

How to try locally (Windows + XAMPP):

1. Place your video files under `uploads/` or `assets/media/`.
2. Open `http://localhost/cinedrive/pages/movie-detail.php` to view the sample page.
3. Open `http://localhost/cinedrive/pages/download.php?file=sample-movie.mp4` to test the countdown.
4. Open `http://localhost/cinedrive/pages/trailers.php` to try the vertical trailer viewer.

Notes:
- Tailwind is included via CDN for quick iteration. For production, compile Tailwind locally.
- Replace placeholder media files and wiring with your database/API as needed.

Next steps (suggested):
- Wire pages to your movies DB and dynamic routing
- Add server-side checks before serving downloads
- Compile Tailwind and add asset versioning
