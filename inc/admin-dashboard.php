<?php
/**
 * Admin Dashboard Pages
 *
 * This file creates all the backend pages for the AI-Wp plugin,
 * allowing the site administrator to view analytics, manage memberships,
 * review leads and suggestions, and see training data.
 *
 * @package AI-Wp
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Registers all the admin pages and sub-pages for the plugin.
 */
add_action( 'admin_menu', 'aicb_register_admin_pages' );
function aicb_register_admin_pages() {
    add_menu_page(
        'AI Chatbox',
        'AI Chatbox',
        'manage_options',
        'aicb-analytics',
        'aicb_render_analytics_page',
        'dashicons-format-chat',
        26
    );
    add_submenu_page(
        'aicb-analytics',
        'Analytics',
        'Analytics',
        'manage_options',
        'aicb-analytics',
        'aicb_render_analytics_page'
    );
    add_submenu_page(
        'aicb-analytics',
        'Membership',
        'Membership',
        'manage_options',
        'aicb-membership',
        'aicb_render_membership_page'
    );
    add_submenu_page(
        'aicb-analytics',
        'Leads',
        'Leads',
        'manage_options',
        'aicb-leads',
        'aicb_render_leads_page'
    );
    add_submenu_page(
        'aicb-analytics',
        'Suggestions',
        'Suggestions',
        'manage_options',
        'aicb-suggestions',
        'aicb_render_suggestions_page'
    );
    add_submenu_page(
        'aicb-analytics',
        'Training Data',
        'Training Data',
        'manage_options',
        'aicb-training',
        'aicb_render_training_page' // This now correctly calls the function from training-page.php
    );
    add_submenu_page(
        'aicb-analytics',
        'Settings',
        'Settings',
        'manage_options',
        'aicb-settings',
        'aicb_render_settings_page'
    );
}

/**
 * Renders the Membership management page.
 */
function aicb_render_membership_page() {
    global $wpdb;
    $packages_table = $wpdb->prefix . 'aicb_membership_packages';
    $members_table = $wpdb->prefix . 'aicb_premium_members';

    // Handle the form submission for adding a new package.
    if ( isset( $_POST['add_package'] ) && isset( $_POST['aicb_add_package_nonce_field'] ) && wp_verify_nonce( $_POST['aicb_add_package_nonce_field'], 'aicb_add_package_nonce' ) ) {
        $package_name = sanitize_text_field( $_POST['package_name'] );
        $package_price = floatval( $_POST['package_price'] );
        $package_duration = intval( $_POST['package_duration'] );
        $package_features = isset($_POST['package_features']) ? array_map('sanitize_text_field', $_POST['package_features']) : [];
        $serialized_features = serialize($package_features);

        if ( ! empty( $package_name ) && $package_price >= 0 && $package_duration > 0 ) {
            $wpdb->insert(
                $packages_table,
                array(
                    'name' => $package_name,
                    'price' => $package_price,
                    'duration' => $package_duration,
                    'features' => $serialized_features
                )
            );
            echo '<div class="notice notice-success is-dismissible"><p>New package added successfully!</p></div>';
        } else {
             echo '<div class="notice notice-error is-dismissible"><p>Please fill out all package fields correctly.</p></div>';
        }
    }

    // Fetch data from the database.
    $packages = $wpdb->get_results( "SELECT * FROM $packages_table ORDER BY price ASC" );
    $members = $wpdb->get_results( "SELECT * FROM $members_table" );
    $options = get_option('aicb_settings');
    $available_features_raw = isset($options['aicb_package_features_list']) ? $options['aicb_package_features_list'] : '';
    $available_features = array_filter(array_map('trim', explode("\n", $available_features_raw)));
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-groups" style="margin-right: 10px;"></span>Premium Membership</h1>
        <p>This page allows you to manage your premium members and subscription packages.</p>
        
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <h2>Add New Package</h2>
                    <form method="post" class="validate">
                        <?php wp_nonce_field('aicb_add_package_nonce', 'aicb_add_package_nonce_field'); ?>
                        <div class="form-field">
                            <label for="package_name">Package Name</label>
                            <input type="text" name="package_name" id="package_name" required />
                        </div>
                        <div class="form-field">
                            <label for="package_price">Price ($)</label>
                            <input type="number" name="package_price" id="package_price" step="0.01" required />
                        </div>
                        <div class="form-field">
                            <label for="package_duration">Duration (in days)</label>
                            <input type="number" name="package_duration" id="package_duration" required />
                        </div>
                        <div class="form-field">
                            <label>Package Features</label>
                            <?php if (!empty($available_features)): ?>
                                <?php foreach ($available_features as $feature): ?>
                                    <label style="display: block;"><input type="checkbox" name="package_features[]" value="<?php echo esc_attr($feature); ?>"> <?php echo esc_html($feature); ?></label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No features defined. Please add some in the <a href="?page=aicb-settings&tab=membership">Membership Settings</a>.</p>
                            <?php endif; ?>
                        </div>
                        <?php submit_button('Add Package', 'primary', 'add_package'); ?>
                    </form>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <h2>Current Packages</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Features</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($packages) : ?>
                                <?php foreach ($packages as $package) : ?>
                                    <tr>
                                        <td><?php echo esc_html($package->name); ?></td>
                                        <td>$<?php echo esc_html($package->price); ?></td>
                                        <td><?php echo esc_html($package->duration); ?> days</td>
                                        <td>
                                            <?php
                                            $features = unserialize($package->features);
                                            if (!empty($features) && is_array($features)) {
                                                echo '<ul>';
                                                foreach ($features as $feature) {
                                                    echo '<li>' . esc_html($feature) . '</li>';
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4">No packages have been created yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <hr class="wp-header-end" style="margin-top: 20px;" />
        
        <h2>Current Members</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Membership Level</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($members) : ?>
                    <?php foreach ($members as $member) : ?>
                        <tr>
                            <td><?php echo esc_html(get_userdata($member->user_id)->user_login); ?></td>
                            <td><?php echo esc_html($member->membership_level); ?></td>
                            <td><?php echo esc_html($member->start_date); ?></td>
                            <td><?php echo esc_html($member->end_date); ?></td>
                            <td><?php echo esc_html($member->status); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5">No members found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Renders the Analytics dashboard page.
 */
function aicb_render_analytics_page() {
    global $wpdb;
    $activity_table = $wpdb->prefix . 'aicb_activity_log';
    $total_events = $wpdb->get_var( "SELECT COUNT(*) FROM $activity_table" );
    ?>
    <div class="wrap">
        <h1>
            <span class="dashicons-chart-area" style="margin-right: 10px;"></span>Chatbot Analytics
            <?php if ( $total_events > 0 ) : ?><span class="aicb-live-indicator"></span><?php endif; ?>
        </h1>
        
        <?php if ( $total_events == 0 ) : ?>
            <div class="notice notice-info is-dismissible" style="padding: 1rem 1.5rem; border-left-color: #2271b1; background: #f6f7f7;">
                <h3 style="margin-top: 0;">Your Analytics Dashboard is Ready!</h3>
                <p>No activity has been recorded yet. Interact with the chatbox on your site to see data here.</p>
            </div>
        <?php else : ?>
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div class="postbox-container" style="width: 100%; margin-bottom: 20px;"><div class="postbox"><h2 class="hndle"><span>Searches per Day (Last 30 Days)</span></h2><div class="inside"><div style="height: 250px;"><canvas id="aicb-searches-by-day-chart"></canvas></div></div></div></div>
                    <div class="postbox-container" style="width: 49%; float: left; margin-right: 2%;"><div class="postbox"><h2 class="hndle"><span>Searches by Device</span></h2><div class="inside"><div style="height: 250px;"><canvas id="aicb-searches-by-device-chart"></canvas></div></div></div></div>
                    <div class="postbox-container" style="width: 49%; float: left;"><div class="postbox"><h2 class="hndle"><span>Top 5 Countries</span></h2><div class="inside"><div style="height: 250px;"><canvas id="aicb-searches-by-country-chart"></canvas></div></div></div></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Renders the Leads page.
 */
function aicb_render_leads_page() {
    global $wpdb;
    $leads_table = $wpdb->prefix . 'aicb_leads';
    $leads = $wpdb->get_results( "SELECT * FROM $leads_table ORDER BY time DESC" );
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-star-filled" style="margin-right: 10px;"></span>Captured Leads</h1>
        <p>This table lists conversations that the AI has flagged as potential sales leads based on user intent.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:150px;">Time</th>
                    <th>Initial Query</th>
                    <th style="width:100px;">Sentiment</th>
                    <th style="width:120px;">Visitor IP</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($leads) { foreach ($leads as $lead) { ?>
                <tr>
                    <td><?php echo esc_html($lead->time); ?></td>
                    <td><?php echo esc_html($lead->user_query); ?></td>
                    <td><?php echo esc_html(ucfirst($lead->sentiment)); ?></td>
                    <td><?php echo esc_html($lead->visitor_ip); ?></td>
                    <td><a href="#" class="view-conversation" data-conversation='<?php echo esc_attr($lead->conversation_history); ?>'>View Details</a></td>
                </tr>
            <?php } } else { echo '<tr><td colspan="5" style="text-align: center; padding: 20px;">No leads have been captured yet.</td></tr>'; } ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Renders the Suggestions page.
 */
function aicb_render_suggestions_page() {
    global $wpdb;
    $suggestions_table = $wpdb->prefix . 'aicb_suggestions';
    $suggestions = $wpdb->get_results( "SELECT * FROM $suggestions_table WHERE status = 'pending' ORDER BY time DESC" );
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-yes-alt" style="margin-right: 10px;"></span>User Suggestions</h1>
        <p>Review user-submitted answers. Approving a suggestion will add it to your Knowledge Base, improving the chatbot's future responses.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:150px;">Time</th>
                    <th>Original Question</th>
                    <th>Suggested Answer</th>
                    <th style="width:120px;">Visitor IP</th>
                    <th style="width:180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($suggestions) { foreach ($suggestions as $suggestion) { ?>
                <tr id="suggestion-<?php echo esc_attr($suggestion->id); ?>">
                    <td><?php echo esc_html($suggestion->time); ?></td>
                    <td><?php echo esc_html($suggestion->original_question); ?></td>
                    <td><?php echo esc_html(wp_trim_words($suggestion->suggested_answer, 20, '...')); ?></td>
                    <td><?php echo esc_html($suggestion->visitor_ip); ?></td>
                    <td>
                        <button class="button button-primary aicb-suggestion-action" data-action="approve" data-id="<?php echo esc_attr($suggestion->id); ?>">Approve</button>
                        <button class="button aicb-suggestion-action" data-action="delete" data-id="<?php echo esc_attr($suggestion->id); ?>">Delete</button>
                    </td>
                </tr>
            <?php } } else { echo '<tr><td colspan="5" style="text-align: center; padding: 20px;">No pending suggestions.</td></tr>'; } ?>
            </tbody>
        </table>
    </div>
    <?php
}
