<?php
/**
 * Floating Ad Bar Rendering
 *
 * This file is responsible for rendering the HTML for the floating ad bar
 * that appears at the bottom of the screen. This version uses inline styles
 * and scripts for maximum compatibility.
 *
 * @package AI-Wp
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Hooks into the WordPress footer to add the floating ad bar HTML and its script.
 */
add_action('wp_footer', 'aicb_add_floating_ad_bar_html', 9999); // High priority to load late
function aicb_add_floating_ad_bar_html() {
    $options = get_option('aicb_settings');
    
    // 1. Check if the feature is globally enabled.
    $is_ad_enabled = isset($options['aicb_enable_floating_ad']) && $options['aicb_enable_floating_ad'];
    if (!$is_ad_enabled) {
        return;
    }

    // 2. Check if the ad should be excluded on the current page.
    if (is_front_page()) {
        return;
    }
    $exclude_ids_str = isset($options['aicb_exclude_floating_ad_pages']) ? $options['aicb_exclude_floating_ad_pages'] : '';
    if (!empty($exclude_ids_str)) {
        $current_id = get_queried_object_id();
        $exclude_ids = array_filter(array_map('trim', explode(',', $exclude_ids_str)));
        if (in_array($current_id, $exclude_ids)) {
            return;
        }
    }

    // 3. Get the ad HTML content.
    $custom_code = isset($options['aicb_floating_ad_custom_code']) ? trim($options['aicb_floating_ad_custom_code']) : '';
    $ad_html = aicb_get_ad_html($custom_code);

    // 4. Render the ad only if there is content to show.
    if (!empty($ad_html)) {
        $position = isset($options['aicb_floating_ad_position']) ? $options['aicb_floating_ad_position'] : 'bottom_bar';
        
        // --- FINAL FIX: Generate inline styles for maximum compatibility ---
        $style = "position: fixed; z-index: 999998; width: auto; max-width: 90vw; display: none; transition: opacity 0.5s ease; opacity: 0;";
        $content_style = "position: relative; line-height: 0;";
        $close_style = "position: absolute; top: -10px; right: -10px; cursor: pointer; border: none; background: #333; color: white; border-radius: 50%; width: 24px; height: 24px; font-size: 18px; line-height: 24px; text-align: center; padding: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.3); z-index: 10;";

        switch ($position) {
            case 'top_bar':
                $style .= "top: 15px; left: 50%; transform: translateX(-50%);";
                break;
            case 'bottom_left':
                $style .= "bottom: 20px; left: 20px;";
                break;
            case 'bottom_right':
                $style .= "bottom: 20px; right: 20px;";
                break;
            case 'bottom_bar':
            default:
                $style .= "bottom: 15px; left: 50%; transform: translateX(-50%);";
                break;
        }
        ?>
        <div id="aicb-floating-ad-bar" style="<?php echo esc_attr($style); ?>">
            <div class="aicb-floating-ad-content" style="<?php echo esc_attr($content_style); ?>">
                <button id="aicb-close-floating-ad" aria-label="Close Ad" style="<?php echo esc_attr($close_style); ?>">&times;</button>
                <?php echo $ad_html; ?>
            </div>
        </div>
        
        <script>
            // Using plain JavaScript for maximum compatibility
            document.addEventListener('DOMContentLoaded', function() {
                var adBar = document.getElementById('aicb-floating-ad-bar');
                var closeButton = document.getElementById('aicb-close-floating-ad');

                if (adBar) {
                    // Make it visible
                    adBar.style.display = 'inline-flex';
                    setTimeout(function() {
                        adBar.style.opacity = '1';
                    }, 100);

                    if (closeButton) {
                        closeButton.addEventListener('click', function() {
                            adBar.style.display = 'none';
                        });
                    }
                }
            });
        </script>
        <?php
    }
}

/**
 * Helper function to get the ad HTML.
 * Prioritizes custom code, falls back to Ads post type.
 */
function aicb_get_ad_html($custom_code) {
    if (!empty($custom_code)) {
        return do_shortcode($custom_code);
    }

    $ad_args = array(
        'post_type' => 'aicb_ad',
        'posts_per_page' => 1,
        'orderby' => 'rand',
        'post_status' => 'publish',
        'no_found_rows' => true
    );
    $ad_query = new WP_Query($ad_args);

    if ($ad_query->have_posts()) {
        $ad_query->the_post();
        $ad_content = get_the_content();
        $ad_target_url = get_post_meta(get_the_ID(), '_aicb_ad_target_url', true);
        wp_reset_postdata();
        
        if (!empty($ad_target_url)) {
            return '<a href="' . esc_url($ad_target_url) . '" target="_blank" rel="noopener noreferrer">' . do_shortcode($ad_content) . '</a>';
        } else {
            return do_shortcode($ad_content);
        }
    }

    return '';
}
