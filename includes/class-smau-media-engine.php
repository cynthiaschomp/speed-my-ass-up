<?php
/**
 * Speed My A$$ Up - Core Web Vitals & Media Engine
 * 
 * LCP Turbo priority elevation, CLS Zero-Shift dimension injection, and YouTube/Vimeo video facades.
 * Part of the Security Sentinel Suite by securemyass.com.
 *
 * @package Speed_My_Ass_Up
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMAU_Media_Engine {

    private static $instance = null;
    private $lcp_detected_image = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Facade activation script
        add_action('wp_footer', [$this, 'inject_facade_script'], 9998);
    }

    /**
     * Master Media & CWV optimization pipeline
     */
    public function optimize_html($html) {
        $settings = get_option('smau_settings', []);

        // 1. YouTube & Vimeo Facades
        if (!empty($settings['video_facades_enabled'])) {
            $html = $this->replace_video_iframes_with_facades($html);
        }

        // 2. LCP Turbo Priority & CLS Zero-Shift Armor
        if (!empty($settings['lcp_turbo_enabled']) || !empty($settings['cls_armor_enabled'])) {
            $html = $this->process_images_for_cwv($html, $settings);
        }

        return $html;
    }

    /**
     * Optimize images for LCP priority and CLS stability
     */
    private function process_images_for_cwv($html, $settings) {
        $lcp_enabled = !empty($settings['lcp_turbo_enabled']);
        $cls_enabled = !empty($settings['cls_armor_enabled']);
        $lazy_enabled = !empty($settings['lazy_load_enabled']);

        $image_count = 0;
        $lcp_preload_tag = '';

        $html = preg_replace_callback(
            '/<img\s+([^>]+)>/i',
            function ($matches) use (&$image_count, &$lcp_preload_tag, $lcp_enabled, $cls_enabled, $lazy_enabled) {
                $attrs = $matches[1];
                $image_count++;

                // Parse src
                if (!preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $attrs, $src_match)) {
                    return $matches[0];
                }
                $src = $src_match[1];

                // Check dimensions for CLS Armor
                $has_width = preg_match('/width=[\'"]?\d+/i', $attrs);
                $has_height = preg_match('/height=[\'"]?\d+/i', $attrs);

                if ($cls_enabled && (!$has_width || !has_height)) {
                    $dims = $this->get_image_dimensions($src);
                    if ($dims) {
                        list($w, $h) = $dims;
                        if (!$has_width) {
                            $attrs .= " width=\"{$w}\"";
                        }
                        if (!$has_height) {
                            $attrs .= " height=\"{$h}\"";
                        }
                        // Add inline aspect ratio
                        $attrs .= " style=\"aspect-ratio: {$w} / {$h};\"";
                    }
                }

                // First image is the prime LCP candidate
                if ($image_count === 1 && $lcp_enabled) {
                    // Elevate priority, remove lazy load
                    $attrs = preg_replace('/loading=[\'"]lazy[\'"]/i', '', $attrs);
                    $attrs = preg_replace('/fetchpriority=[\'"][^\'"]*[\'"]/i', '', $attrs);
                    $attrs .= ' fetchpriority="high" decoding="async"';

                    $lcp_preload_tag = "<link rel=\"preload\" as=\"image\" href=\"{$src}\" fetchpriority=\"high\">";
                } elseif ($lazy_enabled && strpos($attrs, 'loading=') === false && strpos($attrs, 'fetchpriority=') === false) {
                    $attrs .= ' loading="lazy" decoding="async"';
                }

                return "<img {$attrs}>";
            },
            $html
        );

        // Inject LCP preload into <head> if discovered
        if (!empty($lcp_preload_tag)) {
            $html = preg_replace('/<\/head>/i', "{$lcp_preload_tag}\n</head>", $html, 1);
        }

        return $html;
    }

    /**
     * Resolve dimensions of image from local webroot or attachment ID
     */
    private function get_image_dimensions($url) {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        if (empty($path)) return null;

        // Try mapping to local filesystem
        $site_path = parse_url(home_url(), PHP_URL_PATH);
        if ($site_path && strpos($path, $site_path) === 0) {
            $rel_path = substr($path, strlen($site_path));
        } else {
            $rel_path = $path;
        }

        $local_file = ABSPATH . ltrim($rel_path, '/');
        if (file_exists($local_file) && is_readable($local_file)) {
            $size = @getimagesize($local_file);
            if ($size && !empty($size[0]) && !empty($size[1])) {
                return [$size[0], $size[1]];
            }
        }

        return null;
    }

    /**
     * Replace heavy YouTube & Vimeo iframe embeds with lightweight facades
     */
    public function replace_video_iframes_with_facades($html) {
        return preg_replace_callback(
            '/<iframe\s+([^>]*src=[\'"]([^\'"]+)[\'"][^>]*)><\/iframe>/i',
            function ($matches) {
                $attrs = $matches[1];
                $src = $matches[2];

                // YouTube match
                if (preg_match('/(?:youtube\.com\/(?:embed\/|watch\?v=)|youtu\.be\/)([a-zA-Z0-9_\-]+)/i', $src, $yt_match)) {
                    $video_id = $yt_match[1];
                    $thumb_url = "https://i.ytimg.com/vi/{$video_id}/hqdefault.jpg";
                    return $this->render_facade_html($thumb_url, $src, 'YouTube');
                }

                // Vimeo match
                if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/i', $src, $vimeo_match)) {
                    $video_id = $vimeo_match[1];
                    $thumb_url = "https://vumbnail.com/{$video_id}.jpg";
                    return $this->render_facade_html($thumb_url, $src, 'Vimeo');
                }

                return $matches[0];
            },
            $html
        );
    }

    private function render_facade_html($thumb_url, $embed_src, $provider) {
        $embed_src_esc = esc_url($embed_src);
        $thumb_url_esc = esc_url($thumb_url);

        return "<div class=\"smau-video-facade\" data-embed=\"{$embed_src_esc}\" style=\"position:relative;cursor:pointer;background-color:#000;background-image:url('{$thumb_url_esc}');background-size:cover;background-position:center;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:8px;\">" .
               "<div class=\"smau-play-btn\" style=\"position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:68px;height:48px;background:rgba(23,33,83,0.85);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,0.5);\">" .
               "<svg width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"#ffffff\"><path d=\"M8 5v14l11-7z\"/></svg>" .
               "</div>" .
               "</div>";
    }

    /**
     * Inject facade click-to-load handler
     */
    public function inject_facade_script() {
        $settings = get_option('smau_settings', []);
        if (empty($settings['video_facades_enabled'])) {
            return;
        }

        echo "<script>\n" .
            "document.addEventListener('click',function(e){" .
            "const f=e.target.closest('.smau-video-facade');" .
            "if(!f)return;" .
            "const url=f.getAttribute('data-embed');" .
            "const ifr=document.createElement('iframe');" .
            "ifr.setAttribute('src',url+(url.indexOf('?')===-1?'?':'&')+'autoplay=1');" .
            "ifr.setAttribute('frameborder','0');" .
            "ifr.setAttribute('allow','accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');" .
            "ifr.setAttribute('allowfullscreen','1');" .
            "ifr.style.cssText='position:absolute;top:0;left:0;width:100%;height:100%;border:0;';" .
            "f.innerHTML='';" .
            "f.appendChild(ifr);" .
            "});\n" .
            "</script>\n";
    }
}
