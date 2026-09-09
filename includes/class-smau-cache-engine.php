<?php
/**
 * Speed My A$$ Up - Page Cache Engine
 * 
 * High-velocity, zero-bloat static HTML disk caching with sub-millisecond pre-boot delivery.
 * Part of the Security Sentinel Suite by securemyass.com.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMAU_Cache_Engine {

    private static $instance = null;
    private $cache_dir;
    private $is_caching = false;
    private $buffer_active = false;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->cache_dir = WP_CONTENT_DIR . '/cache/smau';
        
        $settings = get_option('smau_settings', []);
        if (!empty($settings['page_cache_enabled'])) {
            $this->init_caching_hooks();
        }

        // Invalidation hooks
        add_action('save_post', [$this, 'invalidate_post_cache'], 10, 2);
        add_action('comment_post', [$this, 'invalidate_comment_cache'], 10, 2);
        add_action('edit_terms', [$this, 'invalidate_term_cache'], 10, 2);
        add_action('switch_theme', [$this, 'purge_all_cache']);
        add_action('wp_trash_post', [$this, 'invalidate_post_cache']);
    }

    /**
     * Hook output buffering for cache capture
     */
    private function init_caching_hooks() {
        // Only run for frontend GET requests
        if (is_admin() || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_CRON') && DOING_CRON) || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }

        add_action('template_redirect', [$this, 'start_buffer'], 0);
    }

    /**
     * Start output buffering
     */
    public function start_buffer() {
        if (!$this->should_cache_request()) {
            return;
        }

        $this->buffer_active = true;
        ob_start([$this, 'process_and_cache_buffer']);
    }

    /**
     * Check if current request is eligible for caching
     */
    public function should_cache_request() {
        if (is_user_logged_in()) {
            return false;
        }

        // Bypass for WooCommerce active carts / sessions
        if (isset($_COOKIE['woocommerce_items_in_cart']) && (int)$_COOKIE['woocommerce_items_in_cart'] > 0) {
            return false;
        }

        // Bypass for standard dynamic cookies
        foreach (array_keys($_COOKIE) as $cookie_name) {
            if (strpos($cookie_name, 'wordpress_logged_in_') === 0 || 
                strpos($cookie_name, 'comment_author_') === 0) {
                return false;
            }
        }

        // Bypass 404, search, feed, or preview
        if (is_404() || is_search() || is_feed() || is_preview() || is_trackback()) {
            return false;
        }

        // Check query strings (allow empty or whitelisted params like utm_*)
        if (!empty($_GET)) {
            $allowed_params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid'];
            $query_keys = array_keys($_GET);
            $diff = array_diff($query_keys, $allowed_params);
            if (!empty($diff)) {
                return false;
            }
        }

        // Sentinel Suite Security Hook: Check query entropy via GMA
        if (class_exists('GMA_ML_Engine') && !empty($_SERVER['QUERY_STRING'])) {
            $entropy = GMA_ML_Engine::calculate_shannon_entropy($_SERVER['QUERY_STRING']);
            if ($entropy >= 4.2) {
                // Potential cache poisoning / exploit payload - bypass cache
                return false;
            }
        }

        return true;
    }

    /**
     * Callback for output buffer: optimize HTML, save cache to disk, and return content
     */
    public function process_and_cache_buffer($buffer) {
        if (empty($buffer) || strlen($buffer) < 255) {
            return $buffer;
        }

        $status_code = http_response_code();
        if ($status_code && $status_code !== 200) {
            return $buffer;
        }

        // Apply Core Web Vitals & Optimization pipeline before caching
        if (class_exists('SMAU_Media_Engine')) {
            $buffer = SMAU_Media_Engine::instance()->optimize_html($buffer);
        }
        if (class_exists('SMAU_Optimizer_Engine')) {
            $buffer = SMAU_Optimizer_Engine::instance()->optimize_html($buffer);
        }

        // Add signature comment
        $timestamp = gmdate('Y-m-d H:i:s');
        $signature = "\n<!-- Speed My A$$ Up (SMAU) Page Cache Engine - Cached at: {$timestamp} UTC -->";
        $cached_html = $buffer . $signature;

        // Write to disk
        $cache_file = $this->get_cache_file_path();
        if ($cache_file) {
            $cache_dir = dirname($cache_file);
            if (!is_dir($cache_dir)) {
                wp_mkdir_p($cache_dir);
                $this->protect_cache_dir($cache_dir);
            }

            @file_put_contents($cache_file, $cached_html, LOCK_EX);
            
            // GZIP pre-compressed version
            if (function_exists('gzencode')) {
                $gzipped = gzencode($cached_html, 9);
                if ($gzipped) {
                    @file_put_contents($cache_file . '.gz', $gzipped, LOCK_EX);
                }
            }

            $this->record_cache_generation();
        }

        header('X-SMAU-Cache: GENERATE');
        return $cached_html;
    }

    /**
     * Generate file path for current request
     */
    public function get_cache_file_path($url = null) {
        if ($url) {
            $parsed = parse_url($url);
            $host = strtolower($parsed['host'] ?? 'localhost');
            $path = $parsed['path'] ?? '/';
        } else {
            $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        }

        // Sanitize host and path in pure PHP without WP dependencies
        $host = preg_replace('/[^a-z0-9\.\-_]/i', '', $host);
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/index';
        }

        $safe_path = preg_replace('/[^a-z0-9\.\-_]/i', '_', trim($path, '/'));
        if (empty($safe_path)) {
            $safe_path = 'index';
        }

        return $this->cache_dir . '/' . $host . '/' . $safe_path . '.html';
    }

    /**
     * Protect cache directory with index.php and execution restrictions
     */
    private function protect_cache_dir($dir) {
        $index_file = $dir . '/index.php';
        if (!file_exists($index_file)) {
            @file_put_contents($index_file, "<?php\n// Silence is golden.\n");
        }

        $htaccess = WP_CONTENT_DIR . '/cache/smau/.htaccess';
        if (!file_exists($htaccess)) {
            $rules = "# SMAU Cache Rules\n<IfModule mod_headers.c>\nHeader set Cache-Control \"public, max-age=3600, must-revalidate\"\n</IfModule>\n";
            @file_put_contents($htaccess, $rules);
        }
    }

    /**
     * Invalidate cache for a specific post
     */
    public function invalidate_post_cache($post_id, $post = null) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        $permalink = get_permalink($post_id);
        if ($permalink) {
            $this->purge_url_cache($permalink);
        }

        // Also invalidate front page and blog archive
        $home_url = home_url('/');
        $this->purge_url_cache($home_url);
    }

    public function invalidate_comment_cache($comment_id, $comment_approved) {
        if ($comment_approved === 1) {
            $comment = get_comment($comment_id);
            if ($comment && !empty($comment->comment_post_ID)) {
                $this->invalidate_post_cache($comment->comment_post_ID);
            }
        }
    }

    public function invalidate_term_cache($term_id, $tt_id) {
        $term_link = get_term_link((int)$term_id);
        if (!is_wp_error($term_link)) {
            $this->purge_url_cache($term_link);
        }
        $this->purge_url_cache(home_url('/'));
    }

    /**
     * Purge specific URL from disk
     */
    public function purge_url_cache($url) {
        $cache_file = $this->get_cache_file_path($url);
        if ($cache_file && file_exists($cache_file)) {
            @unlink($cache_file);
        }
        if ($cache_file && file_exists($cache_file . '.gz')) {
            @unlink($cache_file . '.gz');
        }
    }

    /**
     * Purge all cached files
     */
    public function purge_all_cache() {
        if (!is_dir($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
            @chmod($this->cache_dir, 0777);
            $this->protect_cache_dir($this->cache_dir);
            return true;
        }

        $files = array_diff(scandir($this->cache_dir), ['.', '..', '.htaccess', '.user.ini', 'index.php']);
        foreach ($files as $file) {
            $path = $this->cache_dir . '/' . $file;
            if (is_dir($path)) {
                $this->delete_directory_recursive($path);
            } else {
                @unlink($path);
            }
        }
        @chmod($this->cache_dir, 0777);
        $this->protect_cache_dir($this->cache_dir);

        // Reset metrics transient
        set_transient('smau_last_cache_purge', time(), DAY_IN_SECONDS * 30);
        return true;
    }

    private function delete_directory_recursive($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->delete_directory_recursive($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /**
     * Deploy the pre-boot advanced-cache.php drop-in (100% pure PHP, zero WP core dependencies)
     */
    public function install_advanced_cache() {
        $dest = WP_CONTENT_DIR . '/advanced-cache.php';
        $template = "<?php\n" .
            "// Speed My A\$\$ Up (SMAU) Pre-Boot Fast Path\n" .
            "if (!defined('ABSPATH')) exit;\n\n" .
            "\$cache_dir = WP_CONTENT_DIR . '/cache/smau';\n" .
            "if ((\$_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') return;\n" .
            "if (!empty(\$_COOKIE)) {\n" .
            "    foreach (array_keys(\$_COOKIE) as \$k) {\n" .
            "        if (strpos(\$k, 'wordpress_logged_in_') === 0 || strpos(\$k, 'woocommerce_items_in_cart') === 0) return;\n" .
            "    }\n" .
            "}\n" .
            "\$host = preg_replace('/[^a-z0-9\\.\\-_]/i', '', strtolower(\$_SERVER['HTTP_HOST'] ?? 'localhost'));\n" .
            "\$path = parse_url(\$_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);\n" .
            "\$path = rtrim(\$path, '/');\n" .
            "if (empty(\$path)) \$path = '/index';\n" .
            "\$safe_path = preg_replace('/[^a-z0-9\\.\\-_]/i', '_', trim(\$path, '/'));\n" .
            "if (empty(\$safe_path)) \$safe_path = 'index';\n" .
            "\$cache_file = \$cache_dir . '/' . \$host . '/' . \$safe_path . '.html';\n" .
            "if (file_exists(\$cache_file) && (time() - filemtime(\$cache_file)) < 86400) {\n" .
            "    header('X-SMAU-Cache: PRE-BOOT-HIT');\n" .
            "    header('Content-Type: text/html; charset=UTF-8');\n" .
            "    if (isset(\$_SERVER['HTTP_ACCEPT_ENCODING']) && strpos(\$_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && file_exists(\$cache_file . '.gz')) {\n" .
            "        header('Content-Encoding: gzip');\n" .
            "        readfile(\$cache_file . '.gz');\n" .
            "    } else {\n" .
            "        readfile(\$cache_file);\n" .
            "    }\n" .
            "    exit;\n" .
            "}\n";

        return @file_put_contents($dest, $template);
    }

    public function remove_advanced_cache() {
        $dest = WP_CONTENT_DIR . '/advanced-cache.php';
        if (file_exists($dest)) {
            @unlink($dest);
        }
    }

    private function record_cache_generation() {
        $count = (int)get_transient('smau_cache_generations') + 1;
        set_transient('smau_cache_generations', $count, DAY_IN_SECONDS);
    }

    /**
     * Get disk cache statistics (total files, size in MB)
     */
    public function get_stats() {
        if (!is_dir($this->cache_dir)) {
            return ['files' => 0, 'size_mb' => 0.0, 'generations' => 0];
        }

        $total_size = 0;
        $file_count = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->cache_dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['html', 'gz'])) {
                $total_size += $file->getSize();
                $file_count++;
            }
        }

        return [
            'files'       => $file_count,
            'size_mb'     => round($total_size / (1024 * 1024), 2),
            'generations' => (int)get_transient('smau_cache_generations')
        ];
    }
}
