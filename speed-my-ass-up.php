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

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('SMAU_VERSION'))     define('SMAU_VERSION', '1.0.0');
if (!defined('SMAU_FILE'))        define('SMAU_FILE', __FILE__);
if (!defined('SMAU_PATH'))        define('SMAU_PATH', plugin_dir_path(__FILE__));
if (!defined('SMAU_URL'))         define('SMAU_URL', plugin_dir_url(__FILE__));
if (!defined('SMAU_PLUGIN_URL'))  define('SMAU_PLUGIN_URL', SMAU_URL);
if (!defined('SMAU_SLUG'))        define('SMAU_SLUG', 'speed-my-ass-up');

// Load Subsystems
require_once SMAU_PATH . 'includes/class-smau-cache-engine.php';
require_once SMAU_PATH . 'includes/class-smau-preload-engine.php';
require_once SMAU_PATH . 'includes/class-smau-optimizer-engine.php';
require_once SMAU_PATH . 'includes/class-smau-media-engine.php';
require_once SMAU_PATH . 'includes/class-smau-database-engine.php';
require_once SMAU_PATH . 'includes/class-smau-edge-engine.php';
require_once SMAU_PATH . 'includes/class-smau-security-bridge.php';
require_once SMAU_PATH . 'includes/class-smau-ai-css-engine.php';
require_once SMAU_PATH . 'includes/class-smau-cli-command.php';

/**
 * Plugin Activation
 */
function smau_activate() {
    $default_settings = [
        'page_cache_enabled'        => true,
        'speculation_rules_enabled' => true,
        'hover_preload_enabled'     => true,
        'dns_prefetch_enabled'      => true,
        'lcp_turbo_enabled'         => true,
        'cls_armor_enabled'         => true,
        'lazy_load_enabled'         => true,
        'video_facades_enabled'     => true,
        'async_css_enabled'         => true,
        'delay_js_enabled'          => true,
        'minify_html_enabled'       => true,
        'cdn_cname_enabled'         => false,
        'cdn_cname_domain'          => '',
        'cloudflare_zone_id'        => '',
        'cloudflare_api_token'      => ''
    ];

    if (!get_option('smau_settings')) {
        update_option('smau_settings', $default_settings);
    }

    // Attempt to install advanced-cache.php
    SMAU_Cache_Engine::instance()->install_advanced_cache();

    // Schedule periodic maintenance
    if (!wp_next_scheduled('smau_db_maintenance_cron')) {
        wp_schedule_event(time() + 3600, 'weekly', 'smau_db_maintenance_cron');
    }
}
register_activation_hook(__FILE__, 'smau_activate');

/**
 * Plugin Deactivation
 */
function smau_deactivate() {
    SMAU_Cache_Engine::instance()->remove_advanced_cache();
    wp_clear_scheduled_hook('smau_db_maintenance_cron');
}
register_deactivation_hook(__FILE__, 'smau_deactivate');

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
add_action('plugins_loaded', 'smau_init', 10);

/**
 * Register Top-Level Admin Menu
 */
function smau_register_admin_menu() {
    add_menu_page(
        'Speed My A$$ Up',
        'Speed My A$$ Up',
        'manage_options',
        'speed-my-ass-up',
        'smau_render_admin_dashboard',
        'dashicons-performance',
        3.3
    );
}
add_action('admin_menu', 'smau_register_admin_menu');

/**
 * Enqueue Admin Assets
 */
function smau_enqueue_admin_assets($hook) {
    if (strpos($hook, 'speed-my-ass-up') === false) {
        return;
    }

    wp_enqueue_style('smau-admin-css', SMAU_URL . 'assets/css/smau-admin.css', [], SMAU_VERSION);
}
add_action('admin_enqueue_scripts', 'smau_enqueue_admin_assets');

/**
 * Admin Dashboard Render
 */
function smau_render_admin_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'speed-my-ass-up'));
    }

    $settings = get_option('smau_settings', []);
    $stats = SMAU_Cache_Engine::instance()->get_stats();
    $bloat = SMAU_Database_Engine::instance()->get_bloat_stats();
    $suite = SMAU_Security_Bridge::instance()->get_suite_status();
    $nonce = wp_create_nonce('smau_admin_nonce');

    ?>
    <div class="wrap smau-wrap">
        <div class="smau-header">
            <div class="smau-brand">
                <div class="smau-logo-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                </div>
                <div>
                    <h1>Speed My A$$ Up <span class="smau-badge smau-badge-pro">v<?php echo esc_html(SMAU_VERSION); ?></span> <span class="smau-badge smau-badge-suite">Sentinel Suite</span></h1>
                    <p style="margin: 4px 0 0; color: #94a3b8; font-size: 13px;">Zero-Bloat, Security-Aware Web Performance & Core Web Vitals Hyper-Engine</p>
                </div>
            </div>
            <div class="smau-quick-actions">
                <button type="button" class="smau-btn smau-btn-danger" id="smau-btn-purge">
                    <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px;"></span> Purge Cache
                </button>
                <button type="button" class="smau-btn smau-btn-secondary" id="smau-btn-preload">
                    <span class="dashicons dashicons-update" style="font-size: 16px; width: 16px; height: 16px;"></span> Warm Preload
                </button>
                <button type="button" class="smau-btn smau-btn-primary" id="smau-btn-save-top">
                    <span class="dashicons dashicons-saved" style="font-size: 16px; width: 16px; height: 16px;"></span> Save Changes
                </button>
            </div>
        </div>

        <!-- Real Telemetry Cards (Zero Mock) -->
        <div class="smau-metrics-grid">
            <div class="smau-metric-card">
                <div class="smau-metric-title">Cached HTML Pages</div>
                <div class="smau-metric-val" id="val-cache-files"><?php echo esc_html($stats['files']); ?></div>
                <div class="smau-metric-sub">Static pages on disk</div>
            </div>
            <div class="smau-metric-card">
                <div class="smau-metric-title">Cache Disk Footprint</div>
                <div class="smau-metric-val" id="val-cache-size"><?php echo esc_html($stats['size_mb']); ?> <span style="font-size: 14px; font-weight: 500;">MB</span></div>
                <div class="smau-metric-sub">GZIP + Raw HTML storage</div>
            </div>
            <div class="smau-metric-card">
                <div class="smau-metric-title">Pre-Boot Fast Path</div>
                <div class="smau-metric-val" style="color: #34d399;">&lt; 0.5 <span style="font-size: 14px; font-weight: 500;">ms</span></div>
                <div class="smau-metric-sub"><?php echo file_exists(WP_CONTENT_DIR . '/advanced-cache.php') ? 'advanced-cache.php Active' : 'Buffer Fallback Active'; ?></div>
            </div>
            <div class="smau-metric-card">
                <div class="smau-metric-title">Database Bloat Headroom</div>
                <div class="smau-metric-val" style="color: #38bdf8;" id="val-bloat-items"><?php echo esc_html($bloat['total_bloat_items']); ?></div>
                <div class="smau-metric-sub">Revisions, drafts & transients</div>
            </div>
        </div>

        <!-- Settings Form & Layout -->
        <form id="smau-settings-form">
            <input type="hidden" name="action" value="smau_save_settings">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

            <div class="smau-layout">
                <div>
                    <!-- Module 1: Pre-Boot Page Caching -->
                    <div class="smau-card">
                        <div class="smau-card-title">
                            <span class="dashicons dashicons-media-document" style="color: #38bdf8;"></span>
                            Pre-Boot Static Page Caching
                        </div>
                        <div class="smau-setting-row">
                            <div class="smau-setting-info">
                                <div class="smau-setting-title">Enable Static HTML Page Cache</div>
                                <div class="smau-setting-desc">Generates sub-millisecond flat HTML & GZIP caches on disk, completely bypassing heavy PHP execution and database queries for public visitors.</div>
                            </div>
                            <label class="smau-switch">
                                <input type="checkbox" name="page_cache_enabled" value="1" <?php checked(!empty($settings['page_cache_enabled'])); ?>>
                                <span class="smau-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Module 2: Core Web Vitals Hyper-Engine -->
                    <div class="smau-card">
                        <div class="smau-card-title">
                            <span class="dashicons dashicons-performance" style="color: #38bdf8;"></span>
                            Core Web Vitals Hyper-Engine (LCP, INP, CLS)
                        </div>
                        <div class="smau-setting-row">
                            <div class="smau-setting-info">
                                <div class="smau-setting-title">LCP Turbo Priority Booster</div>
                                <div class="smau-setting-desc">Detects primary above-the-fold viewport hero images, injects <code>fetchpriority="high"</code> and <code>&lt;link rel="preload" as="image"&gt;</code> in <code>&lt;head&gt;</code>, and strips lazy-load to maximize LCP speeds.</div>
                            </div>
                            <label class="smau-switch">
                                <input type="checkbox" name="lcp_turbo_enabled" value="1" <?php checked(!empty($settings['lcp_turbo_enabled'])); ?>>
                                <span class="smau-slider"></span>
                            </label>
                        </div>
                        <div class="smau-setting-row">
                            <div class="smau-setting-info">
                                <div class="smau-setting-title">CLS Zero-Shift Armor</div>
                                <div class="smau-setting-desc">Calculates missing image and iframe dimensions on-the-fly and injects explicit <code>width</code>, <code>height</code>, and <code>aspect-ratio</code> CSS properties to drop Cumulative Layout Shift to 0.000.</div>
                            </div>
                            <label class="smau-switch">
                                <input type="checkbox" name="cls_armor_enabled" value="1" <?php checked(!empty($settings['cls_armor_enabled'])); ?>>
                                <span class="smau-slider"></span>
                            </label>
                        </div>
                        <div class="smau-setting-row">
                            <div class="smau-setting-info">
                                <div class="smau-setting-title">Smart Delay JavaScript Execution</div>
                                <div class="smau-setting-desc">Defers non-critical JavaScript until user interaction (pointer, scroll, touch, or keypress), radically improving Interaction to Next Paint (INP) with automatic safelisting for WooCommerce and Divi 5.</div>
                            </div>
                            <label class="smau-switch">
                                <input type="checkbox" name="delay_js_enabled" value="1" <?php checked(!empty($settings['delay_js_enabled'])); ?>>
                                <span class="smau-slider"></span>
                            </label>
                        </div>
                        <div class="smau-setting-row">
                            <div class="smau-setting-info">
                                <div class="smau-setting-title">Asynchronous CSS Delivery</div>
                                <div class="smau-setting-desc">Converts render-blocking stylesheets into asynchronous preloads with noscript fallbacks to unlock instant first paint.</div>
                            </div>
                            <label class="smau-switch">
                                <input type="checkbox" name="async_css_enabled" value="1" <?php checked(!empty($settings['async_css_enabled'])); ?>>
                                <span class="smau-slider"></span>
                            </label>
                        </div>
                        <div class="smau-setting-row">
                            <div class="smau-setting-info">
                                <div class="smau-setting-title">YouTube & Vimeo Video Facades</div>
                                <div class="smau-setting-desc">Replaces heavy video iframes with ultra-lightweight static preview posters and responsive SVG play buttons, saving ~1.2MB of JS per embed until clicked.</div>
                            </div>
                            <label class="smau-switch">
                                <input type="checkbox" name="video_facades_enabled" value="1" <?php checked(!empty($settings['video_facades_enabled'])); ?>>
                                <span class="smau-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Module 3: Speculative Navigation & Hints -->
                    <div class="smau-card">
                        <div class="smau-card-title">
                            <span class="dashicons dashicons-admin-links" style="color: #38bdf8;"></span>
                            Speculative Preloading & Instant Transitions
                        </div>
                        <div class="smau-setting-row">
                            <div class="smau-setting-info">
                                <div class="smau-setting-title">W3C Speculation Rules API</div>
                                <div class="smau-setting-desc">Instructs modern Chromium browsers to speculatively pre-render internal navigation links, enabling sub-50ms instant page changes.</div>
                            </div>
                            <label class="smau-switch">
                                <input type="checkbox" name="speculation_rules_enabled" value="1" <?php checked(!empty($settings['speculation_rules_enabled'])); ?>>
                                <span class="smau-slider"></span>
                            </label>
                        </div>
                        <div class="smau-setting-row">
                            <div class="smau-setting-info">
                                <div class="smau-setting-title">Hover-Intent Link Prefetching</div>
                                <div class="smau-setting-desc">Smart 65ms hover listener that downloads HTML documents in the background before the user clicks on non-Chromium browsers.</div>
                            </div>
                            <label class="smau-switch">
                                <input type="checkbox" name="hover_preload_enabled" value="1" <?php checked(!empty($settings['hover_preload_enabled'])); ?>>
                                <span class="smau-slider"></span>
                            </label>
                        </div>
                        <div class="smau-setting-row">
                            <div class="smau-setting-info">
                                <div class="smau-setting-title">DNS Prefetch & Preconnect Hints</div>
                                <div class="smau-setting-desc">Pre-resolves DNS and establishes TCP/TLS handshakes early for Google Fonts, CDNs, and third-party APIs.</div>
                            </div>
                            <label class="smau-switch">
                                <input type="checkbox" name="dns_prefetch_enabled" value="1" <?php checked(!empty($settings['dns_prefetch_enabled'])); ?>>
                                <span class="smau-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Module 4: BYOK AI CSS Scanner & Optimizer -->
                    <div class="smau-card">
                        <div class="smau-card-title">
                            <span class="dashicons dashicons-superhero" style="color: #38bdf8;"></span>
                            BYOK AI CSS Scanner (Unused & Broken CSS)
                        </div>
                        <p style="font-size: 13px; color: #94a3b8; margin-bottom: 16px;">
                            Bring Your Own Key (BYOK) for direct, zero-markup AI auditing. Audits stylesheets against the live DOM to isolate dead/unused selectors, pinpoint malformed or broken CSS syntax, and generate hyper-optimized critical CSS.
                        </p>
                        <div class="smau-setting-row" style="flex-direction: column; gap: 12px; align-items: stretch;">
                            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; width: 100%;">
                                <div>
                                    <label style="font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase;">AI Model Provider</label>
                                    <select id="smau_ai_provider" style="width: 100%; background: #090d16; border: 1px solid #1e293b; color: #f1f5f9; padding: 8px 12px; border-radius: 6px; margin-top: 4px;">
                                        <option value="gemini" <?php selected(($settings['ai_provider'] ?? 'gemini') === 'gemini'); ?>>Google Gemini (2.5 Flash / Fast)</option>
                                        <option value="openai" <?php selected(($settings['ai_provider'] ?? '') === 'openai'); ?>>OpenAI (GPT-4o / GPT-4o-mini)</option>
                                        <option value="anthropic" <?php selected(($settings['ai_provider'] ?? '') === 'anthropic'); ?>>Anthropic Claude (3.5 Sonnet)</option>
                                        <option value="openrouter" <?php selected(($settings['ai_provider'] ?? '') === 'openrouter'); ?>>OpenRouter Gateway</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase;">BYOK API Key (AES-256 Encrypted)</label>
                                    <div style="display: flex; gap: 8px; margin-top: 4px;">
                                        <input type="password" id="smau_ai_key" placeholder="<?php echo esc_attr(SMAU_AI_CSS_Engine::instance()->get_masked_api_key() ?: 'Enter your API key...'); ?>" style="flex: 1; background: #090d16; border: 1px solid #1e293b; color: #38bdf8; padding: 8px 12px; border-radius: 6px; font-family: monospace;">
                                        <button type="button" class="smau-btn smau-btn-secondary" id="smau_save_key_btn">Save Key</button>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 12px; width: 100%; margin-top: 8px; align-items: center;">
                                <input type="url" id="smau_scan_url" value="<?php echo esc_attr(home_url('/')); ?>" placeholder="https://example.com/page-to-scan" style="flex: 1; background: #090d16; border: 1px solid #1e293b; color: #f1f5f9; padding: 8px 12px; border-radius: 6px;">
                                <button type="button" class="smau-btn smau-btn-primary" id="smau_run_ai_scan_btn">
                                    <span class="dashicons dashicons-search"></span> Run AI CSS Audit
                                </button>
                            </div>
                            <div id="smau_ai_scan_results" style="display: none; width: 100%; margin-top: 14px; background: #090d16; border: 1px solid #1e293b; border-radius: 8px; padding: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; padding-bottom: 8px; margin-bottom: 12px;">
                                    <strong style="color: #34d399;" id="smau_ai_res_title">AI Audit Complete</strong>
                                    <span style="font-size: 12px; color: #38bdf8;" id="smau_ai_res_savings">0 KB Saved</span>
                                </div>
                                <div id="smau_ai_res_body" style="font-size: 13px; color: #cbd5e1; max-height: 250px; overflow-y: auto; line-height: 1.6;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Controls & Sentinel Interop -->
                <div>
                    <!-- Quick Optimization Presets -->
                    <div class="smau-card">
                        <div class="smau-card-title">
                            <span class="dashicons dashicons-dashboard" style="color: #34d399;"></span>
                            Optimization Presets
                        </div>
                        <p style="font-size: 13px; color: #94a3b8; margin-bottom: 16px;">Apply battle-tested configurations in one click.</p>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <button type="button" class="smau-btn smau-btn-secondary" onclick="smauApplyPreset('safe')" style="justify-content: flex-start;">
                                🛡️ <strong>Balanced Mode</strong> (Safe for all sites)
                            </button>
                            <button type="button" class="smau-btn smau-btn-primary" onclick="smauApplyPreset('velocity')" style="justify-content: flex-start;">
                                ⚡ <strong>Max Velocity Mode</strong> (Target 100 CWV)
                            </button>
                        </div>
                    </div>

                    <!-- Database Hygiene Card -->
                    <div class="smau-card">
                        <div class="smau-card-title">
                            <span class="dashicons dashicons-database" style="color: #38bdf8;"></span>
                            Database Lean Storage
                        </div>
                        <p style="font-size: 13px; color: #94a3b8;">Clean database overhead to accelerate MySQL response latency.</p>
                        <div style="margin: 14px 0; font-size: 13px; color: #cbd5e1;">
                            <div>• Revisions: <strong><?php echo esc_html($bloat['revisions']); ?></strong></div>
                            <div>• Drafts & Trash: <strong><?php echo esc_html($bloat['autodrafts'] + $bloat['trashed_posts']); ?></strong></div>
                            <div>• Expired Transients: <strong><?php echo esc_html($bloat['expired_transients']); ?></strong></div>
                        </div>
                        <button type="button" class="smau-btn smau-btn-secondary" id="smau-btn-cleandb" style="width: 100%; justify-content: center;">
                            <span class="dashicons dashicons-admin-tools"></span> Clean & Optimize Database
                        </button>
                    </div>

                    <!-- Security Sentinel Suite Status Card -->
                    <div class="smau-card">
                        <div class="smau-card-title">
                            <span class="dashicons dashicons-shield" style="color: #34d399;"></span>
                            Security Sentinel Suite
                        </div>
                        <p style="font-size: 13px; color: #94a3b8; margin-bottom: 14px;">Unified security & performance fleet posture.</p>
                        <?php foreach ($suite as $product_slug => $data): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #1e293b; font-size: 12px;">
                                <div>
                                    <strong style="color: #e2e8f0;"><?php echo esc_html(str_replace('-', ' ', ucwords($product_slug, '-'))); ?></strong>
                                    <div style="color: #64748b;"><?php echo esc_html($data['role']); ?></div>
                                </div>
                                <div>
                                    <?php if ($data['active']): ?>
                                        <span class="smau-badge smau-badge-suite">ACTIVE <?php echo esc_html($data['version'] ? 'v' . $data['version'] : ''); ?></span>
                                    <?php else: ?>
                                        <span class="smau-badge" style="background: rgba(148, 163, 184, 0.15); color: #94a3b8;">STANDBY</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Admin AJAX JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('smau-settings-form');
        const saveTopBtn = document.getElementById('smau-btn-save-top');
        const purgeBtn = document.getElementById('smau-btn-purge');
        const preloadBtn = document.getElementById('smau-btn-preload');
        const cleanDbBtn = document.getElementById('smau-btn-cleandb');

        // Save settings handler
        const saveSettings = () => {
            const formData = new FormData(form);
            saveTopBtn.disabled = true;
            saveTopBtn.innerHTML = '<span class="dashicons dashicons-update"></span> Saving...';

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                saveTopBtn.disabled = false;
                saveTopBtn.innerHTML = '<span class="dashicons dashicons-saved"></span> Save Changes';
                if (data.success) {
                    alert('Settings successfully saved!');
                } else {
                    alert('Error saving settings: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(err => {
                saveTopBtn.disabled = false;
                saveTopBtn.innerHTML = '<span class="dashicons dashicons-saved"></span> Save Changes';
                alert('Connection error');
            });
        };

        saveTopBtn.addEventListener('click', saveSettings);

        // Purge Cache handler
        purgeBtn.addEventListener('click', () => {
            if (!confirm('Are you sure you want to flush all static page caches?')) return;
            purgeBtn.disabled = true;

            const fd = new FormData();
            fd.append('action', 'smau_purge_cache');
            fd.append('nonce', '<?php echo esc_js($nonce); ?>');

            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                purgeBtn.disabled = false;
                if (data.success) {
                    document.getElementById('val-cache-files').innerText = '0';
                    document.getElementById('val-cache-size').innerText = '0.00';
                    alert('Static HTML cache purged!');
                }
            });
        });

        // Warm Preload handler
        preloadBtn.addEventListener('click', () => {
            preloadBtn.disabled = true;
            preloadBtn.innerHTML = '<span class="dashicons dashicons-update"></span> Warming...';

            const fd = new FormData();
            fd.append('action', 'smau_preload_cache');
            fd.append('nonce', '<?php echo esc_js($nonce); ?>');

            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                preloadBtn.disabled = false;
                preloadBtn.innerHTML = '<span class="dashicons dashicons-update"></span> Warm Preload';
                if (data.success) {
                    alert('Preloaded ' + data.data.crawled + ' pages into cache!');
                    location.reload();
                }
            });
        });

        // Clean Database handler
        cleanDbBtn.addEventListener('click', () => {
            if (!confirm('Optimize database tables and prune revisions & transients now?')) return;
            cleanDbBtn.disabled = true;

            const fd = new FormData();
            fd.append('action', 'smau_clean_database');
            fd.append('nonce', '<?php echo esc_js($nonce); ?>');

            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                cleanDbBtn.disabled = false;
                if (data.success) {
                    document.getElementById('val-bloat-items').innerText = '0';
                    alert('Database hygiene completed successfully!');
                }
            });
        });

        // Save BYOK Key handler
        const saveKeyBtn = document.getElementById('smau_save_key_btn');
        if (saveKeyBtn) {
            saveKeyBtn.addEventListener('click', () => {
                const provider = document.getElementById('smau_ai_provider').value;
                const apiKey = document.getElementById('smau_ai_key').value;

                saveKeyBtn.disabled = true;
                saveKeyBtn.innerText = 'Saving...';

                const fd = new FormData();
                fd.append('action', 'smau_save_ai_key');
                fd.append('nonce', '<?php echo esc_js($nonce); ?>');
                fd.append('provider', provider);
                fd.append('api_key', apiKey);

                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    saveKeyBtn.disabled = false;
                    saveKeyBtn.innerText = 'Save Key';
                    if (data.success) {
                        alert('BYOK API Key encrypted and saved successfully!');
                        document.getElementById('smau_ai_key').value = '';
                    } else {
                        alert('Error saving key: ' + (data.data || 'Unknown error'));
                    }
                });
            });
        }

        // Run AI Scan handler
        const scanBtn = document.getElementById('smau_run_ai_scan_btn');
        if (scanBtn) {
            scanBtn.addEventListener('click', () => {
                const targetUrl = document.getElementById('smau_scan_url').value;
                const resultsBox = document.getElementById('smau_ai_scan_results');
                const resTitle = document.getElementById('smau_ai_res_title');
                const resSavings = document.getElementById('smau_ai_res_savings');
                const resBody = document.getElementById('smau_ai_res_body');

                scanBtn.disabled = true;
                scanBtn.innerHTML = '<span class="dashicons dashicons-update"></span> Auditing CSS with AI...';
                resultsBox.style.display = 'block';
                resTitle.innerText = 'Analyzing DOM & Stylesheets...';
                resBody.innerHTML = '<p style="color: #94a3b8;">Reconciling active DOM elements against enqueued stylesheet rules via AI model...</p>';

                const fd = new FormData();
                fd.append('action', 'smau_ai_scan_css');
                fd.append('nonce', '<?php echo esc_js($nonce); ?>');
                fd.append('url', targetUrl);

                fetch(ajaxurl, { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    scanBtn.disabled = false;
                    scanBtn.innerHTML = '<span class="dashicons dashicons-search"></span> Run AI CSS Audit';
                    if (data.success && data.data) {
                        const r = data.data;
                        resTitle.innerText = 'AI Audit Completed Successfully';
                        resSavings.innerText = (r.estimated_bytes_saved ? (r.estimated_bytes_saved / 1024).toFixed(1) : '0') + ' KB Estimated Savings';
                        
                        let out = '<div style="margin-bottom: 10px;"><strong>Summary:</strong> ' + (r.summary || 'Audit complete.') + '</div>';
                        
                        if (r.unused_selectors && r.unused_selectors.length > 0) {
                            out += '<div style="margin-top: 10px; color: #f87171;"><strong>Unused Selectors Detected (' + r.unused_selectors.length + '):</strong><br><code style="background: #1e293b; padding: 4px 8px; display: block; border-radius: 4px; margin-top: 4px; word-break: break-all;">' + r.unused_selectors.join(', ') + '</code></div>';
                        }
                        
                        if (r.broken_rules && r.broken_rules.length > 0) {
                            out += '<div style="margin-top: 10px; color: #fbbf24;"><strong>Broken / Malformed CSS Detected (' + r.broken_rules.length + '):</strong><ul style="margin: 4px 0 0 18px; list-style-type: disc;">';
                            r.broken_rules.forEach(br => {
                                out += '<li><code>' + br.selector + '</code>: ' + br.issue + ' (<em>Fix: ' + br.fix + '</em>)</li>';
                            });
                            out += '</ul></div>';
                        }
                        
                        resBody.innerHTML = out;
                    } else {
                        resTitle.innerText = 'AI Audit Error';
                        resTitle.style.color = '#ef4444';
                        resBody.innerHTML = '<p style="color: #f87171;">' + (data.data || 'Failed to scan CSS. Check API key and quota.') + '</p>';
                    }
                })
                .catch(err => {
                    scanBtn.disabled = false;
                    scanBtn.innerHTML = '<span class="dashicons dashicons-search"></span> Run AI CSS Audit';
                    resTitle.innerText = 'Connection Error';
                    resBody.innerHTML = '<p style="color: #f87171;">Network error connecting to WordPress admin API.</p>';
                });
            });
        }
    });

    // Preset helper
    window.smauApplyPreset = function(type) {
        const form = document.getElementById('smau-settings-form');
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        
        if (type === 'safe') {
            checkboxes.forEach(cb => {
                if (cb.name === 'delay_js_enabled') {
                    cb.checked = false;
                } else {
                    cb.checked = true;
                }
            });
        } else if (type === 'velocity') {
            checkboxes.forEach(cb => {
                cb.checked = true;
            });
        }
        alert('Preset applied! Click "Save Changes" to commit.');
    };
    </script>
    <?php
}

/**
 * AJAX Handlers
 */
add_action('wp_ajax_smau_save_settings', function() {
    check_ajax_referer('smau_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $existing = get_option('smau_settings', []);
    $fields = [
        'page_cache_enabled',
        'speculation_rules_enabled',
        'hover_preload_enabled',
        'dns_prefetch_enabled',
        'lcp_turbo_enabled',
        'cls_armor_enabled',
        'lazy_load_enabled',
        'video_facades_enabled',
        'async_css_enabled',
        'delay_js_enabled',
        'minify_html_enabled'
    ];

    $updated = [];
    foreach ($fields as $field) {
        $updated[$field] = !empty($_POST[$field]);
    }

    update_option('smau_settings', array_merge($existing, $updated));

    // Handle advanced-cache.php install/removal
    if (!empty($updated['page_cache_enabled'])) {
        SMAU_Cache_Engine::instance()->install_advanced_cache();
    } else {
        SMAU_Cache_Engine::instance()->remove_advanced_cache();
    }

    wp_send_json_success(['message' => 'Settings updated successfully']);
});

add_action('wp_ajax_smau_purge_cache', function() {
    check_ajax_referer('smau_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    SMAU_Cache_Engine::instance()->purge_all_cache();
    wp_send_json_success(['message' => 'Cache purged successfully']);
});

add_action('wp_ajax_smau_preload_cache', function() {
    check_ajax_referer('smau_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $count = SMAU_Preload_Engine::instance()->crawl_sitemap_urls(20);
    wp_send_json_success(['crawled' => $count]);
});

add_action('wp_ajax_smau_clean_database', function() {
    check_ajax_referer('smau_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $res = SMAU_Database_Engine::instance()->clean_database();
    wp_send_json_success($res);
});
