<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds the AI SEO Meta Box if the feature is enabled.
 */
add_action( 'add_meta_boxes', 'aicb_add_seo_meta_box' );
function aicb_add_seo_meta_box() {
    $options = get_option('aicb_settings');
    if (isset($options['aicb_enable_auto_seo']) && $options['aicb_enable_auto_seo']) {
        add_meta_box(
            'aicb_seo_meta_box',
            __( 'AI SEO Optimizer', 'aicb' ),
            'aicb_render_seo_meta_box',
            ['post', 'page'],
            'side',
            'high'
        );
    }
}

/**
 * Renders the content of the AI SEO Meta Box.
 */
function aicb_render_seo_meta_box( $post ) {
    // Add a nonce field so we can check for it later.
    wp_nonce_field( 'aicb_save_seo_meta_data', 'aicb_seo_meta_box_nonce' );

    $seo_title = get_post_meta( $post->ID, '_aicb_seo_title', true );
    $meta_desc = get_post_meta( $post->ID, '_aicb_meta_description', true );
    ?>
    <div class="aicb-seo-meta-box-content">
        <p>
            <button type="button" id="aicb-generate-seo-btn" class="button button-primary button-large">Generate SEO Meta Tags</button>
            <span class="spinner"></span>
        </p>
        <p>
            <label for="aicb_seo_title"><strong><?php _e( 'SEO Title', 'aicb' ); ?></strong></label>
            <input type="text" id="aicb_seo_title" name="aicb_seo_title" value="<?php echo esc_attr( $seo_title ); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="aicb_meta_description"><strong><?php _e( 'Meta Description', 'aicb' ); ?></strong></label>
            <textarea id="aicb_meta_description" name="aicb_meta_description" rows="4" style="width:100%;"><?php echo esc_textarea( $meta_desc ); ?></textarea>
        </p>
        <p class="description"><?php _e( 'Click the button above to automatically generate SEO-friendly meta tags based on your post content.', 'aicb' ); ?></p>
    </div>
    <?php
}

/**
 * Saves the custom meta data when the post is saved.
 */
add_action( 'save_post', 'aicb_save_seo_meta_data' );
function aicb_save_seo_meta_data( $post_id ) {
    if ( ! isset( $_POST['aicb_seo_meta_box_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['aicb_seo_meta_box_nonce'], 'aicb_save_seo_meta_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( isset( $_POST['post_type'] ) && ( 'page' == $_POST['post_type'] || 'post' == $_POST['post_type'] ) ) {
        if ( ! current_user_can( 'edit_page', $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    if ( isset( $_POST['aicb_seo_title'] ) ) {
        update_post_meta( $post_id, '_aicb_seo_title', sanitize_text_field( $_POST['aicb_seo_title'] ) );
    }
    if ( isset( $_POST['aicb_meta_description'] ) ) {
        update_post_meta( $post_id, '_aicb_meta_description', sanitize_textarea_field( $_POST['aicb_meta_description'] ) );
    }
}

/**
 * Injects the saved SEO meta tags into the page's <head>.
 */
add_action( 'wp_head', 'aicb_inject_seo_meta_tags' );
function aicb_inject_seo_meta_tags() {
    if ( is_singular() ) {
        $post_id = get_queried_object_id();
        $seo_title = get_post_meta( $post_id, '_aicb_seo_title', true );
        $meta_desc = get_post_meta( $post_id, '_aicb_meta_description', true );

        if ( ! empty( $seo_title ) ) {
            echo '<title>' . esc_html( $seo_title ) . '</title>' . "\n";
        }
        if ( ! empty( $meta_desc ) ) {
            echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
        }
    }
}
