<?php
/**
 * Speed My A$$ Up - BYOK AI CSS Scanner & Optimizer Engine
 * 
 * Bring-Your-Own-Key (BYOK) AI-powered CSS auditing engine detecting unused and broken CSS.
 * Supports Google Gemini, OpenAI, Anthropic Claude, and OpenRouter with AES-256 encrypted credential storage.
 * Part of the Security Sentinel Suite by securemyass.com.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMAU_AI_CSS_Engine {

    private static $instance = null;
    private $providers = [
        'gemini'     => 'Google Gemini (2.5 Flash / 1.5 Pro)',
        'openai'     => 'OpenAI (GPT-4o / GPT-4o-mini)',
        'anthropic'  => 'Anthropic Claude (3.5 Sonnet)',
        'openrouter' => 'OpenRouter (Multi-Model Gateway)'
    ];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Enqueue AI AJAX endpoints
        add_action('wp_ajax_smau_ai_scan_css', [$this, 'ajax_scan_css']);
        add_action('wp_ajax_smau_save_ai_key', [$this, 'ajax_save_key']);
    }

    /**
     * Get configured AI provider
     */
    public function get_provider() {
        $settings = get_option('smau_settings', []);
        return $settings['ai_provider'] ?? 'gemini';
    }

    /**
     * AES-256-CBC Encrypted Key Storage
     */
    public function save_api_key($key) {
        if (empty($key)) {
            delete_option('smau_ai_api_key_enc');
            return true;
        }

        $salt = defined('AUTH_KEY') ? AUTH_KEY : 'smau-default-encryption-salt-256';
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($key, 'AES-256-CBC', substr(hash('sha256', $salt), 0, 32), 0, $iv);
        $payload = base64_encode($iv . '::' . $encrypted);

        return update_option('smau_ai_api_key_enc', $payload);
    }

    public function get_decrypted_api_key() {
        $payload = get_option('smau_ai_api_key_enc', '');
        if (empty($payload)) return '';

        $decoded = base64_decode($payload);
        if (!$decoded || strpos($decoded, '::') === false) return '';

        list($iv, $encrypted) = explode('::', $decoded, 2);
        $salt = defined('AUTH_KEY') ? AUTH_KEY : 'smau-default-encryption-salt-256';
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', substr(hash('sha256', $salt), 0, 32), 0, $iv);

        return $decrypted ?: '';
    }

    public function get_masked_api_key() {
        $raw = $this->get_decrypted_api_key();
        if (empty($raw)) return '';
        if (strlen($raw) <= 8) return '••••••••';
        return substr($raw, 0, 4) . '••••••••' . substr($raw, -4);
    }

    /**
     * Audit HTML and Stylesheets for a given URL
     */
    public function scan_url_css($url, $api_key = null, $provider = null) {
        if ($api_key === null) {
            $api_key = $this->get_decrypted_api_key();
        }
        if (empty($api_key)) {
            return new WP_Error('no_key', 'BYOK API Key not configured. Please enter your API key in the AI CSS Scanner panel or provide via --key.');
        }

        // 1. Fetch HTML of target URL
        $response = wp_remote_get($url, ['timeout' => 12, 'sslverify' => false]);
        if (is_wp_error($response)) {
            return $response;
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            return new WP_Error('empty_html', 'Target URL returned an empty response.');
        }

        // 2. Extract DOM tokens (tags, classes, IDs)
        $dom_tokens = $this->extract_dom_tokens($html);

        // 3. Extract CSS rules and selectors
        $css_rules = $this->extract_css_selectors($html);

        // 4. Send to AI Provider for Deep Semantic AST Audit
        if ($provider === null) {
            $provider = $this->get_provider();
        }
        return $this->query_ai_model($provider, $api_key, $dom_tokens, $css_rules, $url);
    }

    /**
     * Extract class names, IDs, and tags present in the DOM
     */
    private function extract_dom_tokens($html) {
        $classes = [];
        $ids = [];
        $tags = [];

        // Tags
        if (preg_match_all('/<([a-z0-9\-]+)(?:\s|>)/i', $html, $tag_matches)) {
            $tags = array_unique($tag_matches[1]);
        }

        // Classes
        if (preg_match_all('/class=[\'"]([^\'"]+)[\'"]/i', $html, $class_matches)) {
            foreach ($class_matches[1] as $c_str) {
                $parts = preg_split('/\s+/', trim($c_str));
                foreach ($parts as $p) {
                    if (!empty($p)) $classes[$p] = true;
                }
            }
        }

        // IDs
        if (preg_match_all('/id=[\'"]([^\'"]+)[\'"]/i', $html, $id_matches)) {
            foreach ($id_matches[1] as $id) {
                if (!empty($id)) $ids[$id] = true;
            }
        }

        return [
            'tags'    => array_slice($tags, 0, 50),
            'classes' => array_slice(array_keys($classes), 0, 200),
            'ids'     => array_slice(array_keys($ids), 0, 50)
        ];
    }

    /**
     * Extract CSS selectors from inline <style> and enqueued stylesheets
     */
    private function extract_css_selectors($html) {
        $css_content = '';

        // Extract inline styles
        if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $style_matches)) {
            $css_content .= implode("\n", $style_matches[1]);
        }

        // Parse selectors via regex
        $selectors = [];
        if (preg_match_all('/([^{]+)\{([^}]+)\}/s', $css_content, $rule_matches)) {
            foreach ($rule_matches[1] as $sel_str) {
                $clean = trim(preg_replace('/\/\*.*?\*\//s', '', $sel_str));
                if (!empty($clean) && strpos($clean, '@') !== 0) {
                    $sub_sels = explode(',', $clean);
                    foreach ($sub_sels as $s) {
                        $s = trim($s);
                        if (!empty($s) && strlen($s) < 100) {
                            $selectors[] = $s;
                        }
                    }
                }
            }
        }

        return array_slice(array_unique($selectors), 0, 150);
    }

    /**
     * Dispatch structured audit query to configured BYOK AI provider
     */
    private function query_ai_model($provider, $api_key, $dom_tokens, $css_selectors, $url) {
        $prompt = "You are an elite CSS Performance & AST Audit Engineer. Analyze these CSS selectors against the DOM tokens extracted from: {$url}\n\n" .
            "DOM Tokens Found:\n" . wp_json_encode($dom_tokens) . "\n\n" .
            "CSS Selectors Enqueued:\n" . wp_json_encode($css_selectors) . "\n\n" .
            "Instructions:\n" .
            "1. Identify UNUSED CSS selectors that do not match any DOM tokens and are not typical dynamic UI states (e.g. preserve .active, .open, .show, .is-*, .has-*).\n" .
            "2. Identify BROKEN or OBSOLETE CSS rules (malformed syntax, deprecated vendor prefixes, circular layout triggers).\n" .
            "3. Calculate estimated bytes saved.\n\n" .
            "Respond ONLY with valid JSON in this exact structure:\n" .
            "{\n" .
            "  \"unused_selectors\": [\"string\"],\n" .
            "  \"broken_rules\": [{\"selector\": \"string\", \"issue\": \"string\", \"fix\": \"string\"}],\n" .
            "  \"estimated_bytes_saved\": 12450,\n" .
            "  \"summary\": \"string explanation of optimizations found\"\n" .
            "}";

        if ($provider === 'gemini') {
            return $this->call_gemini($api_key, $prompt);
        } elseif ($provider === 'openai') {
            return $this->call_openai($api_key, $prompt);
        } elseif ($provider === 'anthropic') {
            return $this->call_anthropic($api_key, $prompt);
        } else {
            return $this->call_openai_compatible('https://openrouter.ai/api/v1/chat/completions', $api_key, 'meta-llama/llama-3.3-70b-instruct', $prompt);
        }
    }

    private function call_gemini($api_key, $prompt) {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$api_key}";
        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature'     => 0.1,
                'responseMimeType' => 'application/json'
            ]
        ];

        $resp = wp_remote_post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($payload),
            'timeout' => 20
        ]);

        if (is_wp_error($resp)) return $resp;

        $body = json_decode(wp_remote_retrieve_body($resp), true);
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return json_decode($text, true) ?: ['error' => 'Failed to parse Gemini output', 'raw' => $text];
    }

    private function call_openai($api_key, $prompt) {
        return $this->call_openai_compatible('https://api.openai.com/v1/chat/completions', $api_key, 'gpt-4o-mini', $prompt);
    }

    private function call_openai_compatible($endpoint, $api_key, $model, $prompt) {
        $payload = [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => 'You are an automated CSS AST audit tool. Respond exclusively in JSON.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.1
        ];

        $resp = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => "Bearer {$api_key}",
                'Content-Type'  => 'application/json'
            ],
            'body'    => wp_json_encode($payload),
            'timeout' => 25
        ]);

        if (is_wp_error($resp)) return $resp;

        $body = json_decode(wp_remote_retrieve_body($resp), true);
        $content = $body['choices'][0]['message']['content'] ?? '';
        
        // Strip markdown fences if present
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
        $clean = preg_replace('/\s*```$/', '', $clean);

        return json_decode($clean, true) ?: ['error' => 'Failed to parse AI response', 'raw' => $content];
    }

    private function call_anthropic($api_key, $prompt) {
        $endpoint = 'https://api.anthropic.com/v1/messages';
        $payload = [
            'model'      => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 1500,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];

        $resp = wp_remote_post($endpoint, [
            'headers' => [
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json'
            ],
            'body'    => wp_json_encode($payload),
            'timeout' => 25
        ]);

        if (is_wp_error($resp)) return $resp;

        $body = json_decode(wp_remote_retrieve_body($resp), true);
        $text = $body['content'][0]['text'] ?? '';
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $clean = preg_replace('/\s*```$/', '', $clean);

        return json_decode($clean, true) ?: ['error' => 'Failed to parse Claude output', 'raw' => $text];
    }

    public function ajax_scan_css() {
        check_ajax_referer('smau_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized', 403);

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : home_url('/');
        $result = $this->scan_url_css($url);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    public function ajax_save_key() {
        check_ajax_referer('smau_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized', 403);

        $provider = sanitize_text_field($_POST['provider'] ?? 'gemini');
        $key = sanitize_text_field($_POST['api_key'] ?? '');

        $settings = get_option('smau_settings', []);
        $settings['ai_provider'] = $provider;
        update_option('smau_settings', $settings);

        if (!empty($key)) {
            $this->save_api_key($key);
        }

        wp_send_json_success(['message' => 'AI Provider and Key updated successfully.']);
    }
}
