import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'lk.zinema.app',
    appName: 'Zinema.lk',
    webDir: 'www',

    // Load your live website in the WebView
    server: {
        url: 'https://zinema.lk',
        cleartext: false,           // HTTPS only
        androidScheme: 'https',
    },

    plugins: {
        SplashScreen: {
            launchShowDuration: 2000,
            launchAutoHide: true,
            backgroundColor: '#0b0c10',
            androidSplashResourceName: 'splash',
            showSpinner: true,
            spinnerColor: '#2a6cff',
        },
        StatusBar: {
            backgroundColor: '#0b0c10',
            style: 'DARK',
        },
    },

    android: {
        // Allow mixed content for loading external resources
        allowMixedContent: true,
        // Custom user agent to identify mobile app
        appendUserAgent: 'ZinemaApp/1.0',
        // Override Android WebView settings
        webContentsDebuggingEnabled: false, // Set true for development
    },
};

export default config;
