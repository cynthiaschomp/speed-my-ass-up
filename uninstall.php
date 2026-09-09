<?php
/**
 * Speed My A$$ Up - Clean Uninstaller
 * 
 * Purges static disk caches, removes drop-ins, and deletes options upon uninstallation.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 1. Remove advanced-cache.php drop-in
$dropin = WP_CONTENT_DIR . '/advanced-cache.php';
if (file_exists($dropin)) {
    @unlink($dropin);
}

// 2. Recursively delete disk cache directory
$cache_dir = WP_CONTENT_DIR . '/cache/smau';
if (is_dir($cache_dir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($cache_dir);
}

// 3. Delete options and transients
delete_option('smau_settings');
delete_option('smau_dns_prefetch_domains');
delete_transient('smau_last_cache_purge');
delete_transient('smau_cache_generations');
delete_transient('smau_last_preload_count');
delete_transient('smau_last_db_clean');

// 4. Clear scheduled crons
wp_clear_scheduled_hook('smau_db_maintenance_cron');
wp_clear_scheduled_hook('smau_sitemap_preload_cron');
