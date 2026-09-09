<?php
/**
 * Speed My A$$ Up - Asset Optimizer & Script Scheduler
 * 
 * Local pure-PHP CSS/HTML minifier, asynchronous stylesheet loader, and Smart Delay JS scheduler.
 * Part of the Security Sentinel Suite by securemyass.com.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMAU_Optimizer_Engine {

    private static $instance = null;
    private $safelist = [
        'jquery.js',
        'jquery.min.js',
        'jquery-core',
        'divi-custom-script',
        'et-builder',
        'wc-cart-fragments',
        'woocommerce.min.js',
        'stripe.com',
        'turnstile',
        'recaptcha',
        'smau-lazy-scripts',
        'smau-hover-preload'
    ];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Enqueue delay script handler if enabled
        add_action('wp_footer', [$this, 'inject_delay_script_loader'], 9999);
    }

    /**
     * Master HTML optimization pipeline called by Cache Engine buffer
     */
    public function optimize_html($html) {
        $settings = get_option('smau_settings', []);

        // 1. Asynchronous CSS
        if (!empty($settings['async_css_enabled'])) {
            $html = $this->apply_async_css($html);
        }

        // 2. Smart Delay JavaScript
        if (!empty($settings['delay_js_enabled'])) {
            $html = $this->apply_smart_delay_js($html);
        }

        // 3. HTML Minification
        if (!empty($settings['minify_html_enabled'])) {
            $html = $this->minify_html($html);
        }

        return $html;
    }

    /**
     * Convert render-blocking stylesheets into asynchronous preloads with noscript fallback
     */
    public function apply_async_css($html) {
        return preg_replace_callback(
            '/<link\s+([^>]*rel=[\'"]stylesheet[\'"][^>]*)>/i',
            function ($matches) {
                $tag = $matches[0];
                $attrs = $matches[1];

                // Skip if already preloaded or excluded
                if (strpos($attrs, 'data-smau-no-async') !== false || strpos($attrs, 'media=') !== false && strpos($attrs, 'media="print"') !== false) {
                    return $tag;
                }

                // Extract href
                if (!preg_match('/href=[\'"]([^\'"]+)[\'"]/i', $attrs, $href_match)) {
                    return $tag;
                }

                $href = $href_match[1];
                $clean_attrs = preg_replace('/rel=[\'"]stylesheet[\'"]/i', '', $attrs);
                $clean_attrs = trim(preg_replace('/\s+/', ' ', $clean_attrs));

                return "<link rel=\"preload\" as=\"style\" href=\"{$href}\" {$clean_attrs} onload=\"this.onload=null;this.rel='stylesheet'\">\n<noscript>{$tag}</noscript>";
            },
            $html
        );
    }

    /**
     * Transform non-safelisted script tags into delayed execution scripts
     */
    public function apply_smart_delay_js($html) {
        // Exclude admin bar, login forms, or edit screens
        if (is_admin()) {
            return $html;
        }

        return preg_replace_callback(
            '/<script(\s+[^>]*)?>(.*?)<\/script>/is',
            function ($matches) {
                $attrs = $matches[1] ?? '';
                $content = $matches[2] ?? '';
                $full_tag = $matches[0];

                // Skip JSON-LD, speculative rules, application/ld+json, or explicitly ignored scripts
                if (preg_match('/type=[\'"](application\/ld\+json|speculationrules|application\/json)[\'"]/i', $attrs)) {
                    return $full_tag;
                }
                if (strpos($attrs, 'data-smau-no-delay') !== false || strpos($attrs, 'data-cfasync') !== false) {
                    return $full_tag;
                }

                // Check safelist against attributes and inline content
                foreach ($this->safelist as $safe_token) {
                    if (stripos($attrs, $safe_token) !== false || stripos($content, $safe_token) !== false) {
                        return $full_tag;
                    }
                }

                // External script with src
                if (preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $attrs, $src_match)) {
                    $src = $src_match[1];
                    $new_attrs = preg_replace('/src=[\'"][^\'"]+[\'"]/i', '', $attrs);
                    $new_attrs = preg_replace('/type=[\'"][^\'"]+[\'"]/i', '', $new_attrs);
                    return "<script type=\"smau-delayed-script\" data-smau-src=\"{$src}\" {$new_attrs}></script>";
                }

                // Inline script with non-empty content
                if (trim($content) !== '') {
                    $new_attrs = preg_replace('/type=[\'"][^\'"]+[\'"]/i', '', $attrs);
                    return "<script type=\"smau-delayed-script\" {$new_attrs}>{$content}</script>";
                }

                return $full_tag;
            },
            $html
        );
    }

    /**
     * Inject lightweight interaction-based script execution scheduler
     */
    public function inject_delay_script_loader() {
        $settings = get_option('smau_settings', []);
        if (empty($settings['delay_js_enabled'])) {
            return;
        }

        echo "\n<!-- Speed My A\$\$ Up (SMAU) Smart Script Scheduler -->\n";
        echo "<script>\n" .
            "(()=>{const e=['keydown','mousedown','mousemove','touchstart','touchmove','wheel','scroll']," .
            "t=()=>{e.forEach(n=>window.removeEventListener(n,t,{passive:!0})),n()}," .
            "n=()=>{const e=document.querySelectorAll('script[type=\"smau-delayed-script\"]');" .
            "e.forEach(e=>{const t=document.createElement('script');" .
            "[...e.attributes].forEach(n=>{'type'!==n.nodeName&&'data-smau-src'!==n.nodeName&&t.setAttribute(n.nodeName,n.nodeValue)});" .
            "e.hasAttribute('data-smau-src')?t.src=e.getAttribute('data-smau-src'):t.text=e.text;" .
            "e.parentNode.replaceChild(t,e)})};" .
            "e.forEach(n=>window.addEventListener(n,t,{passive:!0}));" .
            "setTimeout(t,4000);})();\n" .
            "</script>\n";
    }

    /**
     * Strip whitespace and comments while protecting critical tags
     */
    public function minify_html($html) {
        $protected_blocks = [];
        $placeholder_idx = 0;

        // Protect <textarea>, <pre>, <code>, and <script> contents
        $html = preg_replace_callback(
            '/<(textarea|pre|code)[^>]*>.*?<\/\1>|<script[^>]*>.*?<\/script>/is',
            function ($matches) use (&$protected_blocks, &$placeholder_idx) {
                $placeholder = "###SMAU_PROTECTED_{$placeholder_idx}###";
                $protected_blocks[$placeholder] = $matches[0];
                $placeholder_idx++;
                return $placeholder;
            },
            $html
        );

        // Remove HTML comments (except IE conditionals and SMAU signatures)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);

        // Collapse multiple whitespace
        $html = preg_replace('/\s{2,}/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);

        // Restore protected blocks
        foreach ($protected_blocks as $placeholder => $original) {
            $html = str_replace($placeholder, $original, $html);
        }

        return trim($html);
    }
}
