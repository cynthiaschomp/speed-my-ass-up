<?php
/**
 * Speed My A$$ Up - WP-CLI Suite
 * 
 * Complete command-line management for headless operations, benchmarks, and CI/CD pipelines.
 * Part of the Security Sentinel Suite by securemyass.com.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_CLI')) {
    return;
}

class SMAU_CLI_Command extends WP_CLI_Command {

    /**
     * View real-time status and telemetry of Speed My A$$ Up.
     * 
     * ## EXAMPLES
     * 
     *     wp smau status
     */
    public function status($args, $assoc_args) {
        $settings = get_option('smau_settings', []);
        $stats = SMAU_Cache_Engine::instance()->get_stats();
        $bloat = SMAU_Database_Engine::instance()->get_bloat_stats();
        $suite = SMAU_Security_Bridge::instance()->get_suite_status();

        WP_CLI::line(WP_CLI::colorize('%G====================================================%n'));
        WP_CLI::line(WP_CLI::colorize('%Y  Speed My A$$ Up (SMAU) — Performance Sentinel%n'));
        WP_CLI::line(WP_CLI::colorize("%G  Version: " . SMAU_VERSION . " | Mode: Production%n"));
        WP_CLI::line(WP_CLI::colorize('%G====================================================%n'));

        // Cache Subsystem
        WP_CLI::line(WP_CLI::colorize('%B[Cache Subsystem]%n'));
        WP_CLI::line("  Page Caching:         " . (!empty($settings['page_cache_enabled']) ? WP_CLI::colorize('%GENABLED%n') : WP_CLI::colorize('%RDISABLED%n')));
        WP_CLI::line("  Pre-Boot Hook:        " . (file_exists(WP_CONTENT_DIR . '/advanced-cache.php') ? WP_CLI::colorize('%GACTIVE (advanced-cache.php)%n') : WP_CLI::colorize('%YSTANDBY%n')));
        WP_CLI::line("  Cached Files:         " . $stats['files']);
        WP_CLI::line("  Cache Disk Footprint: " . $stats['size_mb'] . " MB");
        WP_CLI::line("  Cache Generations:    " . $stats['generations']);

        // CWV & Asset Optimization
        WP_CLI::line(WP_CLI::colorize("\n%B[Core Web Vitals & Optimization]%n"));
        WP_CLI::line("  LCP Turbo Priority:   " . (!empty($settings['lcp_turbo_enabled']) ? WP_CLI::colorize('%GENFORCED%n') : WP_CLI::colorize('%RDISABLED%n')));
        WP_CLI::line("  CLS Zero-Shift Armor: " . (!empty($settings['cls_armor_enabled']) ? WP_CLI::colorize('%GENFORCED%n') : WP_CLI::colorize('%RDISABLED%n')));
        WP_CLI::line("  Smart Delay JS:       " . (!empty($settings['delay_js_enabled']) ? WP_CLI::colorize('%GACTIVE%n') : WP_CLI::colorize('%RDISABLED%n')));
        WP_CLI::line("  Asynchronous CSS:     " . (!empty($settings['async_css_enabled']) ? WP_CLI::colorize('%GACTIVE%n') : WP_CLI::colorize('%RDISABLED%n')));
        WP_CLI::line("  Video Facades:        " . (!empty($settings['video_facades_enabled']) ? WP_CLI::colorize('%GACTIVE%n') : WP_CLI::colorize('%RDISABLED%n')));
        WP_CLI::line("  Speculation Rules:    " . (!empty($settings['speculation_rules_enabled']) ? WP_CLI::colorize('%GACTIVE%n') : WP_CLI::colorize('%RDISABLED%n')));

        // Database Hygiene
        WP_CLI::line(WP_CLI::colorize("\n%B[Database Hygiene Headroom]%n"));
        WP_CLI::line("  Revisions:            " . $bloat['revisions']);
        WP_CLI::line("  Auto-Drafts & Trash:  " . ($bloat['autodrafts'] + $bloat['trashed_posts']));
        WP_CLI::line("  Spam Comments:        " . $bloat['spam_comments']);
        WP_CLI::line("  Expired Transients:   " . $bloat['expired_transients']);

        // Security Sentinel Suite Status
        WP_CLI::line(WP_CLI::colorize("\n%B[Security Sentinel Suite Interop]%n"));
        foreach ($suite as $slug => $info) {
            $state = $info['active'] ? WP_CLI::colorize("%G[CONNECTED v{$info['version']}]%n") : WP_CLI::colorize('%Y[STANDALONE]%n');
            WP_CLI::line(sprintf("  %-16s %s - %s", $slug, $state, $info['role']));
        }
    }

    /**
     * Purge the static HTML cache completely or selectively.
     * 
     * ## OPTIONS
     * 
     * [--post=<id>]
     * : Purge cache for a specific post ID.
     * 
     * [--url=<url>]
     * : Purge cache for a specific URL.
     * 
     * ## EXAMPLES
     * 
     *     wp smau cache_clear
     *     wp smau cache_clear --post=42
     */
    public function cache_clear($args, $assoc_args) {
        $engine = SMAU_Cache_Engine::instance();

        if (!empty($assoc_args['post'])) {
            $post_id = (int)$assoc_args['post'];
            $engine->invalidate_post_cache($post_id);
            WP_CLI::success("Cache invalidated for Post #{$post_id}.");
            return;
        }

        if (!empty($assoc_args['url'])) {
            $url = esc_url_raw($assoc_args['url']);
            $engine->purge_url_cache($url);
            WP_CLI::success("Cache invalidated for URL: {$url}");
            return;
        }

        $engine->purge_all_cache();
        WP_CLI::success("Entire static HTML cache purged successfully.");
    }

    /**
     * Preload static page cache via XML sitemap crawler.
     * 
     * ## OPTIONS
     * 
     * [--limit=<count>]
     * : Maximum number of URLs to crawl and cache (default: 50).
     * 
     * ## EXAMPLES
     * 
     *     wp smau preload --limit=20
     */
    public function preload($args, $assoc_args) {
        $limit = isset($assoc_args['limit']) ? (int)$assoc_args['limit'] : 50;
        WP_CLI::line("Crawling sitemaps and pre-warming cache (Limit: {$limit})...");

        $count = SMAU_Preload_Engine::instance()->crawl_sitemap_urls($limit);
        WP_CLI::success("Successfully preloaded {$count} pages into static disk cache.");
    }

    /**
     * Run database maintenance and table optimization.
     * 
     * ## EXAMPLES
     * 
     *     wp smau optimize_db
     */
    public function optimize_db($args, $assoc_args) {
        WP_CLI::line("Executing chunked database hygiene and table optimization...");
        $res = SMAU_Database_Engine::instance()->clean_database();

        WP_CLI::line("  Revisions pruned:    " . ($res['revisions'] ?? 0));
        WP_CLI::line("  Drafts & Trash:      " . ($res['drafts_trash'] ?? 0));
        WP_CLI::line("  Spam Comments:       " . ($res['spam_comments'] ?? 0));
        WP_CLI::line("  Expired Transients:  " . ($res['transients'] ?? 0));
        WP_CLI::line("  Tables Optimized:    " . ($res['tables_optimized'] ?? 0));
        WP_CLI::success("Database hygiene complete.");
    }

    /**
     * Benchmark response latency (TTFB) of a given URL.
     * 
     * ## OPTIONS
     * 
     * <url>
     * : The target URL to benchmark.
     * 
     * [--iterations=<num>]
     * : Number of iterations to measure (default: 5).
     * 
     * ## EXAMPLES
     * 
     *     wp smau benchmark https://example.com/
     */
    public function benchmark($args, $assoc_args) {
        $url = esc_url_raw($args[0] ?? home_url('/'));
        $iterations = isset($assoc_args['iterations']) ? (int)$assoc_args['iterations'] : 5;

        WP_CLI::line("Benchmarking {$url} over {$iterations} sequential requests...");

        $times = [];
        $sizes = [];
        $cache_headers = [];

        for ($i = 1; $i <= $iterations; $i++) {
            $start = microtime(true);
            $response = wp_remote_get($url, ['sslverify' => false, 'timeout' => 10]);
            $duration = (microtime(true) - $start) * 1000;

            if (is_wp_error($response)) {
                WP_CLI::error("Request {$i} failed: " . $response->get_error_message());
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $cache_header = wp_remote_retrieve_header($response, 'x-smau-cache') ?: 'MISS';

            $times[] = $duration;
            $sizes[] = strlen($body);
            $cache_headers[] = $cache_header;

            WP_CLI::line(sprintf("  [%d/%d] Status: %d | Time: %.2f ms | Size: %.1f KB | Cache: %s",
                $i, $iterations, $code, $duration, strlen($body) / 1024, $cache_header
            ));
            usleep(50000);
        }

        $avg_time = array_sum($times) / count($times);
        $min_time = min($times);
        $max_time = max($times);

        WP_CLI::line(WP_CLI::colorize('%G----------------------------------------------------%n'));
        WP_CLI::line(sprintf("  Average TTFB: %s", WP_CLI::colorize(sprintf("%%G%.2f ms%%n", $avg_time))));
        WP_CLI::line(sprintf("  Fastest TTFB: %s", WP_CLI::colorize(sprintf("%%G%.2f ms%%n", $min_time))));
        WP_CLI::line(sprintf("  Slowest TTFB: %.2f ms", $max_time));
        WP_CLI::line(WP_CLI::colorize('%G----------------------------------------------------%n'));
    }

    /**
     * Purge Cloudflare Edge Cache.
     * 
     * ## EXAMPLES
     * 
     *     wp smau edge_purge
     */
    public function edge_purge($args, $assoc_args) {
        WP_CLI::line("Initiating Cloudflare edge cache purge...");
        $res = SMAU_Edge_Engine::instance()->purge_cloudflare_cache();
        if (is_wp_error($res)) {
            WP_CLI::error($res->get_error_message());
        }
        WP_CLI::success("Cloudflare edge cache successfully purged.");
    }

    /**
     * Audit a URL for unused and broken CSS using the configured BYOK AI engine.
     * 
     * ## OPTIONS
     * 
     * [<url>]
     * : The target URL to audit (defaults to home_url()).
     * 
     * [--provider=<provider>]
     * : AI Provider to use: gemini, openai, anthropic, or openrouter.
     * 
     * [--key=<api_key>]
     * : Pass an API key directly or override the stored key.
     * 
     * [--format=<format>]
     * : Output format: summary or json. Default: summary.
     * 
     * ## EXAMPLES
     * 
     *     wp smau ai_scan https://example.com/
     *     wp smau ai_scan https://example.com/ --provider=gemini
     *     wp smau ai_scan https://example.com/ --format=json
     */
    public function ai_scan($args, $assoc_args) {
        $url = esc_url_raw($args[0] ?? home_url('/'));
        $provider = isset($assoc_args['provider']) ? sanitize_text_field($assoc_args['provider']) : null;
        $key = isset($assoc_args['key']) ? sanitize_text_field($assoc_args['key']) : null;
        $format = isset($assoc_args['format']) ? sanitize_text_field($assoc_args['format']) : 'summary';

        $active_provider = $provider ?: SMAU_AI_CSS_Engine::instance()->get_provider();
        WP_CLI::line("Initiating BYOK AI CSS audit on: {$url} (Provider: {$active_provider})...");

        $result = SMAU_AI_CSS_Engine::instance()->scan_url_css($url, $key, $provider);

        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        }

        if (isset($result['error'])) {
            WP_CLI::error($result['error'] . (!empty($result['raw']) ? ' — ' . substr($result['raw'], 0, 200) : ''));
        }

        if ($format === 'json') {
            WP_CLI::line(wp_json_encode($result, JSON_PRETTY_PRINT));
            return;
        }

        $unused = $result['unused_selectors'] ?? [];
        $broken = $result['broken_rules'] ?? [];
        $saved = $result['estimated_bytes_saved'] ?? 0;
        $summary = $result['summary'] ?? 'Audit completed successfully.';

        WP_CLI::line(WP_CLI::colorize('%G====================================================%n'));
        WP_CLI::line(WP_CLI::colorize('%Y  Speed My A$$ Up — BYOK AI CSS Audit Report%n'));
        WP_CLI::line(WP_CLI::colorize('%G====================================================%n'));
        WP_CLI::line("Target URL:            {$url}");
        WP_CLI::line("Provider:              {$active_provider}");
        WP_CLI::line("Estimated Bytes Saved: " . WP_CLI::colorize("%G" . number_format($saved) . " bytes (" . round($saved / 1024, 1) . " KB)%n"));
        WP_CLI::line("Unused Selectors:      " . WP_CLI::colorize("%Y" . count($unused) . " selectors%n"));
        WP_CLI::line("Broken/Malformed:      " . (empty($broken) ? WP_CLI::colorize('%G0 issues%n') : WP_CLI::colorize("%R" . count($broken) . " issues%n")));
        WP_CLI::line("\nSummary:\n" . $summary . "\n");

        if (!empty($unused)) {
            WP_CLI::line(WP_CLI::colorize('%Y--- Top Unused Selectors (up to 15) ---%n'));
            foreach (array_slice($unused, 0, 15) as $sel) {
                WP_CLI::line("  - " . WP_CLI::colorize("%R{$sel}%n"));
            }
            if (count($unused) > 15) {
                WP_CLI::line("  ... and " . (count($unused) - 15) . " more selectors.");
            }
        }

        if (!empty($broken)) {
            WP_CLI::line(WP_CLI::colorize("\n%R--- Broken or Obsolete Rules ---%n"));
            foreach ($broken as $item) {
                WP_CLI::line(sprintf("  Selector: %s\n    Issue:  %s\n    Fix:    %s\n",
                    WP_CLI::colorize('%R' . ($item['selector'] ?? 'unknown') . '%n'),
                    $item['issue'] ?? 'unknown issue',
                    WP_CLI::colorize('%G' . ($item['fix'] ?? 'remove') . '%n')
                ));
            }
        }

        WP_CLI::line(WP_CLI::colorize('%G----------------------------------------------------%n'));
        WP_CLI::success("AI CSS Audit complete.");
    }
}

WP_CLI::add_command('smau', 'SMAU_CLI_Command');
