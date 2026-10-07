# WebStack-2026.10.7 (Apple Design Edition)

An advanced, beautifully crafted WordPress navigation/directory theme forked from [owen0o0/WebStack](https://github.com/owen0o0/WebStack), redesigned with the **Apple Design System** and packed with powerful features: **batch link survival monitor**, **privacy-first lightweight real-time visitor engine**, **Spotlight-style search**, **macOS-like Dock sidebar**, and thorough **mobile engineering overhaul**.

- 🌐 **Live Demo**: [https://dh.kongbaige.net](https://dh.kongbaige.net)
- 📢 **Telegram Channel**: [WebStack_kong](https://t.me/WebStack_kong)
- 💬 **Telegram Community**: [Join Discussion Group](https://t.me/+zIx2kbaAaWxkNzU9)
- 📦 **Releases & Downloads**: [GitHub Releases](https://github.com/kongbai9420/WordPress-WebStack/releases)
- 🇨🇳 **中文说明文档**: [README.md](README.md)

---

### ✨ Deep Dive: New Features & Capabilities

#### 1. 🔍 One-Click Parallel URL Health Check (Site Health Monitor)
- **High-Concurrency Parallel Probing**: Driven by `curl_multi` for asynchronous non-blocking network sweeps; customizable concurrency limits (1~50 threads, default 20). Scans hundreds of links within seconds.
- **Smart Anti-False-Positive Heuristics**:
  - Distinguishes WAF/Anti-Bot verification layers (treats HTTP 403, 429, and 503 as online), safeguarding sites protected by Cloudflare or captcha walls.
  - Dedicated HTTP/SOCKS5 proxy support allows checking overseas/blocked destinations reliably.
  - Flags network-level disconnects as "Unknown/Unchecked" rather than immediately tagging them as dead.
- **Status Badges & Batch Management**:
  - Clear color-coded badges (Healthy / Dead / Unknown) directly in the WordPress Admin sites table with tooltip response codes and latency.
  - Quick status dropdown filters, one-click batch trashing for dead links with full recovery support.

#### 2. 📊 Privacy-Focused Live Traffic & Visitor Engine
- **Real-Time Visitor Heartbeats**: Lightweight dedicated database engine that tracks active concurrent visitors, daily unique visitors (UV), daily page views (PV), and all-time traffic without heavy third-party tracking scripts.
- **Privacy & Rate Limiting**:
  - Salted hash de-identification for visitor identifiers; zero plain-text IP storage.
  - Built-in duplicate PV de-bouncing (default 30s) and heartbeat throttling to prevent traffic inflation.
- **Pulsing Apple-Style Widget**: Integrated live indicator with a pulsing green status dot placed cleanly in the footer copyright area.

#### 3. 🎨 Full Apple Design System Architecture
- **Frosted Glass & Ambient Mesh**: Multi-layered `backdrop-filter: blur` frosted acrylic surfaces with fluid Aurora ambient lighting (respects `prefers-reduced-motion`).
- **Spotlight-Style Search**: Minimalist centered search capsule with segmented engine tabs and zero visual clutter.
- **macOS Smart Dock Sidebar**: Smooth toggling between the 240px expanded navigation and the 68px minimalist icon dock; user states persist via `localStorage`.
- **Restrained Aesthetics & Dark Mode**: Minimalist vertical accent indicators for category headers; squircle icon rounding with soft shadows; dark mode tuned specifically for OLED screens.

#### 4. 📱 Precision Mobile Engineering & Bug Fixes
- **No White-Screen Stalls**: Eliminated remote Google Fonts that cause prolonged blocking on restricted networks, falling back to fast native system typography.
- **Independent Top Capsule**: Redesigned header into an independent 52px frosted capsule with a 34px pill menu trigger that never overflows or clips.
- **Overlay Elimination**: Resolved legacy mobile full-height overlay bugs where fixed `top:0` + `bottom:0` rules created transparent shields blocking tap events.
- **Responsive Footer**: Aligned live stats and credit badges neatly without horizontal margin cutoffs.

#### 5. 🛡️ Administrative Experience & Reliability
- **Sticky Admin Navigation (CS Framework)**: Sticky gradient header bar prevents whiteout illegibility during scrolling; left tab navigation is locked and scrollable independently.
- **Dynamic Asset Cache-Busting**: Stylesheets automatically inherit the `filemtime` timestamp, refreshing user browser caches instantly upon theme updates.
- **Hardened Security**: Rigorous CSRF Nonce validation across all AJAX requests; remediated legacy file upload vulnerabilities.

---

### 🖥️ Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 / 8.0 / 8.1 / 8.2 (`curl` extension required for URL health check)
- **Database**: MySQL 5.7 / 8.0 or MariaDB 10.4+
- **Web Server**: Nginx (Recommended) or Apache

---

### 🚀 Installation & Setup

1. **Download**: Grab the latest release archive `WebStack-*.zip` from [Releases](https://github.com/kongbai9420/WordPress-WebStack/releases).
2. **Install**: In WordPress dashboard: **Appearance** → **Themes** → **Upload Theme**, upload the archive and activate it.
3. **Flush Permalinks**: If category links yield 404 errors, visit **Settings** → **Permalinks** and click **Save Changes** once.
4. **Nginx Rewrite Configuration**:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$args;
   }
   rewrite /wp-admin$ $scheme://$host$uri/ permanent;
   ```

---

### 💡 Acknowledgements & Credits

- Original frontend design by [Viggo (WebStackPage)](https://github.com/WebStackPage/WebStackPage.github.io)
- WordPress theme foundation by [iowen (一为忆)](https://github.com/owen0o0/WebStack)
- Maintained & modernized by [kongbai9420](https://github.com/kongbai9420/WordPress-WebStack)

---

### 📄 License

GPL-3.0 License. Please retain author attribution credits in the footer when utilizing this theme.
