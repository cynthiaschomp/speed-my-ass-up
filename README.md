# Speed My A$$ Up (SMAU) 🏎️⚡

> **Zero-Bloat, Security-Aware Web Performance & Core Web Vitals Hyper-Optimizer for WordPress**  
> The 4th Pillar of the **[Security Sentinel Suite](https://securemyass.com)** by **[securemyass.com](https://securemyass.com)**.  
> Direct open architecture alternative to **WP Rocket**.

---

## ⚡ Key Highlights

- **Sub-0.5ms Pre-Boot Page Caching**: Generates static flat HTML & GZIP caches on disk, served directly via `advanced-cache.php` before database connections or heavy WordPress core loads.
- **W3C Speculation Rules API Native**: Injects native browser pre-rendering rules for modern Chromium engines, delivering sub-50ms instantaneous page transitions.
- **LCP Turbo Priority Elevation**: Automatically identifies the primary above-the-fold viewport hero image, excludes it from LazyLoad, injects `<link rel="preload" as="image" fetchpriority="high">` into `<head>`, and applies native `fetchpriority="high"`.
- **CLS Zero-Shift Armor**: Parses HTML for images and iframes lacking dimension attributes, calculates native aspect ratios, and injects `width`, `height`, and inline CSS `aspect-ratio` to eliminate Cumulative Layout Shift (`CLS = 0.000`).
- **Smart Delay & Defer Script Scheduler**: Defers non-critical JavaScript until user interaction (`pointerdown`, `keydown`, `scroll`, `wheel`) with zero-break safelists for Divi 5, WooCommerce, and Stripe.
- **Zero SaaS Dependencies**: 100% on-server pure PHP and native browser standards. No external cloud SaaS queues, no quota throttles, and no recurring API fees.
- **Security-Aware Cache Shield**: Integrates with **Guard My Ass (GMA)** Shannon entropy engine to detect and reject query-string cache poisoning and exploit payloads.
- **YouTube & Vimeo Video Facades**: Replaces heavy iframe video embeds with lightweight preview posters and responsive SVG play buttons, saving ~1.2MB of JS per embed.
- **Full WP-CLI Operations**: Comprehensive command-line control via `wp smau status`, `wp smau cache_clear`, `wp smau preload`, `wp smau optimize_db`, and `wp smau benchmark`.

---

## 🏗️ Architecture Overview

```
                     Incoming HTTP(S) Request
                                │
                                ▼
        ┌─────────────────────────────────────────────┐
        │        Traefik v3 Edge Reverse Proxy        │ ── Static File Cache Hit (<0.2ms)
        └──────────────────────┬──────────────────────┘
                               │ Cache Miss
                               ▼
        ┌─────────────────────────────────────────────┐
        │   GMA Pre-Boot Shield (Shannon Entropy)     │ ── Block / Poison Drop (403)
        └──────────────────────┬──────────────────────┘
                               │ Clean
                               ▼
        ┌─────────────────────────────────────────────┐
        │  SMAU Pre-Boot Fast Path (advanced-cache.php)│ ── Disk HTML Cache Hit (<0.5ms)
        └──────────────────────┬──────────────────────┘
                               │ Cache Miss
                               ▼
        ┌─────────────────────────────────────────────┐
        │           WordPress Core Bootstrap          │
        │                                             │
        │  ┌───────────────────────────────────────┐  │
        │  │     SMAU Core Engine Pipeline         │  │
        │  │  - LCP Turbo Priority & CLS Armor     │  │
        │  │  - Speculation Rules API Generator    │  │
        │  │  - Smart Delay & Defer JS Scheduler   │  │
        │  │  - Pure-PHP Local CSS/HTML Minifier   │  │
        │  │  - Responsive Facade Lazy-Loader      │  │
        │  └───────────────────────────────────────┘  │
        │                                             │
        │  ┌───────────────────────────────────────┐  │
        │  │  Output Buffer Capture & Compress     │  │
        │  │  Write to /wp-content/cache/smau/     │  │
        │  └───────────────────────────────────────┘  │
        └─────────────────────────────────────────────┘
```

---

## 🥊 Head-to-Head: Speed My A$$ Up vs. WP Rocket

| Capability | WP Rocket | Speed My A$$ Up (SMAU) |
| :--- | :--- | :--- |
| **Critical CSS / RUCSS** | External cloud SaaS queue (slow & rate-limited) | Pure-PHP local AST & DOM regex extraction |
| **Pre-Boot Fast Path** | Standard output buffer | Sub-0.5ms `advanced-cache.php` disk delivery |
| **Pre-Rendering** | Legacy `instant.page` script | Native **W3C Speculation Rules API** |
| **Security Synergy** | None (vulnerable to cache-poisoning) | Integrates with **Guard My Ass** entropy engine |
| **Video Facades** | Basic placeholders | Smart responsive SVG facade engine |
| **DevOps & CLI** | Basic cache clear | Full `wp smau` suite with automated TTFB benchmarking |

---

## 🚀 Installation & Setup

1. **Clone or Copy**:
   Place the `speed-my-ass-up` directory into your WordPress plugins folder:
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   git clone git@github.com:cynthiaschomp/speed-my-ass-up.git
   ```
2. **Activate via WP-CLI**:
   ```bash
   wp plugin activate speed-my-ass-up --allow-root
   ```
3. **Verify Status & Pre-Boot Engine**:
   ```bash
   wp smau status --allow-root
   ```

---

## 💻 WP-CLI Command Reference

| Command | Purpose |
| :--- | :--- |
| `wp smau status` | View real-time status, cache disk usage, and enabled optimizations |
| `wp smau cache_clear` | Purge the entire static HTML page cache |
| `wp smau cache_clear --post=<id>` | Invalidate static cache for a specific post and its associated archives |
| `wp smau preload` | Trigger background XML sitemap crawler to pre-warm the cache |
| `wp smau optimize_db` | Prune revisions, auto-drafts, spam comments, and optimize tables |
| `wp smau benchmark <url>` | Measure 5-iteration TTFB and response size comparing cached performance |
| `wp smau edge_purge` | Invalidate Cloudflare edge cache via API |

---

## 🔒 Security Sentinel Suite Interoperability

**Speed My A$$ Up** coordinates natively with companion products:
- **[Guard My Ass](https://github.com/cynthiaschomp/guard-my-ass)**: Pre-boot entropy filtering prevents cache-poisoning attacks from being stored on disk.
- **[Back My A$$ Up](https://github.com/cynthiaschomp/back-my-ass-up)**: Automatically excludes temporary cache files from backup archives to preserve cloud storage headroom.
- **[Secure My Ass](https://github.com/cynthiaschomp/secure-my-ass-wp-plugin-ui)**: Enforces execution barriers (`.htaccess`, `.user.ini`, `index.php`) inside cache directories to eliminate PHP execution vectors.

---

## 📄 License

Distributed under the **GPLv3** License. Developed by **[Cynthia Schomp](https://cynthiaschomp.com)**.
