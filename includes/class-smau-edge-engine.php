<?php
/**
 * Speed My A$$ Up - Edge Reverse-Proxy & CDN Engine
 * 
 * Cloudflare Edge cache purge, Traefik edge reverse-proxy configuration, and static CNAME asset rewriter.
 * Part of the Security Sentinel Suite by securemyass.com.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMAU_Edge_Engine {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $settings = get_option('smau_settings', []);
        if (!empty($settings['cdn_cname_enabled']) && !empty($settings['cdn_cname_domain'])) {
            add_filter('wp_get_attachment_url', [$this, 'rewrite_cdn_url']);
            add_filter('style_loader_src', [$this, 'rewrite_cdn_url']);
            add_filter('script_loader_src', [$this, 'rewrite_cdn_url']);
        }
    }

    /**
     * Rewrite static asset URLs to CDN CNAME
     */
    public function rewrite_cdn_url($url) {
        if (is_admin()) {
            return $url;
        }

        $settings = get_option('smau_settings', []);
        $cdn_domain = rtrim($settings['cdn_cname_domain'] ?? '', '/');
        if (empty($cdn_domain)) {
            return $url;
        }

        $site_url = home_url();
        if (strpos($url, $site_url) === 0) {
            // Check if it is a static asset path
            if (preg_match('/\/(wp-content|wp-includes)\/.*\.(css|js|jpe?g|png|gif|svg|webp|avif|woff2?|ttf|eot)(\?.*)?$/i', $url)) {
                return str_replace($site_url, $cdn_domain, $url);
            }
        }

        return $url;
    }

    /**
     * Purge Cloudflare Zone Cache
     */
    public function purge_cloudflare_cache($urls = []) {
        $settings = get_option('smau_settings', []);
        $zone_id = $settings['cloudflare_zone_id'] ?? '';
        $api_token = $settings['cloudflare_api_token'] ?? '';

        if (empty($zone_id) || empty($api_token)) {
            return new WP_Error('missing_creds', 'Cloudflare Zone ID or API Token not configured');
        }

        $endpoint = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/purge_cache";
        $payload = empty($urls) ? ['purge_everything' => true] : ['files' => (array)$urls];

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => "Bearer {$api_token}",
                'Content-Type'  => 'application/json'
            ],
            'body'    => wp_json_encode($payload),
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 && !empty($body['success'])) {
            return true;
        }

        $err_msg = $body['errors'][0]['message'] ?? 'Unknown Cloudflare API Error';
        return new WP_Error('cf_error', $err_msg);
    }

    /**
     * Generate Traefik Dynamic Edge Router Configuration
     */
    public function generate_traefik_config() {
        $host = parse_url(home_url(), PHP_URL_HOST);
        return <<<YAML
# Speed My A$$ Up (SMAU) - Traefik v3 Dynamic Cache Router
http:
  routers:
    smau-static-cache:
      rule: "Host(`{$host}`) && PathPrefix(`/wp-content/cache/smau/`)"
      service: "smau-file-service"
      priority: 25000
      tls:
        certResolver: "letsencrypt"
  services:
    smau-file-service:
      loadBalancer:
        servers:
          - url: "http://securemyass-wp:80"
YAML;
    }
}
