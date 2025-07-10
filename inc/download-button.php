<?php
/**
 * Handles the Direct Download Link feature for posts and pages.
 *
 * @package AI-Wp
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Adds the meta box to the post editor screen.
 */
add_action( 'add_meta_boxes', 'aicb_add_download_link_meta_box' );
function aicb_add_download_link_meta_box() {
    add_meta_box(
        'aicb_download_link_box',           // Unique ID
        'Direct Download Link',             // Box title
        'aicb_render_download_link_meta_box', // Callback function
        ['post', 'page'],                   // Post types
        'side',                             // Context (side, normal, advanced)
        'high'                              // Priority
    );
}

/**
 * Renders the HTML for the meta box.
 *
 * @param WP_Post $post The post object.
 */
function aicb_render_download_link_meta_box( $post ) {
    // Add a nonce field for security
    wp_nonce_field( 'aicb_save_download_link_data', 'aicb_download_link_nonce' );

    // Get the existing value from the database
    $download_link = get_post_meta( $post->ID, '_aicb_download_link', true );

    // Output the field
    echo '<label for="aicb_download_link_field" class="screen-reader-text">Download URL:</label>';
    echo '<input type="url" id="aicb_download_link_field" name="aicb_download_link_field" value="' . esc_attr( $download_link ) . '" size="25" style="width:100%;" placeholder="https://example.com/file.zip" />';
    echo '<p class="description">Enter the full URL for the download button. If left empty, no button will be shown.</p>';
}

/**
 * Saves the custom meta data when the post is saved.
 *
 * @param int $post_id The ID of the post being saved.
 */
add_action( 'save_post', 'aicb_save_download_link_data' );
function aicb_save_download_link_data( $post_id ) {
    // Check if our nonce is set.
    if ( ! isset( $_POST['aicb_download_link_nonce'] ) ) {
        return;
    }
    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['aicb_download_link_nonce'], 'aicb_save_download_link_data' ) ) {
        return;
    }
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    // Sanitize user input.
    $new_download_link = isset( $_POST['aicb_download_link_field'] ) ? esc_url_raw( $_POST['aicb_download_link_field'] ) : '';

    // Update the meta field in the database.
    update_post_meta( $post_id, '_aicb_download_link', $new_download_link );
}

/**
 * Appends the download button to the end of the post content.
 *
 * @param string $content The post content.
 * @return string The modified post content.
 */
add_filter( 'the_content', 'aicb_display_download_button' );
function aicb_display_download_button( $content ) {
    if ( !is_admin() && is_singular() && in_the_loop() && is_main_query() ) {
        remove_filter('the_content', __FUNCTION__);

        $post_id = get_the_ID();
        $download_link = get_post_meta( $post_id, '_aicb_download_link', true );

        if ( ! empty( $download_link ) ) {
            $options = get_option('aicb_settings');
            $button_text = !empty($options['aicb_main_download_button_text']) ? $options['aicb_main_download_button_text'] : 'Download Now';
            $icon_class = !empty($options['aicb_main_download_button_icon']) ? $options['aicb_main_download_button_icon'] : 'dashicons-download';

            $button_attributes = '';
            $button_href = '';

            if ( aicb_is_premium_user() ) {
                $button_href = esc_url( $download_link );
                $button_attributes = 'target="_blank" rel="noopener noreferrer"';
            } else {
                $button_href = '#';
                $button_attributes = 'data-premium-required="true"';
            }

            $button_html = '
                <div class="aicb-download-button-wrapper">
                    <a href="' . $button_href . '" class="aicb-download-button" ' . $button_attributes . '>
                        <span class="dashicons ' . esc_attr($icon_class) . '"></span> ' . esc_html($button_text) . '
                    </a>
                </div>
            ';
            $content .= $button_html;
        }

        add_filter('the_content', __FUNCTION__);
    }
    return $content;
}
