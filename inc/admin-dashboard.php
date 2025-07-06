<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'aicb_register_admin_pages' );
function aicb_register_admin_pages() {
    add_menu_page( 'AI Chatbox', 'AI Chatbox', 'manage_options', 'aicb-analytics', 'aicb_render_analytics_page', 'dashicons-format-chat', 26 );
    add_submenu_page( 'aicb-analytics', 'Analytics', 'Analytics', 'manage_options', 'aicb-analytics', 'aicb_render_analytics_page' );
    add_submenu_page( 'aicb-analytics', 'Training Data', 'Training Data', 'manage_options', 'aicb-training', 'aicb_render_training_page' );
    add_submenu_page( 'aicb-analytics', 'Settings', 'Settings', 'manage_options', 'aicb-settings', 'aicb_render_settings_page' );
}

function aicb_render_analytics_page() {
    global $wpdb;
    $activity_table = $wpdb->prefix . 'aicb_activity_log';
    $total_events = $wpdb->get_var( "SELECT COUNT(*) FROM $activity_table" );
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'searches';
    ?>
    <div class="wrap">
        <h1>
            <span class="dashicons-chart-area" style="margin-right: 10px;"></span>Chatbot Analytics
            <?php if ( $total_events > 0 ) : ?><span class="aicb-live-indicator"></span><?php endif; ?>
        </h1>
        
        <?php if ( $total_events == 0 ) : ?>
            <div class="notice notice-info is-dismissible" style="padding: 1rem 1.5rem; border-left-color: #2271b1; background: #f6f7f7;">
                <h3 style="margin-top: 0;">Your Analytics Dashboard is Ready!</h3>
                <p>No activity has been recorded yet. To start seeing your analytics, follow these steps:</p>
                <ol style="list-style-type: decimal; padding-left: 20px;">
                    <li>Go to your website's frontend (the public-facing part of your site).</li>
                    <li>Open the chatbox and ask a few questions.</li>
                    <li>Come back to this page. The charts and activity feed will automatically populate with data.</li>
                </ol>
            </div>
        <?php else : ?>
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div class="postbox-container" style="width: 100%; margin-bottom: 20px;"><div class="postbox"><h2 class="hndle"><span>Searches per Day (Last 30 Days)</span></h2><div class="inside"><div style="height: 250px;"><canvas id="aicb-searches-by-day-chart"></canvas></div></div></div></div>
                    <div class="postbox-container" style="width: 49%; float: left; margin-right: 2%;"><div class="postbox"><h2 class="hndle"><span>Searches by Device</span></h2><div class="inside"><div style="height: 250px;"><canvas id="aicb-searches-by-device-chart"></canvas></div></div></div></div>
                    <div class="postbox-container" style="width: 49%; float: left;"><div class="postbox"><h2 class="hndle"><span>Searches by Country</span></h2><div class="inside"><div style="height: 250px;"><canvas id="aicb-searches-by-country-chart"></canvas></div></div></div></div>
                    <div style="clear: both;"></div>
                </div>
            </div>

            <div id="dashboard-widgets-wrap" style="margin-top: 20px;">
                <h2 class="nav-tab-wrapper">
                    <a href="?page=aicb-analytics&tab=searches" class="nav-tab <?php echo $active_tab == 'searches' ? 'nav-tab-active' : ''; ?>">Recent Searches</a>
                    <a href="?page=aicb-analytics&tab=clicks" class="nav-tab <?php echo $active_tab == 'clicks' ? 'nav-tab-active' : ''; ?>">Links Clicked</a>
                    <a href="?page=aicb-analytics&tab=ratings" class="nav-tab <?php echo $active_tab == 'ratings' ? 'nav-tab-active' : ''; ?>">Ratings</a>
                </h2>
                <div class="postbox-container" style="width: 100%; margin-top: 10px;">
                    <div class="postbox">
                        <div class="inside">
                            <?php 
                            switch ($active_tab) {
                                case 'clicks':
                                    $clicks = $wpdb->get_results( "SELECT * FROM $activity_table WHERE event_type = 'click' ORDER BY time DESC LIMIT 100" );
                                    ?>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead><tr><th style="width:180px;">Time</th><th>URL Clicked</th><th style="width:150px;">Country</th><th style="width:120px;">Device</th></tr></thead>
                                        <tbody>
                                        <?php if ($clicks) { foreach ($clicks as $log) { 
                                            echo '<tr><td>' . esc_html($log->time) . '</td><td><a href="' . esc_url($log->content) . '" target="_blank">' . esc_html($log->content) . '</a></td><td>' . esc_html($log->country) . '</td><td>' . esc_html($log->device) . '</td></tr>'; 
                                        } } else { echo '<tr><td colspan="4" style="text-align: center; padding: 20px;">No link clicks have been recorded yet.</td></tr>'; } ?>
                                        </tbody>
                                    </table>
                                    <?php
                                    break;
                                
                                case 'ratings':
                                    $ratings = $wpdb->get_results( "SELECT * FROM $activity_table WHERE event_type = 'rating' OR event_type = 'content_rating' ORDER BY time DESC LIMIT 100" );
                                    ?>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th style="width:180px;">Time</th>
                                                <th>Context</th>
                                                <th style="width:120px;">Type</th>
                                                <th style="width:100px;">Rating</th>
                                                <th style="width:150px;">Country</th>
                                                <th style="width:120px;">Device</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php 
                                        if ($ratings) { 
                                            foreach ($ratings as $log) {
                                                $context = '';
                                                $rating_part = '';
                                                if ($log->event_type == 'rating') {
                                                    $type = 'Answer';
                                                    $parts = explode(' | Question: ', $log->content);
                                                    $rating_part = isset($parts[0]) ? str_replace('Rating: ', '', $parts[0]) : 'N/A';
                                                    $context = '<em>Question:</em> ' . (isset($parts[1]) ? esc_html($parts[1]) : '(Not captured)');
                                                } else { // content_rating
                                                    $type = 'Content';
                                                    $parts = explode(' | Content: ', $log->content);
                                                    $rating_part = isset($parts[0]) ? str_replace('Rating: ', '', $parts[0]) : 'N/A';
                                                    $context = '<em>Article:</em> ' . (isset($parts[1]) ? esc_html($parts[1]) : '(Not captured)');
                                                }

                                                $rating_icon = ($rating_part == 'up') ? '👍' : '👎';
                                                
                                                echo '<tr>';
                                                echo '<td>' . esc_html($log->time) . '</td>';
                                                echo '<td>' . $context . '</td>';
                                                echo '<td>' . esc_html($type) . '</td>';
                                                echo '<td>' . esc_html($rating_icon) . '</td>';
                                                echo '<td>' . esc_html($log->country) . '</td>';
                                                echo '<td>' . esc_html($log->device) . '</td>';
                                                echo '</tr>'; 
                                            } 
                                        } else { 
                                            echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">No ratings have been recorded yet.</td></tr>'; 
                                        } 
                                        ?>
                                        </tbody>
                                    </table>
                                    <?php
                                    break;

                                case 'searches':
                                default:
                                    $searches = $wpdb->get_results( "SELECT * FROM $activity_table WHERE event_type = 'search' ORDER BY time DESC LIMIT 100" );
                                    ?>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead><tr><th style="width:180px;">Time</th><th>Search Query</th><th style="width:150px;">Country</th><th style="width:120px;">Device</th></tr></thead>
                                        <tbody>
                                        <?php if ($searches) { foreach ($searches as $log) { 
                                            echo '<tr><td>' . esc_html($log->time) . '</td><td>' . esc_html($log->content) . '</td><td>' . esc_html($log->country) . '</td><td>' . esc_html($log->device) . '</td></tr>'; 
                                        } } else { echo '<tr><td colspan="4" style="text-align: center; padding: 20px;">No searches have been recorded yet.</td></tr>'; } ?>
                                        </tbody>
                                    </table>
                                    <?php
                                    break;
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
