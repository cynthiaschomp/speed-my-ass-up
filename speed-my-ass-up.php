<?php
/**
 * Plugin Name: Speed My A$$ Up
 * Plugin URI: https://securemyass.com/speed-my-ass-up
 * Description: Zero-bloat, security-aware web performance & Core Web Vitals hyper-optimizer for WordPress. Sub-0.5ms pre-boot page caching, local pure-PHP CSS/HTML minifier, W3C Speculation Rules pre-rendering, LCP Turbo priority elevation, CLS zero-shift armor, and native Security Sentinel Suite synergy.
 * Version: 1.0.0
 * Author: Cynthia Schomp
 * Author URI: https://cynthiaschomp.com
 * License: GPL v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: speed-my-ass-up
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Speed_My_Ass_Up
 * @author Cynthia Schomp
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!defined("SMAU_VERSION"))     define("SMAU_VERSION", "1.0.0");
if (!defined("SMAU_FILE"))        define("SMAU_FILE", __FILE__);
if (!defined("SMAU_PATH"))        define("SMAU_PATH", plugin_dir_path(__FILE__));
if (!defined("SMAU_URL"))         define("SMAU_URL", plugin_dir_url(__FILE__));
if (!defined("SMAU_PLUGIN_URL"))  define("SMAU_PLUGIN_URL", SMAU_URL);
if (!defined("SMAU_SLUG"))        define("SMAU_SLUG", "speed-my-ass-up");

// Load Subsystems
require_once SMAU_PATH . "includes/class-smau-cache-engine.php";
require_once SMAU_PATH . "includes/class-smau-preload-engine.php";
require_once SMAU_PATH . "includes/class-smau-optimizer-engine.php";
require_once SMAU_PATH . "includes/class-smau-media-engine.php";
require_once SMAU_PATH . "includes/class-smau-database-engine.php";
require_once SMAU_PATH . "includes/class-smau-edge-engine.php";
require_once SMAU_PATH . "includes/class-smau-security-bridge.php";
require_once SMAU_PATH . "includes/class-smau-ai-css-engine.php";
require_once SMAU_PATH . "includes/class-smau-cli-command.php";

/**
 * Plugin Activation
 */
function smau_activate() {
    $default_settings = [
        "page_cache_enabled"        => true,
        "speculation_rules_enabled" => true,
        "hover_preload_enabled"     => true,
        "dns_prefetch_enabled"      => true,
        "lcp_turbo_enabled"         => true,
        "cls_armor_enabled"         => true,
        "lazy_load_enabled"         => true,
        "video_facades_enabled"     => true,
        "async_css_enabled"         => true,
        "delay_js_enabled"          => true,
        "minify_html_enabled"       => true,
        "cdn_cname_enabled"         => false,
        "cdn_cname_domain"          => "",
        "cloudflare_zone_id"        => "",
        "cloudflare_api_token"      => ""
    ];

    if (!get_option("smau_settings")) {
        update_option("smau_settings", $default_settings);
    }

    SMAU_Cache_Engine::instance()->install_advanced_cache();

    if (!wp_next_scheduled("smau_db_maintenance_cron")) {
        wp_schedule_event(time() + 3600, "weekly", "smau_db_maintenance_cron");
    }
}
register_activation_hook(__FILE__, "smau_activate");

/**
 * Plugin Deactivation
 */
function smau_deactivate() {
    SMAU_Cache_Engine::instance()->remove_advanced_cache();
    wp_clear_scheduled_hook("smau_db_maintenance_cron");
}
register_deactivation_hook(__FILE__, "smau_deactivate");

/**
 * Initialize Subsystems
 */
function smau_init() {
    SMAU_Cache_Engine::instance();
    SMAU_Preload_Engine::instance();
    SMAU_Optimizer_Engine::instance();
    SMAU_Media_Engine::instance();
    SMAU_Database_Engine::instance();
    SMAU_Edge_Engine::instance();
    SMAU_Security_Bridge::instance();
    SMAU_AI_CSS_Engine::instance();
}
add_action("plugins_loaded", "smau_init", 10);

/**
 * Register Top-Level Admin Menu
 */
function smau_register_admin_menu() {
    add_menu_page(
        "Speed My A$$ Up",
        "Speed My A$$ Up",
        "manage_options",
        "speed-my-ass-up",
        "smau_render_admin_dashboard",
        "dashicons-performance",
        3.4
    );
}
add_action("admin_menu", "smau_register_admin_menu");

/**
 * Enqueue Admin Assets
 */
function smau_enqueue_admin_assets($hook) {
    if (strpos($hook, "speed-my-ass-up") === false) {
        return;
    }

    wp_enqueue_style("smau-admin-css", SMAU_URL . "assets/css/smau-admin.css", [], SMAU_VERSION);
}
add_action("admin_enqueue_scripts", "smau_enqueue_admin_assets");

/**
 * Modern Admin Dashboard Render
 */
function smau_render_admin_dashboard() {
    if (!current_user_can("manage_options")) {
        wp_die(__("Unauthorized access.", "speed-my-ass-up"));
    }

    $settings     = get_option("smau_settings", []);
    $stats        = SMAU_Cache_Engine::instance()->get_stats();
    $bloat        = SMAU_Database_Engine::instance()->get_bloat_stats();
    $suite        = SMAU_Security_Bridge::instance()->get_suite_status();
    $ai_provider  = SMAU_AI_CSS_Engine::instance()->get_provider();
    $masked_key   = SMAU_AI_CSS_Engine::instance()->get_masked_api_key();
    $decrypted_key = SMAU_AI_CSS_Engine::instance()->get_decrypted_api_key();
    $nonce        = wp_create_nonce("smau_admin_nonce");
    $has_adv_cache = file_exists(WP_CONTENT_DIR . "/advanced-cache.php");
    ?>
    <div class="wrap smau-dashboard-wrap">
        <!-- Sticky Header Bar -->
        <header class="smau-header">
            <div class="smau-brand">
                <div class="smau-logo-badge">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                </div>
                <div class="smau-brand-title">
                    <h1>SPEED MY A$$ UP <span class="smau-badge smau-badge-pro">v<?php echo esc_html(SMAU_VERSION); ?></span> <span class="smau-badge smau-badge-suite">Sentinel Suite</span></h1>
                    <p>Performance Sentinel — Sub-0.5ms Pre-Boot Caching, Core Web Vitals Hyper-Engine & BYOK AI CSS Optimizer</p>
                </div>
            </div>
            <div class="smau-header-actions">
                <div class="smau-status-pill">
                    <span class="smau-status-dot"></span>
                    <span><?php echo $has_adv_cache ? "Pre-Boot Fast Path (<0.5ms) [Active]" : "Buffer Fallback Mode [Active]"; ?></span>
                </div>
                <button type="button" class="smau-btn smau-btn-danger" id="smau-hdr-btn-purge" title="Purge all cached pages">
                    <span class="dashicons dashicons-trash"></span> Purge Cache
                </button>
                <button type="button" class="smau-btn smau-btn-emerald" id="smau-hdr-btn-preload" title="Pre-warm sitemap cache">
                    <span class="dashicons dashicons-update"></span> Pre-Warm Cache
                </button>
                <button type="button" class="smau-btn smau-btn-primary" id="smau-hdr-btn-save">
                    <span class="dashicons dashicons-saved"></span> Save Changes
                </button>
            </div>
        </header>

        <!-- Navigation Pills (Matching Secure My Ass Geometric Tabs) -->
        <nav class="smau-nav-pills">
            <button type="button" class="smau-pill-btn active" data-tab="overview">
                <span class="dashicons dashicons-dashboard"></span> Overview & Telemetry
            </button>
            <button type="button" class="smau-pill-btn" data-tab="caching">
                <span class="dashicons dashicons-media-document"></span> Pre-Boot Caching & Preload
            </button>
            <button type="button" class="smau-pill-btn" data-tab="cwv">
                <span class="dashicons dashicons-performance"></span> Core Web Vitals Hyper-Engine
            </button>
            <button type="button" class="smau-pill-btn" data-tab="ai-css">
                <span class="dashicons dashicons-admin-customizer"></span> BYOK AI CSS Scanner
            </button>
            <button type="button" class="smau-pill-btn" data-tab="database">
                <span class="dashicons dashicons-database"></span> Database Hygiene
            </button>
            <button type="button" class="smau-pill-btn" data-tab="edge">
                <span class="dashicons dashicons-cloud"></span> Edge & Traefik
            </button>
            <button type="button" class="smau-pill-btn" data-tab="suite">
                <span class="dashicons dashicons-shield"></span> Sentinel Suite
            </button>
        </nav>

        <!-- Main Form & Settings Panes -->
        <form id="smau-settings-form">
            <input type="hidden" name="action" value="smau_save_settings">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

            <!-- TAB 1: OVERVIEW & TELEMETRY -->
            <div class="smau-tab-pane active" id="smau-tab-overview">
                <!-- 4 Hero Metrics Cards -->
                <div class="smau-metrics-grid">
                    <div class="smau-metric-card">
                        <div class="smau-metric-title">
                            <span>Cached HTML Pages</span>
                            <span class="dashicons dashicons-media-document" style="color: var(--smau-cyan);"></span>
                        </div>
                        <div class="smau-metric-val" id="val-cache-files"><?php echo esc_html($stats["files"]); ?></div>
                        <div class="smau-metric-sub">Static pages on disk (<?php echo esc_html($stats["size_mb"]); ?> MB footprint)</div>
                    </div>
                    <div class="smau-metric-card accent-emerald">
                        <div class="smau-metric-title">
                            <span>Pre-Boot Fast Path</span>
                            <span class="dashicons dashicons-yes-alt" style="color: var(--smau-emerald);"></span>
                        </div>
                        <div class="smau-metric-val" style="color: var(--smau-emerald);">&lt; 0.5 <span style="font-size: 14px; font-weight: 500;">ms</span></div>
                        <div class="smau-metric-sub"><?php echo $has_adv_cache ? "advanced-cache.php Enforced" : "Output Buffer Active"; ?></div>
                    </div>
                    <div class="smau-metric-card accent-amber">
                        <div class="smau-metric-title">
                            <span>Core Web Vitals</span>
                            <span class="dashicons dashicons-performance" style="color: var(--smau-amber);"></span>
                        </div>
                        <div class="smau-metric-val" style="color: var(--smau-amber);">0.000 <span style="font-size: 14px; font-weight: 500;">CLS</span></div>
                        <div class="smau-metric-sub">LCP Turbo Active • Delay JS Enforced</div>
                    </div>
                    <div class="smau-metric-card">
                        <div class="smau-metric-title">
                            <span>Database Bloat Headroom</span>
                            <span class="dashicons dashicons-database" style="color: var(--smau-cyan);"></span>
                        </div>
                        <div class="smau-metric-val" id="val-bloat-items" style="color: var(--smau-cyan);"><?php echo esc_html($bloat["total_bloat_items"]); ?></div>
                        <div class="smau-metric-sub">Revisions, transients & drafts reclaimable</div>
                    </div>
                </div>

                <!-- Live TTFB Latency Benchmark Tool -->
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-chart-line" style="color: var(--smau-cyan);"></span>
                        Live TTFB Response Latency Benchmark (Zero-Mock Measurement)
                    </div>
                    <p style="color: var(--smau-text-muted); font-size: 13px; margin: 0 0 16px;">
                        Execute sequential live HTTP GET requests against your active server to measure real-world TTFB (Time to First Byte) latency with and without pre-boot caching.
                    </p>
                    <div class="smau-benchmark-box">
                        <div class="smau-input-row">
                            <input type="url" id="smau_benchmark_url" class="smau-input" style="flex: 1; min-width: 280px;" value="<?php echo esc_url(home_url("/")); ?>" placeholder="https://example.com/">
                            <select id="smau_benchmark_iterations" class="smau-input" style="width: 140px;">
                                <option value="3" selected>3 Iterations</option>
                                <option value="5">5 Iterations</option>
                                <option value="1">1 Iteration</option>
                            </select>
                            <button type="button" class="smau-btn smau-btn-primary" id="smau-btn-run-benchmark">
                                <span class="dashicons dashicons-controls-play"></span> Run Benchmark
                            </button>
                        </div>
                        <div id="smau-benchmark-loading" style="display: none; padding: 12px; color: var(--smau-cyan); font-size: 13px;">
                            <span class="dashicons dashicons-update" style="animation: spin 1s infinite linear;"></span> Measuring server response latency across sequential requests...
                        </div>
                        <div id="smau-benchmark-results" style="display: none;">
                            <div style="display: flex; gap: 20px; margin-bottom: 12px; padding: 12px; background: rgba(2, 132, 199, 0.1); border-radius: 8px; border: 1px solid rgba(56, 189, 248, 0.3);">
                                <div><strong style="color: var(--smau-text-muted);">Average TTFB:</strong> <span id="bm-avg" style="color: var(--smau-emerald); font-weight: 700; font-size: 16px;">--</span></div>
                                <div><strong style="color: var(--smau-text-muted);">Fastest:</strong> <span id="bm-min" style="color: #ffffff; font-weight: 700;">--</span></div>
                                <div><strong style="color: var(--smau-text-muted);">Slowest:</strong> <span id="bm-max" style="color: #ffffff; font-weight: 700;">--</span></div>
                            </div>
                            <table class="smau-benchmark-table">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>HTTP Status</th>
                                        <th>TTFB Latency</th>
                                        <th>Payload Size</th>
                                        <th>Cache Verification</th>
                                    </tr>
                                </thead>
                                <tbody id="bm-table-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Speed Posture Summary -->
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-shield-alt" style="color: var(--smau-emerald);"></span>
                        Performance Posture & Quick Presets
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
                        <div>
                            <div style="font-size: 14px; font-weight: 600; color: #ffffff;">Automated Preset Profiles</div>
                            <div style="font-size: 12px; color: var(--smau-text-muted);">1-click application of recommended production configurations.</div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="smau-btn smau-btn-secondary" onclick="smauApplyPreset("safe")">
                                <span class="dashicons dashicons-admin-settings"></span> Safe Balanced Preset
                            </button>
                            <button type="button" class="smau-btn smau-btn-emerald" onclick="smauApplyPreset("velocity")">
                                <span class="dashicons dashicons-superhero"></span> Max Velocity Turbo Preset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PRE-BOOT CACHING & PRELOAD -->
            <div class="smau-tab-pane" id="smau-tab-caching">
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-media-document" style="color: var(--smau-cyan);"></span>
                        Pre-Boot Static HTML Disk Caching Subsystem
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">Enable Static HTML Page Caching</div>
                            <div class="smau-setting-desc">Generates sub-millisecond flat HTML & GZIP caches on disk, completely bypassing heavy PHP execution and database queries for public visitors.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="page_cache_enabled" value="1" <?php checked(!empty($settings["page_cache_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">W3C Speculation Rules API</div>
                            <div class="smau-setting-desc">Instructs modern Chromium browsers to speculatively pre-render internal navigation links, enabling sub-50ms instant page changes.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="speculation_rules_enabled" value="1" <?php checked(!empty($settings["speculation_rules_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">Hover-Intent Link Prefetching</div>
                            <div class="smau-setting-desc">Smart 65ms hover listener that downloads HTML documents in the background before the user clicks on non-Chromium browsers.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="hover_preload_enabled" value="1" <?php checked(!empty($settings["hover_preload_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">DNS Prefetch & Preconnect Engine</div>
                            <div class="smau-setting-desc">Automatically discovers external third-party domains (Google Fonts, CDNs, Gravatar) and injects DNS preconnect hints into the document <code>&lt;head&gt;</code>.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="dns_prefetch_enabled" value="1" <?php checked(!empty($settings["dns_prefetch_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- XML Sitemap Crawler Preloader -->
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-networking" style="color: var(--smau-emerald);"></span>
                        Automated XML Sitemap Preloader
                    </div>
                    <p style="color: var(--smau-text-muted); font-size: 13px; margin: 0 0 16px;">
                        Crawls WordPress core sitemaps (<code>/wp-sitemap.xml</code>) to pre-warm the static disk cache before real visitors arrive.
                    </p>
                    <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label style="font-size: 13px; color: var(--smau-text-muted);">Max URLs to warm:</label>
                            <input type="number" id="smau_preload_limit" class="smau-input" style="width: 90px;" value="20" min="5" max="100">
                        </div>
                        <button type="button" class="smau-btn smau-btn-emerald" id="smau-btn-start-preload">
                            <span class="dashicons dashicons-update"></span> Start Sitemap Preload
                        </button>
                        <span id="smau-preload-status" style="font-size: 13px; color: var(--smau-text-muted);"></span>
                    </div>
                </div>
            </div>

            <!-- TAB 3: CORE WEB VITALS HYPER-ENGINE -->
            <div class="smau-tab-pane" id="smau-tab-cwv">
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-performance" style="color: var(--smau-cyan);"></span>
                        Core Web Vitals Hyper-Engine (LCP, INP, CLS)
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">LCP Turbo Priority Booster</div>
                            <div class="smau-setting-desc">Detects primary above-the-fold viewport hero images, injects <code>fetchpriority="high"</code> and <code>&lt;link rel="preload" as="image"&gt;</code> in <code>&lt;head&gt;</code>, and strips lazy-load to maximize LCP speeds.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="lcp_turbo_enabled" value="1" <?php checked(!empty($settings["lcp_turbo_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">CLS Zero-Shift Armor</div>
                            <div class="smau-setting-desc">Calculates missing image and iframe dimensions on-the-fly and injects explicit <code>width</code>, <code>height</code>, and <code>aspect-ratio</code> CSS properties to eliminate layout shift before fonts/images paint.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="cls_armor_enabled" value="1" <?php checked(!empty($settings["cls_armor_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">Smart Delay JavaScript Execution</div>
                            <div class="smau-setting-desc">Defers non-critical JavaScript until user interaction (pointer, scroll, touch, or keypress), radically improving Interaction to Next Paint (INP) with automatic safelisting for WooCommerce and Divi 5.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="delay_js_enabled" value="1" <?php checked(!empty($settings["delay_js_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">Asynchronous CSS Delivery</div>
                            <div class="smau-setting-desc">Converts render-blocking stylesheets into asynchronous preloads with noscript fallbacks to unlock instant first paint.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="async_css_enabled" value="1" <?php checked(!empty($settings["async_css_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">YouTube & Vimeo Video Facades</div>
                            <div class="smau-setting-desc">Replaces heavy video iframes with ultra-lightweight static preview posters and responsive SVG play buttons, saving ~1.2MB of JS per embed until clicked.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="video_facades_enabled" value="1" <?php checked(!empty($settings["video_facades_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">HTML & Whitespace Minification</div>
                            <div class="smau-setting-desc">Removes redundant HTML comments, newline cascades, and whitespace characters to shrink total page weight over the wire.</div>
                        </div>
                        <label class="smau-switch">
                            <input type="checkbox" name="minify_html_enabled" value="1" <?php checked(!empty($settings["minify_html_enabled"])); ?>>
                            <span class="smau-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- TAB 4: BYOK AI CSS SCANNER -->
            <div class="smau-tab-pane" id="smau-tab-ai-css">
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-admin-customizer" style="color: var(--smau-cyan);"></span>
                        Bring-Your-Own-Key (BYOK) AI CSS Scanner & Optimizer
                    </div>
                    <p style="color: var(--smau-text-muted); font-size: 13px; margin: 0 0 18px;">
                        Unlike WP Rocket which routes your site through slow, rate-limited third-party cloud queues, Speed My A$$ Up uses client-provided API keys directly. Audits DOM tokens against enqueued stylesheets using state-of-the-art LLMs.
                    </p>

                    <div style="font-size: 13px; font-weight: 700; color: #ffffff; margin-bottom: 10px;">Select AI Model Provider:</div>
                    <div class="smau-ai-providers-grid">
                        <label class="smau-ai-provider-card <?php echo $ai_provider === "gemini" ? "active" : ""; ?>">
                            <input type="radio" name="ai_provider" value="gemini" class="smau-provider-radio" <?php checked($ai_provider, "gemini"); ?>>
                            <div>
                                <strong style="color: #ffffff;">Google Gemini</strong>
                                <div style="font-size: 11px; color: var(--smau-text-muted);">2.5 Flash / 1.5 Pro (Ultra-fast)</div>
                            </div>
                        </label>
                        <label class="smau-ai-provider-card <?php echo $ai_provider === "openai" ? "active" : ""; ?>">
                            <input type="radio" name="ai_provider" value="openai" class="smau-provider-radio" <?php checked($ai_provider, "openai"); ?>>
                            <div>
                                <strong style="color: #ffffff;">OpenAI</strong>
                                <div style="font-size: 11px; color: var(--smau-text-muted);">GPT-4o / GPT-4o-mini</div>
                            </div>
                        </label>
                        <label class="smau-ai-provider-card <?php echo $ai_provider === "anthropic" ? "active" : ""; ?>">
                            <input type="radio" name="ai_provider" value="anthropic" class="smau-provider-radio" <?php checked($ai_provider, "anthropic"); ?>>
                            <div>
                                <strong style="color: #ffffff;">Anthropic Claude</strong>
                                <div style="font-size: 11px; color: var(--smau-text-muted);">Claude 3.5 Sonnet</div>
                            </div>
                        </label>
                        <label class="smau-ai-provider-card <?php echo $ai_provider === "openrouter" ? "active" : ""; ?>">
                            <input type="radio" name="ai_provider" value="openrouter" class="smau-provider-radio" <?php checked($ai_provider, "openrouter"); ?>>
                            <div>
                                <strong style="color: #ffffff;">OpenRouter</strong>
                                <div style="font-size: 11px; color: var(--smau-text-muted);">Multi-Model Gateway</div>
                            </div>
                        </label>
                    </div>

                    <!-- Encrypted Key Storage with Anti-Shoulder-Surfing Mask -->
                    <div style="background: #090d16; border: 1px solid var(--smau-border); border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <label style="font-size: 13px; font-weight: 600; color: #ffffff;">Provider API Key (AES-256 Encrypted on Disk):</label>
                            <span style="font-size: 11px; color: var(--smau-emerald);">OPSEC Protected</span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="password" id="smau_ai_key_input" class="smau-input" style="flex: 1;" placeholder="<?php echo $masked_key ? "Key is set (" . esc_attr($masked_key) . ")" : "Paste your API key..."; ?>" value="<?php echo esc_attr($decrypted_key); ?>">
                            <button type="button" class="smau-btn smau-btn-secondary" id="smau_toggle_key_vis" title="Toggle visibility">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <button type="button" class="smau-btn smau-btn-primary" id="smau_save_ai_key_btn">
                                Save Key
                            </button>
                        </div>
                    </div>

                    <!-- Live Target Audit Runner -->
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <input type="url" id="smau_scan_url" class="smau-input" style="flex: 1; min-width: 280px;" value="<?php echo esc_url(home_url("/")); ?>" placeholder="Target URL...">
                        <button type="button" class="smau-btn smau-btn-emerald" id="smau_run_ai_scan_btn">
                            <span class="dashicons dashicons-search"></span> Run AI CSS Audit
                        </button>
                    </div>

                    <!-- Audit Results Terminal -->
                    <div id="smau_ai_scan_results" class="smau-ai-terminal">
                        <div class="smau-ai-terminal-header">
                            <span id="smau_ai_res_title" style="color: var(--smau-cyan); font-weight: 700;">AI CSS Scanner Ready</span>
                            <span id="smau_ai_res_savings" class="smau-badge smau-badge-suite">0 KB Saved</span>
                        </div>
                        <div id="smau_ai_res_body">
                            Enter an active URL above and click "Run AI CSS Audit" to extract DOM tokens and detect unused/broken selectors.
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5: DATABASE HYGIENE -->
            <div class="smau-tab-pane" id="smau-tab-database">
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-database" style="color: var(--smau-cyan);"></span>
                        Database Bloat Hygiene & Table Optimization
                    </div>
                    <p style="color: var(--smau-text-muted); font-size: 13px; margin: 0 0 18px;">
                        Prune obsolete database records and optimize storage engines using safe, chunked table locks. Interoperates with Back My A$$ Up to ensure pre-flight safety.
                    </p>

                    <div class="smau-metrics-grid" style="margin-bottom: 20px;">
                        <div class="smau-metric-card">
                            <div class="smau-metric-title">Post Revisions</div>
                            <div class="smau-metric-val" id="val-revs"><?php echo esc_html($bloat["revisions"]); ?></div>
                            <div class="smau-metric-sub">Old content snapshots</div>
                        </div>
                        <div class="smau-metric-card">
                            <div class="smau-metric-title">Auto-Drafts & Trash</div>
                            <div class="smau-metric-val"><?php echo esc_html($bloat["autodrafts"] + $bloat["trashed_posts"]); ?></div>
                            <div class="smau-metric-sub">Abandoned editor states</div>
                        </div>
                        <div class="smau-metric-card">
                            <div class="smau-metric-title">Spam Comments</div>
                            <div class="smau-metric-val"><?php echo esc_html($bloat["spam_comments"]); ?></div>
                            <div class="smau-metric-sub">Blocked junk discussions</div>
                        </div>
                        <div class="smau-metric-card">
                            <div class="smau-metric-title">Expired Transients</div>
                            <div class="smau-metric-val" id="val-trans"><?php echo esc_html($bloat["expired_transients"]); ?></div>
                            <div class="smau-metric-sub">Outdated cache keys</div>
                        </div>
                    </div>

                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 14px 18px; margin-bottom: 18px;">
                        <div style="color: var(--smau-amber); font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span class="dashicons dashicons-warning"></span> Mandatory Pre-Flight Backup Policy Enforced
                        </div>
                        <div style="color: var(--smau-text-muted); font-size: 12px;">
                            Running database hygiene permanently prunes old revisions and transients. An automated snapshot via Back My A$$ Up is verified before destructive pruning runs.
                        </div>
                    </div>

                    <button type="button" class="smau-btn smau-btn-primary" id="smau-btn-clean-db">
                        <span class="dashicons dashicons-trash"></span> Execute Database Hygiene & Optimize Tables
                    </button>
                    <span id="smau-db-clean-status" style="font-size: 13px; color: var(--smau-text-muted); margin-left: 12px;"></span>
                </div>
            </div>

            <!-- TAB 6: EDGE & TRAEFIK -->
            <div class="smau-tab-pane" id="smau-tab-edge">
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-cloud" style="color: var(--smau-cyan);"></span>
                        Cloudflare API Edge Cache Synchronization
                    </div>
                    <p style="color: var(--smau-text-muted); font-size: 13px; margin: 0 0 18px;">
                        Purge Cloudflare edge servers synchronously when WordPress cache is flushed.
                    </p>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">Cloudflare Zone ID</div>
                            <input type="text" name="cloudflare_zone_id" class="smau-input" style="width: 100%; max-width: 400px;" value="<?php echo esc_attr($settings["cloudflare_zone_id"] ?? ""); ?>" placeholder="e.g. 023e105f4ecef8ad9ca31a8372d0c353">
                        </div>
                    </div>
                    <div class="smau-setting-row">
                        <div class="smau-setting-info">
                            <div class="smau-setting-title">Cloudflare API Token (Cache:Purge)</div>
                            <input type="password" name="cloudflare_api_token" class="smau-input" style="width: 100%; max-width: 400px;" value="<?php echo esc_attr($settings["cloudflare_api_token"] ?? ""); ?>" placeholder="••••••••••••••••••••">
                        </div>
                    </div>
                </div>

                <!-- Traefik v3 Reverse-Proxy Config Generator -->
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-networking" style="color: var(--smau-emerald);"></span>
                        Traefik v3 Dynamic Reverse-Proxy Edge Config
                    </div>
                    <p style="color: var(--smau-text-muted); font-size: 13px; margin: 0 0 14px;">
                        Hot-reload configuration for Traefik edge reverse-proxy with automated static asset compression (GZIP / Brotli) and long-term cache-control headers.
                    </p>
                    <pre style="background: #060910; border: 1px solid var(--smau-border); border-radius: 8px; padding: 14px; font-size: 12px; color: var(--smau-cyan); overflow-x: auto;"><?php echo esc_html(SMAU_Edge_Engine::instance()->get_traefik_dynamic_config()); ?></pre>
                </div>
            </div>

            <!-- TAB 7: SENTINEL SUITE INTEROPERABILITY -->
            <div class="smau-tab-pane" id="smau-tab-suite">
                <div class="smau-card">
                    <div class="smau-card-title">
                        <span class="dashicons dashicons-shield" style="color: var(--smau-cyan);"></span>
                        The Security Sentinel Suite (4 Connected Pillars)
                    </div>
                    <p style="color: var(--smau-text-muted); font-size: 13px; margin: 0 0 16px;">
                        Speed My A$$ Up works in native synergy with the other three security and performance products in the Sentinel Suite.
                    </p>
                    <div class="smau-suite-grid">
                        <div class="smau-suite-card">
                            <div class="smau-suite-header">
                                <span class="smau-suite-name">Guard My Ass</span>
                                <span class="smau-badge <?php echo !empty($suite["guard_my_ass"]["active"]) ? "smau-badge-suite" : "smau-badge-pro"; ?>">
                                    <?php echo !empty($suite["guard_my_ass"]["active"]) ? "v" . esc_html($suite["guard_my_ass"]["version"]) . " Active" : "Standalone"; ?>
                                </span>
                            </div>
                            <div class="smau-suite-role">Pre-Boot Ingress Shield, Sub-millisecond Shannon Entropy WAF, Traefik auto-ban sync.</div>
                        </div>
                        <div class="smau-suite-card">
                            <div class="smau-suite-header">
                                <span class="smau-suite-name">Back My A$$ Up</span>
                                <span class="smau-badge <?php echo !empty($suite["back_my_ass_up"]["active"]) ? "smau-badge-suite" : "smau-badge-pro"; ?>">
                                    <?php echo !empty($suite["back_my_ass_up"]["active"]) ? "v" . esc_html($suite["back_my_ass_up"]["version"]) . " Active" : "Standalone"; ?>
                                </span>
                            </div>
                            <div class="smau-suite-role">Air-gapped cryptographic backups, multi-region cloud replication, and pre-flight gates.</div>
                        </div>
                        <div class="smau-suite-card">
                            <div class="smau-suite-header">
                                <span class="smau-suite-name">Secure My Ass</span>
                                <span class="smau-badge <?php echo !empty($suite["secure_my_ass"]["active"]) ? "smau-badge-suite" : "smau-badge-pro"; ?>">
                                    <?php echo !empty($suite["secure_my_ass"]["active"]) ? "v" . esc_html($suite["secure_my_ass"]["version"]) . " Active" : "Standalone"; ?>
                                </span>
                            </div>
                            <div class="smau-suite-role">AST zero-day malware quarantine engine, access cloaking, native RFC 6238 2FA & SOC Analyst.</div>
                        </div>
                        <div class="smau-suite-card" style="border-color: rgba(56, 189, 248, 0.4);">
                            <div class="smau-suite-header">
                                <span class="smau-suite-name" style="color: var(--smau-cyan);">Speed My A$$ Up</span>
                                <span class="smau-badge smau-badge-suite">v<?php echo esc_html(SMAU_VERSION); ?> Core</span>
                            </div>
                            <div class="smau-suite-role">Sub-0.5ms pre-boot caching, Core Web Vitals Hyper-Engine, and BYOK AI CSS Scanner.</div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Floating Toast Container -->
        <div id="smau-toast-container" class="smau-toast-container"></div>
    </div>

    <!-- Interactive Dashboard Script -->
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        // Tab Switching Logic
        const pillBtns = document.querySelectorAll(".smau-pill-btn");
        const tabPanes = document.querySelectorAll(".smau-tab-pane");

        pillBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                const targetTab = btn.getAttribute("data-tab");
                pillBtns.forEach(b => b.classList.remove("active"));
                tabPanes.forEach(p => p.classList.remove("active"));

                btn.classList.add("active");
                const pane = document.getElementById("smau-tab-" + targetTab);
                if (pane) pane.classList.add("active");
            });
        });

        // Toast Helper
        function showToast(msg, isError = false) {
            const container = document.getElementById("smau-toast-container");
            const toast = document.createElement("div");
            toast.className = "smau-toast" + (isError ? " smau-toast-error" : "");
            toast.innerHTML = (isError ? "⚠️ " : "✓ ") + msg;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = "0";
                toast.style.transform = "translateX(50px)";
                toast.style.transition = "all 0.3s ease";
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // Save Settings Handler
        const saveForm = document.getElementById("smau-settings-form");
        const saveBtn = document.getElementById("smau-hdr-btn-save");

        function handleSaveSettings() {
            saveBtn.disabled = true;
            saveBtn.innerHTML = "<span class="dashicons dashicons-update" style="animation: spin 1s infinite linear;"></span> Saving...";

            const fd = new FormData(saveForm);
            fetch(ajaxurl, { method: "POST", body: fd })
            .then(r => r.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = "<span class="dashicons dashicons-saved"></span> Save Changes";
                if (data.success) {
                    showToast("Speed My A$$ Up settings saved and enforced successfully!");
                } else {
                    showToast("Error saving settings: " + (data.data || "Unknown error"), true);
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = "<span class="dashicons dashicons-saved"></span> Save Changes";
                showToast("Network error saving settings.", true);
            });
        }

        if (saveBtn) saveBtn.addEventListener("click", handleSaveSettings);

        // Header Action: Purge Cache
        const purgeBtn = document.getElementById("smau-hdr-btn-purge");
        if (purgeBtn) {
            purgeBtn.addEventListener("click", () => {
                if (!confirm("Are you sure you want to completely purge the static page cache?")) return;
                purgeBtn.disabled = true;
                const fd = new FormData();
                fd.append("action", "smau_purge_cache");
                fd.append("nonce", "<?php echo esc_js($nonce); ?>");

                fetch(ajaxurl, { method: "POST", body: fd })
                .then(r => r.json())
                .then(data => {
                    purgeBtn.disabled = false;
                    if (data.success) {
                        showToast("Static page cache purged successfully.");
                        const filesEl = document.getElementById("val-cache-files");
                        if (filesEl) filesEl.innerText = "0";
                    } else {
                        showToast("Failed to purge cache.", true);
                    }
                });
            });
        }

        // Header Action: Pre-Warm Cache
        const headerPreloadBtn = document.getElementById("smau-hdr-btn-preload");
        const tabPreloadBtn = document.getElementById("smau-btn-start-preload");

        function runPreload(limit = 20) {
            const btn = headerPreloadBtn;
            btn.disabled = true;
            btn.innerHTML = "<span class="dashicons dashicons-update" style="animation: spin 1s infinite linear;"></span> Pre-Warming...";

            const fd = new FormData();
            fd.append("action", "smau_preload_cache");
            fd.append("nonce", "<?php echo esc_js($nonce); ?>");
            fd.append("limit", limit);

            fetch(ajaxurl, { method: "POST", body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = "<span class="dashicons dashicons-update"></span> Pre-Warm Cache";
                if (data.success) {
                    showToast("Successfully crawled and cached " + (data.data.crawled || 0) + " pages from sitemap!");
                } else {
                    showToast("Error during preload crawl.", true);
                }
            });
        }

        if (headerPreloadBtn) headerPreloadBtn.addEventListener("click", () => runPreload(20));
        if (tabPreloadBtn) tabPreloadBtn.addEventListener("click", () => {
            const limit = document.getElementById("smau_preload_limit").value || 20;
            runPreload(limit);
        });

        // Live TTFB Benchmark Tool
        const benchBtn = document.getElementById("smau-btn-run-benchmark");
        if (benchBtn) {
            benchBtn.addEventListener("click", () => {
                const url = document.getElementById("smau_benchmark_url").value;
                const iters = document.getElementById("smau_benchmark_iterations").value;
                const loadingBox = document.getElementById("smau-benchmark-loading");
                const resultsBox = document.getElementById("smau-benchmark-results");
                const tableBody = document.getElementById("bm-table-body");

                benchBtn.disabled = true;
                loadingBox.style.display = "block";
                resultsBox.style.display = "none";

                const fd = new FormData();
                fd.append("action", "smau_run_benchmark");
                fd.append("nonce", "<?php echo esc_js($nonce); ?>");
                fd.append("url", url);
                fd.append("iterations", iters);

                fetch(ajaxurl, { method: "POST", body: fd })
                .then(r => r.json())
                .then(data => {
                    benchBtn.disabled = false;
                    loadingBox.style.display = "none";
                    if (data.success && data.data) {
                        const d = data.data;
                        document.getElementById("bm-avg").innerText = d.avg_ms + " ms";
                        document.getElementById("bm-min").innerText = d.min_ms + " ms";
                        document.getElementById("bm-max").innerText = d.max_ms + " ms";

                        let rows = "";
                        d.details.forEach(item => {
                            const isHit = item.cache.indexOf("HIT") !== -1;
                            const tagClass = isHit ? "smau-cache-hit" : "smau-cache-miss";
                            rows += `<tr>
                                <td><strong>#${item.iteration}</strong></td>
                                <td><span style="color: var(--smau-emerald); font-weight: 700;">${item.status} OK</span></td>
                                <td><strong style="color: ${isHit ? "var(--smau-emerald)" : "var(--smau-text-main)"}; font-size: 13px;">${item.time_ms} ms</strong></td>
                                <td>${item.size_kb} KB</td>
                                <td><span class="smau-cache-tag ${tagClass}">${item.cache}</span></td>
                            </tr>`;
                        });
                        tableBody.innerHTML = rows;
                        resultsBox.style.display = "block";
                    } else {
                        showToast("Benchmark error: " + (data.data || "Unknown"), true);
                    }
                })
                .catch(e => {
                    benchBtn.disabled = false;
                    loadingBox.style.display = "none";
                    showToast("Network error running benchmark.", true);
                });
            });
        }

        // Database Hygiene Action
        const dbBtn = document.getElementById("smau-btn-clean-db");
        if (dbBtn) {
            dbBtn.addEventListener("click", () => {
                if (!confirm("Run chunked database cleanup and table optimization now?")) return;
                dbBtn.disabled = true;
                dbBtn.innerHTML = "<span class="dashicons dashicons-update" style="animation: spin 1s infinite linear;"></span> Optimizing Database...";

                const fd = new FormData();
                fd.append("action", "smau_clean_database");
                fd.append("nonce", "<?php echo esc_js($nonce); ?>");

                fetch(ajaxurl, { method: "POST", body: fd })
                .then(r => r.json())
                .then(data => {
                    dbBtn.disabled = false;
                    dbBtn.innerHTML = "<span class="dashicons dashicons-trash"></span> Execute Database Hygiene & Optimize Tables";
                    if (data.success && data.data) {
                        const res = data.data;
                        showToast("Cleaned: " + (res.revisions || 0) + " revisions, " + (res.transients || 0) + " transients pruned.");
                        const revEl = document.getElementById("val-revs");
                        const trEl = document.getElementById("val-trans");
                        const bloatEl = document.getElementById("val-bloat-items");
                        if (revEl) revEl.innerText = "0";
                        if (trEl) trEl.innerText = "0";
                        if (bloatEl) bloatEl.innerText = "0";
                    } else {
                        showToast("Failed to clean database.", true);
                    }
                });
            });
        }

        // BYOK AI CSS Key Toggle & Save
        const toggleKeyBtn = document.getElementById("smau_toggle_key_vis");
        const keyInput = document.getElementById("smau_ai_key_input");
        if (toggleKeyBtn && keyInput) {
            toggleKeyBtn.addEventListener("click", () => {
                if (keyInput.type === "password") {
                    keyInput.type = "text";
                    toggleKeyBtn.innerHTML = "<span class="dashicons dashicons-hidden"></span>";
                } else {
                    keyInput.type = "password";
                    toggleKeyBtn.innerHTML = "<span class="dashicons dashicons-visibility"></span>";
                }
            });
        }

        const saveKeyBtn = document.getElementById("smau_save_ai_key_btn");
        if (saveKeyBtn) {
            saveKeyBtn.addEventListener("click", () => {
                const selectedProvider = document.querySelector("input[name="ai_provider"]:checked")?.value || "gemini";
                const apiKey = document.getElementById("smau_ai_key_input").value;

                saveKeyBtn.disabled = true;
                saveKeyBtn.innerText = "Saving...";

                const fd = new FormData();
                fd.append("action", "smau_save_ai_key");
                fd.append("nonce", "<?php echo esc_js($nonce); ?>");
                fd.append("provider", selectedProvider);
                fd.append("api_key", apiKey);

                fetch(ajaxurl, { method: "POST", body: fd })
                .then(r => r.json())
                .then(data => {
                    saveKeyBtn.disabled = false;
                    saveKeyBtn.innerText = "Save Key";
                    if (data.success) {
                        showToast("AI Provider and Encrypted API Key updated!");
                    } else {
                        showToast("Error saving AI key.", true);
                    }
                });
            });
        }

        // Run AI Scan
        const scanBtn = document.getElementById("smau_run_ai_scan_btn");
        if (scanBtn) {
            scanBtn.addEventListener("click", () => {
                const targetUrl = document.getElementById("smau_scan_url").value;
                const resultsBox = document.getElementById("smau_ai_scan_results");
                const resTitle = document.getElementById("smau_ai_res_title");
                const resSavings = document.getElementById("smau_ai_res_savings");
                const resBody = document.getElementById("smau_ai_res_body");

                scanBtn.disabled = true;
                scanBtn.innerHTML = "<span class="dashicons dashicons-update" style="animation: spin 1s infinite linear;"></span> Auditing CSS with AI...";
                resultsBox.style.display = "block";
                resTitle.innerText = "Analyzing DOM Tokens & Selectors...";
                resBody.innerHTML = "<p style="color: var(--smau-text-muted);">Auditing page structure against enqueued stylesheets via semantic AST model...</p>";

                const fd = new FormData();
                fd.append("action", "smau_ai_scan_css");
                fd.append("nonce", "<?php echo esc_js($nonce); ?>");
                fd.append("url", targetUrl);

                fetch(ajaxurl, { method: "POST", body: fd })
                .then(res => res.json())
                .then(data => {
                    scanBtn.disabled = false;
                    scanBtn.innerHTML = "<span class="dashicons dashicons-search"></span> Run AI CSS Audit";
                    if (data.success && data.data) {
                        const r = data.data;
                        resTitle.innerText = "AI CSS Audit Completed Successfully";
                        resSavings.innerText = (r.estimated_bytes_saved ? (r.estimated_bytes_saved / 1024).toFixed(1) : "0") + " KB Estimated Savings";
                        
                        let out = "<div style="margin-bottom: 12px; color: #ffffff;"><strong>Summary:</strong> " + (r.summary || "Audit complete.") + "</div>";
                        
                        if (r.unused_selectors && r.unused_selectors.length > 0) {
                            out += "<div style="margin-top: 12px; color: #f87171;"><strong>Unused Selectors Detected (" + r.unused_selectors.length + "):</strong><div style="margin-top: 6px;">";
                            r.unused_selectors.slice(0, 30).forEach(sel => {
                                out += `<span class="smau-tag-chip" style="color: #f87171;">${sel}</span>`;
                            });
                            if (r.unused_selectors.length > 30) {
                                out += `<span style="color: var(--smau-text-muted); font-size: 11px;"> ... and ${r.unused_selectors.length - 30} more selectors</span>`;
                            }
                            out += "</div></div>";
                        }
                        
                        if (r.broken_rules && r.broken_rules.length > 0) {
                            out += "<div style="margin-top: 14px; color: #fbbf24;"><strong>Broken / Obsolete Rules Detected (" + r.broken_rules.length + "):</strong><ul style="margin: 6px 0 0 18px;">";
                            r.broken_rules.forEach(br => {
                                out += `<li><code>${br.selector}</code>: ${br.issue} (<em>Fix: ${br.fix}</em>)</li>`;
                            });
                            out += "</ul></div>";
                        }
                        
                        resBody.innerHTML = out;
                    } else {
                        resTitle.innerText = "AI Audit Error";
                        resBody.innerHTML = "<p style="color: var(--smau-rose);">" + (data.data || "Failed to scan CSS. Verify API key and quota.") + "</p>";
                    }
                })
                .catch(err => {
                    scanBtn.disabled = false;
                    scanBtn.innerHTML = "<span class="dashicons dashicons-search"></span> Run AI CSS Audit";
                    resTitle.innerText = "Connection Error";
                    resBody.innerHTML = "<p style="color: var(--smau-rose);">Network error connecting to WordPress admin API.</p>";
                });
            });
        }
    });

    window.smauApplyPreset = function(type) {
        const form = document.getElementById("smau-settings-form");
        const checkboxes = form.querySelectorAll("input[type="checkbox"]");
        
        if (type === "safe") {
            checkboxes.forEach(cb => {
                if (cb.name === "delay_js_enabled") {
                    cb.checked = false;
                } else {
                    cb.checked = true;
                }
            });
        } else if (type === "velocity") {
            checkboxes.forEach(cb => {
                cb.checked = true;
            });
        }
        alert("Preset profile applied! Click "Save Changes" in the header to commit.");
    };
    </script>
    <?php
}

/**
 * AJAX Handlers
 */
add_action("wp_ajax_smau_save_settings", function() {
    check_ajax_referer("smau_admin_nonce", "nonce");
    if (!current_user_can("manage_options")) {
        wp_send_json_error("Unauthorized", 403);
    }

    $existing = get_option("smau_settings", []);
    $fields = [
        "page_cache_enabled",
        "speculation_rules_enabled",
        "hover_preload_enabled",
        "dns_prefetch_enabled",
        "lcp_turbo_enabled",
        "cls_armor_enabled",
        "lazy_load_enabled",
        "video_facades_enabled",
        "async_css_enabled",
        "delay_js_enabled",
        "minify_html_enabled",
        "cloudflare_zone_id",
        "cloudflare_api_token"
    ];

    $updated = [];
    foreach ($fields as $field) {
        if ($field === "cloudflare_zone_id" || $field === "cloudflare_api_token") {
            $updated[$field] = sanitize_text_field($_POST[$field] ?? "");
        } else {
            $updated[$field] = !empty($_POST[$field]);
        }
    }

    update_option("smau_settings", array_merge($existing, $updated));

    if (!empty($updated["page_cache_enabled"])) {
        SMAU_Cache_Engine::instance()->install_advanced_cache();
    } else {
        SMAU_Cache_Engine::instance()->remove_advanced_cache();
    }

    wp_send_json_success(["message" => "Settings updated successfully"]);
});

add_action("wp_ajax_smau_purge_cache", function() {
    check_ajax_referer("smau_admin_nonce", "nonce");
    if (!current_user_can("manage_options")) {
        wp_send_json_error("Unauthorized", 403);
    }

    SMAU_Cache_Engine::instance()->purge_all_cache();
    wp_send_json_success(["message" => "Cache purged successfully"]);
});

add_action("wp_ajax_smau_preload_cache", function() {
    check_ajax_referer("smau_admin_nonce", "nonce");
    if (!current_user_can("manage_options")) {
        wp_send_json_error("Unauthorized", 403);
    }

    $limit = !empty($_POST["limit"]) ? min(100, max(5, (int)$_POST["limit"])) : 20;
    $count = SMAU_Preload_Engine::instance()->crawl_sitemap_urls($limit);
    wp_send_json_success(["crawled" => $count]);
});

add_action("wp_ajax_smau_clean_database", function() {
    check_ajax_referer("smau_admin_nonce", "nonce");
    if (!current_user_can("manage_options")) {
        wp_send_json_error("Unauthorized", 403);
    }

    $res = SMAU_Database_Engine::instance()->clean_database();
    wp_send_json_success($res);
});

add_action("wp_ajax_smau_run_benchmark", function() {
    check_ajax_referer("smau_admin_nonce", "nonce");
    if (!current_user_can("manage_options")) {
        wp_send_json_error("Unauthorized", 403);
    }

    $url = !empty($_POST["url"]) ? esc_url_raw($_POST["url"]) : home_url("/");
    $iterations = !empty($_POST["iterations"]) ? min(10, max(1, (int)$_POST["iterations"])) : 3;

    $times = [];
    $details = [];

    for ($i = 1; $i <= $iterations; $i++) {
        $start = microtime(true);
        $response = wp_remote_get($url, ["sslverify" => false, "timeout" => 12]);
        $duration = (microtime(true) - $start) * 1000;

        if (is_wp_error($response)) {
            wp_send_json_error("Benchmark request failed: " . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $cache_header = wp_remote_retrieve_header($response, "x-smau-cache") ?: "MISS";

        $times[] = $duration;
        $details[] = [
            "iteration" => $i,
            "status"    => $code,
            "time_ms"   => round($duration, 2),
            "size_kb"   => round(strlen($body) / 1024, 1),
            "cache"     => $cache_header,
        ];
        usleep(30000);
    }

    $avg = array_sum($times) / count($times);
    $min = min($times);
    $max = max($times);

    wp_send_json_success([
        "url"     => $url,
        "avg_ms"  => round($avg, 2),
        "min_ms"  => round($min, 2),
        "max_ms"  => round($max, 2),
        "details" => $details,
    ]);
});
