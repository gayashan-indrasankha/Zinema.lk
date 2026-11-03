# Zinema.lk

Sri Lankan movie and TV series site (PHP 8.2 + MySQL/MariaDB). Visitors browse movies,
series and collections, watch short "shots" from Facebook, and get full videos through a
WhatsApp bot using one-time tokens.

## Project structure

```
/                       Public pages (one file per URL: index.php = Shots, movies.php,
                        tv-series.php, collections.php, *-details.php, download.php,
                        whatsapp-watch.php, trailers.php, profile.php, login/signup, ...)
_top_nav.php            Shared top navigation
_bottom_nav.php         Shared bottom navigation (mobile)
_pagination.php         Shared pagination
ajax_*.php              Sign-up, login, email verification and password reset handlers
admin/                  Admin panel (movies, series, episodes, collections, shots,
                        trailers, settings, analytics, WhatsApp bot status)
  config.php            Session, database connection, admin login/CSRF checks
  cron/                 Scheduled jobs for hosting (token cleanup, daily stats)
api/                    JSON endpoints used by the site's JavaScript
  whatsapp/             API for the WhatsApp delivery bot (requires the bot API key)
includes/               Shared PHP: environment config, database (PDO), mailer,
                        settings, WhatsApp tokens, rate limiter, CSRF, trailers
  PHPMailer/            Third-party mail library
css/, js/               Site stylesheets and scripts
assets/images/          Logo and favicon images
assets/vendor/          Self-hosted Bootstrap 5.3.0 and Font Awesome 6.4.0
migrations/             SQL for optional tables (movie/episode parts, trailers)
uploads/                Uploaded cover images and trailers (not in Git)
runtime/php-sessions/   PHP session files (not in Git)
```

## Configuration

Settings come from the web server environment (Apache `SetEnv`), not from `.env` files.
`.env.example` lists every variable. Each integration (`MAIL`, `OAUTH`, `WHATSAPP`, `MEDIA`,
`EXTRACTOR`, `PAYMENT`) has a `*_MODE` of `disabled`, `local` or `live`; `live` requires
`APP_ENV=production`.

## Related projects

- **WhatsApp bot** (Node.js, Baileys): separate project; calls `api/whatsapp/`.
- **FB video API** (`fb-video-api`): separate repository deployed on Render; the Shots
  page calls it through `FB_VIDEO_API_URL` to get playable Facebook video URLs.
