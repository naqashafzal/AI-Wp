<?php
/**
 * Plugin Name:       AI WP – Intelligent Chatbot for WordPress
 * Description:       A fully-featured, intelligent, AI-powered chatbox for your WordPress site.
 * Version:           1.0 
 * Author:            Nullpk
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

// Define plugin constants for easy access.
define( 'AICB_PLUGIN_FILE', __FILE__ );
define( 'AICB_PLUGIN_DIR', plugin_dir_path( AICB_PLUGIN_FILE ) );

/**
 * Ensures the database tables for analytics and training exist, creating them if necessary.
 */
function aicb_verify_database_tables_on_load() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $activity_table = $wpdb->prefix . 'aicb_activity_log';
    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $activity_table ) ) != $activity_table ) {
        $sql = "CREATE TABLE $activity_table ( id bigint(20) NOT NULL AUTO_INCREMENT, time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, event_type varchar(50) NOT NULL, content text NOT NULL, post_id bigint(20) DEFAULT 0 NOT NULL, country varchar(100) DEFAULT '' NOT NULL, device varchar(50) DEFAULT '' NOT NULL, PRIMARY KEY  (id) ) $charset_collate;";
        dbDelta( $sql );
    }

    $training_table = $wpdb->prefix . 'aicb_training_data';
    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $training_table ) ) != $training_table ) {
        $sql = "CREATE TABLE $training_table ( id bigint(20) NOT NULL AUTO_INCREMENT, time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, question text NOT NULL, answer text NOT NULL, PRIMARY KEY  (id) ) $charset_collate;";
        dbDelta( $sql );
    }
}
add_action( 'plugins_loaded', 'aicb_verify_database_tables_on_load' );

// --- UX: Guided Setup Admin Notice ---
add_action( 'admin_notices', 'aicb_setup_guide_admin_notice' );
function aicb_setup_guide_admin_notice() {
    $options = get_option('aicb_settings');
    if ( !isset($options['aicb_gemini_api_key']) || empty($options['aicb_gemini_api_key']) ) {
        $settings_url = admin_url('admin.php?page=aicb-settings&tab=api');
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Welcome to AI WP!</strong> To get started, please <a href="' . esc_url($settings_url) . '">enter your Google Gemini API key</a>.</p>';
        echo '</div>';
    }
}


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
