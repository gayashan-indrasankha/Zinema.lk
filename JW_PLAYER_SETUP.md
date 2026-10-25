# JW Player Setup Guide for CineDrive

## ✅ Complete Integration Added to movie-details.php

The JW Player video integration has been fully implemented with Facebook video support.

---

## 🚀 Quick Setup (3 Steps)

### Step 1: Get JW Player License Key

1. **Sign up for JW Player** at https://www.jwplayer.com/
   - Free tier available for testing
   - Or use existing license key

2. **Get your player library URL**:
   - Format: `https://cdn.jwplayer.com/libraries/YOUR_KEY_HERE.js`
   - Example: `https://cdn.jwplayer.com/libraries/AbCdEfGh.js`

### Step 2: Update movie-details.php

Open `movie-details.php` and find this line (near the bottom):

```html
<script src="https://cdn.jwplayer.com/libraries/YOUR_JW_PLAYER_KEY.js"></script>
```

**Replace `YOUR_JW_PLAYER_KEY` with your actual JW Player key:**

```html
<script src="https://cdn.jwplayer.com/libraries/AbCdEfGh.js"></script>
```

### Step 3: Test It!

1. Start backend server:
   ```bash
   cd backend
   npm start
   ```

2. Open movie details page in browser:
   ```
   http://localhost/cinedrive/movie-details.php?id=8
   ```

3. Click the **Play** button

4. Video should load in a modal lightbox!

---

## 🎬 Features Included

✅ **Modal Lightbox** - Opens video in fullscreen overlay  
✅ **Facebook Video Support** - Handles Facebook embed URLs  
✅ **Auto-fetch from Backend** - Gets video URL from API automatically  
✅ **Cache Detection** - Shows if URL is cached or fresh  
✅ **Auto-retry** - Refreshes expired URLs automatically  
✅ **Loading Spinner** - Shows while fetching video  
✅ **Error Handling** - Displays helpful error messages  
✅ **Keyboard Controls** - ESC key to close modal  
✅ **Click-outside Close** - Click overlay to close  
✅ **Responsive Design** - Works on mobile/tablet/desktop  
✅ **Purple/Blue Theme** - Matches your site colors  

---

## 🎮 How It Works

### User Flow:

1. **User clicks Play button** on movie-details.php
2. **Modal opens** with loading spinner
3. **JavaScript fetches** video URL from backend API:
   ```
   GET http://localhost:3000/api/video/8
   ```
4. **Backend returns**:
   ```json
   {
     "success": true,
     "url": "https://www.facebook.com/plugins/video.php?href=...",
     "cached": false,
     "expiresAt": "2025-12-07T10:30:00Z",
     "movie": {
       "id": 8,
       "title": "Harry Potter"
     }
   }
   ```
5. **JW Player initializes** with the URL
6. **Video plays** automatically

### If Video Errors:

1. **Auto-retry triggered**
2. **Calls refresh endpoint**:
   ```
   POST http://localhost:3000/api/refresh/8
   ```
3. **Gets fresh URL** from Facebook
4. **Reloads player** with new URL

---

## 📝 Code Structure

### HTML Added:

```html
<!-- Video Modal -->
<div id="videoModal" class="video-modal">
  <div class="video-modal-overlay"></div>
  <div class="video-modal-content">
    <button id="closeVideoBtn" class="close-video-btn">&times;</button>
    <div id="videoPlayerWrapper" class="player-wrapper">
      <div id="jwPlayerContainer"></div>
    </div>
    <div class="loading-indicator" id="loadingIndicator">
      <div class="spinner"></div>
      <p>⏳ Loading video...</p>
    </div>
  </div>
</div>
```

### JavaScript Functions:

- `openVideoModal()` - Opens modal and fetches video
- `closeVideoModal()` - Closes modal and destroys player
- `initializePlayer(url)` - Initializes JW Player with URL
- `handlePlayerError()` - Auto-refreshes on error

### CSS Styles:

- `.video-modal` - Fullscreen overlay
- `.video-modal-content` - Modal container (90% width, max 1200px)
- `.close-video-btn` - Close button (top-right)
- `.player-wrapper` - 16:9 aspect ratio container
- `.loading-indicator` - Loading spinner
- `body.modal-open` - Prevents body scroll when modal open

---

## 🔧 Customization Options

### Change Player Skin:

In `initializePlayer()` function, change the skin name:

```javascript
skin: {
    name: 'seven'  // Options: seven, bekle, glow, roundster, vapor
}
```

### Change Primary Color:

```javascript
primary: 'rgba(42, 108, 255, 1)'  // Your purple/blue color
```

### Disable Autoplay:

```javascript
autostart: false  // User must click play
```

### Add Logo:

```javascript
logo: {
    file: 'assets/images/logo.png',
    position: 'top-right',
    link: 'https://cinedrive.com'
}
```

### Change Aspect Ratio:

```css
.player-wrapper {
    padding-bottom: 56.25%; /* 16:9 */
    /* OR */
    padding-bottom: 75%;    /* 4:3 */
    /* OR */
    padding-bottom: 100%;   /* 1:1 (square) */
}
```

---

## 🐛 Troubleshooting

### Issue: "JW Player library not loaded!"

**Solution:** Check your JW Player script tag:
```html
<script src="https://cdn.jwplayer.com/libraries/YOUR_KEY.js"></script>
```
- Make sure URL is correct
- Check browser console for 404 errors
- Verify your JW Player license is active

### Issue: Modal opens but shows error

**Solution:** Check these:
1. Backend server running? (`npm start`)
2. API endpoint correct? (`http://localhost:3000/api/video/8`)
3. Database has valid video_url? (Check movies table)
4. Facebook video ID is correct?

### Issue: Video loads but won't play

**Solutions:**
1. **Facebook requires authentication** - Add cookies to backend/.env
2. **Video is private** - Must be public or add auth cookies
3. **Expired URL** - Click retry or wait for auto-refresh
4. **Browser blocked autoplay** - User must interact first

### Issue: Close button not working

**Check:**
- JavaScript console for errors
- Button has correct ID: `closeVideoBtn`
- Event listener is attached

### Issue: Modal not centered on mobile

**Add to CSS:**
```css
@media (max-width: 768px) {
    .video-modal-content {
        width: 95%;
        margin: 10px;
    }
}
```

---

## 📊 API Endpoints Used

### GET /api/video/:id

**Request:**
```
GET http://localhost:3000/api/video/8
```

**Response (Success):**
```json
{
  "success": true,
  "url": "https://video.fbcdn.net/...",
  "cached": true,
  "expiresAt": "2025-12-07T10:30:00Z",
  "movie": {
    "id": 8,
    "title": "Harry Potter"
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "Failed to retrieve video URL from Facebook"
}
```

### POST /api/refresh/:id

**Request:**
```
POST http://localhost:3000/api/refresh/8
```

**Response:**
```json
{
  "success": true,
  "message": "Cache refreshed successfully",
  "url": "https://video.fbcdn.net/...",
  "expiresAt": "2025-12-07T12:00:00Z",
  "movie": {
    "id": 8,
    "title": "Harry Potter"
  }
}
```

---

## 🎯 Testing Checklist

- [ ] JW Player script URL updated with your key
- [ ] Backend server running on port 3000
- [ ] Database has valid Facebook video IDs
- [ ] Click Play button opens modal
- [ ] Loading spinner appears
- [ ] Video loads and plays automatically
- [ ] Close button (X) works
- [ ] ESC key closes modal
- [ ] Click outside modal closes it
- [ ] Video stops when modal closes
- [ ] Error handling works (try invalid movie ID)
- [ ] Auto-retry works (check console logs)
- [ ] Mobile responsive (test on phone)

---

## 📱 Browser Compatibility

✅ **Chrome** - Full support  
✅ **Firefox** - Full support  
✅ **Safari** - Full support  
✅ **Edge** - Full support  
⚠️ **IE11** - Limited support (requires polyfills)  

---

## 🔐 Security Notes

### CORS Configuration

Backend already has CORS enabled for all origins. For production, restrict to your domain:

```javascript
// backend/server.js
app.use(cors({
    origin: 'https://yourdomain.com'
}));
```

### API Endpoint Protection

Consider adding rate limiting:

```bash
npm install express-rate-limit
```

```javascript
const rateLimit = require('express-rate-limit');

const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100 // limit each IP to 100 requests per windowMs
});

app.use('/api/', limiter);
```

---

## 📚 Additional Resources

- **JW Player Docs**: https://docs.jwplayer.com/
- **JW Player API Reference**: https://docs.jwplayer.com/players/reference/
- **Facebook Video Scraping**: See `backend/FACEBOOK_SCRAPING.md`
- **Backend Setup**: See `backend/setup.js`

---

## 💡 Tips for Best Performance

1. **Use cached URLs** - Backend automatically caches for 24 hours
2. **Add Facebook cookies** - Prevents scraping failures
3. **Test with public videos first** - Easier to debug
4. **Monitor console logs** - Shows cache hits/misses
5. **Enable JW Player analytics** - Track video views

---

## 🎉 Success!

If everything is working, you should see:

### Browser Console:
```
📡 Fetching video URL for movie: 8
✅ Video data received
📹 URL: https://www.facebook.com/plugins/video.php?href=...
💾 Cached: false
⏰ Expires: 12/7/2025, 10:30:00 AM
🎬 Initializing JW Player...
✅ JW Player ready
▶️ Video playing
```

### User Experience:
- Click Play button
- Modal slides in with fade animation
- Video loads in 2-3 seconds
- Plays automatically in HD quality
- Controls are responsive
- Close button works smoothly

---

## 🆘 Need Help?

If you're still having issues:

1. Check backend logs: `node backend/server.js`
2. Check browser console (F12)
3. Verify database has correct video IDs
4. Test API endpoint directly: `curl http://localhost:3000/api/video/8`
5. Review `backend/FACEBOOK_SCRAPING.md` for scraping issues

---

## 📝 Quick Reference

### Play Button (already updated):
```html
<button class="hero-btn hero-btn-play play-button" id="playBtn">
    <i class="fas fa-play"></i> Play
</button>
```

### JW Player Script (UPDATE THIS):
```html
<script src="https://cdn.jwplayer.com/libraries/YOUR_KEY.js"></script>
```

### Backend API Base URL:
```javascript
const API_BASE_URL = 'http://localhost:3000/api';
```

That's it! Your video player is ready! 🎬🍿
