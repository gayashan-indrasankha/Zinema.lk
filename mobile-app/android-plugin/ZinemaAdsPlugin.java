package lk.zinema.app.plugins;

import android.app.Activity;
import android.util.Log;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

// Start.io SDK imports
import com.startapp.sdk.adsbase.StartAppSDK;
import com.startapp.sdk.adsbase.StartAppAd;
import com.startapp.sdk.adsbase.adlisteners.AdEventListener;
import com.startapp.sdk.adsbase.adlisteners.VideoListener;

/**
 * Zinema Ads - Capacitor Plugin
 * Uses Start.io for ALL ad types: banners, interstitials, and rewarded video
 */
@CapacitorPlugin(name = "ZinemaAds")
public class ZinemaAdsPlugin extends Plugin {

    private static final String TAG = "ZinemaAds";

    // Start.io App ID
    private static final String STARTIO_APP_ID = "201304336";

    private StartAppAd startAppAd;
    private boolean adsEnabled = true;
    private int movieClickCount = 0;

    @Override
    public void load() {
        super.load();

        Activity activity = getActivity();

        // Initialize Start.io SDK
        StartAppSDK.init(activity, STARTIO_APP_ID, false);

        // TODO: Remove this line before release!
        // StartAppSDK.setTestAdsEnabled(true);

        // Create ad object for interstitials and rewarded
        startAppAd = new StartAppAd(activity);

        Log.d(TAG, "Zinema Ads Plugin loaded - Start.io App ID: " + STARTIO_APP_ID);
    }

    /**
     * Enable ads (for non-subscribers)
     */
    @PluginMethod()
    public void enableAds(PluginCall call) {
        adsEnabled = true;
        Log.d(TAG, "Ads enabled");
        call.resolve();
    }

    /**
     * Disable ads (for IdeaMart subscribers)
     */
    @PluginMethod()
    public void disableAds(PluginCall call) {
        adsEnabled = false;
        Log.d(TAG, "Ads disabled (subscriber)");
        call.resolve();
    }

    /**
     * Show Start.io banner ad
     */
    @PluginMethod()
    public void showBanner(PluginCall call) {
        if (!adsEnabled) {
            call.resolve();
            return;
        }

        getActivity().runOnUiThread(() -> {
            Log.d(TAG, "Banner ad requested");
        });

        call.resolve();
    }

    /**
     * Show Start.io interstitial ad
     * Called every 3rd movie click
     */
    @PluginMethod()
    public void showInterstitial(PluginCall call) {
        if (!adsEnabled) {
            call.resolve();
            return;
        }

        movieClickCount++;

        // Only show interstitial every 3rd click
        if (movieClickCount % 3 != 0) {
            call.resolve();
            return;
        }

        getActivity().runOnUiThread(() -> {
            startAppAd.showAd(new AdEventListener() {
                @Override
                public void onReceiveAd(com.startapp.sdk.adsbase.Ad ad) {
                    Log.d(TAG, "Interstitial shown");
                    call.resolve();
                }

                @Override
                public void onFailedToReceiveAd(com.startapp.sdk.adsbase.Ad ad) {
                    Log.e(TAG, "Interstitial failed to load");
                    call.resolve();
                }
            });
        });
    }

    /**
     * Show Start.io rewarded video ad
     * Called before movie playback for non-subscribers
     */
    @PluginMethod()
    public void showRewarded(PluginCall call) {
        if (!adsEnabled) {
            JSObject result = new JSObject();
            result.put("rewarded", true);
            call.resolve(result);
            return;
        }

        getActivity().runOnUiThread(() -> {
            StartAppAd rewardedAd = new StartAppAd(getActivity());

            rewardedAd.setVideoListener(new VideoListener() {
                @Override
                public void onVideoCompleted() {
                    Log.d(TAG, "Rewarded video completed");
                    JSObject result = new JSObject();
                    result.put("rewarded", true);
                    call.resolve(result);
                }
            });

            rewardedAd.loadAd(StartAppAd.AdMode.REWARDED_VIDEO, new AdEventListener() {
                @Override
                public void onReceiveAd(com.startapp.sdk.adsbase.Ad ad) {
                    rewardedAd.showAd();
                }

                @Override
                public void onFailedToReceiveAd(com.startapp.sdk.adsbase.Ad ad) {
                    Log.e(TAG, "Rewarded video failed to load");
                    // Allow access if ad fails to load
                    JSObject result = new JSObject();
                    result.put("rewarded", true);
                    call.resolve(result);
                }
            });
        });
    }
}
