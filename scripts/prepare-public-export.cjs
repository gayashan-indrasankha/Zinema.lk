'use strict';
// ZL-10: local preparation only. No application imports, Git commands or network I/O.
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const { TextDecoder } = require('node:util');

const POLICY_VERSION = 'zl10-v1';
const MANIFEST = 'public-export-manifest.json';
const MAX_FILE_BYTES = 2 * 1024 * 1024;
const SOURCE_ROOTS = new Set(['Zinema.lk Website', 'whatsapp-bot-deploy', 'docs', 'scripts', '.github']);
const APPROVED_PATHS = new Set([
  ".env.example",
  ".gitattributes",
  ".github/pull_request_template.md",
  ".gitignore",
  "AGENTS.md",
  "CONTRIBUTING.md",
  "README.md",
  "Zinema.lk Website/.env.example",
  "Zinema.lk Website/.gitignore",
  "Zinema.lk Website/.htaccess",
  "Zinema.lk Website/JW_PLAYER_SETUP.md",
  "Zinema.lk Website/README.md",
  "Zinema.lk Website/_bottom_nav.php",
  "Zinema.lk Website/_pagination.php",
  "Zinema.lk Website/_top_nav.php",
  "Zinema.lk Website/admin/_layout.php",
  "Zinema.lk Website/admin/analytics.php",
  "Zinema.lk Website/admin/assets/admin.css",
  "Zinema.lk Website/admin/assets/admin.js",
  "Zinema.lk Website/admin/collections.php",
  "Zinema.lk Website/admin/config.php",
  "Zinema.lk Website/admin/cron/cleanup-tokens.php",
  "Zinema.lk Website/admin/cron/reset-daily-stats.php",
  "Zinema.lk Website/admin/episodes.php",
  "Zinema.lk Website/admin/functions.php",
  "Zinema.lk Website/admin/index.php",
  "Zinema.lk Website/admin/login.php",
  "Zinema.lk Website/admin/logout.php",
  "Zinema.lk Website/admin/manage_shots.php",
  "Zinema.lk Website/admin/migrate_shots.sql",
  "Zinema.lk Website/admin/movies.php",
  "Zinema.lk Website/admin/run_migration.php",
  "Zinema.lk Website/admin/series.php",
  "Zinema.lk Website/admin/settings.php",
  "Zinema.lk Website/admin/trailers.php",
  "Zinema.lk Website/admin/whatsapp-bot-tracking.php",
  "Zinema.lk Website/ajax_forgot_password.php",
  "Zinema.lk Website/ajax_login.php",
  "Zinema.lk Website/ajax_resend_verification.php",
  "Zinema.lk Website/ajax_reset_password.php",
  "Zinema.lk Website/ajax_signup.php",
  "Zinema.lk Website/api/add_comment.php",
  "Zinema.lk Website/api/auth-handler.php",
  "Zinema.lk Website/api/check-session.php",
  "Zinema.lk Website/api/collections.php",
  "Zinema.lk Website/api/comments.php",
  "Zinema.lk Website/api/favorite_shot.php",
  "Zinema.lk Website/api/get_comments.php",
  "Zinema.lk Website/api/includes/comment_helpers.php",
  "Zinema.lk Website/api/like_shot.php",
  "Zinema.lk Website/api/live_search.php",
  "Zinema.lk Website/api/movies.php",
  "Zinema.lk Website/api/payment-notify.php",
  "Zinema.lk Website/api/record_view.php",
  "Zinema.lk Website/api/search_content.php",
  "Zinema.lk Website/api/series.php",
  "Zinema.lk Website/api/server-check.php",
  "Zinema.lk Website/api/subscribe.php",
  "Zinema.lk Website/api/track.php",
  "Zinema.lk Website/api/trailers.php",
  "Zinema.lk Website/api/whatsapp/add-message-id.php",
  "Zinema.lk Website/api/whatsapp/bot-error.php",
  "Zinema.lk Website/api/whatsapp/bot-heartbeat.php",
  "Zinema.lk Website/api/whatsapp/config.php",
  "Zinema.lk Website/api/whatsapp/get-aging-messages.php",
  "Zinema.lk Website/api/whatsapp/get-all-message-ids.php",
  "Zinema.lk Website/api/whatsapp/get-message-id.php",
  "Zinema.lk Website/api/whatsapp/get-message-ids.php",
  "Zinema.lk Website/api/whatsapp/get-movie-parts.php",
  "Zinema.lk Website/api/whatsapp/get-popular-content.php",
  "Zinema.lk Website/api/whatsapp/get-popular-files.php",
  "Zinema.lk Website/api/whatsapp/log-cache-warming.php",
  "Zinema.lk Website/api/whatsapp/log-forward.php",
  "Zinema.lk Website/api/whatsapp/log-refresh.php",
  "Zinema.lk Website/api/whatsapp/mark-used.php",
  "Zinema.lk Website/api/whatsapp/needs-refresh.php",
  "Zinema.lk Website/api/whatsapp/refresh-stats.php",
  "Zinema.lk Website/api/whatsapp/update-cache-message-id.php",
  "Zinema.lk Website/api/whatsapp/update-daily-stats.php",
  "Zinema.lk Website/api/whatsapp/update-forward-stats.php",
  "Zinema.lk Website/api/whatsapp/validate-token.php",
  "Zinema.lk Website/assets/css/style.css",
  "Zinema.lk Website/assets/images/README.txt",
  "Zinema.lk Website/assets/js/download.js",
  "Zinema.lk Website/assets/js/main.js",
  "Zinema.lk Website/assets/js/trailers.js",
  "Zinema.lk Website/collection-details.php",
  "Zinema.lk Website/collections.php",
  "Zinema.lk Website/css/comments.css",
  "Zinema.lk Website/css/desktop-scroll-fix.css",
  "Zinema.lk Website/css/desktop-tablet.css",
  "Zinema.lk Website/css/nav_spacing.css",
  "Zinema.lk Website/css/pages.css",
  "Zinema.lk Website/css/pagination_styles.css",
  "Zinema.lk Website/css/profile-style.css",
  "Zinema.lk Website/css/responsive.css",
  "Zinema.lk Website/css/search_popup_styles.css",
  "Zinema.lk Website/css/shots-style.css",
  "Zinema.lk Website/css/style.css",
  "Zinema.lk Website/css/trailer-feed.css",
  "Zinema.lk Website/css/video-player.css",
  "Zinema.lk Website/download.php",
  "Zinema.lk Website/fb-video-api/.env.example",
  "Zinema.lk Website/fb-video-api/.gitignore",
  "Zinema.lk Website/fb-video-api/README.md",
  "Zinema.lk Website/fb-video-api/environment.js",
  "Zinema.lk Website/fb-video-api/index.js",
  "Zinema.lk Website/fb-video-api/package.json",
  "Zinema.lk Website/find_movies.php",
  "Zinema.lk Website/google-callback.php",
  "Zinema.lk Website/includes/TrailerManager.php",
  "Zinema.lk Website/includes/ads.php",
  "Zinema.lk Website/includes/csrf_helper.php",
  "Zinema.lk Website/includes/database.php",
  "Zinema.lk Website/includes/environment.php",
  "Zinema.lk Website/includes/footer.php",
  "Zinema.lk Website/includes/functions.php",
  "Zinema.lk Website/includes/header.php",
  "Zinema.lk Website/includes/mailer.php",
  "Zinema.lk Website/includes/rate_limiter.php",
  "Zinema.lk Website/includes/settings.php",
  "Zinema.lk Website/includes/whatsapp_token.php",
  "Zinema.lk Website/index.php",
  "Zinema.lk Website/js/activeNav.js",
  "Zinema.lk Website/js/collection.js",
  "Zinema.lk Website/js/collections.js",
  "Zinema.lk Website/js/comments.js",
  "Zinema.lk Website/js/countdown-timer.js",
  "Zinema.lk Website/js/download-handler.js",
  "Zinema.lk Website/js/main.js",
  "Zinema.lk Website/js/main.min.js",
  "Zinema.lk Website/js/nav.js",
  "Zinema.lk Website/js/navbar.js",
  "Zinema.lk Website/js/series.js",
  "Zinema.lk Website/js/subscribe.js",
  "Zinema.lk Website/js/tracker.js",
  "Zinema.lk Website/js/trailer-feed.js",
  "Zinema.lk Website/js/tv-series.js",
  "Zinema.lk Website/js/video-player.js",
  "Zinema.lk Website/linkedin_project_roadmap.md",
  "Zinema.lk Website/login.php",
  "Zinema.lk Website/logout.php",
  "Zinema.lk Website/manifest.json",
  "Zinema.lk Website/migrations/episode_parts.sql",
  "Zinema.lk Website/migrations/movie_parts.sql",
  "Zinema.lk Website/movie-comments.php",
  "Zinema.lk Website/movie-details.php",
  "Zinema.lk Website/movies.php",
  "Zinema.lk Website/package-lock.json",
  "Zinema.lk Website/pages/download.php",
  "Zinema.lk Website/pages/movie-detail.php",
  "Zinema.lk Website/pages/trailers.php",
  "Zinema.lk Website/profile.php",
  "Zinema.lk Website/reset-password.php",
  "Zinema.lk Website/robots.txt",
  "Zinema.lk Website/series-details.php",
  "Zinema.lk Website/signup.php",
  "Zinema.lk Website/sitemap.php",
  "Zinema.lk Website/trailers.php",
  "Zinema.lk Website/tv-series.php",
  "Zinema.lk Website/verify-email.php",
  "Zinema.lk Website/whatsapp-watch.php",
  "docs/BASELINE.md",
  "docs/CONTRIBUTIONS.md",
  "docs/SECURITY_EXPORT.md",
  "docs/baseline-source-sha256.txt",
  "docs/recovery/DECISIONS.md",
  "docs/recovery/PROJECT_DESCRIPTION.md",
  "docs/recovery/PUBLISHING.md",
  "docs/recovery/STATUS.md",
  "scripts/check-source.cjs",
  "scripts/prepare-public-export.cjs",
  "scripts/test-public-export.cjs",
  "whatsapp-bot-deploy/.env.example",
  "whatsapp-bot-deploy/.gitignore",
  "whatsapp-bot-deploy/README.md",
  "whatsapp-bot-deploy/config/api-client.js",
  "whatsapp-bot-deploy/config/auto-refresher.js",
  "whatsapp-bot-deploy/config/cache-warmer.js",
  "whatsapp-bot-deploy/config/database.js",
  "whatsapp-bot-deploy/config/environment.js",
  "whatsapp-bot-deploy/config/file-manager.js",
  "whatsapp-bot-deploy/config/health-monitor.js",
  "whatsapp-bot-deploy/config/multi-tier-forwarder.js",
  "whatsapp-bot-deploy/config/upload-queue.js",
  "whatsapp-bot-deploy/database/migration_ultimate_system.sql",
  "whatsapp-bot-deploy/database/migration_ultimate_system_FIXED.sql",
  "whatsapp-bot-deploy/database/schema.sql",
  "whatsapp-bot-deploy/index.js",
  "whatsapp-bot-deploy/package-lock.json",
  "whatsapp-bot-deploy/package.json",
  "whatsapp-bot-deploy/test-scheduler.js"
]);
const EXAMPLES = new Set([
  '.env.example', 'Zinema.lk Website/.env.example', 'whatsapp-bot-deploy/.env.example',
  'Zinema.lk Website/fb-video-api/.env.example'
]);
const EXAMPLE_KEYS = new Set(["ADMIN_NUMBERS","ALLOW_LIVE_INTEGRATIONS","ALLOW_LIVE_WHATSAPP","ALLOW_PRODUCTION_API","API_KEY","API_URL","APP_ENV","BASE_URL","BOT_INSTANCE_ID","BOT_PHONE_NUMBER","CACHE_GROUP_ID_BOT1","DB_HOST","DB_NAME","DB_PASS","DB_USER","EXTRACTOR_MODE","FAKE_MEDIA_URL","FB_VIDEO_API_URL","FUTURE_AI_API_KEY","FUTURE_AI_ENABLED","FUTURE_AI_MAX_INPUT_CHARS","FUTURE_AI_MAX_REQUESTS_PER_DAY","FUTURE_AI_MODEL","FUTURE_AI_PROVIDER","FUTURE_AI_REQUESTS_PER_MINUTE","FUTURE_AI_TIMEOUT_MS","FUTURE_DELIVERY_MODE","FUTURE_DELIVERY_SIMULATOR_URL","GOOGLE_CLIENT_ID","GOOGLE_CLIENT_SECRET","GOOGLE_REDIRECT_URI","LOCAL_MEDIA_PATH","MAIL_FROM_EMAIL","MAIL_FROM_NAME","MAIL_HOST","MAIL_MODE","MAIL_PASSWORD","MAIL_PORT","MAIL_USERNAME","MEDIA_MODE","OAUTH_AUTHORIZE_URL","OAUTH_MODE","OAUTH_TOKEN_URL","OAUTH_USERINFO_URL","PAYMENT_API_URL","PAYMENT_APP_ID","PAYMENT_APP_SECRET","PAYMENT_MODE","PORT","SESSION_PATH","SESSION_SAVE_PATH","STORAGE_GROUP_ID","STORAGE_GROUP_IDS","STREAM_BASE_URL","STREAM_SIGNING_SECRET","TOKEN_PREFIX","WHATSAPP_API_KEY","WHATSAPP_MODE"]);
const MIGRATIONS = new Set([
  'Zinema.lk Website/admin/migrate_shots.sql', 'Zinema.lk Website/migrations/movie_parts.sql',
  'Zinema.lk Website/migrations/episode_parts.sql', 'whatsapp-bot-deploy/database/schema.sql',
  'whatsapp-bot-deploy/database/migration_ultimate_system.sql',
  'whatsapp-bot-deploy/database/migration_ultimate_system_FIXED.sql'
]);
const DENIED_DIRS = new Set([
  '.git', '.recovery-private', '.runtime', 'node_modules', 'vendor', 'phpmailer',
  '.wwebjs_auth', '.wwebjs_cache', 'whatsapp-session', 'sessions', 'session', 'auth-state',
  'session-store', 'logs', 'uploads', 'media', 'bot-media', 'local-media', 'cache', '.cache',
  'profiles', 'browser-profiles', 'chrome-profile', 'backups', 'dumps', 'exports',
  'analytics-exports', 'dist', 'build', 'tmp', 'temp', 'coverage', '.nyc_output',
  '.idea', '.vscode', '__pycache__', 'secrets', 'old'
]);
const REASONS = new Set([
  'excluded-directory', 'unapproved-root', 'unapproved-path', 'sensitive-name', 'environment',
  'database-dump', 'binary-media', 'archive', 'legacy-variant', 'link', 'hardlink',
  'non-regular', 'size-limit', 'encoding', 'credential-pattern', 'contact-pattern',
  'personal-path', 'private-url', 'sql-data-or-execution', 'unsafe-example',
  'invalid-json', 'source-changed'
]);

class ExportError extends Error {
  constructor(code) { super(code); this.name = 'ExportError'; this.code = code; }
}
function fail(code) { throw new ExportError(code); }
function digest(bytes) { return crypto.createHash('sha256').update(bytes).digest('hex'); }
function exists(p) { try { fs.lstatSync(p); return true; } catch (e) { if (e.code === 'ENOENT') return false; throw e; } }
function inside(candidate, parent) {
  const rel = path.relative(parent, candidate);
  return rel === '' || (rel !== '..' && !rel.startsWith('..' + path.sep) && !path.isAbsolute(rel));
}
function safeRelative(p) {
  return typeof p === 'string' && p.length > 0 && !path.isAbsolute(p) &&
    !p.includes('\\') && !/[:\0\r\n]/.test(p) && p.split('/').every(s => s && s !== '.' && s !== '..');
}
function assertAbsolute(p) {
  if (typeof p !== 'string' || !path.isAbsolute(p) || /[\0\r\n]/.test(p) ||
      p.split(/[\\/]/).some(s => s === '.' || s === '..') || /^(?:\\\\|\/\/)/.test(p)) fail('UNSAFE_ABSOLUTE_PATH');
}
function noLinks(p) {
  const absolute = path.resolve(p);
  const parsed = path.parse(absolute);
  let current = parsed.root;
  for (const part of absolute.slice(parsed.root.length).split(path.sep).filter(Boolean)) {
    current = path.join(current, part);
    if (!exists(current)) break;
    if (fs.lstatSync(current).isSymbolicLink()) fail('LINK_PATH_REJECTED');
  }
}
function sourceRoot(input) {
  assertAbsolute(input);
  noLinks(input);
  if (!exists(input) || !fs.lstatSync(input).isDirectory()) fail('INVALID_SOURCE');
  return fs.realpathSync.native(input);
}
function destination(source, input, validation = false) {
  assertAbsolute(input);
  noLinks(input);
  const normalized = path.resolve(input);
  if (inside(normalized, source) || inside(source, normalized)) fail('DESTINATION_OVERLAPS_SOURCE');
  const parent = path.dirname(normalized);
  if (!exists(parent) || !fs.lstatSync(parent).isDirectory()) fail('DESTINATION_PARENT_REQUIRED');
  const canonical = path.join(fs.realpathSync.native(parent), path.basename(normalized));
  if (inside(canonical, source) || inside(source, canonical)) fail('DESTINATION_OVERLAPS_SOURCE');
  if (validation) {
    if (!exists(canonical) || !fs.lstatSync(canonical).isDirectory()) fail('EXPORT_DIRECTORY_REQUIRED');
  } else if (exists(canonical)) fail('DESTINATION_EXISTS');
  return canonical;
}
function directoryDenied(name) {
  const lower = name.toLowerCase();
  return DENIED_DIRS.has(lower) || /^(?:auth_info|baileys_auth|message-store|hot-cache)/i.test(name);
}
function nameReason(p) {
  const name = path.posix.basename(p);
  if (EXAMPLES.has(p)) return null;
  if (/^\.env(?:\.|$)|\.env(?:\.|$)|^\.envrc$/i.test(name)) return 'environment';
  if (/^(?:Database\.sql|backup\.sql)$|(?:^dump[-_]|[-_]dump\.sql$)|\.(?:dump|db|sqlite\d*)(?:[.-]|$)/i.test(name)) return 'database-dump';
  if (/\.(?:zip|7z|rar|tar|tgz|gz)$/i.test(name)) return 'archive';
  if (/\.(?:png|ico|jpe?g|gif|webp|svg|mp4|mkv|avi|webm|mov|m4v|mp3|wav|ogg|flac)$/i.test(name)) return 'binary-media';
  if (/-OLD\.|-original\.|-before-fix\.|-temp\.|^refresh-method-new\.js$/i.test(name)) return 'legacy-variant';
  if (/^(?:store|message-cache|pending-requests|creds|cookies|credentials[^.]*)\.json$|^\.(?:npmrc|netrc|pypirc)$|\.(?:log|pem|key|crt|cer|p12|pfx|bak|backup|tmp|temp|pid|pyc)$|(?:^error_log$|_log\.txt$|^exports\.txt$)/i.test(name)) return 'sensitive-name';
  return null;
}
function approved(p) {
  if (!safeRelative(p) || !APPROVED_PATHS.has(p) || nameReason(p)) return false;
  if (p.includes('/') && !SOURCE_ROOTS.has(p.split('/')[0])) return false;
  return EXAMPLES.has(p) || MIGRATIONS.has(p) ||
    /\.(?:php|js|cjs|css|json|md|txt)$/.test(p) ||
    ['.gitignore', '.gitattributes', 'Zinema.lk Website/.htaccess',
      'Zinema.lk Website/.gitignore', 'whatsapp-bot-deploy/.gitignore',
      'Zinema.lk Website/fb-video-api/.gitignore'].includes(p);
}
function placeholder(value) {
  return /^(?:REPLACE_|YOUR_|FAKE_|EXAMPLE_|SYNTHETIC_)/i.test(value) ||
    /^(?:disabled|local|development|test|dummy|demo)$/i.test(value) ||
    /^\$\{[A-Za-z_][A-Za-z0-9_.]*\}$/.test(value);
}
function exampleSafe(text) {
  if (/-----BEGIN|\b(?:ghp_|github_pat_|sk-proj-)[A-Za-z0-9_-]{16,}/.test(text)) return false;
  const rows = text.split(/\r?\n/).filter(s => s.trim() && !s.trim().startsWith('#'));
  if (!rows.length) return false;
  for (const row of rows) {
    const m = row.match(/^([A-Z][A-Z0-9_]*)=(.*)$/);
    if (!m) return false;
    const key = m[1], value = m[2].trim();
    if (!EXAMPLE_KEYS.has(key)) return false;
    if (/^(?:ALLOW_|FUTURE_AI_ENABLED)/.test(key) && value !== 'false') return false;
    if (key === 'APP_ENV' && !['development', 'test'].includes(value)) return false;
    if (/(?:_MODE|_PROVIDER)$/.test(key) && !['disabled', 'local'].includes(value)) return false;
    if (/^(?:STORAGE_GROUP_IDS?|ADMIN_NUMBERS|BOT_PHONE_NUMBER|CACHE_GROUP_ID_BOT\d+)$/.test(key) && value) return false;
    if (/(?:PASSWORD|PASS|SECRET|API_KEY|SIGNING_KEY|CLIENT_ID|APP_ID)$/.test(key) && value && !placeholder(value)) return false;
    if (/(?:URL|URI)$/.test(key) && value) {
      try {
        const u = new URL(value);
        if (u.hostname !== '127.0.0.1' || u.protocol !== 'http:' || u.username || u.password || u.search || u.hash) return false;
      } catch { return false; }
    }
    if (key === 'DB_HOST' && value !== '127.0.0.1') return false;
    if (key === 'DB_NAME' && !/^zinema_recovery_(?:development|test)$/.test(value)) return false;
    if (key === 'DB_USER' && !/^zinema_recovery_/.test(value)) return false;
    if (/^(?:MAIL_USERNAME|MAIL_FROM_NAME)$/.test(key) && value && !/^(?:REPLACE_|Recovery Demo$)/.test(value)) return false;
    if (key === 'MAIL_FROM_EMAIL' && !/^[A-Za-z0-9._+-]+@example\.invalid$/.test(value)) return false;
    if (/^(?:SESSION_SAVE_PATH|SESSION_PATH|LOCAL_MEDIA_PATH)$/.test(key) &&
        !/^(?:[A-Za-z]:\/Zinema\.lk\/\.runtime\/|\/ABSOLUTE\/PATH\/TO\/)/.test(value)) return false;
  }
  return true;
}
function sqlStructure(text) {
  // Narrow lexer, not a SQL interpreter. Never return literal values or execute SQL.
  let result = '', i = 0;
  while (i < text.length) {
    const ch = text[i], next = text[i + 1];
    if (ch === '/' && next === '*') {
      if (['!', '+'].includes(text[i + 2])) return null; // executable/versioned comments
      const end = text.indexOf('*/', i + 2);
      if (end < 0) return null;
      result += ' '; i = end + 2; continue;
    }
    if ((ch === '-' && next === '-') || ch === '#') {
      const end = text.indexOf('\n', i);
      i = end < 0 ? text.length : end;
      result += ' '; continue;
    }
    if (ch === "'" || ch === '"' || ch === '`') {
      const quote = ch; let closed = false;
      i++;
      while (i < text.length) {
        if (text[i] === '\\') { i += 2; continue; }
        if (text[i] === quote) {
          if (text[i + 1] === quote) { i += 2; continue; }
          i++; closed = true; break;
        }
        i++;
      }
      if (!closed) return null;
      result += quote === '`' ? 'identifier' : "''";
      continue;
    }
    result += ch; i++;
  }
  return result;
}
// Detectors return category names only. Never retain or emit matched values.
function contentReason(p, bytes) {
  let text;
  try { text = new TextDecoder('utf-8', { fatal: true }).decode(bytes); } catch { return 'encoding'; }
  if (text.includes('\0')) return 'encoding';
  if (/-----BEGIN (?:[A-Z ]+)?PRIVATE KEY-----|\b(?:ghp_|github_pat_|sk-proj-)[A-Za-z0-9_-]{16,}/.test(text)) return 'credential-pattern';
  const assignments = /(?:password|passwd|api[_-]?key|api[_-]?secret|client[_-]?secret|auth[_-]?token|access[_-]?token|signing[_-]?secret)["']?\s*[:=]\s*(['"\x60])([^'"\x60\r\n]{4,})\1/ig;
  for (const m of text.matchAll(assignments)) if (!placeholder(m[2])) return 'credential-pattern';
  const definitions = /define\s*\(\s*['"][A-Z_]*(?:PASSWORD|PASS|SECRET|API_KEY|TOKEN)['"]\s*,\s*['"]([^'"]+)['"]/g;
  for (const m of text.matchAll(definitions)) if (!placeholder(m[1])) return 'credential-pattern';
  if (/https?:\/\/[^\s"'<>]+(?:[?&](?:token|key|secret|signature|password|access_token|x-amz-signature)=|:[^\s@/]+@)/i.test(text)) return 'private-url';
  if (/\b\d{8,}(?:-\d+)?@(?:g\.us|s\.whatsapp\.net|c\.us)\b|(?:\+94|\b94|\b07)\d{8,9}\b/.test(text)) return 'contact-pattern';
  if (!p.endsWith('package-lock.json')) {
    const emails = /[A-Za-z0-9._%+-]+@([A-Za-z0-9.-]+\.[A-Za-z]{2,})/g;
    for (const m of text.matchAll(emails)) if (!/(?:^|\.)example\.(?:invalid|com|org)$/.test(m[1]) && !m[1].endsWith('.invalid')) return 'contact-pattern';
  }
  if (/(?:[A-Za-z]:[\\/](?:Users[\\/]|001[\\/])|\/(?:Users|home)\/(?!YOUR_USERNAME)[A-Za-z0-9_.-]+\/)/.test(text)) return 'personal-path';
  if (EXAMPLES.has(p)) return exampleSafe(text) ? null : 'unsafe-example';
  if (MIGRATIONS.has(p)) {
    const tokens = sqlStructure(text);
    if (tokens === null) return 'sql-data-or-execution';
    const statements = tokens.replace(/\bON\s+(?:UPDATE|DELETE)\s+(?:CURRENT_TIMESTAMP(?:\(\))?|CASCADE|SET\s+NULL|RESTRICT|NO\s+ACTION)/ig, '');
    if (/\b(?:INSERT|REPLACE|UPDATE|DELETE|DROP|TRUNCATE|GRANT|REVOKE|DEFINER|PREPARE|EXECUTE|CALL|LOAD\s+DATA|INTO\s+OUTFILE|COPY)\b|CREATE\s+(?:USER|DATABASE|PROCEDURE|FUNCTION)\b/i.test(statements)) return 'sql-data-or-execution';
    const parts = statements.split(';').map(s => s.trim()).filter(Boolean);
    if (!parts.length || parts.some(s => !/^(?:CREATE\s+(?:TABLE|(?:UNIQUE\s+)?INDEX)|ALTER\s+TABLE|DESCRIBE\s+|SHOW\s+(?:COLUMNS\s+FROM|CREATE\s+TABLE))\b/i.test(s))) return 'sql-data-or-execution';
  }
  if (p.endsWith('.json')) {
    try { const obj = JSON.parse(text); if (!obj || typeof obj !== 'object') return 'invalid-json'; } catch { return 'invalid-json'; }
  }
  return null;
}
function readCandidate(root, rel) {
  if (!approved(rel)) fail('UNAPPROVED_CANDIDATE');
  const full = path.join(root, ...rel.split('/'));
  noLinks(full);
  const before = fs.lstatSync(full);
  if (!before.isFile()) return { reason: 'non-regular' };
  if (before.nlink > 1) return { reason: 'hardlink' };
  if (before.size > MAX_FILE_BYTES) return { reason: 'size-limit' };
  const flags = fs.constants.O_RDONLY | (fs.constants.O_NOFOLLOW || 0);
  const fd = fs.openSync(full, flags);
  let bytes, during;
  try { during = fs.fstatSync(fd); bytes = fs.readFileSync(fd); } finally { fs.closeSync(fd); }
  noLinks(full);
  const after = fs.lstatSync(full);
  if (!during.isFile() || during.nlink > 1 || before.ino !== during.ino ||
      during.ino !== after.ino || before.dev !== after.dev || before.size !== after.size ||
      before.mtimeMs !== after.mtimeMs || before.ctimeMs !== after.ctimeMs) return { reason: 'source-changed' };
  const reason = contentReason(rel, bytes);
  return reason ? { reason } : { bytes, sha256: digest(bytes), size: bytes.length };
}
function buildPlan(options) {
  const source = sourceRoot(options.source);
  const output = destination(source, options.output);
  const entries = [], exclusions = {}, review = [];
  function omit(reason, rel) {
    if (!REASONS.has(reason)) fail('UNKNOWN_POLICY_REASON');
    exclusions[reason] = (exclusions[reason] || 0) + 1;
    if (rel && APPROVED_PATHS.has(rel)) review.push({ path: rel, category: reason });
  }
  function visit(dir, prefix) {
    for (const item of fs.readdirSync(dir, { withFileTypes: true }).sort((a, b) => a.name.localeCompare(b.name, 'en'))) {
      const rel = prefix ? prefix + '/' + item.name : item.name;
      const full = path.join(dir, item.name);
      if (!safeRelative(rel)) { omit('sensitive-name'); continue; }
      const stat = fs.lstatSync(full);
      if (stat.isSymbolicLink()) { omit('link', rel); continue; }
      if (stat.isDirectory()) {
        if (directoryDenied(item.name)) { omit('excluded-directory'); continue; }
        if (!prefix && !SOURCE_ROOTS.has(item.name)) { omit('unapproved-root'); continue; }
        if (![...APPROVED_PATHS].some(p => p.startsWith(rel + '/'))) { omit('unapproved-path'); continue; }
        noLinks(full);
        visit(full, rel);
        continue;
      }
      const denied = nameReason(rel);
      if (denied) { omit(denied); continue; }
      if (!approved(rel)) { omit('unapproved-path'); continue; }
      const result = readCandidate(source, rel);
      if (result.reason) { omit(result.reason, rel); continue; }
      entries.push({ path: rel, size: result.size, sha256: result.sha256 });
    }
  }
  visit(source, '');
  entries.sort((a, b) => a.path < b.path ? -1 : a.path > b.path ? 1 : 0);
  if (!entries.length) fail('NO_APPROVED_FILES');
  return { source, output, entries, exclusions, review };
}
function publicSummary(plan, action) {
  return { action, policyVersion: POLICY_VERSION, sourceKind: 'current-working-tree',
    fileCount: plan.entries.length, excludedCounts: plan.exclusions,
    manualReviewCandidates: plan.review, manualApprovalRequiredBeforePublication: true,
    sourceModified: false, networkUsed: false };
}
function createParents(root, rel) {
  let current = root;
  for (const part of rel.split('/').slice(0, -1)) {
    current = path.join(current, part);
    noLinks(current);
    if (!exists(current)) fs.mkdirSync(current, { mode: 0o700 });
    if (!fs.lstatSync(current).isDirectory()) fail('UNSAFE_EXPORT_PARENT');
  }
}
function performExport(plan) {
  destination(plan.source, plan.output);
  // Validate all current bytes before creating any output.
  for (const entry of plan.entries) {
    const current = readCandidate(plan.source, entry.path);
    if (current.reason || current.sha256 !== entry.sha256 || current.size !== entry.size) fail('SOURCE_CHANGED');
  }
  fs.mkdirSync(plan.output, { mode: 0o700 });
  for (const entry of plan.entries) {
    noLinks(plan.output);
    const current = readCandidate(plan.source, entry.path);
    if (current.reason || current.sha256 !== entry.sha256 || current.size !== entry.size) fail('SOURCE_CHANGED');
    createParents(plan.output, entry.path);
    fs.writeFileSync(path.join(plan.output, ...entry.path.split('/')), current.bytes, { flag: 'wx', mode: 0o600 });
  }
  const manifest = { schemaVersion: 1, policyVersion: POLICY_VERSION, sourceKind: 'current-working-tree',
    fileCount: plan.entries.length, files: plan.entries, excludedCounts: plan.exclusions,
    manualApprovalRequiredBeforePublication: true };
  noLinks(plan.output);
  fs.writeFileSync(path.join(plan.output, MANIFEST), JSON.stringify(manifest, null, 2) + '\n', { flag: 'wx', mode: 0o600 });
  return validateExport({ source: plan.source, output: plan.output });
}
function validateExport(options) {
  const source = sourceRoot(options.source);
  const output = destination(source, options.output, true);
  const manifestPath = path.join(output, MANIFEST);
  noLinks(manifestPath);
  if (!exists(manifestPath) || !fs.lstatSync(manifestPath).isFile() ||
      fs.lstatSync(manifestPath).nlink !== 1 || fs.statSync(manifestPath).size > MAX_FILE_BYTES) fail('INVALID_EXPORT_MANIFEST');
  let manifest;
  try { manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8')); } catch { fail('INVALID_EXPORT_MANIFEST'); }
  const allowedKeys = ['schemaVersion', 'policyVersion', 'sourceKind', 'fileCount', 'files',
    'excludedCounts', 'manualApprovalRequiredBeforePublication'].sort();
  if (!manifest || JSON.stringify(Object.keys(manifest).sort()) !== JSON.stringify(allowedKeys) ||
      manifest.schemaVersion !== 1 || manifest.policyVersion !== POLICY_VERSION ||
      manifest.sourceKind !== 'current-working-tree' || manifest.manualApprovalRequiredBeforePublication !== true ||
      !Array.isArray(manifest.files) || manifest.fileCount !== manifest.files.length || !manifest.fileCount ||
      !manifest.excludedCounts || typeof manifest.excludedCounts !== 'object' || Array.isArray(manifest.excludedCounts) ||
      Object.entries(manifest.excludedCounts).some(([k, v]) => !REASONS.has(k) || !Number.isSafeInteger(v) || v < 0)) fail('INVALID_EXPORT_MANIFEST');
  const expected = new Map(), caseNames = new Set();
  for (const entry of manifest.files) {
    if (!entry || JSON.stringify(Object.keys(entry).sort()) !== JSON.stringify(['path', 'sha256', 'size']) ||
        !approved(entry.path) || !/^[a-f0-9]{64}$/.test(entry.sha256) ||
        !Number.isSafeInteger(entry.size) || entry.size < 0 || entry.size > MAX_FILE_BYTES ||
        expected.has(entry.path) || caseNames.has(entry.path.toLowerCase())) fail('INVALID_EXPORT_ENTRY');
    expected.set(entry.path, entry);
    caseNames.add(entry.path.toLowerCase());
  }
  const actual = new Set();
  function walk(dir, prefix) {
    noLinks(dir);
    for (const item of fs.readdirSync(dir, { withFileTypes: true })) {
      const rel = prefix ? prefix + '/' + item.name : item.name;
      const full = path.join(dir, item.name), stat = fs.lstatSync(full);
      if (!safeRelative(rel) || stat.isSymbolicLink()) fail('UNSAFE_EXPORT_PATH');
      if (stat.isDirectory()) {
        if (directoryDenied(item.name) || ![...expected.keys()].some(p => p.startsWith(rel + '/'))) fail('UNAPPROVED_EXPORT_DIRECTORY');
        walk(full, rel);
      } else {
        if (!stat.isFile() || stat.nlink !== 1) fail('UNSAFE_EXPORT_PATH');
        if (rel === MANIFEST) continue;
        const entry = expected.get(rel);
        if (!entry) fail('UNAPPROVED_EXPORT_FILE');
        if (stat.size !== entry.size) fail('EXPORT_HASH_MISMATCH');
        const bytes = fs.readFileSync(full);
        if (digest(bytes) !== entry.sha256 || contentReason(rel, bytes)) fail('EXPORT_CONTENT_REJECTED');
        const original = readCandidate(source, rel);
        if (original.reason || original.sha256 !== entry.sha256 || original.size !== entry.size) fail('EXPORT_SOURCE_MISMATCH');
        actual.add(rel);
      }
    }
  }
  walk(output, '');
  if (actual.size !== expected.size) fail('EXPORT_FILE_COUNT_MISMATCH');
  return { action: 'validate', valid: true, fileCount: actual.size, policyVersion: POLICY_VERSION,
    hashesMatchCurrentSource: true, manualApprovalRequiredBeforePublication: true };
}
function main(argv = process.argv.slice(2)) {
  const help = 'Usage: node scripts/prepare-public-export.cjs --output ABSOLUTE_LOCAL_DIRECTORY [--source ABSOLUTE_SOURCE] [--dry-run | --export | --validate]\nDefault: dry-run; no output files created. Export requires an absent directory outside source. Validate checks an existing export. No upload/publish action exists.';
  if (argv.length === 1 && argv[0] === '--help') { process.stdout.write(help + '\n'); return; }
  const options = { source: path.resolve(__dirname, '..') };
  let mode = 'dry-run', specified = false;
  for (let i = 0; i < argv.length; i++) {
    const arg = argv[i];
    if (['--source', '--output'].includes(arg)) {
      if (!argv[i + 1] || argv[i + 1].startsWith('--') || options[arg.slice(2) + 'Specified']) fail('INVALID_ARGUMENTS');
      options[arg.slice(2)] = argv[++i];
      options[arg.slice(2) + 'Specified'] = true;
    } else if (['--dry-run', '--export', '--validate'].includes(arg)) {
      if (specified) fail('INVALID_ARGUMENTS');
      specified = true; mode = arg.slice(2);
    } else fail('INVALID_ARGUMENTS');
  }
  if (!options.output) fail('OUTPUT_REQUIRED');
  if (mode === 'validate') process.stdout.write(JSON.stringify(validateExport(options), null, 2) + '\n');
  else {
    const plan = buildPlan(options);
    if (mode === 'export') {
      performExport(plan);
      process.stdout.write(JSON.stringify(publicSummary(plan, 'export'), null, 2) + '\n');
    } else process.stdout.write(JSON.stringify(publicSummary(plan, 'dry-run'), null, 2) + '\n');
  }
}
module.exports = { buildPlan, performExport, validateExport, contentReason, approved, main,
  ExportError, MANIFEST, POLICY_VERSION };
if (require.main === module) {
  try { main(); } catch (e) {
    const code = e instanceof ExportError ? e.code : 'FILESYSTEM_OR_POLICY_ERROR';
    // Never print stack traces, raw filesystem errors, matched source or input paths.
    process.stderr.write(JSON.stringify({ error: code, valuesRedacted: true }) + '\n');
    process.exitCode = 1;
  }
}
