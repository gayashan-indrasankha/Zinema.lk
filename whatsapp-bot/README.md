# WhatsApp Video Forwarder Bot

A Node.js bot using **whatsapp-web.js** that forwards videos from a storage group based on tokens. 100% free, no official WhatsApp API required.

## Features

- 📱 **QR Code Authentication** - Scan with your WhatsApp to login
- 🗄️ **MySQL Database** - Store video tokens and message IDs
- 📹 **2GB Video Support** - Files sent as documents to support large files
- 📊 **Download Logging** - Track all forwarding activities
- 🔐 **Admin Commands** - Add tokens and manage the bot

## Prerequisites

- **Node.js** v14 or higher
- **MySQL** 5.7+ or 8.0
- **Windows/Linux/macOS**

## Installation

### 1. Install Dependencies

```bash
npm install
```

### 2. Set Up Database

Run the SQL schema in your MySQL:

```bash
mysql -u root -p < database/schema.sql
```

Or copy-paste the contents of `database/schema.sql` into phpMyAdmin.

### 3. Configure Environment

Edit `.env` file with your credentials:

```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=whatsapp_bot
STORAGE_GROUP_ID=your_storage_group_id@g.us
ADMIN_NUMBERS=94771234567
```

### 4. Run the Bot

```bash
npm start
```

Or for development with auto-reload:

```bash
npm run dev
```

## First Time Setup

1. **Start the bot** - A QR code will appear in terminal
2. **Scan with WhatsApp** - Open WhatsApp > Settings > Linked Devices > Link a Device
3. **Create a Storage Group** - Create a WhatsApp group for storing videos
4. **Get Group ID** - Send `!groupid` in that group
5. **Update .env** - Add the group ID to `STORAGE_GROUP_ID`
6. **Restart the bot**

## Usage

### For Users

| Command | Description |
|---------|-------------|
| `!get TOKEN` | Get video by token |
| `!help` | Show help message |

### For Admins

| Command | Description |
|---------|-------------|
| `!groupid` | Get current group's ID |
| `!msgid` | Reply to a message to get its ID |
| `!addtoken TOKEN MESSAGE_ID [FILENAME]` | Add a new token |

## How to Add Videos

1. **Upload video** to your storage group (as document for 2GB support)
2. **Get Message ID** - Reply to the uploaded video and send `!msgid`
3. **Add token** via command or database:

   **Via Command:**
   ```
   !addtoken MOVIE001 true_123456789@g.us_ABC123 Avengers_2019.mp4
   ```

   **Via Database:**
   ```sql
   INSERT INTO video_tokens (token, message_id, file_name, description) 
   VALUES ('MOVIE001', 'true_123456789@g.us_ABC123', 'Avengers_2019.mp4', 'Avengers Endgame HD');
   ```

## File Structure

```
whatsapp-bot/
├── config/
│   └── database.js      # MySQL connection & queries
├── database/
│   └── schema.sql       # Database schema
├── whatsapp-session/    # Session data (auto-created)
├── .env                 # Your configuration
├── .env.example         # Example configuration
├── index.js             # Main bot file
├── package.json
└── README.md
```

## Important Notes

⚠️ **WhatsApp Web Session**
- Only one WhatsApp Web session can be active
- If you use WhatsApp Web in browser, the bot will disconnect
- The bot uses `LocalAuth` to persist sessions

⚠️ **Large Files**
- Files up to 2GB work when sent as documents
- Forwarding large files may take several minutes
- Users are notified while the file is being forwarded

⚠️ **Rate Limits**
- WhatsApp may temporarily restrict accounts that send too many messages
- Implement delays between messages if needed

## Troubleshooting

| Issue | Solution |
|-------|----------|
| QR code doesn't appear | Delete `whatsapp-session` folder and restart |
| Session expired | Delete `whatsapp-session` folder and scan again |
| Database connection failed | Check MySQL is running and credentials are correct |
| Message not found | Ensure the message exists in storage group |

## License

ISC
