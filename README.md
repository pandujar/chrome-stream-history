# chrome-stream-history
Extension to stream Chrome browsing history to 3rd party server, php logger and viewer.

Lightweight browser activity monitoring system designed for environments where visibility of Chrome usage is critical (e.g., corporate or audit settings). It includes:

- 📦 A Chrome Extension to capture web activity
- 🖥 A PHP logger endpoint to receive and store events
- 🌐 A web-based viewer with filtering and CSV export

---

## 🔧 Components

### 1. Chrome Extension (`/extension`)
Monitors browser navigation in real time using:

- `webNavigation.onCompleted`
- `webNavigation.onHistoryStateUpdated`
- `tabs.onUpdated`
- `browserId` (UUID stored via `chrome.storage.local` for unique browser tracking)
- Event deduplication and retry cache (resilient to connectivity issues)

✅ Captures:
- Title
- URL
- User ID
- Browser ID
- Timestamp
- Source (trigger event)

### 2. PHP Logger (`/logger`)
A lightweight endpoint that receives logs via POST and writes them line-by-line to a JSON log file.

**File:** `/home/data/www/logger.php`  
**Log path:** `/home/data/www/logger.log`

Each line in the log is a JSON object for easy parsing.

### 3. Web Viewer (`/browsing`)
HTML+PHP interface to explore, filter and export the browsing activity.

Features:
- Real-time auto-refresh (every 15s)
- Filters by user, domain, keyword
- Sorts newest-first
- CSV export of filtered results
- Adaptive layout for desktop screens
- Timestamps are converted to `Europe/Madrid` timezone

---

## 📂 Repository Structure
/
├── extension/ # Chrome extension (unpacked)
├── logger.php # POST endpoint to write logs
└── viewer.php # Simple log viewer

## 🚀 Usage

1. **Deploy the logger** (`logger.php`) to your web server (e.g. `https://yourdomain.com/logger.php`)
2. **Install the extension** on each target browser. Use, Developer Mode and enable incognito within extension's details
3. **Open `/viewer.php`** to browse, filter and export activity logs

> 🛠 Ensure your web server has write permission to the `logger.log` path.

---

## ⚠️ Disclaimer

This tool is designed for testing pourposes. Ensure that its use complies with applicable privacy laws and internal policies.

---

## 📃 License

MIT License

