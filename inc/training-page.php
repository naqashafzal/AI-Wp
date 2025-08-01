<?php
/**
 * Admin Training Data Page
 *
 * This file renders the page where administrators can view and export
 * the AI conversation data for model training purposes.
 *
 * @package AI-Wp
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Renders the HTML for the Training Data page.
 *
 * The menu item for this page is registered in inc/admin-dashboard.php,
 * and this function is called as the callback to display the page content.
 */
function aicb_render_training_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aicb_training_data';
    
    // Check if the table exists to prevent errors if activation hook failed.
    $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) == $table_name;
    $training_data = [];
    if ($table_exists) {
        $training_data = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY time DESC" );
    }
    ?>
    <div class="wrap">
        <h1>
            <span class="dashicons dashicons-database-view" style="margin-right: 10px;"></span>AI Training Data
            <?php if ( ! empty( $training_data ) ) : ?>
                <a href="#" id="aicb-export-button" class="page-title-action">Export for Training</a>
            <?php endif; ?>
        </h1>
        <p>This table contains the questions and answers from visitor interactions with the Gemini API. You can review, edit, or delete entries here. Use the "Export" button to download this data in JSONL format, ready for fine-tuning a model in Google AI Studio.</p>

        <form id="training-data-form" method="post">
            <input type="hidden" name="page" value="aicb-training" />
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 20%;">Time</th>
                        <th>Question</th>
                        <th>Answer</th>
                        <th style="width: 8%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $training_data ) ) : ?>
                        <?php foreach ( $training_data as $row ) : ?>
                            <tr id="entry-<?php echo esc_attr( $row->id ); ?>">
                                <td><?php echo esc_html( $row->id ); ?></td>
                                <td><?php echo esc_html( $row->time ); ?></td>
                                <td><?php echo esc_html( $row->question ); ?></td>
                                <td><?php echo esc_html( wp_trim_words( $row->answer, 30, '...' ) ); ?></td>
                                <td><a href="#" class="aicb-delete-entry" data-id="<?php echo esc_attr( $row->id ); ?>">Delete</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">No training data has been collected yet. Data is saved when a visitor gets a successful answer from the Gemini API.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
    <?php
}
