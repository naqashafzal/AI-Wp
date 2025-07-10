<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main shortcode to render the chatbox interface.
 * This function now also handles enqueuing its own assets to prevent loops.
 */
add_shortcode( 'ai_chatbox', 'aicb_render_chatbox_shortcode' );
function aicb_render_chatbox_shortcode() {
    
    // Enqueue assets specifically for this shortcode instance.
    $options = get_option('aicb_settings');
    
    $post_id = 0;
    $post_categories = [];
    if (is_singular()) {
        $post_id = get_the_ID();
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $post_categories = wp_list_pluck($categories, 'name');
        }
    }

    $script_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aicb_chat_nonce'),
        'enable_personalized_welcome' => isset($options['aicb_enable_personalized_welcome']) ? (bool)$options['aicb_enable_personalized_welcome'] : false,
        'autocomplete_enabled' => isset($options['aicb_enable_autocomplete']) ? (bool)$options['aicb_enable_autocomplete'] : true,
        'enable_memory' => isset($options['aicb_enable_memory']) ? (bool)$options['aicb_enable_memory'] : true,
        'content_display_mode' => isset($options['aicb_content_display_mode']) ? $options['aicb_content_display_mode'] : 'in_chatbox',
        'post_id' => $post_id,
        'post_categories' => $post_categories,
        'premium_modal_title' => isset($options['aicb_premium_modal_title']) ? $options['aicb_premium_modal_title'] : 'Premium Feature',
        'premium_modal_message' => isset($options['aicb_premium_modal_message']) ? $options['aicb_premium_modal_message'] : 'Upgrade your account to access this feature.',
        'premium_modal_button_text' => isset($options['aicb_premium_modal_button_text']) ? $options['aicb_premium_modal_button_text'] : 'Upgrade Now',
        'premium_modal_button_url' => isset($options['aicb_premium_modal_button_url']) ? $options['aicb_premium_modal_button_url'] : '#',
    );

    wp_enqueue_style('aicb-style', plugin_dir_url(AICB_PLUGIN_FILE) . 'css/chat-style.css', array(), '51.0');
    
    // --- FIX: Add aicb-modal-js as a dependency for the main chat script ---
    $chat_deps = array('jquery', 'aicb-modal-js');
    if ($script_data['enable_personalized_welcome']) {
        if (!wp_script_is('aicb-tracker-js', 'enqueued')) {
            wp_enqueue_script('aicb-tracker-js', plugin_dir_url(AICB_PLUGIN_FILE) . 'js/tracker.js', array(), '51.0', true);
            wp_localize_script('aicb-tracker-js', 'aicb_page_data', $script_data);
        }
        $chat_deps[] = 'aicb-tracker-js';
    }
    wp_enqueue_script('aicb-chat-js', plugin_dir_url(AICB_PLUGIN_FILE) . 'js/chat.js', $chat_deps, '51.0', true);
    wp_localize_script('aicb-chat-js', 'aicb_settings', $script_data);


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
    if ( !is_admin() && is_singular() && in_the_loop() && is_main_query() ) {
        remove_filter('the_content', __FUNCTION__);
        
        $post_id = get_the_ID();
        $post_url = get_permalink($post_id);
        
        $button_html = '
            <div class="aicb-report-page-wrapper">
                <button class="aicb-report-page-button" data-url="' . esc_attr($post_url) . '" data-post-id="' . esc_attr($post_id) . '">
                    Report an issue with this page
                </button>
            </div>
        ';
        $content .= $button_html;
        
        add_filter('the_content', __FUNCTION__);
    }
    
    return $content;
}

add_action('wp_ajax_aicb_report_issue', 'aicb_handle_report_issue');
add_action('wp_ajax_nopriv_aicb_report_issue', 'aicb_handle_report_issue');
function aicb_handle_report_issue() {
    check_ajax_referer('aicb_chat_nonce', 'nonce');
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if ($post_id > 0) {
        $post_title = get_the_title($post_id);
        aicb_log_activity('issue_report', "Reported issue with: " . $post_title, $post_id);
        wp_send_json_success(['message' => 'Thank you for your report.']);
    } else {
        wp_send_json_error(['message' => 'Invalid post ID.']);
    }
}
