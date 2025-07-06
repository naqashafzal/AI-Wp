<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', 'aicb_enqueue_frontend_assets' );
function aicb_enqueue_frontend_assets() {
    $options = get_option('aicb_settings');
    
    $script_data = array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce('aicb_chat_nonce'),
        'enable_personalized_welcome' => isset($options['aicb_enable_personalized_welcome']) ? (bool)$options['aicb_enable_personalized_welcome'] : false,
        'autocomplete_enabled' => isset($options['aicb_enable_autocomplete']) ? (bool)$options['aicb_enable_autocomplete'] : true,
        'enable_memory' => isset($options['aicb_enable_memory']) ? (bool)$options['aicb_enable_memory'] : true,
    );

    if (isset($options['aicb_enable_personalized_welcome']) && $options['aicb_enable_personalized_welcome']) {
        wp_enqueue_script( 'aicb-tracker-js', plugin_dir_url( AICB_PLUGIN_FILE ) . 'js/tracker.js', array(), '41.0', true );
        $post_categories = array();
        if ( is_singular() ) {
            $categories = get_the_category();
            if ( ! empty( $categories ) ) {
                $post_categories = wp_list_pluck( $categories, 'name' );
            }
        }
        $script_data['post_categories'] = $post_categories;
        wp_localize_script('aicb-tracker-js', 'aicb_page_data', $script_data);
    }
    
    wp_enqueue_script( 'aicb-intelligent-links-js', plugin_dir_url( AICB_PLUGIN_FILE ) . 'js/intelligent-links.js', array(), '41.0', true );
    wp_localize_script('aicb-intelligent-links-js', 'aicb_settings', $script_data);

    if ( is_singular() ) {
        wp_enqueue_script( 'aicb-report-button-js', plugin_dir_url( AICB_PLUGIN_FILE ) . 'js/report-button.js', array( 'jquery' ), '41.0', true );
        wp_localize_script('aicb-report-button-js', 'aicb_settings', $script_data);
    }

    $is_floating_enabled = isset($options['aicb_enable_floating_chatbox']) && $options['aicb_enable_floating_chatbox'];
    if ( $is_floating_enabled && !is_front_page() ) {
        wp_enqueue_style( 'aicb-floating-widget-style', plugin_dir_url( AICB_PLUGIN_FILE ) . 'css/floating-widget.css', array(), '41.0' );
        wp_enqueue_script( 'aicb-floating-chat-js', plugin_dir_url( AICB_PLUGIN_FILE ) . 'js/floating-chat.js', array( 'jquery' ), '41.0', true );
        wp_localize_script('aicb-floating-chat-js', 'aicb_settings', $script_data);
    }

    $is_takeover_active = isset($options['aicb_front_page_takeover']) && $options['aicb_front_page_takeover'];
    global $post;
    $has_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ai_chatbox' );
    if ( ($is_takeover_active && is_front_page()) || $has_shortcode ) {
        wp_enqueue_style( 'aicb-style', plugin_dir_url( AICB_PLUGIN_FILE ) . 'css/chat-style.css', array(), '41.0' );
        wp_enqueue_script( 'aicb-chat-js', plugin_dir_url( AICB_PLUGIN_FILE ) . 'js/chat.js', array( 'jquery' ), '41.0', true );
        wp_localize_script('aicb-chat-js', 'aicb_settings', $script_data);
    }
}

add_action( 'admin_enqueue_scripts', 'aicb_admin_enqueue_scripts' );
function aicb_admin_enqueue_scripts() {
    $current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
    $allowed_pages = ['aicb-analytics', 'aicb-settings', 'aicb-training', 'aicb-link-reports'];
    if ( !in_array($current_page, $allowed_pages) ) { 
        return; 
    }
    
    wp_enqueue_style( 'aicb-admin-style', plugin_dir_url( AICB_PLUGIN_FILE ) . 'css/admin-style.css' );
    wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
    wp_enqueue_script( 'aicb-admin-dashboard-js', plugin_dir_url( AICB_PLUGIN_FILE ) . 'js/admin-dashboard.js', array( 'jquery', 'chart-js' ), '41.0', true );
    wp_localize_script('aicb-admin-dashboard-js', 'aicb_dashboard_obj', array( 
        'ajax_url' => admin_url( 'admin-ajax.php' ), 
        'nonce' => wp_create_nonce( 'aicb_admin_nonce' ) 
    ));
}