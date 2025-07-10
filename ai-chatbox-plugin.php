<?php
/**
 * Plugin Name:       AI-Wp By Nullpk
 * Description:       A fully-featured, intelligent, AI-powered chatbox for your WordPress site with a complete membership system.
 * Version:           2.3
 * Author:            Naqash Afzal
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

// Define plugin constants for easy access.
define( 'AICB_PLUGIN_FILE', __FILE__ );
define( 'AICB_PLUGIN_DIR', plugin_dir_path( AICB_PLUGIN_FILE ) );

// Load all separate plugin components in the correct order.
require_once AICB_PLUGIN_DIR . 'inc/activation.php';
require_once AICB_PLUGIN_DIR . 'inc/enqueue.php';
require_once AICB_PLUGIN_DIR . 'inc/post-types.php';
require_once AICB_PLUGIN_DIR . 'inc/admin-dashboard.php';
require_once AICB_PLUGIN_DIR . 'inc/settings-page.php';
require_once AICB_PLUGIN_DIR . 'inc/training-page.php';
require_once AICB_PLUGIN_DIR . 'inc/ajax-handlers.php';
require_once AICB_PLUGIN_DIR . 'inc/html-render.php';
require_once AICB_PLUGIN_DIR . 'inc/shortcode.php';
require_once AICB_PLUGIN_DIR . 'inc/widgets.php';
require_once AICB_PLUGIN_DIR . 'inc/floating-widget-render.php';
require_once AICB_PLUGIN_DIR . 'inc/post-ratings.php';
require_once AICB_PLUGIN_DIR . 'inc/membership-handler.php';
require_once AICB_PLUGIN_DIR . 'inc/seo-meta-box.php';
require_once AICB_PLUGIN_DIR . 'inc/floating-ad-render.php';
require_once AICB_PLUGIN_DIR . 'inc/download-button.php'; // Add this line


/**
 * Clear chat response transients when settings are updated to reflect changes immediately.
 */
add_action( 'update_option_aicb_settings', 'aicb_clear_transients_on_settings_update', 10, 2 );
function aicb_clear_transients_on_settings_update( $old_value, $new_value ) {
    global $wpdb;
    $transient_prefix = '_transient_aicb_query_v3_';
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $transient_prefix ) . '%' ) );
}


/**
 * Display a setup guide admin notice if the API key is missing.
 */
add_action( 'admin_notices', 'aicb_setup_guide_admin_notice' );
function aicb_setup_guide_admin_notice() {
    $options = get_option('aicb_settings');
    if ( !isset($options['aicb_gemini_api_key']) || empty($options['aicb_gemini_api_key']) ) {
        $settings_url = admin_url('admin.php?page=aicb-settings&tab=api');
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Welcome to AI Chatbox!</strong> To get started, please <a href="' . esc_url($settings_url) . '">enter your Google Gemini API key</a>.</p>';
        echo '</div>';
    }
}

/**
 * Remove the default WordPress footer text on plugin pages.
 */
add_filter('admin_footer_text', 'aicb_remove_footer_admin_text', 999);
function aicb_remove_footer_admin_text($text) {
    $current_screen = get_current_screen();
    if ($current_screen && strpos($current_screen->id, 'aicb-') !== false) {
        return '';
    }
    return $text;
}

/**
 * Add a settings link to the plugin's action links on the plugins page.
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'aicb_add_settings_link' );
function aicb_add_settings_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=aicb-settings' ) . '">' . __( 'Settings' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
