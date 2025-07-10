<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds the rating section to the end of post content.
 */
add_filter('the_content', 'aicb_add_post_rating_to_content');
function aicb_add_post_rating_to_content($content) {
    $options = get_option('aicb_settings');
    $is_ratings_enabled = isset($options['aicb_enable_post_ratings']) && $options['aicb_enable_post_ratings'];

    // --- FINAL FIX: Add !is_admin() check to prevent running in the dashboard ---
    if ( !is_admin() && $is_ratings_enabled && is_singular() && !is_front_page() && in_the_loop() && is_main_query() ) {
        // Temporarily remove the filter to prevent an infinite loop
        remove_filter('the_content', __FUNCTION__);

        $post_id = get_the_ID();
        $visitor_ip = aicb_get_visitor_ip();
        $rated_ips = get_post_meta($post_id, '_aicb_rated_ips', true);
        if (!is_array($rated_ips)) {
            $rated_ips = [];
        }
        $already_rated_by_ip = in_array($visitor_ip, $rated_ips);
        $already_rated_by_cookie = isset($_COOKIE['aicb_rated_post_' . $post_id]);
        $already_rated = $already_rated_by_cookie || $already_rated_by_ip;

        $up_votes = get_post_meta($post_id, '_aicb_rating_up', true) ?: 0;
        $down_votes = get_post_meta($post_id, '_aicb_rating_down', true) ?: 0;
        $total_votes = (int)$up_votes + (int)$down_votes;
        
        $rating_percentage = $total_votes > 0 ? (($up_votes / $total_votes) * 100) : 50;

        $rating_html = '
        <div class="aicb-post-rating-container" data-post-id="' . esc_attr($post_id) . '">
            <h3 class="aicb-rating-title">Was this article helpful?</h3>
            <div class="aicb-rating-buttons ' . ($already_rated ? 'rated' : '') . '">
                <button class="aicb-post-rating-thumb" data-rating="up">👍 <span class="aicb-vote-count">' . esc_html($up_votes) . '</span></button>
                <button class="aicb-post-rating-thumb" data-rating="down">👎 <span class="aicb-vote-count">' . esc_html($down_votes) . '</span></button>
            </div>
            <div class="aicb-rating-bar">
                <div class="aicb-rating-bar-inner" style="width: ' . esc_attr($rating_percentage) . '%;"></div>
            </div>
        </div>';

        $content .= $rating_html;
        
        // Re-add the filter so it runs on other posts if needed
        add_filter('the_content', __FUNCTION__);
    }
    return $content;
}
