<?php
/**
 * Plugin Activation
 *
 * This file runs when the plugin is activated. It's responsible for setting up
 * the initial database structure required for the plugin to function.
 *
 * @package AI-Wp
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * The main activation function that is hooked into the `register_activation_hook`.
 *
 * It calls the function to create all necessary database tables.
 */
register_activation_hook( AICB_PLUGIN_FILE, 'aicb_activate' );
function aicb_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // --- Activity Log Table ---
    // This table tracks various user interactions with the chatbot, such as
    // searches, clicks, and ratings, providing valuable analytics.
    $activity_table = $wpdb->prefix . 'aicb_activity_log';
    $sql = "CREATE TABLE $activity_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        event_type varchar(50) NOT NULL,
        content text NOT NULL,
        post_id bigint(20) DEFAULT 0 NOT NULL,
        country varchar(100) DEFAULT '' NOT NULL,
        device varchar(50) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql );

    // --- Training Data Table ---
    // Stores the question-and-answer pairs from successful AI interactions.
    // This data can be exported to fine-tune a custom AI model.
    $training_table = $wpdb->prefix . 'aicb_training_data';
    $sql = "CREATE TABLE $training_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        question text NOT NULL,
        answer text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql );

    // --- Suggestions Table ---
    // When users rate an answer negatively, they can submit a suggestion for
    // a better response. These are stored here for admin review.
    $suggestions_table = $wpdb->prefix . 'aicb_suggestions';
     $sql = "CREATE TABLE $suggestions_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        original_question text NOT NULL,
        suggested_answer text NOT NULL,
        status varchar(20) DEFAULT 'pending' NOT NULL,
        visitor_ip varchar(100) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql );

    // --- Leads Table ---
    // Captures conversations that the AI identifies as potential sales leads
    // based on sentiment and buying intent analysis.
    $leads_table = $wpdb->prefix . 'aicb_leads';
    $sql = "CREATE TABLE $leads_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        user_query text NOT NULL,
        sentiment varchar(50) NOT NULL,
        is_lead tinyint(1) NOT NULL DEFAULT 0,
        conversation_history longtext NOT NULL,
        visitor_ip varchar(100) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql );
    
    // --- Premium Members Table ---
    // Stores information about users who have an active subscription.
    $premium_table = $wpdb->prefix . 'aicb_premium_members';
    $sql = "CREATE TABLE $premium_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        membership_level varchar(50) NOT NULL,
        start_date datetime NOT NULL,
        end_date datetime NOT NULL,
        status varchar(20) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";
    dbDelta( $sql );

    // --- Membership Packages Table ---
    // Stores the different subscription plans (packages) that you create,
    // including their name, price, duration, and included features.
    $packages_table = $wpdb->prefix . 'aicb_membership_packages';
    $sql = "CREATE TABLE $packages_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        price decimal(10, 2) NOT NULL,
        duration int(11) NOT NULL,
        features text,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql );
}
