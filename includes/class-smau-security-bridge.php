<?php
/**
 * Speed My A$$ Up - Security Sentinel Suite Bridge
 * 
 * Interoperability engine connecting SMAU with Guard My Ass, Back My A$$ Up, and Secure My Ass.
 * Part of the Security Sentinel Suite by securemyass.com.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMAU_Security_Bridge {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('bma_vault_exclude_paths', [$this, 'exclude_cache_from_backups']);
        add_action('init', [$this, 'enforce_cache_execution_barriers'], 1);
    }

    /**
     * Prevent cache directory from bloating BMA backup archives
     */
    public function exclude_cache_from_backups($excludes) {
        if (!is_array($excludes)) $excludes = [];
        $excludes[] = 'wp-content/cache/smau';
        return $excludes;
    }

    /**
     * Enforce execution barriers in the cache directory (Triple Barrier: .htaccess, .user.ini, index.php)
     */
    public function enforce_cache_execution_barriers() {
        $cache_dir = WP_CONTENT_DIR . '/cache/smau';
        if (!is_dir($cache_dir)) {
            return;
        }

        // 1. index.php
        $index_file = $cache_dir . '/index.php';
        if (!file_exists($index_file)) {
            @file_put_contents($index_file, "<?php\n// Security Sentinel Execution Barrier\nhttp_response_code(403);\nexit('Access Denied');\n");
        }

        // 2. .htaccess (Deny PHP execution in cache)
        $htaccess = $cache_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            $rules = "# Security Sentinel Suite: Neutralize PHP execution in cache\n" .
                     "<Files *.php>\n" .
                     "Order Deny,Allow\n" .
                     "Deny from all\n" .
                     "</Files>\n" .
                     "<IfModule mod_php7.c>\nphp_flag engine off\n</IfModule>\n" .
                     "<IfModule mod_php8.c>\nphp_flag engine off\n</IfModule>\n";
            @file_put_contents($htaccess, $rules);
        }

        // 3. .user.ini (OPSEC hardening)
        $user_ini = $cache_dir . '/.user.ini';
        if (!file_exists($user_ini)) {
            @file_put_contents($user_ini, "; Security Sentinel Barrier\nengine = Off\n");
        }
    }

    /**
     * Retrieve status of all suite companions
     */
    public function get_suite_status() {
        return [
            'guard_my_ass' => [
                'active'  => class_exists('GMA_Firewall_Engine'),
                'version' => defined('GMA_FIREWALL_VERSION') ? GMA_FIREWALL_VERSION : null,
                'role'    => 'Pre-Boot Ingress Shield & Entropy WAF'
            ],
            'back_my_ass_up' => [
                'active'  => class_exists('SMA_Vault_Engine') || defined('BMA_BACKUP_VERSION'),
                'version' => defined('BMA_BACKUP_VERSION') ? BMA_BACKUP_VERSION : null,
                'role'    => 'Cryptographic Air-Gap Vault Backups'
            ],
            'secure_my_ass' => [
                'active'  => defined('SECURE_MY_ASS_VERSION') || defined('SMA_VERSION'),
                'version' => defined('SECURE_MY_ASS_VERSION') ? SECURE_MY_ASS_VERSION : (defined('SMA_VERSION') ? SMA_VERSION : null),
                'role'    => 'AST Zero-Day Quarantine & SOC Platform'
            ],
            'speed_my_ass_up' => [
                'active'  => true,
                'version' => SMAU_VERSION,
                'role'    => 'Pre-Boot Caching & Core Web Vitals Hyper-Engine'
            ],
            'compress_my_ass' => [
                'active'  => defined('CMA_VERSION'),
                'version' => defined('CMA_VERSION') ? CMA_VERSION : null,
                'role'    => 'Zero-SaaS Local Image Compression & WebP/AVIF Hyper-Optimizer'
            ]
        ];
    }
}
