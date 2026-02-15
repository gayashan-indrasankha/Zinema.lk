package lk.zinema.app;

import android.os.Bundle;
import com.getcapacitor.BridgeActivity;
import lk.zinema.app.plugins.ZinemaAdsPlugin;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        // Register the ad plugin BEFORE super.onCreate
        registerPlugin(ZinemaAdsPlugin.class);
        super.onCreate(savedInstanceState);
    }
}
