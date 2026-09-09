<?php
/**
 * Speed My A$$ Up - Database Hygiene & Table Optimizer Engine
 * 
 * Chunked table optimization, post revision pruning, transient cleanup, and Sentinel Suite backup synergy.
 * Part of the Security Sentinel Suite by securemyass.com.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMAU_Database_Engine {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('smau_db_maintenance_cron', [$this, 'run_automated_cleanup']);
        // Pre-backup hook from Back My A$$ Up
        add_action('bma_pre_backup_cleanup', [$this, 'run_pre_backup_hygiene']);
    }

    /**
     * Run full database cleanup
     */
    public function clean_database($options = []) {
        global $wpdb;

        $defaults = [
            'revisions'  => true,
            'autodrafts' => true,
            'trash'      => true,
            'spam'       => true,
            'transients' => true,
            'optimize'   => true
        ];
        $opts = wp_parse_args($options, $defaults);
        $results = [];

        // 1. Post Revisions
        if (!empty($opts['revisions'])) {
            $query = "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'";
            $deleted = $wpdb->query($query);
            $results['revisions'] = (int)$deleted;
        }

        // 2. Auto-drafts & Trashed Posts
        if (!empty($opts['autodrafts']) || !empty($opts['trash'])) {
            $statuses = [];
            if (!empty($opts['autodrafts'])) $statuses[] = "'auto-draft'";
            if (!empty($opts['trash'])) $statuses[] = "'trash'";
            
            $status_sql = implode(',', $statuses);
            $deleted = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status IN ({$status_sql})");
            $results['drafts_trash'] = (int)$deleted;
        }

        // 3. Spam & Trash Comments
        if (!empty($opts['spam'])) {
            $deleted = $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved IN ('spam', 'trash')");
            $results['spam_comments'] = (int)$deleted;
        }

        // 4. Expired & Orphaned Transients
        if (!empty($opts['transients'])) {
            $now = time();
            $deleted_timeout = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                    '_transient_timeout_%',
                    $now
                )
            );
            $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' AND option_name NOT IN (
                    SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%'
                )"
            );
            $results['transients'] = (int)$deleted_timeout;
        }

        // 5. Optimize Tables
        if (!empty($opts['optimize'])) {
            $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
            $optimized_count = 0;
            foreach ($tables as $table) {
                $wpdb->query("OPTIMIZE TABLE `{$table}`");
                $optimized_count++;
            }
            $results['tables_optimized'] = $optimized_count;
        }

        set_transient('smau_last_db_clean', time(), DAY_IN_SECONDS * 30);
        return $results;
    }

    public function run_automated_cleanup() {
        return $this->clean_database();
    }

    public function run_pre_backup_hygiene() {
        // Fast cleanup of transients and revisions before BMA archives database
        return $this->clean_database([
            'revisions'  => true,
            'autodrafts' => true,
            'trash'      => false,
            'spam'       => true,
            'transients' => true,
            'optimize'   => false
        ]);
    }

    /**
     * Gather database bloat telemetry
     */
    public function get_bloat_stats() {
        global $wpdb;

        $revisions = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
        $autodrafts = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
        $trashed_posts = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'");
        $spam_comments = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'");
        $expired_transients = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                '_transient_timeout_%',
                time()
            )
        );

        return [
            'revisions'          => $revisions,
            'autodrafts'         => $autodrafts,
            'trashed_posts'      => $trashed_posts,
            'spam_comments'      => $spam_comments,
            'expired_transients' => $expired_transients,
            'total_bloat_items'  => $revisions + $autodrafts + $trashed_posts + $spam_comments + $expired_transients
        ];
    }
}
