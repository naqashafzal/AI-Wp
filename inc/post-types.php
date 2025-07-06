<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers the 'Knowledge Base' and 'Ads' custom post types.
 */
add_action( 'init', 'aicb_register_cpts' );
function aicb_register_cpts() {
    // Knowledge Base Post Type
    $kb_labels = array(
        'name'                  => _x( 'Knowledge Base', 'Post type general name', 'aicb' ),
        'singular_name'         => _x( 'Knowledge Entry', 'Post type singular name', 'aicb' ),
        'menu_name'             => _x( 'Knowledge Base', 'Admin Menu text', 'aicb' ),
        'add_new_item'          => __( 'Add New Custom Answer', 'aicb' ),
        'add_new'               => __( 'Add New', 'aicb' ),
        'edit_item'             => __( 'Edit Entry', 'aicb' ),
        'new_item'              => __( 'New Entry', 'aicb' ),
        'view_item'             => __( 'View Entry', 'aicb' ),
        'search_items'          => __( 'Search Knowledge Base', 'aicb' ),
        'not_found'             => __( 'No entries found', 'aicb' ),
        'not_found_in_trash'    => __( 'No entries found in Trash', 'aicb' ),
    );
    $kb_args = array(
        'labels'        => $kb_labels,
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'aicb-analytics',
        'supports'      => array('title', 'editor'),
        'menu_icon'     => 'dashicons-lightbulb',
    );
    register_post_type( 'aicb_knowledge', $kb_args );

    // Ads Post Type
    $ad_labels = array(
        'name'                  => _x( 'Ads', 'Post type general name', 'aicb' ),
        'singular_name'         => _x( 'Ad', 'Post type singular name', 'aicb' ),
        'menu_name'             => _x( 'Ads', 'Admin Menu text', 'aicb' ),
        'add_new_item'          => __( 'Add New Ad', 'aicb' ),
        'add_new'               => __( 'Add New', 'aicb' ),
        'edit_item'             => __( 'Edit Ad', 'aicb' ),
        'new_item'              => __( 'New Ad', 'aicb' ),
        'view_item'             => __( 'View Ad', 'aicb' ),
        'search_items'          => __( 'Search Ads', 'aicb' ),
        'not_found'             => __( 'No ads found', 'aicb' ),
        'not_found_in_trash'    => __( 'No ads found in Trash', 'aicb' ),
    );
    $ad_args = array(
        'labels'        => $ad_labels,
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'aicb-analytics',
        'supports'      => array('title', 'editor'),
        'menu_icon'     => 'dashicons-megaphone'
    );
    register_post_type( 'aicb_ad', $ad_args );
}

/**
 * Adds a meta box for the 'Target URL' to the 'Ad' post type editor.
 */
add_action( 'add_meta_boxes', 'aicb_ad_meta_box' );
function aicb_ad_meta_box() {
    add_meta_box(
        'aicb_ad_url_meta_box',
        'Ad Settings',
        'aicb_ad_url_meta_box_callback',
        'aicb_ad',
        'side',
        'default'
    );
}

/**
 * Renders the HTML for the 'Target URL' meta box.
 */
function aicb_ad_url_meta_box_callback( $post ) {
    wp_nonce_field( 'aicb_save_ad_meta_box_data', 'aicb_ad_meta_box_nonce' );
    $value = get_post_meta( $post->ID, '_aicb_ad_target_url', true );
    
    echo '<label for="aicb_ad_target_url" style="font-weight:bold;">Target URL:</label> ';
    echo '<input type="url" id="aicb_ad_target_url" name="aicb_ad_target_url" value="' . esc_attr( $value ) . '" size="25" style="width:100%; margin-top:5px;" />';
    echo '<p>The destination URL for this ad when used for in-line rotation. The Ad Title will be used as the link text.</p>';
}

/**
 * Saves the 'Target URL' meta box data when an ad is saved.
 */
add_action( 'save_post', 'aicb_save_ad_meta_box_data' );
function aicb_save_ad_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['aicb_ad_meta_box_nonce'] ) ) { return; }
    if ( ! wp_verify_nonce( $_POST['aicb_ad_meta_box_nonce'], 'aicb_save_ad_meta_box_data' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( isset( $_POST['post_type'] ) && 'aicb_ad' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    }
    if ( ! isset( $_POST['aicb_ad_target_url'] ) ) { return; }
    
    $my_data = esc_url_raw( $_POST['aicb_ad_target_url'] );
    update_post_meta( $post_id, '_aicb_ad_target_url', $my_data );
}