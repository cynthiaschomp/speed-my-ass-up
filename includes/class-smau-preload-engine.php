<?php
/**
 * Speed My A$$ Up - Speculative Preload & Sitemap Crawler Engine
 * 
 * Generates W3C Speculation Rules API payloads, hover-intent listeners, and background sitemap cache preloading.
 * Part of the Security Sentinel Suite by securemyass.com.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMAU_Preload_Engine {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $settings = get_option('smau_settings', []);

        if (!empty($settings['speculation_rules_enabled'])) {
            add_action('wp_head', [$this, 'inject_speculation_rules'], 2);
        }

        if (!empty($settings['hover_preload_enabled'])) {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_hover_preload']);
        }

        if (!empty($settings['dns_prefetch_enabled'])) {
            add_action('wp_head', [$this, 'inject_resource_hints'], 1);
        }

        // Background preload cron
        add_action('smau_sitemap_preload_cron', [$this, 'run_sitemap_preload_batch']);
    }

    /**
     * Inject W3C Speculation Rules API for instant pre-rendering
     */
    public function inject_speculation_rules() {
        if (is_admin() || is_user_logged_in()) {
            return;
        }

        $home_url = home_url();
        $rules = [
            'prerender' => [
                [
                    'source'    => 'document',
                    'where'     => [
                        'and' => [
                            ['href_matches' => '/*'],
                            ['not' => ['href_matches' => '/wp-admin/*']],
                            ['not' => ['href_matches' => '/wp-login.php*']],
                            ['not' => ['href_matches' => '/cart/*']],
                            ['not' => ['href_matches' => '/checkout/*']],
                            ['not' => ['href_matches' => '/*\\?*']]
                        ]
                    ],
                    'eagerness' => 'moderate'
                ]
            ]
        ];

        $json = wp_json_encode($rules, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n<!-- Speed My A\$\$ Up (SMAU) - W3C Speculation Rules API Pre-Render Engine -->\n";
        echo "<script type=\"speculationrules\">\n" . $json . "\n</script>\n";
    }

    /**
     * Enqueue lightweight hover intent preloader
     */
    public function enqueue_hover_preload() {
        if (is_admin() || is_user_logged_in()) {
            return;
        }

        wp_enqueue_script(
            'smau-hover-preload',
            SMAU_PLUGIN_URL . 'assets/js/smau-hover-preload.js',
            [],
            SMAU_VERSION,
            true
        );
    }

    /**
     * Inject DNS prefetch and preconnect hints
     */
    public function inject_resource_hints() {
        $domains = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://secure.gravatar.com'
        ];

        $custom_domains = get_option('smau_dns_prefetch_domains', []);
        if (is_array($custom_domains)) {
            $domains = array_unique(array_merge($domains, $custom_domains));
        }

        echo "\n<!-- Speed My A\$\$ Up (SMAU) Resource Hints -->\n";
        foreach ($domains as $domain) {
            $domain = esc_url($domain);
            echo "<link rel=\"dns-prefetch\" href=\"{$domain}\">\n";
            echo "<link rel=\"preconnect\" href=\"{$domain}\" crossorigin>\n";
        }
    }

    /**
     * Discover sitemap URLs and crawl them to build disk cache
     */
    public function crawl_sitemap_urls($limit = 50) {
        $sitemap_candidates = [
            home_url('/wp-sitemap.xml'),
            home_url('/sitemap_index.xml'),
            home_url('/sitemap.xml')
        ];

        $page_urls = [home_url('/')];

        foreach ($sitemap_candidates as $sitemap_url) {
            $response = wp_remote_get($sitemap_url, ['timeout' => 8, 'sslverify' => false]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                if (preg_match_all('/<loc>(https?:\/\/[^<]+)<\/loc>/i', $body, $matches)) {
                    $found_urls = array_unique($matches[1]);
                    foreach ($found_urls as $u) {
                        if (str_ends_with($u, '.xml')) {
                            // Sub-sitemap: fetch child URLs
                            $sub_resp = wp_remote_get($u, ['timeout' => 5, 'sslverify' => false]);
                            if (!is_wp_error($sub_resp) && wp_remote_retrieve_response_code($sub_resp) === 200) {
                                $sub_body = wp_remote_retrieve_body($sub_resp);
                                if (preg_match_all('/<loc>(https?:\/\/[^<]+)<\/loc>/i', $sub_body, $sub_matches)) {
                                    foreach ($sub_matches[1] as $sub_u) {
                                        if (!str_ends_with($sub_u, '.xml')) {
                                            $page_urls[] = $sub_u;
                                        }
                                    }
                                }
                            }
                        } else {
                            $page_urls[] = $u;
                        }
                    }
                    break;
                }
            }
        }

        // Add published posts and pages as supplemental sources
        $recent_posts = get_posts([
            'numberposts' => $limit,
            'post_status' => 'publish',
            'post_type'   => ['post', 'page']
        ]);
        foreach ($recent_posts as $post) {
            $page_urls[] = get_permalink($post->ID);
        }

        $page_urls = array_values(array_unique(array_filter($page_urls)));

        $crawled = 0;
        foreach (array_slice($page_urls, 0, $limit) as $target_url) {
            $response = wp_remote_get($target_url, [
                'timeout'    => 5,
                'sslverify'  => false,
                'user-agent' => 'SMAU-Sitemap-Preloader/' . SMAU_VERSION
            ]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $crawled++;
            }
            usleep(25000);
        }

        set_transient('smau_last_preload_count', $crawled, DAY_IN_SECONDS);
        return $crawled;
    }

    public function run_sitemap_preload_batch() {
        return $this->crawl_sitemap_urls(25);
    }
}
