<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_enqueue_scripts', 'aicb_admin_enqueue_scripts' );
function aicb_admin_enqueue_scripts( $hook ) {
    
    // Define the plugin's own admin pages
    $plugin_pages = [
        'toplevel_page_aicb-analytics',
        'ai-chatbox_page_aicb-leads',
        'ai-chatbox_page_aicb-suggestions',
        'ai-chatbox_page_aicb-training',
        'ai-chatbox_page_aicb-settings'
    ];

    // Define the post editor pages
    $post_edit_screens = ['post.php', 'post-new.php'];

    // Load scripts if the current page is a plugin page OR a post edit screen
    if ( in_array( $hook, $plugin_pages ) || in_array( $hook, $post_edit_screens ) ) {
        
        // Enqueue admin styles
        wp_enqueue_style( 'aicb-admin-style', plugin_dir_url( AICB_PLUGIN_FILE ) . 'css/admin-style.css' );
        
        // Enqueue Chart.js only on the main analytics page
        if ($hook === 'toplevel_page_aicb-analytics') {
            wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
        }

        // Enqueue the main admin dashboard script
        wp_enqueue_script( 'aicb-admin-dashboard-js', plugin_dir_url( AICB_PLUGIN_FILE ) . 'js/admin-dashboard.js', array( 'jquery' ), '1.9', true );
        
        // Localize script with necessary data
        wp_localize_script('aicb-admin-dashboard-js', 'aicb_dashboard_obj', array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ), 
            'nonce' => wp_create_nonce( 'aicb_admin_nonce' ) 
        ));
    }
}

add_action( 'wp_enqueue_scripts', 'aicb_enqueue_frontend_assets' );
function aicb_enqueue_frontend_assets() {
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
    
    // Enqueue Modal Assets Globally
    wp_enqueue_style('aicb-modal-style', plugin_dir_url(AICB_PLUGIN_FILE) . 'css/modal-style.css', array(), '1.1');
    wp_enqueue_script('aicb-modal-js', plugin_dir_url(AICB_PLUGIN_FILE) . 'js/modal.js', array(), '1.1', true);

    // Always enqueue the tracker if personalization is on
    if ($script_data['enable_personalized_welcome']) {
        wp_enqueue_script('aicb-tracker-js', plugin_dir_url(AICB_PLUGIN_FILE) . 'js/tracker.js', array(), '51.0', true);
        wp_localize_script('aicb-tracker-js', 'aicb_page_data', $script_data);
    }
    
    // Intelligent links can be used anywhere
    wp_enqueue_script('aicb-intelligent-links-js', plugin_dir_url(AICB_PLUGIN_FILE) . 'js/intelligent-links.js', array(), '51.0', true);
    wp_localize_script('aicb-intelligent-links-js', 'aicb_settings', $script_data);

    // Ratings for single posts
    $is_ratings_enabled = isset($options['aicb_enable_post_ratings']) && $options['aicb_enable_post_ratings'];
    if ($is_ratings_enabled && is_singular() && !is_front_page()) {
        wp_enqueue_style('aicb-post-ratings-style', plugin_dir_url(AICB_PLUGIN_FILE) . 'css/post-ratings.css', array(), '1.2');
        wp_enqueue_script('aicb-post-ratings-js', plugin_dir_url(AICB_PLUGIN_FILE) . 'js/post-ratings.js', array('jquery'), '1.2', true);
        wp_localize_script('aicb-post-ratings-js', 'aicb_settings', $script_data);
    }

    // Floating widget logic
    $is_floating_enabled = isset($options['aicb_enable_floating_chatbox']) && $options['aicb_enable_floating_chatbox'];
    $is_takeover_active = isset($options['aicb_front_page_takeover']) && $options['aicb_front_page_takeover'];
    
    if ($is_floating_enabled && (!is_front_page() || !$is_takeover_active)) {
        wp_enqueue_style('aicb-floating-widget-style', plugin_dir_url(AICB_PLUGIN_FILE) . 'css/floating-widget.css', array(), '51.0');
        
        $floating_chat_deps = array('jquery', 'aicb-modal-js');
        if ($script_data['enable_personalized_welcome']) {
            $floating_chat_deps[] = 'aicb-tracker-js';
        }
        wp_enqueue_script('aicb-floating-chat-js', plugin_dir_url(AICB_PLUGIN_FILE) . 'js/floating-chat.js', $floating_chat_deps, '51.0', true);
        wp_localize_script('aicb-floating-chat-js', 'aicb_settings', $script_data);
    }

    // Main chatbox styles and scripts for takeover mode
    if ($is_takeover_active && is_front_page()) {
        wp_enqueue_style('aicb-style', plugin_dir_url(AICB_PLUGIN_FILE) . 'css/chat-style.css', array(), '51.0');
        
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
    }
    
    // Enqueue download button assets if on a single post/page
    if (is_singular()) {
        wp_enqueue_style('aicb-download-button-style', plugin_dir_url(AICB_PLUGIN_FILE) . 'css/download-button.css', array(), '1.0');
        wp_enqueue_script('aicb-download-button-js', plugin_dir_url(AICB_PLUGIN_FILE) . 'js/download-button.js', array('jquery', 'aicb-modal-js'), '1.0', true);
        wp_localize_script('aicb-download-button-js', 'aicb_settings', $script_data);
    }
}
