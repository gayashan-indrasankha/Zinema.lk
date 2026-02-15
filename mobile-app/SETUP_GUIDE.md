# Zinema.lk Mobile App — Setup & Build Guide

## Prerequisites

1. **Node.js** (v18+): [Download here](https://nodejs.org/)
2. **Android Studio**: [Download here](https://developer.android.com/studio)
3. **Java JDK 17**: (Usually comes with Android Studio)

---

## Step 1: Install Node.js

Download and install from https://nodejs.org/  
After install, verify in PowerShell:
```powershell
node --version
npm --version
```

---

## Step 2: Install Dependencies

```powershell
cd d:\Zinema.lk\mobile-app
npm install
```

---

## Step 3: Add Android Platform

```powershell
npx cap add android
npx cap sync
```

---

## Step 4: Add Ad SDK Dependencies

Open `mobile-app/android/app/build.gradle` and add inside `dependencies`:

```gradle
dependencies {
    // ... existing dependencies ...
    
    // Start.io SDK
    implementation 'com.startapp:inapp-sdk:5.+'
    
    // Unity Ads SDK
    implementation 'com.unity3d.ads:unity-ads:4.12.5'
}
```

Also add the Start.io maven repo in `android/build.gradle` (project-level):
```gradle
allprojects {
    repositories {
        // ... existing repos ...
        maven { url 'https://repo.start.io' }
    }
}
```

---

## Step 5: Register the Ad Plugin

Copy `android-plugin/ZinemaAdsPlugin.java` to:
```
mobile-app/android/app/src/main/java/lk/zinema/app/plugins/ZinemaAdsPlugin.java
```

Then register it in `MainActivity.java`:
```java
package lk.zinema.app;

import android.os.Bundle;
import com.getcapacitor.BridgeActivity;
import lk.zinema.app.plugins.ZinemaAdsPlugin;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        // Register custom plugins BEFORE super.onCreate
        registerPlugin(ZinemaAdsPlugin.class);
        super.onCreate(savedInstanceState);
    }
}
```

---

## Step 6: Set Your Ad Credentials

Edit `ZinemaAdsPlugin.java` and replace:
```java
private static final String STARTIO_APP_ID = "YOUR_STARTIO_APP_ID";
private static final String UNITY_GAME_ID = "YOUR_UNITY_GAME_ID";
```

**To get these:**
1. **Start.io**: Register at https://portal.start.io → Create app → Get App ID
2. **Unity Ads**: Register at https://dashboard.unity3d.com → Monetization → Get Game ID

---

## Step 7: Add App Icon & Splash Screen

Replace these files in `android/app/src/main/res/`:
- `mipmap-*/ic_launcher.png` — App icon (generate at https://icon.kitchen/)
- `drawable/splash.xml` — Splash screen (dark background matching #0b0c10)

---

## Step 8: Build Debug APK

```powershell
cd d:\Zinema.lk\mobile-app
npx cap sync
cd android
.\gradlew assembleDebug
```

The APK will be at:
```
android/app/build/outputs/apk/debug/app-debug.apk
```

---

## Step 9: Build Release APK (for distribution)

First, create a keystore:
```powershell
keytool -genkey -v -keystore zinema-release.keystore -alias zinema -keyalg RSA -keysize 2048 -validity 10000
```

Then build release:
```powershell
cd android
.\gradlew assembleRelease
```

---

## Step 10: Distribute via WhatsApp

1. Rename the APK to `Zinema-v1.0.0.apk`
2. Upload to your website: `https://zinema.lk/app/zinema-latest.apk`
3. Share the download link in your WhatsApp channels
4. Update `api/app-update.php` with the new version number

---

## IdeaMart Setup

To complete the IdeaMart integration:
1. Register at https://ideamart.lk
2. Get your App ID and App Secret
3. Update `api/subscribe.php` with real credentials:
```php
$APP_ID = "APP_XXXXXX";      // Your IdeaMart App ID
$APP_SECRET = "your_secret";  // Your IdeaMart App Secret
```

---

## Testing Checklist

- [ ] App opens and shows zinema.lk website
- [ ] Login/Register works
- [ ] Movies and series browse correctly
- [ ] Video playback works
- [ ] Start.io banner appears at bottom (for non-subscribers)
- [ ] Interstitial shows after every 3rd movie click
- [ ] Unity rewarded video plays before movie access
- [ ] IdeaMart subscriber sees no ads
- [ ] Update checker prompts when new APK available
- [ ] Back button navigates correctly
- [ ] App icon and splash screen look good

---

## Troubleshooting

**WebView shows blank page?**
→ Check `capacitor.config.ts` — ensure `server.url` is `https://zinema.lk`

**Ads not showing?**
→ Check that test mode is enabled in dev, disabled in production
→ Verify App IDs are correct in `ZinemaAdsPlugin.java`

**Video won't play?**
→ Add to AndroidManifest.xml: `android:hardwareAccelerated="true"`

**App crashes on launch?**
→ Check logcat: `adb logcat -s ZinemaAds`
