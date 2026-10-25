# 🚀 Zinema.lk — LinkedIn-Ready Project Roadmap

> A step-by-step plan to understand, clean, modernize, and professionally
> publish this project — with a clean, readable Git commit history.

---

## 📋 The 5 Phases

| Phase | Goal | Git Commits |
|---|---|---|
| **0 — Understand** | Deep-read the current code, map the flow | None (reading only) |
| **1 — Git Setup** | Initialize repo, write .gitignore, first clean commit | `feat: initial project structure` |
| **2 — Cleanup & Security** | Remove hardcoded secrets, fix issues, organize files | Multiple small commits |
| **3 — Architecture Upgrade** | Introduce MVC structure, routing, env config | Multiple structured commits |
| **4 — Stack Modernization** | Selective modern tooling (not a full rewrite) | Feature-by-feature commits |
| **5 — Documentation** | README, architecture diagram, LinkedIn writeup | `docs: add README and architecture` |

---

## 🔍 PHASE 0 — Understanding the Current Project

### What You Have Right Now

#### Current Architecture: **"Big Ball of Mud"** Pattern

This is an honest, common starting point for many real-world solo-built PHP projects.

```
Every page = its own .php file
Logic + HTML + SQL all in one file
No separation of concerns
No routing layer
No environment config
```

**Example — what `movies.php` does in one file:**
1. Connects to DB (via `require_once 'admin/config.php'`)
2. Reads GET params for filters/search/sort/pagination
3. Builds SQL query
4. Runs query
5. Outputs 1500+ lines of HTML/CSS/JavaScript

This is not inherently wrong — many production sites work this way — but it has real problems:

| Problem | Impact |
|---|---|
| DB password hardcoded in `admin/config.php` | Security risk if pushed to GitHub |
| Logic + HTML mixed everywhere | Hard to test, hard to read |
| Same SQL patterns copy-pasted across 8 files | Any DB change needs to be updated in 8 places |
| No routing — every page is a file | URLs tied to filenames |
| Two DB drivers (`mysqli` + `PDO`) both used | Inconsistency, confusion |
| `getGoogleAuthUrl()` function defined in 5 different files | Duplication |

---

### Current Architecture Map (Visualized)

```
Browser
  │
  ▼
Apache (.htaccess handles URL rewriting)
  │
  ├── index.php          ← SQL + Business Logic + HTML + JS (1730 lines)
  ├── movies.php         ← SQL + Business Logic + HTML + JS (1501 lines)
  ├── tv-series.php      ← SQL + Business Logic + HTML + JS (1337 lines)
  ├── movie-details.php  ← SQL + Business Logic + HTML + JS (672 lines)
  ├── series-details.php ← SQL + Business Logic + HTML + JS (1107 lines)
  ├── ...more pages...
  │
  ├── includes/          ← Shared helpers (somewhat separated)
  │   ├── database.php   ← PDO version (not actually used)
  │   ├── ads.php        ← Ad rendering functions
  │   ├── mailer.php     ← Email functions
  │   ├── settings.php   ← DB config functions
  │   ├── csrf_helper.php← Security
  │   └── rate_limiter.php
  │
  ├── admin/             ← Separate protected area
  │   ├── config.php     ← DB connection + hardcoded secrets ⚠️
  │   └── ...admin pages...
  │
  └── api/whatsapp/      ← REST endpoints for the WhatsApp bot
      └── 22 PHP files
```

---

### What Architecture Would Be Better?

#### Recommended: **MVC + Service Layer** (PHP, but structured)

This does NOT mean rewriting everything. It means reorganizing the code so:
- Models handle database queries
- Views handle HTML output
- Controllers handle request → response logic
- Services handle complex business logic (WhatsApp token generation, etc.)

```
Browser
  │
  ▼
public/index.php  ← Single entry point (Front Controller)
  │
  ▼
Router            ← Maps URLs to Controllers
  │
  ├── Controllers/
  │   ├── MovieController.php
  │   ├── SeriesController.php
  │   ├── AuthController.php
  │   └── ProfileController.php
  │
  ├── Models/
  │   ├── Movie.php
  │   ├── Series.php
  │   ├── Episode.php
  │   └── User.php
  │
  ├── Views/
  │   ├── movies/index.php
  │   ├── movies/show.php
  │   └── ...
  │
  └── Services/
      ├── WhatsAppTokenService.php
      ├── AuthService.php
      └── AdsService.php
```

---

### Should We Upgrade the Full Stack?

Here's an honest comparison of your options:

| Option | Pros | Cons | My Recommendation |
|---|---|---|---|
| **Keep PHP, restructure** | No rewrite, same hosting, fast | Still "old school" on resume | ✅ Do this first |
| **Laravel (PHP framework)** | Modern PHP, industry standard, same language | Learning curve, needs refactor | ✅ Phase 3 goal |
| **Next.js (React + API)** | Most in-demand, impressive on LinkedIn | Full rewrite needed, different hosting | ⚡ Future version |
| **Full rewrite from scratch** | Clean slate | Lose your working product | ❌ Don't do this |

**Decision: Gradual upgrade path**
```
Current PHP (Big Ball of Mud)
  → PHP + MVC structure (Phase 3)
    → Laravel (Phase 4, select pages only)
      → Future: Full Next.js rewrite (separate branch)
```

---

## 🔧 PHASE 1 — Git Setup & Initial Commit

### Step 1.1: Create a proper `.gitignore`

Before the first commit, we must ensure secrets and junk files are excluded.

**Files that MUST NOT be committed:**
- `admin/config.php` (contains DB password, OAuth secret, Gmail password)
- `error_log` files
- `whatsapp-bot-deploy/.env*` files
- `whatsapp-bot-deploy/node_modules/`
- `whatsapp-bot-deploy/whatsapp-session/`

### Step 1.2: Create a `.env` system

Move all secrets from `admin/config.php` into a `.env` file.

```
# .env (NOT committed to git)
DB_HOST=localhost
DB_USER=zinexxio_dbuser
DB_PASS=your_password
DB_NAME=zinexxio_cinedrive

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=zinema.lkteam@gmail.com
MAIL_PASSWORD=your_app_password

GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret

BASE_URL=https://zinema.lk
```

### Step 1.3: Initial commit

```bash
git init
git add .
git commit -m "feat: initial commit — Zinema.lk streaming platform"
```

---

## 🛡️ PHASE 2 — Code Cleanup & Security Hardening

### Planned commits for this phase:

```
fix: move all secrets to .env file, remove hardcoded credentials
fix: deduplicate getGoogleAuthUrl() function into includes/auth.php
fix: standardize database connection — remove unused PDO database.php
refactor: extract pagination logic into includes/pagination.php
refactor: extract common SEO meta tag builder into includes/seo.php
style: remove unused CSS selectors from style.css
chore: clean up error_log files, add to .gitignore
```

### Key Issues to Fix:

1. **5 copies of `getGoogleAuthUrl()`** — one in each page file
2. **`includes/database.php`** — PDO version that's never used
3. **`admin/config.php`** — contains live DB credentials
4. **`error_log` files** — 2.8MB of errors committed to the project
5. **Mixed `$conn` (mysqli) and `$pdo` (PDO)** — pick one

---

## ⚙️ PHASE 3 — Architecture Upgrade (MVC Introduction)

### Goal: Introduce clean structure without breaking the live site

We will NOT delete the old files immediately. We'll build the new structure alongside and migrate page by page.

```
New folder structure:
├── src/
│   ├── Controllers/     (new)
│   ├── Models/          (new)
│   ├── Services/        (new)
│   └── Views/           (move existing PHP views here)
├── public/
│   ├── index.php        (new single entry point)
│   ├── css/             (move)
│   ├── js/              (move)
│   └── assets/          (move)
├── config/
│   ├── database.php     (clean single DB connection)
│   └── app.php          (constants, base URL, etc.)
└── .env                 (secrets, not committed)
```

### Planned commits for this phase:

```
refactor: introduce src/ folder with MVC structure
refactor: create Movie model class with DB query methods
refactor: create Series model class
refactor: create MovieController — move movies.php logic here
refactor: create AuthService — consolidate auth logic
refactor: move all views to src/Views/
refactor: introduce simple Router class
feat: single public/index.php entry point (Front Controller pattern)
```

---

## 🌟 PHASE 4 — Stack Modernization (Selective)

### What We'll Add (Not Replace):

| Current | Upgrade | Reason |
|---|---|---|
| No dependency management | **Composer** for PHP | Industry standard |
| No env file | **vlucas/phpdotenv** | Secure config |
| Raw PHP router | **Bramus Router** or **FastRoute** | Clean routing |
| No DB abstraction | **Eloquent ORM** (standalone) | Laravel-style queries |
| Manual form handling | **Respect/Validation** | Clean validation |
| No frontend build | **Vite** (for CSS/JS) | Modern assets |
| No testing | **PHPUnit** (basic tests) | Professional |

### Planned commits:

```
chore: init Composer, add autoloading
feat: add phpdotenv for environment configuration
feat: add FastRoute for clean URL routing
feat: integrate Eloquent ORM for Movie and Series models
feat: add Vite for CSS/JS bundling
feat: add basic PHPUnit test for WhatsApp token generation
```

---

## 📚 PHASE 5 — Documentation & GitHub Presentation

### What Makes a GitHub Repo Look Professional:

1. **README.md** — Project overview, screenshots, tech stack badges, setup guide
2. **Architecture diagram** — Visual flowchart of the system
3. **CONTRIBUTING.md** — How to contribute
4. **`.env.example`** — Template for environment variables
5. **GitHub Actions** — CI/CD pipeline (even a simple one)
6. **Proper branch strategy** — `main`, `develop`, `feature/*`

### Planned commits:

```
docs: add comprehensive README with screenshots and tech stack
docs: add system architecture diagram
docs: add .env.example with all required variables
docs: add CONTRIBUTING.md
ci: add GitHub Actions workflow for PHP syntax check
```

### LinkedIn Post Strategy:

Your LinkedIn post should highlight:
- 🎬 **What the project does** (1 sentence)
- ⚙️ **The unique technical innovation** (WhatsApp bot delivery system)
- 📊 **Scale** (if you have user numbers, page views)
- 🛠️ **Tech stack** (PHP, MySQL, Node.js, Baileys, Cloudflare)
- 🔗 **Link to the live site** + GitHub repo
- 📸 **Screenshots/demo video** (most important)

---

## 🗓️ Suggested Order (Step by Step)

```
WEEK 1: Phase 0 + Phase 1
  ✅ Day 1-2: Deep read every major file together
  ✅ Day 3:   Set up .gitignore + .env
  ✅ Day 4:   Initial clean GitHub commit

WEEK 2: Phase 2 (Cleanup)
  ✅ Day 1: Fix secrets (most critical)
  ✅ Day 2: Deduplicate functions
  ✅ Day 3: Standardize DB connection
  ✅ Day 4: Clean up CSS/JS/error logs

WEEK 3-4: Phase 3 (Architecture)
  ✅ Introduce MVC structure gradually
  ✅ Migrate one page at a time
  ✅ Each migration = one commit

WEEK 5: Phase 4 (Modern Stack)
  ✅ Add Composer + phpdotenv
  ✅ Add FastRoute
  ✅ Optional: Vite for assets

WEEK 6: Phase 5 (Documentation)
  ✅ Write README
  ✅ Create architecture diagram
  ✅ Publish on LinkedIn
```

---

## ❓ Open Questions Before We Start

> [!IMPORTANT]
> **Before Phase 1, you need to decide:**
> 1. Do you want to put this on GitHub as a **public** repo or **private**?
>    - Public = better for LinkedIn, but we MUST remove all secrets first
>    - Private = safer, but less visibility
>
> 2. Do you want to keep the **live site running** while we refactor?
>    - Yes → We work on a local copy / separate branch
>    - No → We can be more aggressive with changes
>
> 3. Do you already have a **GitHub account** with a local Git setup?
>
> 4. Are you comfortable with **command line Git**, or do you prefer a GUI (like GitHub Desktop)?

---

## ✅ Where to Start Right Now

**The very first task is Phase 0, Step 1:**

> Open `admin/config.php`, `movies.php`, `index.php`, and `whatsapp-watch.php`
> and walk through them together so you understand every line.

Tell me: **"Start Phase 0"** and I'll guide you through each file, explaining every line in plain language.

Or tell me: **"Start Phase 1"** and we'll set up Git immediately.
