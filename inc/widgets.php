<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the custom sidebar for the AI Chatbox.
 * This creates the widget area in the WordPress admin panel.
 */
add_action( 'widgets_init', 'aicb_widgets_init' );
function aicb_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'AI Chatbox Sidebar', 'aicb' ),
        'id'            => 'aicb-chat-sidebar',
        'description'   => __( 'Widgets in this area will be shown in the sidebar of the full-page AI Chatbox.', 'aicb' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}