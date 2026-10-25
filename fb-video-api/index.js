const recoveryConfig = require('./environment').readConfig();
const express = require('express');
const cors = require('cors');
const {normalizeUrl,extractVideoUrlFromHtml}=require('./extraction');
const fetchFacebookHtml=require('./fetch-html').createFetcher();

const app = express();
const PORT = recoveryConfig.port;

// CORS configuration
app.use(cors({
    origin: '*',
    methods: ['GET', 'POST', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization']
}));

app.use(express.json());

// ============================================
// CONFIGURATION
// ============================================
const USER_AGENT = 'facebookexternalhit/1.1';
const HTTP_TIMEOUT = 10000; // 10 seconds
const PUPPETEER_TIMEOUT = 30000; // 30 seconds

// ============================================
// HTTP EXTRACTION (FAST - Primary Method)
// ============================================

/**
 * Clean and unescape URL from Facebook's escaped format
 */
const extractViaHttp = async (videoUrl) => {
    const normalizedUrl = normalizeUrl(videoUrl);
    
    const body = await fetchFacebookHtml(normalizedUrl);

    const cdnUrl = extractVideoUrlFromHtml(body);
    
    if (cdnUrl) {
        return { success: true, url: cdnUrl, method: 'http' };
    }
    
    return null;
};

// ============================================
// PUPPETEER EXTRACTION (FALLBACK - Slower but reliable)
// ============================================

let puppeteer = null;
const loadPuppeteer = async () => {
    if (!puppeteer) puppeteer = await import('puppeteer-core');
    const executablePath = process.env.CHROMIUM_EXECUTABLE;
    if (!executablePath || !require('path').isAbsolute(executablePath)) throw new Error('Explicit Chromium executable required');
    return {puppeteer, executablePath};
};

/**
 * Puppeteer-based extraction (slower, ~3-8s, but more reliable)
 */
const extractViaPuppeteer = async (videoUrl) => {
    throw new Error('Browser fallback awaits isolated runtime verification');
    const { puppeteer, executablePath } = await loadPuppeteer();
    
    let browser = null;
    
    try {
        browser = await puppeteer.launch({
            executablePath,
            headless: 'shell',
        });

        const page = await browser.newPage();
        
        await page.setUserAgent(USER_AGENT);
        await page.setRequestInterception(true);
        
        // Block unnecessary resources
        page.on('request', (request) => {
            const resourceType = request.resourceType();
            if (['image', 'stylesheet', 'font', 'media'].includes(resourceType)) {
                request.abort();
            } else {
                request.continue();
            }
        });

        const normalizedUrl = normalizeUrl(videoUrl);
        await page.goto(normalizedUrl, { 
            waitUntil: 'domcontentloaded',
            timeout: PUPPETEER_TIMEOUT 
        });

        // Wait a bit for any JS to execute
        await new Promise(resolve => setTimeout(resolve, 2000));

        const html = await page.content();
        const cdnUrl = extractVideoUrlFromHtml(html);

        if (cdnUrl) {
            return { success: true, url: cdnUrl, method: 'puppeteer' };
        }

        return null;
    } finally {
        if (browser) {
            await browser.close();
        }
    }
};

// ============================================
// API ROUTES
// ============================================

/**
 * Health check endpoint
 */
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        timestamp: new Date().toISOString(),
        uptime: process.uptime(),
        version: '1.0.0'
    });
});

/**
 * API info endpoint
 */
app.get('/', (req, res) => {
    res.json({
        name: 'FB Video CDN Extractor API',
        version: '1.0.0',
        endpoints: {
            'GET /health': 'Health check',
            'GET /api/extract?url=<FB_URL>': 'Extract CDN URL from Facebook video'
        }
    });
});

/**
 * Main extraction endpoint
 * Uses HTTP-first approach with Puppeteer fallback
 */
app.get('/api/extract', async (req, res) => {
    // No legacy HTTP extraction or Chromium startup in recovery, in any environment.
    if (recoveryConfig.mode === 'local') return res.json({success:true, url:recoveryConfig.fakeMediaUrl, method:'local-fixture'});
    return res.status(503).json({success:false, error:'Extraction disabled during recovery.'});

    const videoUrl = req.query.url;

    if (!videoUrl) {
        return res.status(400).json({ 
            success: false, 
            error: 'Missing required parameter: url' 
        });
    }

    // Validate Facebook URL
    if (!videoUrl.includes('facebook.com') && !videoUrl.includes('fb.com')) {
        return res.status(400).json({ 
            success: false, 
            error: 'Invalid URL: Must be a Facebook video URL' 
        });
    }

    console.log(`\n📥 [${new Date().toISOString()}] Extraction request`);
    console.log(`🔗 URL: ${videoUrl}`);

    const startTime = Date.now();

    try {
        // Step 1: Try HTTP extraction first (fast)
        console.log('🚀 Attempting HTTP extraction...');
        let result = await extractViaHttp(videoUrl);

        // Step 2: If HTTP fails, try Puppeteer (slower but reliable)
        if (!result) {
            console.log('⚠️ HTTP extraction failed, trying Puppeteer...');
            result = await extractViaPuppeteer(videoUrl);
        }

        const duration = Date.now() - startTime;

        if (result) {
            console.log(`✅ Success via ${result.method} in ${duration}ms`);
            return res.json({
                success: true,
                url: result.url,
                method: result.method,
                duration_ms: duration
            });
        }

        console.log(`❌ Extraction failed after ${duration}ms`);
        return res.status(404).json({
            success: false,
            error: 'Could not extract video URL. The video may be private or deleted.',
            duration_ms: duration
        });

    } catch (error) {
        const duration = Date.now() - startTime;
        console.error(`❌ Error after ${duration}ms:`, error.message);
        
        return res.status(500).json({
            success: false,
            error: error.message,
            duration_ms: duration
        });
    }
});

// ============================================
// SERVER STARTUP
// ============================================

app.listen(PORT, recoveryConfig.host, () => {
    console.log('═'.repeat(50));
    console.log('🎬 FB Video CDN Extractor API');
    console.log('═'.repeat(50));
    console.log(`✅ Server running at: http://localhost:${PORT}`);
    console.log('');
    console.log('Endpoints:');
    console.log(`  GET /health          - Health check`);
    console.log(`  GET /api/extract     - Extract video CDN URL`);
    console.log('');
    console.log('Strategy: HTTP-first with Puppeteer fallback');
    console.log('═'.repeat(50));
    console.log('');
    console.log('📋 Waiting for requests...');
});
