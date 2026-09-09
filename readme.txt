=== Speed My A$$ Up ===
Contributors: CynthiaSchomp
Tags: cache, caching, performance, speed, core web vitals, lcp, inp, cls, wp-rocket
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Zero-bloat, security-aware web performance & Core Web Vitals hyper-optimizer for WordPress.

== Description ==

**Speed My A$$ Up (SMAU)** is the high-velocity, security-aware performance platform designed to disrupt traditional SaaS-reliant caching plugins like WP Rocket.

Features:
* Sub-0.5ms Pre-Boot Page Caching: Flat HTML and GZIP disk delivery before DB or PHP bootstrap.
* W3C Speculation Rules API: Native sub-50ms Chromium pre-rendering and instant navigation.
* LCP Turbo Booster: Automatic hero element detection, priority elevation (fetchpriority="high"), and preload injection.
* CLS Zero-Shift Armor: Calculates image/iframe dimensions and injects inline aspect-ratio CSS to drop CLS to 0.000.
* Smart Delay JS Scheduler: Defers non-critical scripts until user interaction with built-in Divi 5 & WooCommerce safelists.
* YouTube & Vimeo Video Facades: Lightweight preview posters saving ~1.2MB of JS per video embed.
* Security Sentinel Suite Synergy: Integrates with Guard My Ass to stop cache-poisoning attacks before they hit disk.
* Complete WP-CLI Suite: Headless administration via `wp smau ...`.

== Installation ==

1. Upload `speed-my-ass-up` to `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress or via `wp plugin activate speed-my-ass-up`.
3. Configure settings in the 'Speed My A$$ Up' admin panel.

== Frequently Asked Questions ==

= How does this compare to WP Rocket? =
Speed My A$$ Up does not rely on external cloud SaaS servers for Critical CSS or optimizations. It runs 100% on your server with pure PHP and native browser APIs, with zero recurring SaaS fees or bottlenecks.

== Changelog ==

= 1.0.0 =
* Initial production release.
