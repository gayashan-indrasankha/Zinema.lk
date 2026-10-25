> Current audit status (2026-10-25): **live extraction remains disabled**.
> The historical deployment/performance notes below are not launch evidence.
> Use Node >=24 and the committed lockfile with `npm ci --ignore-scripts`.
> The full dependency candidate is audited separately from HTTP-only fixtures.
> Pure parser and DNS-pinned HTTPS transport are tested with original/mock data;
> no Facebook request has been made. Browser fallback remains blocked until an
> explicitly supplied, compatible Chrome executable and outbound isolation are
> verified. Do not deploy this folder or enable live access from these old notes.
> See `../docs/recovery/PRODUCTION_STAGING.md` and `PRODUCTION_LIVE_TEST_PLAN.md`.

# FB Video CDN Extractor API

Facebook video CDN URL extractor for Zinema.lk shots page.

## Features

- **HTTP-first extraction** (fast ~200-500ms) using regex patterns
- **Puppeteer fallback** (reliable ~3-8s) when HTTP fails
- CORS enabled for cross-origin requests
- Health check endpoint for monitoring

## Deployment to Render.com

1. Push this folder to a GitHub repository

2. Go to [Render.com](https://render.com) and create a new **Web Service**

3. Connect your GitHub repository

4. Configure:
   - **Build Command**: `npm install`
   - **Start Command**: `npm start`
   - **Environment**: Node

5. Deploy and copy the URL (e.g., `https://fb-video-api.onrender.com`)

## API Endpoints

### Health Check
```
GET /health
```
Response:
```json
{
  "status": "ok",
  "timestamp": "2024-12-21T00:00:00.000Z",
  "uptime": 12345,
  "version": "1.0.0"
}
```

### Extract Video URL
```
GET /api/extract?url=<FACEBOOK_VIDEO_URL>
```
Response:
```json
{
  "success": true,
  "url": "https://scontent.xx.fbcdn.net/...",
  "method": "http",
  "duration_ms": 450
}
```

## Local Testing

```bash
cd fb-video-api
npm install
npm start
```

Then test:
```
http://localhost:3333/api/extract?url=https://www.facebook.com/share/v/1AKqPLQU7T/
```
