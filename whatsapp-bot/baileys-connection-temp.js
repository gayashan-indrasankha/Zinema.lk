/**
 * Baileys Connection Function
 * Handles WhatsApp socket connection, QR code, reconnection, and message events
 */

async function connectToWhatsApp() {
    const sessionPath = `./whatsapp-session/${sessionName}`;

    // Ensure session directory exists
    if (!fs.existsSync('./whatsapp-session')) {
        fs.mkdirSync('./whatsapp-session', { recursive: true });
    }

    const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

    // Create an in-memory store for faster message lookups
    store = makeInMemoryStore({ logger: pino().child({ level: 'silent', stream: 'store' }) });
    store.readFromFile(`./whatsapp-session/${sessionName}/store.json`);

    // Save store every 10 seconds
    setInterval(() => {
        try {
            store.writeToFile(`./whatsapp-session/${sessionName}/store.json`);
        } catch (err) {
            console.error('Failed to save store:', err.message);
        }
    }, 10_000);


    console.log(`📁 Session folder: whatsapp-session/${sessionName}`);

    const { version } = await fetchLatestBaileysVersion();

    sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        browser: Browsers.ubuntu('Chrome'),
        auth: state,
        getMessage: async (key) => {
            if (store) {
                const msg = await store.loadMessage(key.remoteJid, key.id);
                return msg?.message || undefined;
            }
            return undefined;
        }
    });

    // Bind store to socket events
    store?.bind(sock.ev);

    // Save credentials when updated
    sock.ev.on('creds.update', saveCreds);

    // Connection updates (QR code, connection status)
    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('\n📱 Scan this QR code with your WhatsApp:\n');
            qrcode.generate(qr, { small: true });
            console.log('\n⏳ Waiting for QR code scan...\n');
        }

        if (connection === 'close') {
            const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('❌ Connection closed:', lastDisconnect?.error?.message || 'Unknown reason');

            if (shouldReconnect) {
                console.log('🔄 Reconnecting in 5 seconds...');
                setTimeout(() => connectToWhatsApp(), 5000);
            } else {
                console.log('🔐 Logged out. Please delete session folder and restart.');
            }
        } else if (connection === 'open') {
            console.log('╔════════════════════════════════════════════════════════╗');
            console.log('║  ✅ WhatsApp Bot is READY! (Baileys + Queue System)    ║');
            console.log('╠════════════════════════════════════════════════════════╣');
            console.log(`║  📌 Logged in as: ${sock.user?.name || 'Unknown'}`);
            console.log(`║  📞 Phone: ${sock.user?.id.split(':')[0] || 'Unknown'}`);
            console.log('║  💡 Commands:                                           ║');
            console.log(`║     ${TOKEN_PREFIX} <TOKEN> - Get video with token       ║`);
            console.log('║     !help - Show help message                           ║');
            console.log('║     !queue - Show queue status                          ║');
            console.log('╚════════════════════════════════════════════════════════╝\n');

            await testConnection();

            // Display all storage groups
            const allStorageGroups = STORAGE_GROUP_IDS.length > 0
                ? STORAGE_GROUP_IDS
                : (STORAGE_GROUP_ID ? [STORAGE_GROUP_ID] : []);

            if (allStorageGroups.length > 0) {
                console.log(`📦 Storage Groups (${allStorageGroups.length}):`);
