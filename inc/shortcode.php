<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main shortcode to render the chatbox interface.
 */
add_shortcode( 'ai_chatbox', 'aicb_render_chatbox_shortcode' );
function aicb_render_chatbox_shortcode() {
    ob_start();
    // This function is defined in inc/html-render.php
    aicb_render_chat_html();
    return ob_get_clean();
}

/**
 * Shortcode to display intelligent content links.
 * This shortcode just creates a placeholder div. The content is loaded via JavaScript.
 */
add_shortcode( 'intelligent_content_links', 'aicb_render_intelligent_links_shortcode' );
function aicb_render_intelligent_links_shortcode() {
    // The JavaScript file 'intelligent-links.js' will target this class.
    return '<div class="aicb-intelligent-links-container"><p><em>Loading recommendations...</em></p></div>';
}

/**
 * Filter to handle the front page takeover feature.
 */
add_filter( 'template_include', 'aicb_template_include', 99 );
function aicb_template_include( $template ) {
    $options = get_option('aicb_settings');
    $is_takeover_active = isset($options['aicb_front_page_takeover']) && $options['aicb_front_page_takeover'];
    if ( $is_takeover_active && is_front_page() ) {
        // This function is defined in inc/html-render.php
        aicb_render_chat_page();
        exit;
    }
    return $template;
}

/**
 * Appends a "Report this page" button to the end of every post/page.
 */
add_filter( 'the_content', 'aicb_add_report_button_to_content' );
function aicb_add_report_button_to_content( $content ) {
    // Only add the button to single posts and pages, not on archive pages.
    if ( is_singular() && in_the_loop() && is_main_query() ) {
        $post_id = get_the_ID();
        $post_url = get_permalink($post_id);
        
        $button_html = '
            <div class="aicb-report-page-wrapper">
                <button class="aicb-report-page-button" data-url="' . esc_attr($post_url) . '" data-post-id="' . esc_attr($post_id) . '">
                    Report an issue with this page
                </button>
            </div>
        ';
        // Append the button to the existing post content
        return $content . $button_html;
    }
    
    // Return the content untouched for other views
    return $content;
}