<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Renders the main settings page wrapper with sidebar navigation.
 * It acts as a router to display the correct settings tab.
 */
function aicb_render_settings_page() {
    // Determine the active tab from the URL, default to 'api'
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'api';
    ?>
    <div class="aicb-settings-wrap">
        <div class="aicb-settings-sidebar">
            <div class="aicb-sidebar-header">
                <span class="dashicons dashicons-format-chat"></span>
                <h2>AI Chatbox</h2>
            </div>
            <ul class="aicb-settings-menu">
                <li><a href="?page=aicb-analytics"><span class="dashicons dashicons-chart-area"></span> Analytics</a></li>
                <li><a href="?page=aicb-training"><span class="dashicons dashicons-database-view"></span> Training Data</a></li>
                <li style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dcdcde;"><a href="?page=aicb-settings&tab=api" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-network"></span> API Settings</a></li>
                <li><a href="?page=aicb-settings&tab=persona" class="aicb-settings-menu-item"><span class="dashicons dashicons-businessman"></span> Chatbot Persona</a></li>
                <li><a href="?page=aicb-settings&tab=branding" class="aicb-settings-menu-item"><span class="dashicons dashicons-art"></span> Branding</a></li>
                <li><a href="?page=aicb-settings&tab=personalization" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-users"></span> Personalization</a></li>
                <li><a href="?page=aicb-settings&tab=action_buttons" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-links"></span> Action Buttons</a></li>
                <li><a href="?page=aicb-settings&tab=inline_ad" class="aicb-settings-menu-item"><span class="dashicons dashicons-megaphone"></span> In-line Ad</a></li>
                <li><a href="?page=aicb-settings&tab=features" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-generic"></span> Feature Control</a></li>
                <li><a href="?page=aicb-settings&tab=display" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-appearance"></span> Post-Answer Display</a></li>
                <li><a href="?page=aicb-settings&tab=misc" class="aicb-settings-menu-item"><span class="dashicons dashicons-admin-settings"></span> Miscellaneous</a></li>
                <li><a href="?page=aicb-settings&tab=shortcodes"><span class="dashicons dashicons-editor-code"></span> Shortcodes</a></li>
            </ul>
        </div>
        <div class="aicb-settings-content">
            <?php
            if ( 'shortcodes' === $active_tab ) {
                aicb_render_shortcodes_tab();
            } else {
                ?>
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'aicb_options_group' );
                    echo '<input type="hidden" name="aicb_active_tab" value="' . esc_attr( $active_tab ) . '" />';
                    
                    // Display the correct section based on the active tab
                    echo "<div class='aicb-settings-card'>";
                    switch ($active_tab) {
                        case 'persona':
                            do_settings_sections('aicb_persona_settings');
                            break;
                        case 'branding':
                            do_settings_sections('aicb_branding_settings');
                            break;
                        case 'personalization':
                            do_settings_sections('aicb_personalization_settings');
                            break;
                        case 'action_buttons':
                            do_settings_sections('aicb_action_buttons_settings');
                            break;
                        case 'inline_ad':
                            do_settings_sections('aicb_inline_ad_settings');
                            break;
                        case 'features':
                            do_settings_sections('aicb_features_settings');
                            break;
                        case 'display':
                            do_settings_sections('aicb_display_settings');
                            break;
                        case 'misc':
                            do_settings_sections('aicb_misc_settings');
                            break;
                        default: // 'api' tab
                            do_settings_sections('aicb_api_settings');
                            break;
                    }
                    echo '</div>';
                    
                    submit_button( 'Save Settings' );
                    ?>
                </form>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Renders the content for the "Shortcodes" tab.
 */
function aicb_render_shortcodes_tab() {
    ?>
    <div class="aicb-settings-card">
        <h2><span class="dashicons dashicons-editor-code" style="font-size: 20px; margin-right: 8px;"></span>Available Shortcodes</h2>
        <p>You can use these shortcodes in your posts, pages, or widgets.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th style="width: 30%;">Shortcode</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>[ai_chatbox]</code></td><td>Displays the main chatbox interface.</td></tr>
                <tr><td><code>[intelligent_content_links]</code></td><td>Displays a list of recommended articles based on the visitor's recent browsing history.</td></tr>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Registers all the sections and fields for the settings page.
 */
add_action( 'admin_init', 'aicb_register_settings' );
function aicb_register_settings() {
    register_setting( 'aicb_options_group', 'aicb_settings', 'aicb_sanitize_settings' );
    
    // API Settings
    add_settings_section('aicb_api_settings', 'API Settings', null, 'aicb_api_settings');
    add_settings_field('aicb_enable_gemini', 'Enable Gemini API', 'aicb_enable_gemini_callback', 'aicb_api_settings', 'aicb_api_settings');
    add_settings_field('aicb_gemini_api_key', 'Google Gemini API Key', 'aicb_gemini_api_key_callback', 'aicb_api_settings', 'aicb_api_settings');
    add_settings_field('aicb_tuned_model_name', 'Fine-Tuned Model Name', 'aicb_tuned_model_name_callback', 'aicb_api_settings', 'aicb_api_settings');

    // Persona Settings
    add_settings_section('aicb_persona_settings', 'Chatbot Persona & Context', null, 'aicb_persona_settings');
    add_settings_field('aicb_system_prompt', 'System Prompt (Chatbot Instructions)', 'aicb_system_prompt_callback', 'aicb_persona_settings', 'aicb_persona_settings');
    add_settings_field('aicb_enable_memory', 'Enable Conversational Memory', 'aicb_enable_memory_callback', 'aicb_persona_settings', 'aicb_persona_settings');

    // Branding Settings
    add_settings_section('aicb_branding_settings', 'Branding', null, 'aicb_branding_settings');
    add_settings_field('aicb_sidebar_logo_url', 'Sidebar Logo URL', 'aicb_sidebar_logo_url_callback', 'aicb_branding_settings', 'aicb_branding_settings');

    // Personalization Settings
    add_settings_section('aicb_personalization_settings', 'Personalization', null, 'aicb_personalization_settings');
    add_settings_field('aicb_enable_personalized_welcome', 'Enable Personalized Welcome', 'aicb_enable_personalized_welcome_callback', 'aicb_personalization_settings', 'aicb_personalization_settings');
    add_settings_field('aicb_show_related_content', 'Show Related Content in Welcome', 'aicb_show_related_content_callback', 'aicb_personalization_settings', 'aicb_personalization_settings');
    add_settings_field('aicb_welcome_prompt', 'Custom Welcome Template', 'aicb_welcome_prompt_callback', 'aicb_personalization_settings', 'aicb_personalization_settings');
    
    // Action Buttons Settings
    add_settings_section('aicb_action_buttons_settings', 'Action Buttons', null, 'aicb_action_buttons_settings');
    add_settings_field('aicb_button_1_label', 'Button 1 Label', 'aicb_button_1_label_callback', 'aicb_action_buttons_settings', 'aicb_action_buttons_settings');
    add_settings_field('aicb_button_1_url', 'Button 1 URL', 'aicb_button_1_url_callback', 'aicb_action_buttons_settings', 'aicb_action_buttons_settings');
    add_settings_field('aicb_button_2_label', 'Button 2 Label', 'aicb_button_2_label_callback', 'aicb_action_buttons_settings', 'aicb_action_buttons_settings');
    add_settings_field('aicb_button_2_url', 'Button 2 URL', 'aicb_button_2_url_callback', 'aicb_action_buttons_settings', 'aicb_action_buttons_settings');
    
    // In-line Ad Settings
    add_settings_section('aicb_inline_ad_settings', 'In-line Advertisement', null, 'aicb_inline_ad_settings');
    add_settings_field('aicb_inline_ad_text', 'Ad Text', 'aicb_inline_ad_text_callback', 'aicb_inline_ad_settings', 'aicb_inline_ad_settings');
    add_settings_field('aicb_inline_ad_url', 'Ad URL', 'aicb_inline_ad_url_callback', 'aicb_inline_ad_settings', 'aicb_inline_ad_settings');
    add_settings_field('aicb_inline_ad_position', 'Ad Position', 'aicb_inline_ad_position_callback', 'aicb_inline_ad_settings', 'aicb_inline_ad_settings');

    // Feature Control Settings
    add_settings_section('aicb_features_settings', 'Feature Control', null, 'aicb_features_settings');
    add_settings_field('aicb_enable_floating_chatbox', 'Enable Floating Chatbox', 'aicb_enable_floating_chatbox_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_launcher_notification', 'Launcher Notification Badge', 'aicb_launcher_notification_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_launcher_cta_text', 'Launcher Call to Action', 'aicb_launcher_cta_text_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_front_page_takeover', 'Enable Chat Mode on Front Page', 'aicb_front_page_takeover_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_enable_knowledge_base', 'Enable Knowledge Base', 'aicb_enable_knowledge_base_callback', 'aicb_features_settings', 'aicb_features_settings');
    add_settings_field('aicb_enable_autocomplete', 'Enable Autocomplete Suggestions', 'aicb_enable_autocomplete_callback', 'aicb_features_settings', 'aicb_features_settings');
    
    // Display Settings
    add_settings_section('aicb_display_settings', 'Post-Answer Display', null, 'aicb_display_settings');
    add_settings_field('aicb_show_sources', 'Show Source Links', 'aicb_show_sources_callback', 'aicb_display_settings', 'aicb_display_settings');
    add_settings_field('aicb_show_ad', 'Show an Ad', 'aicb_show_ad_callback', 'aicb_display_settings', 'aicb_display_settings');
    add_settings_field('aicb_custom_ad_code', 'Custom Ad Code', 'aicb_custom_ad_code_callback', 'aicb_display_settings', 'aicb_display_settings');
    
    // Miscellaneous Settings
    add_settings_section('aicb_misc_settings', 'Miscellaneous', null, 'aicb_misc_settings');
    add_settings_field('aicb_footer_text', 'Chatbox Footer Text', 'aicb_footer_text_callback', 'aicb_misc_settings', 'aicb_misc_settings');
}

function aicb_sanitize_settings( $input ) {
    $saved_options = get_option( 'aicb_settings' );
    if ( ! is_array( $saved_options ) ) { $saved_options = array(); }
    $new_input = array_merge( $saved_options, $input );
    $active_tab = isset($_POST['aicb_active_tab']) ? sanitize_key($_POST['aicb_active_tab']) : 'api';
    $tab_checkboxes = [
        'api' => ['aicb_enable_gemini'],
        'persona' => ['aicb_enable_memory'],
        'personalization' => ['aicb_enable_personalized_welcome', 'aicb_show_related_content'],
        'features' => ['aicb_enable_floating_chatbox', 'aicb_front_page_takeover', 'aicb_enable_knowledge_base', 'aicb_enable_autocomplete'],
        'display' => ['aicb_show_sources', 'aicb_show_ad'],
    ];
    if (isset($tab_checkboxes[$active_tab])) {
        foreach ($tab_checkboxes[$active_tab] as $cb) {
            if (!isset($input[$cb])) { $new_input[$cb] = 0; }
        }
    }
    if (isset($input['aicb_gemini_api_key'])) $new_input['aicb_gemini_api_key'] = sanitize_text_field($input['aicb_gemini_api_key']);
    if (isset($input['aicb_tuned_model_name'])) $new_input['aicb_tuned_model_name'] = sanitize_text_field($input['aicb_tuned_model_name']);
    if (isset($input['aicb_system_prompt'])) $new_input['aicb_system_prompt'] = sanitize_textarea_field($input['aicb_system_prompt']);
    if (isset($input['aicb_welcome_prompt'])) $new_input['aicb_welcome_prompt'] = sanitize_textarea_field($input['aicb_welcome_prompt']);
    if (isset($input['aicb_sidebar_logo_url'])) $new_input['aicb_sidebar_logo_url'] = esc_url_raw($input['aicb_sidebar_logo_url']);
    if (isset($input['aicb_button_1_label'])) $new_input['aicb_button_1_label'] = sanitize_text_field($input['aicb_button_1_label']);
    if (isset($input['aicb_button_1_url'])) $new_input['aicb_button_1_url'] = esc_url_raw($input['aicb_button_1_url']);
    if (isset($input['aicb_button_2_label'])) $new_input['aicb_button_2_label'] = sanitize_text_field($input['aicb_button_2_label']);
    if (isset($input['aicb_button_2_url'])) $new_input['aicb_button_2_url'] = esc_url_raw($input['aicb_button_2_url']);
    if (isset($input['aicb_inline_ad_text'])) $new_input['aicb_inline_ad_text'] = sanitize_text_field($input['aicb_inline_ad_text']);
    if (isset($input['aicb_inline_ad_url'])) $new_input['aicb_inline_ad_url'] = esc_url_raw($input['aicb_inline_ad_url']);
    if (isset($input['aicb_inline_ad_position'])) $new_input['aicb_inline_ad_position'] = absint($input['aicb_inline_ad_position']);
    if (isset($input['aicb_launcher_notification'])) $new_input['aicb_launcher_notification'] = sanitize_text_field($input['aicb_launcher_notification']);
    if (isset($input['aicb_launcher_cta_text'])) $new_input['aicb_launcher_cta_text'] = sanitize_text_field($input['aicb_launcher_cta_text']);
    if (isset($input['aicb_footer_text'])) $new_input['aicb_footer_text'] = sanitize_text_field($input['aicb_footer_text']);
    if (isset($input['aicb_custom_ad_code'])) $new_input['aicb_custom_ad_code'] = $input['aicb_custom_ad_code'];
    return $new_input;
}

function aicb_enable_gemini_callback() {
    $options = get_option('aicb_settings');
    $checked = isset($options['aicb_enable_gemini']) ? $options['aicb_enable_gemini'] : 0;
    echo '<label><input type="checkbox" id="aicb_enable_gemini_checkbox" name="aicb_settings[aicb_enable_gemini]" value="1"' . checked(1, $checked, false) . '/> Enable AI-powered answers and features using the Gemini API.</label>';
}
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
function aicb_system_prompt_callback() {
    $options = get_option('aicb_settings');
    $default_prompt = "You are a helpful assistant for a website named " . get_bloginfo('name') . ". Your primary goal is to answer user questions based on the context provided from the website's content. Be friendly, concise, and helpful.";
    $value = isset($options['aicb_system_prompt']) ? $options['aicb_system_prompt'] : $default_prompt;
    echo '<textarea name="aicb_settings[aicb_system_prompt]" rows="6" class="large-text">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">This is the master instruction for the AI.</p>';
}
function aicb_enable_memory_callback() {
    $options = get_option('aicb_settings');
    $checked = isset($options['aicb_enable_memory']) ? $options['aicb_enable_memory'] : 1;
    echo '<label><input type="checkbox" name="aicb_settings[aicb_enable_memory]" value="1"' . checked(1, $checked, false) . '/> Allow the chatbot to remember the last few messages.</label>';
}
function aicb_enable_personalized_welcome_callback() {
    $options = get_option('aicb_settings');
    $checked = isset($options['aicb_enable_personalized_welcome']) ? $options['aicb_enable_personalized_welcome'] : 0;
    echo '<label><input type="checkbox" name="aicb_settings[aicb_enable_personalized_welcome]" value="1"' . checked(1, $checked, false) . '/> If checked, the plugin will track pages a user visits and use the template below to create a personalized welcome message.</label>';
}
function aicb_show_related_content_callback() {
    $options = get_option('aicb_settings');
    $checked = isset($options['aicb_show_related_content']) ? $options['aicb_show_related_content'] : 1;
    echo '<label><input type="checkbox" name="aicb_settings[aicb_show_related_content]" value="1"' . checked(1, $checked, false) . '/> If checked, the personalized welcome message will also include links to other relevant content on your site.</label>';
}
function aicb_welcome_prompt_callback() {
    $options = get_option('aicb_settings');
    $default_template = "Hello! I see you're interested in our {categories} articles. How can I help you today?";
    $value = isset($options['aicb_welcome_prompt']) && !empty($options['aicb_welcome_prompt']) ? $options['aicb_welcome_prompt'] : $default_template;
    echo '<textarea name="aicb_settings[aicb_welcome_prompt]" rows="4" class="large-text">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">Customize the welcome message. Use <code>{categories}</code> to insert the post categories the visitor has been viewing.</p>';
}
function aicb_sidebar_logo_url_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_sidebar_logo_url']) ? $options['aicb_sidebar_logo_url'] : '';
    echo '<input type="url" name="aicb_settings[aicb_sidebar_logo_url]" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://example.com/logo.png" />';
    echo '<p class="description">Paste the full URL to your logo image. This will appear at the top of the sidebar.</p>';
}
function aicb_button_1_label_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_button_1_label']) ? $options['aicb_button_1_label'] : '';
    echo '<input type="text" name="aicb_settings[aicb_button_1_label]" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g., Our Services" />';
}
function aicb_button_1_url_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_button_1_url']) ? $options['aicb_button_1_url'] : '';
    echo '<input type="url" name="aicb_settings[aicb_button_1_url]" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://example.com/services" />';
}
function aicb_button_2_label_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_button_2_label']) ? $options['aicb_button_2_label'] : '';
    echo '<input type="text" name="aicb_settings[aicb_button_2_label]" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g., Contact Us" />';
}
function aicb_button_2_url_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_button_2_url']) ? $options['aicb_button_2_url'] : '';
    echo '<input type="url" name="aicb_settings[aicb_button_2_url]" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://example.com/contact" />';
}
function aicb_inline_ad_text_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_inline_ad_text']) ? $options['aicb_inline_ad_text'] : '';
    echo '<input type="text" name="aicb_settings[aicb_inline_ad_text]" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g., Sponsored Link" />';
    echo '<p class="description">The text to display for your in-line ad. Leave blank to disable.</p>';
}
function aicb_inline_ad_url_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_inline_ad_url']) ? $options['aicb_inline_ad_url'] : '';
    echo '<input type="url" name="aicb_settings[aicb_inline_ad_url]" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://your-affiliate-link.com" />';
}
function aicb_inline_ad_position_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_inline_ad_position']) ? $options['aicb_inline_ad_position'] : 3;
    echo '<input type="number" name="aicb_settings[aicb_inline_ad_position]" value="' . esc_attr($value) . '" class="small-text" min="1" max="10" />';
    echo '<p class="description">The position in the list where the ad should appear (e.g., 3 for the third spot).</p>';
}
function aicb_enable_floating_chatbox_callback() {
    $options = get_option('aicb_settings');
    $checked = isset($options['aicb_enable_floating_chatbox']) ? $options['aicb_enable_floating_chatbox'] : 0;
    echo '<label><input type="checkbox" name="aicb_settings[aicb_enable_floating_chatbox]" value="1"' . checked(1, $checked, false) . '/> Adds a chat bubble to the bottom corner of all pages.</label>';
    echo '<p class="description">Note: This is recommended over the "Front Page Takeover" or shortcode method for most sites.</p>';
}
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
function aicb_front_page_takeover_callback() {
    $options = get_option('aicb_settings');
    $checked = isset($options['aicb_front_page_takeover']) ? $options['aicb_front_page_takeover'] : 0;
    echo '<label><input type="checkbox" name="aicb_settings[aicb_front_page_takeover]" value="1"' . checked(1, $checked, false) . '/> If checked, the website homepage will be replaced by the AI Chatbox interface.</label>';
}
function aicb_enable_knowledge_base_callback() {
    $options = get_option( 'aicb_settings' );
    $checked = isset( $options['aicb_enable_knowledge_base'] ) ? $options['aicb_enable_knowledge_base'] : 1;
    echo '<label><input type="checkbox" name="aicb_settings[aicb_enable_knowledge_base]" value="1"' . checked( 1, $checked, false ) . '/> Prioritize answers from your custom "Knowledge Base" before performing a general search.</label>';
}
function aicb_enable_autocomplete_callback() {
    $options = get_option( 'aicb_settings' );
    $checked = isset( $options['aicb_enable_autocomplete'] ) ? $options['aicb_enable_autocomplete'] : 1;
    echo '<label><input type="checkbox" name="aicb_settings[aicb_enable_autocomplete]" value="1"' . checked( 1, $checked, false ) . '/> Show a "ghost text" suggestion as the user types.</label>';
}
function aicb_show_sources_callback() {
    $options = get_option('aicb_settings');
    $checked = isset($options['aicb_show_sources']) ? $options['aicb_show_sources'] : 0;
    echo '<label><input type="checkbox" name="aicb_settings[aicb_show_sources]" value="1"' . checked(1, $checked, false) . '/> Displays links to the posts/pages used by Gemini to generate an answer. (Only applies when Gemini API is active).</label>';
}
function aicb_show_ad_callback() {
    $options = get_option('aicb_settings');
    $checked = isset($options['aicb_show_ad']) ? $options['aicb_show_ad'] : 0;
    echo '<label><input type="checkbox" name="aicb_settings[aicb_show_ad]" value="1"' . checked(1, $checked, false) . '/> Displays an ad in a separate message bubble after every answer.</label>';
}
function aicb_custom_ad_code_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_custom_ad_code']) ? $options['aicb_custom_ad_code'] : '';
    echo '<textarea name="aicb_settings[aicb_custom_ad_code]" rows="5" class="large-text" placeholder="Paste your ad code here...">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">This code will be used if "Show an ad" is checked. It has priority over ads from the "Ads" post type.</p>';
}
function aicb_footer_text_callback() {
    $options = get_option('aicb_settings');
    $value = isset($options['aicb_footer_text']) ? $options['aicb_footer_text'] : 'AI Chatbox can make mistakes.';
    echo '<input type="text" name="aicb_settings[aicb_footer_text]" value="' . esc_attr($value) . '" class="regular-text" />';
}
