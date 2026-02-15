<p align="center">
  <img src="assets/images/logo.png" alt="Zinema.lk Logo" width="120" />
</p>

<h1 align="center">🎬 Zinema.lk</h1>

<p align="center">
  <strong>Sri Lanka's Premier Sinhala Movie & TV Series Streaming Platform</strong>
</p>

<p align="center">
  <a href="https://zinema.lk">🌐 Live Site</a> •
  <a href="#features">✨ Features</a> •
  <a href="#tech-stack">🛠 Tech Stack</a> •
  <a href="#getting-started">🚀 Getting Started</a> •
  <a href="#project-structure">📁 Structure</a>
</p>

---

## 📖 About

**Zinema.lk** is a full-stack movie and TV series streaming platform built for the Sri Lankan audience. It offers HD streaming of Sinhala dubbed and original content — complete with a responsive web app, native Android app, admin dashboard, REST API, and an integrated WhatsApp bot for content delivery.

---

## ✨ Features

### 🌐 Web Platform
- **Movie & TV Series Streaming** — Browse, search, and watch content in HD via JW Player
- **Collections** — Curated movie collections (e.g., Spider-Man, Harry Potter, Jumanji)
- **Shots** — TikTok-style vertical short video clips linked to movies/series with likes, comments, and favorites
- **Trailers** — Vertical swipeable trailer viewer
- **User Accounts** — Sign up, login, email verification, Google OAuth, password reset
- **Comments & Social** — Users can comment on movies and shots
- **Subscription System** — Premium content access via Ideamart SMS billing
- **Live Search** — Real-time AJAX-powered content search
- **SEO Optimized** — Dynamic sitemap, meta tags, and Open Graph support
- **PWA Support** — Installable as a Progressive Web App with offline fallback
- **Mobile Detection** — Auto-detects mobile users and suggests the native app
- **Ad Integration** — Configurable ad system with interstitial and banner placements

### 🔧 Admin Dashboard
- **Content Management** — Full CRUD for movies, TV series, episodes, trailers, and shots
- **Collection Manager** — Create and manage movie collections with cover images
- **Analytics Dashboard** — Track page views, popular content, and user engagement
- **Settings Panel** — Configure site-wide settings, ad placements, and API keys
- **WhatsApp Bot Tracking** — Monitor bot activity, token usage, and forward logs
- **Cron Jobs** — Automated tasks for maintenance and scheduling

### 📱 Mobile App (Android)
- **Capacitor-based** hybrid app wrapping the live website
- Native splash screen, status bar customization, and deep linking
- Custom `ZinemaAdsPlugin` for native ad integration
- Network detection with offline fallback page
- App update checking via the API
- App ID: `lk.zinema.app`

### 🤖 WhatsApp Bot
- **Token-based Content Delivery** — Users send a token to receive movie/episode files directly on WhatsApp
- **Multi-bot Architecture** — Supports up to 5 bot instances for load distribution
- **Baileys Integration** — Uses `@whiskeysockets/baileys` for WhatsApp Web API
- **Media Refresh** — Scheduled media refresh to keep file links alive
- **Forward Logging** — Tracks all file forwards with success/failure status
- **Database Synced** — Tokens and message IDs managed via MySQL

### 🔌 REST API
| Endpoint | Description |
|---|---|
| `GET /api/movies.php` | List & search movies |
| `GET /api/series.php` | List & search TV series |
| `GET /api/collections.php` | Browse movie collections |
| `GET /api/trailers.php` | Fetch trailers |
| `GET /api/shots.php` | Feed of short video clips |
| `POST /api/auth-handler.php` | User authentication (login/signup) |
| `GET /api/check-subscription.php` | Verify user subscription status |
| `GET /api/check-session.php` | Validate active user session |
| `POST /api/comments.php` | Manage comments |
| `POST /api/like_shot.php` | Like/unlike shots |
| `POST /api/favorite_shot.php` | Save/unsave favorite shots |
| `POST /api/record_view.php` | Track content views |
| `POST /api/subscribe.php` | Handle subscriptions via Ideamart |
| `GET /api/live_search.php` | Real-time search results |
| `GET /api/app-update.php` | Check for mobile app updates |
| `GET /api/server-check.php` | Server health check |

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla), JW Player |
| **Backend** | PHP 8.x |
| **Database** | MariaDB / MySQL |
| **Server** | Apache (`.htaccess` URL rewriting) |
| **Mobile App** | Capacitor 6 (Android WebView) |
| **WhatsApp Bot** | Node.js, Baileys, mysql2 |
| **Email** | PHPMailer |
| **Auth** | Session-based, Google OAuth, JWT for API |
| **Payments** | Ideamart SMS Subscription |
| **Video Streaming** | JW Player + Google Drive Streamer |

---

## 📁 Project Structure

```
Zinema.lk/
├── admin/                  # Admin dashboard (content management, analytics)
│   ├── cron/               # Scheduled tasks
│   └── assets/             # Admin-specific assets
├── api/                    # REST API endpoints
│   ├── whatsapp/           # WhatsApp bot API routes
│   └── includes/           # API middleware & helpers
├── assets/                 # Static assets (images, icons)
├── css/                    # Stylesheets
├── js/                     # Client-side JavaScript
├── includes/               # Shared PHP includes
│   ├── PHPMailer/          # Email library
│   ├── database.php        # Database connection
│   ├── jwt-helper.php      # JWT token utilities
│   ├── rate_limiter.php    # Rate limiting middleware
│   ├── whatsapp_token.php  # WhatsApp token management
│   └── settings.php        # Site configuration
├── mobile-app/             # Capacitor Android app
│   ├── android/            # Native Android project
│   ├── android-plugin/     # Custom Capacitor plugins
│   ├── www/                # Web assets for the app
│   └── capacitor.config.ts # Capacitor configuration
├── whatsapp-bot/           # WhatsApp bot (Node.js)
│   ├── config/             # Bot configuration files
│   ├── database/           # Database utilities
│   └── index.js            # Main bot entry point
├── pages/                  # Static pages
├── uploads/                # User-uploaded content
├── migrations/             # Database migrations
├── fb-video-api/           # Facebook video integration
├── index.php               # Homepage (movies, shots, trending)
├── movies.php              # Movies listing page
├── tv-series.php           # TV series listing page
├── collections.php         # Movie collections page
├── movie-details.php       # Individual movie page
├── series-details.php      # Series details & episodes
├── download.php            # Download handler with countdown
├── login.php               # User login page
├── signup.php              # User registration page
├── profile.php             # User profile & settings
├── Database.sql            # Database schema
└── .htaccess               # Apache URL rewriting & security
```

---

## 🚀 Getting Started

### Prerequisites

- **PHP 8.x** with `pdo_mysql`, `mbstring`, `openssl` extensions
- **MySQL 8.0+** or **MariaDB 11.x**
- **Apache** with `mod_rewrite` enabled
- **Node.js 14+** (for WhatsApp bot)
- **Android Studio** (for mobile app development)
- **Composer** (optional, for dependency management)

### 1. Clone the Repository

```bash
git clone https://github.com/gayashan-indrasankha/Zinema.lk.git
cd Zinema.lk
```

### 2. Database Setup

```bash
# Create database and import schema
mysql -u root -p -e "CREATE DATABASE zinema_db;"
mysql -u root -p zinema_db < Database.sql
```

### 3. Configure the Application

Update `includes/database.php` with your database credentials:

```php
$host = 'localhost';
$dbname = 'zinema_db';
$username = 'your_username';
$password = 'your_password';
```

Configure site settings in `includes/settings.php` or via the admin panel at `/admin`.

### 4. Web Server (XAMPP / Apache)

```
Place the project in your Apache document root (e.g., C:\xampp\htdocs\Zinema.lk)
Navigate to: http://localhost/Zinema.lk
```

### 5. WhatsApp Bot Setup

```bash
cd whatsapp-bot
cp .env.example .env
# Edit .env with your database credentials and bot configuration
npm install
npm start
# Scan the QR code with WhatsApp to authenticate
```

### 6. Mobile App Setup

> See [`mobile-app/SETUP_GUIDE.md`](mobile-app/SETUP_GUIDE.md) for the full Android build guide.

```bash
cd mobile-app
npm install
npx cap sync
npx cap open android   # Opens in Android Studio
```

---

## 🗄️ Database Schema

The platform uses **16+ tables** including:

| Table | Purpose |
|---|---|
| `movies` | Movie catalog with metadata & streaming URLs |
| `series` | TV series information |
| `episodes` | Individual episodes linked to series |
| `collections` | Curated movie collections |
| `shots` | Short video clips (TikTok-style) |
| `users` | User accounts with verification & subscriptions |
| `admins` | Admin accounts |
| `analytics` | Page view & engagement tracking |
| `whatsapp_tokens` | Token-based WhatsApp content delivery |
| `whatsapp_message_ids` | WhatsApp message ID mapping for content |
| `whatsapp_forward_logs` | Forward delivery tracking |
| `video_tokens` | Legacy video token system |
| `shot_likes` | Shot like tracking |
| `shot_comments` | Shot comment system |
| `user_favorites` | User's saved favorite shots |
| `login_attempts` | Security: login rate limiting |

---

## 🔐 Security Features

- **CSRF Protection** — Token-based cross-site request forgery prevention
- **Rate Limiting** — Configurable rate limiter for login attempts and API calls
- **Password Hashing** — bcrypt password hashing
- **JWT Authentication** — Token-based API authentication for the mobile app
- **Input Sanitization** — Prepared statements and parameterized queries
- **Email Verification** — Required email verification for new accounts
- **`.htaccess` Hardening** — Directory listing disabled, sensitive files protected

---

## 📄 License

This project is **UNLICENSED** — All rights reserved.

---

<p align="center">
  Made with ❤️ in Sri Lanka 🇱🇰
</p>
