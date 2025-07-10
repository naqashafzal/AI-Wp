<?php
/**
 * Admin Settings Page
 *
 * This file creates the main settings page for the AI-Wp plugin,
 * complete with a tabbed interface for easy navigation and configuration of all features.
 *
 * @package AI-Wp
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Renders the main settings page wrapper and handles tab navigation.
 */
function aicb_render_settings_page() {
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'api';
    ?>
    <div class="wrap aicb-settings-wrap">
        <div class="aicb-settings-inner-wrap">
            <div class="aicb-settings-sidebar">
                <div class="aicb-sidebar-header">
                    <span class="dashicons dashicons-format-chat"></span>
                    AI WP
                </div>
                <ul class="aicb-settings-menu">
                    <li><a href="?page=aicb-analytics"><span class="dashicons dashicons-chart-area"></span> Analytics</a></li>
                    <li><a href="?page=aicb-membership"><span class="dashicons dashicons-groups"></span> Membership</a></li>
                    <li><a href="?page=aicb-leads"><span class="dashicons dashicons-star-filled"></span> Leads</a></li>
                    <li><a href="?page=aicb-suggestions"><span class="dashicons dashicons-yes-alt"></span> Suggestions</a></li>
                    <li><a href="?page=aicb-training"><span class="dashicons dashicons-database-view"></span> Training Data</a></li>
                    <li class="aicb-menu-separator"><hr></li>
                    <li><a href="?page=aicb-settings&tab=api" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-network"></span> API Settings</a></li>
                    <li><a href="?page=aicb-settings&tab=features" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-generic"></span> Feature Control</a></li>
                    <li><a href="?page=aicb-settings&tab=display" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-appearance"></span> Display & Ads</a></li>
                    <li><a href="?page=aicb-settings&tab=persona" class="aicb-settings-menu-item"><span class="dashicons dashicons-businessman"></span> Chatbot Persona</a></li>
                    <li><a href="?page=aicb-settings&tab=branding" class="aicb-settings-menu-item"><span class="dashicons dashicons-art"></span> Branding</a></li>
                    <li><a href="?page=aicb-settings&tab=personalization" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-users"></span> Personalization</a></li>
                    <li><a href="?page=aicb-settings&tab=lead_generation" class="aicb-settings-menu-item"><span class="dashicons dashicons-money"></span> Lead Generation</a></li>
                    <li><a href="?page=aicb-settings&tab=membership" class="aicb-settings-menu-item"><span class="dashicons dashicons-id-alt"></span> Membership</a></li>
                    <li><a href="?page=aicb-settings&tab=integrations" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-plugins"></span> Integrations</a></li>
                    <li><a href="?page=aicb-settings&tab=misc" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-settings"></span> Miscellaneous</a></li>
                    <li><a href="?page=aicb-settings&tab=shortcodes"><span class="dashicons dashicons-editor-code"></span> Shortcodes</a></li>
                </ul>
                <div class="aicb-settings-footer">
                    Developed by <a href="https://nullpk.com" target="_blank">Nullpk</a>
                </div>
            </div>
            <div class="aicb-settings-content">
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'aicb_options_group' );
                    echo '<input type="hidden" name="aicb_active_tab" value="' . esc_attr( $active_tab ) . '" />';
                    
                    // Render the content for the active tab
                    call_user_func('aicb_render_settings_tab_' . $active_tab);

                    // Don't show save button on the shortcodes tab
                    if ($active_tab !== 'shortcodes') {
                        submit_button( 'Save Settings' );
                    }
                    ?>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Generic function to render a settings tab by calling its section.
 */
function aicb_render_tab_content($tab_name, $title) {
    ?>
    <div class="aicb-settings-card">
        <h2 class="aicb-card-header"><?php echo esc_html($title); ?></h2>
        <div class="aicb-card-content">
            <table class="form-table">
                <?php do_settings_sections('aicb_' . $tab_name . '_settings'); ?>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Special render function for the display tab to handle multiple sections.
 */
function aicb_render_settings_tab_display() {
    ?>
    <div class="aicb-settings-card">
        <h2 class="aicb-card-header">General Display</h2>
        <div class="aicb-card-content">
            <table class="form-table">
                <?php do_settings_sections('aicb_display_settings_general'); ?>
            </table>
        </div>
    </div>
    <div class="aicb-settings-card">
        <h2 class="aicb-card-header">Floating Ad Bar</h2>
        <div class="aicb-card-content">
            <table class="form-table">
                <?php do_settings_sections('aicb_display_settings_floating_ad'); ?>
            </table>
        </div>
    </div>
    <div class="aicb-settings-card">
        <h2 class="aicb-card-header">Modal Content</h2>
        <div class="aicb-card-content">
            <p class="description">Customize the content of the pop-up modal for premium features.</p>
            <table class="form-table">
                <?php do_settings_sections('aicb_display_settings_modal'); ?>
            </table>
        </div>
    </div>
    <?php
}


// Create individual functions for each tab to call the generic renderer
function aicb_render_settings_tab_api() { aicb_render_tab_content('api', 'API Settings'); }
function aicb_render_settings_tab_features() { aicb_render_tab_content('features', 'Feature Control'); }
function aicb_render_settings_tab_persona() { aicb_render_tab_content('persona', 'Chatbot Persona'); }
function aicb_render_settings_tab_branding() { aicb_render_tab_content('branding', 'Branding'); }
function aicb_render_settings_tab_personalization() { aicb_render_tab_content('personalization', 'Personalization'); }
function aicb_render_settings_tab_lead_generation() { aicb_render_tab_content('lead_generation', 'Lead & Sentiment Analysis'); }
function aicb_render_settings_tab_membership() { aicb_render_tab_content('membership', 'Premium Membership'); }
function aicb_render_settings_tab_integrations() { aicb_render_tab_content('integrations', 'Integrations'); }
function aicb_render_settings_tab_misc() { aicb_render_tab_content('misc', 'Miscellaneous'); }

/**
 * Renders the content for the "Shortcodes" tab.
 */
function aicb_render_settings_tab_shortcodes() {
    ?>
    <div class="aicb-settings-card">
        <h2 class="aicb-card-header"><span class="dashicons dashicons-editor-code"></span>Available Shortcodes</h2>
        <div class="aicb-card-content">
            <p>You can use these shortcodes in your posts, pages, or widgets.</p>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 1rem;">
                <thead><tr><th style="width: 30%;">Shortcode</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>[ai_chatbox]</code></td><td>Displays the main chatbox interface.</td></tr>
                    <tr><td><code>[intelligent_content_links]</code></td><td>Displays a list of recommended articles based on the visitor's recent Browse history.</td></tr>
                    <tr><td><code>[aicb_premium_content]...[/aicb_premium_content]</code></td><td>Restricts the enclosed content to premium members only.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}


/**
 * Registers all settings, sections, and fields for the plugin.
 */
add_action( 'admin_init', 'aicb_register_settings' );
function aicb_register_settings() {
    register_setting( 'aicb_options_group', 'aicb_settings', 'aicb_sanitize_settings' );
    
    // --- API Settings ---
    add_settings_section('aicb_api_settings', null, null, 'aicb_api_settings');
    add_settings_field('aicb_enable_gemini', 'Enable Gemini API', 'aicb_enable_gemini_callback', 'aicb_api_settings', 'aicb_api_settings');
    add_settings_field('aicb_gemini_api_key', 'Google Gemini API Key', 'aicb_gemini_api_key_callback', 'aicb_api_settings', 'aicb_api_settings');
    add_settings_field('aicb_tuned_model_name', 'Fine-Tuned Model Name', 'aicb_tuned_model_name_callback', 'aicb_api_settings', 'aicb_api_settings');

    // --- Feature Control ---
    add_settings_section('aicb_features_settings', null, null, 'aicb_features_settings');
    add_settings_field('aicb_enable_floating_chatbox', 'Enable Floating Chatbox', 'aicb_enable_floating_chatbox_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_launcher_notification', 'Launcher Notification Badge', 'aicb_launcher_notification_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_launcher_cta_text', 'Launcher Call to Action', 'aicb_launcher_cta_text_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_front_page_takeover', 'Enable Chat Mode on Front Page', 'aicb_front_page_takeover_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_enable_knowledge_base', 'Enable Knowledge Base', 'aicb_enable_knowledge_base_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_enable_autocomplete', 'Enable Autocomplete Suggestions', 'aicb_enable_autocomplete_callback', 'aicb_features_settings', 'aicb_features_settings');
    
    // --- Display & Ads ---
    // General Display Settings
    add_settings_section('aicb_display_settings_general', null, null, 'aicb_display_settings_general');
    add_settings_field('aicb_content_display_mode', 'Content Link Behavior', 'aicb_content_display_mode_callback', 'aicb_display_settings_general', 'aicb_display_settings_general');
    add_settings_field('aicb_show_sources', 'Show Source Links', 'aicb_show_sources_callback', 'aicb_display_settings_general', 'aicb_display_settings_general');
    add_settings_field('aicb_enable_post_ratings', 'Enable Post/Page Ratings', 'aicb_enable_post_ratings_callback', 'aicb_display_settings_general', 'aicb_display_settings_general');
    add_settings_field('aicb_main_download_button_text', 'Main Download Button Text', 'aicb_main_download_button_text_callback', 'aicb_display_settings_general', 'aicb_display_settings_general');
    add_settings_field('aicb_main_download_button_icon', 'Main Download Button Icon', 'aicb_main_download_button_icon_callback', 'aicb_display_settings_general', 'aicb_display_settings_general');
    add_settings_field('aicb_chat_download_button_text', 'Chat Download Button Text', 'aicb_chat_download_button_text_callback', 'aicb_display_settings_general', 'aicb_display_settings_general');
    add_settings_field('aicb_chat_download_button_icon', 'Chat Download Button Icon', 'aicb_chat_download_button_icon_callback', 'aicb_display_settings_general', 'aicb_display_settings_general');
    add_settings_field('aicb_show_ad', 'Show In-Chat Ad', 'aicb_show_ad_callback', 'aicb_display_settings_general', 'aicb_display_settings_general');
    add_settings_field('aicb_custom_ad_code', 'Custom In-Chat Ad Code', 'aicb_custom_ad_code_callback', 'aicb_display_settings_general', 'aicb_display_settings_general');
    
    // Floating Ad Settings
    add_settings_section('aicb_display_settings_floating_ad', null, null, 'aicb_display_settings_floating_ad');
    add_settings_field('aicb_enable_floating_ad', 'Enable Floating Ad Bar', 'aicb_enable_floating_ad_callback', 'aicb_display_settings_floating_ad', 'aicb_display_settings_floating_ad');
    add_settings_field('aicb_floating_ad_position', 'Position', 'aicb_floating_ad_position_callback', 'aicb_display_settings_floating_ad', 'aicb_display_settings_floating_ad');
    add_settings_field('aicb_floating_ad_custom_code', 'Custom Ad Code', 'aicb_floating_ad_custom_code_callback', 'aicb_display_settings_floating_ad', 'aicb_display_settings_floating_ad');
    add_settings_field('aicb_exclude_floating_ad_pages', 'Exclude Ad on Specific Pages', 'aicb_exclude_floating_ad_pages_callback', 'aicb_display_settings_floating_ad', 'aicb_display_settings_floating_ad');
    
    // Modal Settings
    add_settings_section('aicb_display_settings_modal', null, null, 'aicb_display_settings_modal');
    add_settings_field('aicb_premium_modal_title', 'Upgrade Modal Title', 'aicb_premium_modal_title_callback', 'aicb_display_settings_modal', 'aicb_display_settings_modal');
    add_settings_field('aicb_premium_modal_message', 'Upgrade Modal Message', 'aicb_premium_modal_message_callback', 'aicb_display_settings_modal', 'aicb_display_settings_modal');
    add_settings_field('aicb_premium_modal_button_text', 'Upgrade Modal Button Text', 'aicb_premium_modal_button_text_callback', 'aicb_display_settings_modal', 'aicb_display_settings_modal');
    add_settings_field('aicb_premium_modal_button_url', 'Upgrade Modal Button URL', 'aicb_premium_modal_button_url_callback', 'aicb_display_settings_modal', 'aicb_display_settings_modal');
    
    // --- Persona ---
    add_settings_section('aicb_persona_settings', null, null, 'aicb_persona_settings');
    add_settings_field('aicb_website_specialty', 'Website Specialty', 'aicb_website_specialty_callback', 'aicb_persona_settings', 'aicb_persona_settings');
    add_settings_field('aicb_target_audience', 'Target Audience', 'aicb_target_audience_callback', 'aicb_persona_settings', 'aicb_persona_settings');
    add_settings_field('aicb_communication_style', 'Communication Style', 'aicb_communication_style_callback', 'aicb_persona_settings', 'aicb_persona_settings');
    add_settings_field('aicb_system_prompt', 'System Prompt (Master AI Instructions)', 'aicb_system_prompt_callback', 'aicb_persona_settings', 'aicb_persona_settings');
    add_settings_field('aicb_enable_memory', 'Enable Conversational Memory', 'aicb_enable_memory_callback', 'aicb_persona_settings', 'aicb_persona_settings');

    // --- Branding ---
    add_settings_section('aicb_branding_settings', null, null, 'aicb_branding_settings');
    add_settings_field('aicb_sidebar_logo_url', 'Sidebar Logo URL', 'aicb_sidebar_logo_url_callback', 'aicb_branding_settings', 'aicb_branding_settings');

    // --- Personalization ---
    add_settings_section('aicb_personalization_settings', null, null, 'aicb_personalization_settings');
    add_settings_field('aicb_enable_personalized_welcome', 'Enable Personalized Welcome', 'aicb_enable_personalized_welcome_callback', 'aicb_personalization_settings', 'aicb_personalization_settings');
    add_settings_field('aicb_show_related_content', 'Show Related Content in Welcome', 'aicb_show_related_content_callback', 'aicb_personalization_settings', 'aicb_personalization_settings');
    add_settings_field('aicb_welcome_prompt', 'Custom Welcome Template', 'aicb_welcome_prompt_callback', 'aicb_personalization_settings', 'aicb_personalization_settings');
    
    // --- Lead Generation ---
    add_settings_section('aicb_lead_generation_settings', null, null, 'aicb_lead_generation_settings');
    add_settings_field('aicb_enable_lead_analysis', 'Enable Lead Analysis', 'aicb_enable_lead_analysis_callback', 'aicb_lead_generation_settings', 'aicb_lead_generation_settings');

    // --- Membership ---
    add_settings_section('aicb_membership_settings', null, null, 'aicb_membership_settings');
    add_settings_field('aicb_enable_membership', 'Enable Premium Membership', 'aicb_enable_membership_callback', 'aicb_membership_settings', 'aicb_membership_settings');
    add_settings_field('aicb_package_features_list', 'Available Package Features', 'aicb_package_features_list_callback', 'aicb_membership_settings', 'aicb_membership_settings');
    add_settings_field('aicb_payment_url', 'Payment URL', 'aicb_payment_url_callback', 'aicb_membership_settings', 'aicb_membership_settings');
    
    // --- Integrations ---
    add_settings_section('aicb_integrations_settings', null, null, 'aicb_integrations_settings');
    add_settings_field('aicb_paypal_email', 'PayPal Email', 'aicb_paypal_email_callback', 'aicb_integrations_settings', 'aicb_integrations_settings');
    add_settings_field('aicb_stripe_secret_key', 'Stripe Secret Key', 'aicb_stripe_secret_key_callback', 'aicb_integrations_settings', 'aicb_integrations_settings');
    add_settings_field('aicb_mailchimp_api_key', 'Mailchimp API Key', 'aicb_mailchimp_api_key_callback', 'aicb_integrations_settings', 'aicb_integrations_settings');

    // --- Miscellaneous ---
    add_settings_section('aicb_misc_settings', null, null, 'aicb_misc_settings');
    add_settings_field('aicb_footer_text', 'Chatbox Footer Text', 'aicb_footer_text_callback', 'aicb_misc_settings', 'aicb_misc_settings');
}

/**
 * Sanitizes the settings input before saving to the database.
 */
function aicb_sanitize_settings( $input ) {
    $saved_options = get_option( 'aicb_settings', array() );
    $new_input = $saved_options; // Start with the old options

    // Get the active tab from the hidden input field
    $active_tab = isset($_POST['aicb_active_tab']) ? sanitize_key($_POST['aicb_active_tab']) : 'api';

    // A map of which settings belong to which tab
    $tab_fields = [
        'api' => ['aicb_enable_gemini', 'aicb_gemini_api_key', 'aicb_tuned_model_name'],
        'features' => ['aicb_enable_floating_chatbox', 'aicb_launcher_notification', 'aicb_launcher_cta_text', 'aicb_front_page_takeover', 'aicb_enable_knowledge_base', 'aicb_enable_autocomplete'],
        'display' => ['aicb_content_display_mode', 'aicb_show_sources', 'aicb_enable_post_ratings', 'aicb_main_download_button_text', 'aicb_main_download_button_icon', 'aicb_chat_download_button_text', 'aicb_chat_download_button_icon', 'aicb_show_ad', 'aicb_custom_ad_code', 'aicb_enable_floating_ad', 'aicb_floating_ad_position', 'aicb_floating_ad_custom_code', 'aicb_exclude_floating_ad_pages', 'aicb_premium_modal_title', 'aicb_premium_modal_message', 'aicb_premium_modal_button_text', 'aicb_premium_modal_button_url'],
        'persona' => ['aicb_website_specialty', 'aicb_target_audience', 'aicb_communication_style', 'aicb_system_prompt', 'aicb_enable_memory'],
        'branding' => ['aicb_sidebar_logo_url'],
        'personalization' => ['aicb_enable_personalized_welcome', 'aicb_show_related_content', 'aicb_welcome_prompt'],
        'lead_generation' => ['aicb_enable_lead_analysis'],
        'membership' => ['aicb_enable_membership', 'aicb_package_features_list', 'aicb_payment_url'],
        'integrations' => ['aicb_paypal_email', 'aicb_stripe_secret_key', 'aicb_mailchimp_api_key'],
        'misc' => ['aicb_footer_text'],
    ];

    // Handle checkboxes for the active tab
    $checkboxes_for_tab = [
        'api' => ['aicb_enable_gemini'],
        'features' => ['aicb_enable_floating_chatbox', 'aicb_front_page_takeover', 'aicb_enable_knowledge_base', 'aicb_enable_autocomplete'],
        'display' => ['aicb_show_sources', 'aicb_enable_post_ratings', 'aicb_show_ad', 'aicb_enable_floating_ad'],
        'persona' => ['aicb_enable_memory'],
        'personalization' => ['aicb_enable_personalized_welcome', 'aicb_show_related_content'],
        'lead_generation' => ['aicb_enable_lead_analysis'],
        'membership' => ['aicb_enable_membership'],
    ];

    if (isset($checkboxes_for_tab[$active_tab])) {
        foreach ($checkboxes_for_tab[$active_tab] as $checkbox) {
            $new_input[$checkbox] = isset($input[$checkbox]) ? 1 : 0;
        }
    }

    // Loop through the fields for the active tab and sanitize them
    if (isset($tab_fields[$active_tab])) {
        foreach ($tab_fields[$active_tab] as $field) {
            if (isset($input[$field])) {
                if (strpos($field, '_url') !== false || strpos($field, '_link') !== false) {
                    $new_input[$field] = esc_url_raw($input[$field]);
                } elseif (strpos($field, '_email') !== false) {
                    $new_input[$field] = sanitize_email($input[$field]);
                } elseif (strpos($field, '_prompt') !== false || strpos($field, '_list') !== false || strpos($field, '_message') !== false) {
                    $new_input[$field] = sanitize_textarea_field($input[$field]);
                } elseif (strpos($field, '_code') !== false) {
                    if (current_user_can('unfiltered_html')) {
                        $new_input[$field] = $input[$field];
                    } else {
                        $new_input[$field] = wp_kses_post($input[$field]);
                    }
                } else {
                    $new_input[$field] = sanitize_text_field($input[$field]);
                }
            }
        }
    }

    return $new_input;
}


/**
 * Renders a checkbox as a toggle switch.
 */
function aicb_render_toggle_switch($args) {
    $options = get_option('aicb_settings');
    $name = $args['name'];
    $checked = isset($options[$name]) ? $options[$name] : 0;
    echo '<label class="aicb-switch"><input type="checkbox" name="aicb_settings[' . esc_attr($name) . ']" value="1"' . checked(1, $checked, false) . '/><span class="aicb-slider"></span></label>';
    if (isset($args['description'])) {
        echo '<p class="description" style="margin-left: 60px;">' . esc_html($args['description']) . '</p>';
    }
}

// --- Field Callback Functions ---

function aicb_enable_gemini_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_gemini', 'description' => 'Enable AI-powered answers and features using the Gemini API.']); }
function aicb_gemini_api_key_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_gemini_api_key']) ? $options['aicb_gemini_api_key'] : '';
    echo '<div id="aicb_api_key_wrapper">';
    echo '<input type="text" id="aicb-gemini-api-key-field" name="aicb_settings[aicb_gemini_api_key]" value="' . esc_attr($value) . '" class="regular-text" style="width: 350px;"/>';
    echo '<button type="button" class="button button-secondary" id="aicb-test-api-button" style="margin-left: 10px;">Test Connection</button>';
    echo '<span id="aicb-api-test-result" style="margin-left: 10px; font-weight: bold;"></span>';
    echo '</div>';
}
function aicb_tuned_model_name_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_tuned_model_name']) ? $options['aicb_tuned_model_name'] : '';
    echo '<div id="aicb_tuned_model_wrapper">';
    echo '<input type="text" name="aicb_settings[aicb_tuned_model_name]" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g., my-tuned-model-1234" />';
    echo '<p class="description">Optional. If you have a fine-tuned model, enter its name here.</p>';
    echo '</div>';
}

function aicb_enable_membership_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_membership', 'description' => 'Enable the premium membership system.']); }
function aicb_package_features_list_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_package_features_list']) ? $options['aicb_package_features_list'] : '';
    echo '<textarea name="aicb_settings[aicb_package_features_list]" rows="5" class="large-text" placeholder="One feature per line...">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">Enter each available feature on a new line. These will be available to select when creating a new package.</p>';
}
function aicb_payment_url_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_payment_url']) ? $options['aicb_payment_url'] : '';
    echo '<input type="url" name="aicb_settings[aicb_payment_url]" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://example.com/payment" />';
    echo '<p class="description">The URL to your payment page. Users will be redirected here after email verification.</p>';
}

function aicb_paypal_email_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_paypal_email']) ? $options['aicb_paypal_email'] : '';
    echo '<input type="email" name="aicb_settings[aicb_paypal_email]" value="' . esc_attr($value) . '" class="regular-text" placeholder="your-paypal-email@example.com" />';
    echo '<p class="description">Enter your PayPal email address to accept payments.</p>';
}
function aicb_stripe_secret_key_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_stripe_secret_key']) ? $options['aicb_stripe_secret_key'] : '';
    echo '<input type="text" name="aicb_settings[aicb_stripe_secret_key]" value="' . esc_attr($value) . '" class="regular-text" placeholder="sk_test_..."/>';
    echo '<p class="description">Enter your Stripe secret key to accept payments.</p>';
}
function aicb_mailchimp_api_key_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_mailchimp_api_key']) ? $options['aicb_mailchimp_api_key'] : '';
    echo '<input type="text" name="aicb_settings[aicb_mailchimp_api_key]" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Enter your Mailchimp API key to sync your members with your mailing list.</p>';
}

function aicb_website_specialty_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_website_specialty']) ? $options['aicb_website_specialty'] : '';
    echo '<input type="text" name="aicb_settings[aicb_website_specialty]" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g., high-end digital cameras" />';
    echo '<p class="description">What is the main topic of your website? This gives the AI crucial context.</p>';
}
function aicb_target_audience_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_target_audience']) ? $options['aicb_target_audience'] : '';
    echo '<input type="text" name="aicb_settings[aicb_target_audience]" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g., professional photographers" />';
    echo '<p class="description">Describe your ideal visitor or customer.</p>';
}
function aicb_communication_style_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_communication_style']) ? $options['aicb_communication_style'] : 'Professional';
    ?>
    <select name="aicb_settings[aicb_communication_style]">
        <option value="Professional" <?php selected($value, 'Professional'); ?>>Professional & Expert</option>
        <option value="Friendly" <?php selected($value, 'Friendly'); ?>>Friendly & Casual</option>
        <option value="Enthusiastic" <?php selected($value, 'Enthusiastic'); ?>>Enthusiastic & Encouraging</option>
        <option value="Formal" <?php selected($value, 'Formal'); ?>>Formal & Academic</option>
    </select>
    <p class="description">Select the overall tone for the chatbot's responses.</p>
    <?php
}
function aicb_system_prompt_callback() {
    $options = get_option('aicb_settings');
    $default_prompt = "You are a specialized, world-class assistant for the website '{site_name}'.\n\n**Core Mission:** Your primary goal is to provide accurate, helpful, and well-structured answers to user questions.\n\n**Website & Audience Context:**\n-   **Website Specialty:** {specialty}\n-   **Target Audience:** {audience}\n-   **Communication Style:** {style}\n\n**Rules of Engagement & Persona:**\n1.  **Adopt the Persona:** Embody the chosen **Communication Style** in every response.\n2.  **Use Provided Context First:** If context from the website is provided, you **must** base your answer on it.\n3.  **Handling Lack of Context:** If no context is provided, answer using your general knowledge, while still maintaining your persona.\n4.  **Structure and Proactivity:** Structure your response for clarity using formatting like **bolding** and bullet points. If you provide a good answer, suggest a logical next question.\n5.  **No Self-Reference as 'AI':** Do not refer to yourself as an 'AI' or 'language model'.";
    $value = isset($options['aicb_system_prompt']) ? $options['aicb_system_prompt'] : $default_prompt;
    echo '<textarea name="aicb_settings[aicb_system_prompt]" rows="18" class="large-text">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">This is the master instruction for the AI. Use <code>{site_name}</code>, <code>{specialty}</code>, <code>{audience}</code>, and <code>{style}</code> to dynamically insert settings. A detailed prompt leads to better responses.</p>';
}
function aicb_enable_memory_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_memory', 'description' => 'Allow the chatbot to remember the last few messages in the conversation.']); }

function aicb_sidebar_logo_url_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_sidebar_logo_url']) ? $options['aicb_sidebar_logo_url'] : '';
    echo '<input type="url" name="aicb_settings[aicb_sidebar_logo_url]" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://example.com/logo.png" />';
    echo '<p class="description">Paste the full URL to your logo image. This will appear at the top of the sidebar.</p>';
}

function aicb_enable_personalized_welcome_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_personalized_welcome', 'description' => 'Track pages a user visits to create a personalized welcome message.']); }
function aicb_show_related_content_callback() { aicb_render_toggle_switch(['name' => 'aicb_show_related_content', 'description' => 'Include links to related content in the personalized welcome message.']); }
function aicb_welcome_prompt_callback() {
    $options = get_option('aicb_settings');
    $default_template = "Hello! I see you're interested in our {categories} articles. How can I help you today?";
    $value = isset($options['aicb_welcome_prompt']) && !empty($options['aicb_welcome_prompt']) ? $options['aicb_welcome_prompt'] : $default_template;
    echo '<textarea name="aicb_settings[aicb_welcome_prompt]" rows="4" class="large-text">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">Customize the welcome message. Use <code>{categories}</code> to insert the post categories the visitor has been viewing.</p>';
}

function aicb_enable_lead_analysis_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_lead_analysis', 'description' => 'Automatically analyze user queries for sentiment and buying intent.']); }

function aicb_enable_floating_chatbox_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_floating_chatbox', 'description' => 'Adds a chat bubble to the bottom corner of all pages.']); }
function aicb_launcher_notification_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_launcher_notification']) ? $options['aicb_launcher_notification'] : '1';
    echo '<input type="text" name="aicb_settings[aicb_launcher_notification]" value="' . esc_attr($value) . '" class="regular-text" style="width: 50px;" />';
    echo '<p class="description">Enter a number or symbol to display on the chat launcher icon. Leave blank to hide.</p>';
}
function aicb_launcher_cta_text_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_launcher_cta_text']) ? $options['aicb_launcher_cta_text'] : 'Have a question?';
    echo '<input type="text" name="aicb_settings[aicb_launcher_cta_text]" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Text to display next to the launcher to encourage clicks. Leave blank to hide.</p>';
}
function aicb_front_page_takeover_callback() { aicb_render_toggle_switch(['name' => 'aicb_front_page_takeover', 'description' => 'Replace the website homepage with the full-screen AI Chatbox interface.']); }
function aicb_enable_knowledge_base_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_knowledge_base', 'description' => 'Prioritize answers from your custom "Knowledge Base" before a general search.']); }
function aicb_enable_autocomplete_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_autocomplete', 'description' => 'Show a "ghost text" suggestion as the user types.']); }

function aicb_content_display_mode_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_content_display_mode']) ? $options['aicb_content_display_mode'] : 'in_chatbox';
    ?>
    <select name="aicb_settings[aicb_content_display_mode]">
        <option value="in_chatbox" <?php selected($value, 'in_chatbox'); ?>>Open in Chatbox Viewer</option>
        <option value="new_tab" <?php selected($value, 'new_tab'); ?>>Open in New Tab</option>
    </select>
    <p class="description">Choose how content links (like search results or sources) should open.</p>
    <?php
}
function aicb_main_download_button_text_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_main_download_button_text']) ? $options['aicb_main_download_button_text'] : 'Download Now';
    echo '<input type="text" name="aicb_settings[aicb_main_download_button_text]" value="' . esc_attr($value) . '" class="regular-text" />';
}
function aicb_main_download_button_icon_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_main_download_button_icon']) ? $options['aicb_main_download_button_icon'] : 'dashicons-download';
    echo '<input type="text" name="aicb_settings[aicb_main_download_button_icon]" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Enter a WordPress Dashicon class name. <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank">View available icons</a>.</p>';
}
function aicb_chat_download_button_text_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_chat_download_button_text']) ? $options['aicb_chat_download_button_text'] : 'Download';
    echo '<input type="text" name="aicb_settings[aicb_chat_download_button_text]" value="' . esc_attr($value) . '" class="regular-text" />';
}
function aicb_chat_download_button_icon_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_chat_download_button_icon']) ? $options['aicb_chat_download_button_icon'] : '✨';
    echo '<input type="text" name="aicb_settings[aicb_chat_download_button_icon]" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Enter a single emoji or icon character.</p>';
}
function aicb_show_sources_callback() { aicb_render_toggle_switch(['name' => 'aicb_show_sources', 'description' => 'Display links to the posts/pages used by Gemini to generate an answer.']); }
function aicb_show_ad_callback() { aicb_render_toggle_switch(['name' => 'aicb_show_ad', 'description' => 'Display an ad in a separate message bubble after every answer.']); }
function aicb_custom_ad_code_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_custom_ad_code']) ? $options['aicb_custom_ad_code'] : '';
    echo '<textarea name="aicb_settings[aicb_custom_ad_code]" rows="5" class="large-text" placeholder="Paste your ad code here...">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">This code has priority over ads from the "Ads" post type.</p>';
}
function aicb_enable_post_ratings_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_post_ratings', 'description' => 'Add a thumbs up/down rating section to the bottom of single posts and pages.']); }

// Floating Ad Callbacks
function aicb_enable_floating_ad_callback() { aicb_render_toggle_switch(['name' => 'aicb_enable_floating_ad', 'description' => 'Display a dismissible ad bar on your site.']); }
function aicb_floating_ad_position_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_floating_ad_position']) ? $options['aicb_floating_ad_position'] : 'bottom_bar';
    ?>
    <select name="aicb_settings[aicb_floating_ad_position]">
        <option value="bottom_bar" <?php selected($value, 'bottom_bar'); ?>>Bottom Bar</option>
        <option value="top_bar" <?php selected($value, 'top_bar'); ?>>Top Bar</option>
        <option value="bottom_left" <?php selected($value, 'bottom_left'); ?>>Bottom Left Corner</option>
        <option value="bottom_right" <?php selected($value, 'bottom_right'); ?>>Bottom Right Corner</option>
    </select>
    <?php
}
function aicb_floating_ad_custom_code_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_floating_ad_custom_code']) ? $options['aicb_floating_ad_custom_code'] : '';
    echo '<textarea name="aicb_settings[aicb_floating_ad_custom_code]" rows="5" class="large-text" placeholder="Paste your ad code here..."></textarea>';
    echo '<p class="description">This has priority over the "Ads" post type.</p>';
}
function aicb_exclude_floating_ad_pages_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_exclude_floating_ad_pages']) ? $options['aicb_exclude_floating_ad_pages'] : '';
    echo '<input type="text" name="aicb_settings[aicb_exclude_floating_ad_pages]" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g., 12, 345, 67" />';
    echo '<p class="description">Enter a comma-separated list of Post or Page IDs where you do NOT want the floating ad to appear.</p>';
}

// Modal Content Callbacks
function aicb_premium_modal_title_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_premium_modal_title']) ? $options['aicb_premium_modal_title'] : 'Premium Feature';
    echo '<input type="text" name="aicb_settings[aicb_premium_modal_title]" value="' . esc_attr($value) . '" class="regular-text" />';
}
function aicb_premium_modal_message_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_premium_modal_message']) ? $options['aicb_premium_modal_message'] : 'Upgrade your account to access this feature.';
    echo '<textarea name="aicb_settings[aicb_premium_modal_message]" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
}
function aicb_premium_modal_button_text_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_premium_modal_button_text']) ? $options['aicb_premium_modal_button_text'] : 'Upgrade Now';
    echo '<input type="text" name="aicb_settings[aicb_premium_modal_button_text]" value="' . esc_attr($value) . '" class="regular-text" />';
}
function aicb_premium_modal_button_url_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_premium_modal_button_url']) ? $options['aicb_premium_modal_button_url'] : '';
    echo '<input type="url" name="aicb_settings[aicb_premium_modal_button_url]" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://yoursite.com/subscriptions" />';
    echo '<p class="description">Enter the full URL to your subscription or pricing page.</p>';
}

function aicb_footer_text_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_footer_text']) ? $options['aicb_footer_text'] : 'AI Chatbox can make mistakes.';
    echo '<input type="text" name="aicb_settings[aicb_footer_text]" value="' . esc_attr($value) . '" class="regular-text" />';
}
