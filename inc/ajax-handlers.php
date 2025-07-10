<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// --- PREMIUM MEMBERSHIP FUNCTIONS ---

/**
 * Checks if a user is a premium member.
 *
 * @param int $user_id The ID of the user to check.
 * @return bool True if the user is a premium member, false otherwise.
 */
function aicb_is_premium_user($user_id = 0) {
    if ($user_id === 0) {
        $user_id = get_current_user_id();
    }

    if ($user_id === 0) {
        return false;
    }

    global $wpdb;
    $premium_table = $wpdb->prefix . 'aicb_premium_members';
    $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM $premium_table WHERE user_id = %d AND status = 'active'", $user_id));

    if ($member && strtotime($member->end_date) > time()) {
        return true;
    }

    return false;
}

/**
 * Checks if the current user can use a specific feature.
 *
 * @param string $feature The feature to check.
 * @return bool True if the user can use the feature, false otherwise.
 */
function aicb_can_use_feature($feature) {
    $options = get_option('aicb_settings');
    $premium_features = isset($options['aicb_premium_features']) ? $options['aicb_premium_features'] : [];

    if (in_array($feature, $premium_features)) {
        return aicb_is_premium_user();
    }

    return true;
}


// --- UTILITY FUNCTIONS ---

function aicb_get_fallback_suggestions( $query ) {
    $suggestions_html = '';
    $options = get_option('aicb_settings');
    $posts_array = [];

    $args = array( 'post_type' => array( 'post', 'page' ), 'posts_per_page' => 3, 's' => $query, 'post_status' => 'publish' );
    $related_query = new WP_Query( $args );

    if ( !$related_query->have_posts() ) {
        $args = array( 'post_type' => array( 'post', 'page' ), 'posts_per_page' => 3, 'orderby' => 'rand', 'post_status' => 'publish' );
        $related_query = new WP_Query( $args );
    }

    if ( $related_query->have_posts() ) {
        while ( $related_query->have_posts() ) {
            $related_query->the_post();
            $posts_array[] = ['id' => get_the_ID(), 'title' => get_the_title(), 'url' => get_permalink()];
        }
        wp_reset_postdata();
    }

    $inline_ad_text = isset($options['aicb_inline_ad_text']) ? trim($options['aicb_inline_ad_text']) : '';
    if (!empty($inline_ad_text) && !empty($options['aicb_inline_ad_url'])) {
        $ad_position = isset($options['aicb_inline_ad_position']) ? absint($options['aicb_inline_ad_position']) - 1 : 2;
        $ad_item = ['title' => $inline_ad_text, 'url' => $options['aicb_inline_ad_url'], 'is_ad' => true];
        array_splice($posts_array, $ad_position, 0, [$ad_item]);
    }
    
    if (!empty($posts_array)) {
        $suggestions_html .= '<div class="aicb-related-container" style="margin-top: 1.5rem;"><h4>You might also like:</h4><ul>';
        $button_text = !empty($options['aicb_chat_download_button_text']) ? esc_html($options['aicb_chat_download_button_text']) : 'Download';
        $button_icon = !empty($options['aicb_chat_download_button_icon']) ? esc_html($options['aicb_chat_download_button_icon']) : '✨';

        foreach ($posts_array as $post_item) {
            if (isset($post_item['is_ad'])) {
                $suggestions_html .= '<li class="aicb-inline-ad"><a href="' . esc_url($post_item['url']) . '" target="_blank">' . esc_html($post_item['title']) . '</a></li>';
            } else {
                $download_link = get_post_meta($post_item['id'], '_aicb_download_link', true);
                $action_button = '';
                if (!empty($download_link)) {
                    if (aicb_is_premium_user()) {
                        $action_button = '<button class="aicb-link-action-btn" title="Download" data-download-url="' . esc_url($download_link) . '">' . $button_text . ' ' . $button_icon . '</button>';
                    } else {
                        $action_button = '<button class="aicb-link-action-btn" title="Upgrade to Download" data-premium-required="true">' . $button_text . ' ' . $button_icon . '</button>';
                    }
                }
                $suggestions_html .= '<li><a href="#" class="aicb-content-loader" data-post-id="' . esc_attr($post_item['id']) . '" data-permalink="' . esc_url($post_item['url']) . '">' . esc_html($post_item['title']) . '</a>' . $action_button . '</li>';
            }
        }
        $suggestions_html .= '</ul></div>';
    }

    return $suggestions_html;
}

function aicb_get_configured_ad($options) {
    if (isset($options['aicb_show_ad']) && $options['aicb_show_ad']) {
        if (!empty($options['aicb_custom_ad_code'])) { return do_shortcode($options['aicb_custom_ad_code']); }
        $ad_args = array('post_type' => 'aicb_ad', 'posts_per_page' => 1, 'orderby' => 'rand', 'post_status' => 'publish', 'no_found_rows' => true);
        $ad_query = new WP_Query($ad_args);
        if ($ad_query->have_posts()) {
            $ad_query->the_post();
            $ad_content = get_the_content();
            wp_reset_postdata();
            return do_shortcode($ad_content);
        }
    }
    return null;
}

function aicb_get_visitor_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) { return $ip; }
            }
        }
    }
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if (in_array($ip, ['127.0.0.1', '::1'])) { $ip = '8.8.8.8'; }
    return $ip;
}

function aicb_log_activity($event_type, $content, $post_id = 0) {
    global $wpdb;
    $activity_table_name = $wpdb->prefix . 'aicb_activity_log';
    $country = '';
    $ip_address = aicb_get_visitor_ip();
    if (!empty($ip_address)) {
        $response = wp_remote_get( "http://ip-api.com/json/{$ip_address}?fields=status,country" );
        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $geo_data = json_decode( wp_remote_retrieve_body( $response ) );
            if ( isset( $geo_data->status ) && $geo_data->status === 'success' && isset( $geo_data->country ) ) {
                $country = sanitize_text_field( $geo_data->country );
            }
        }
    }
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $device = 'Desktop';
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) { $device = 'Tablet'; }
    elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) { $device = 'Mobile'; }
    $wpdb->insert($activity_table_name, array('time' => current_time('mysql'), 'event_type' => $event_type, 'content' => $content, 'post_id' => $post_id, 'country' => $country, 'device' => $device));
}

function aicb_log_training_data($question, $answer) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aicb_training_data';
    $wpdb->insert($table_name, array('time' => current_time('mysql'), 'question' => $question, 'answer' => $answer));
}

function aicb_log_lead($user_query, $conversation_history, $sentiment, $is_lead) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aicb_leads';
    $wpdb->insert($table_name, array(
        'time' => current_time('mysql'),
        'user_query' => $user_query,
        'sentiment' => $sentiment,
        'is_lead' => $is_lead ? 1 : 0,
        'conversation_history' => wp_json_encode($conversation_history),
        'visitor_ip' => aicb_get_visitor_ip()
    ));
}

// --- AJAX HANDLERS ---
add_action('wp_ajax_aicb_get_response', 'aicb_handle_ai_query');
add_action('wp_ajax_nopriv_aicb_get_response', 'aicb_handle_ai_query');
function aicb_handle_ai_query() {
    check_ajax_referer('aicb_chat_nonce', 'nonce');
    $user_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    if (empty($user_query)) { wp_send_json_error(['answer' => 'Query cannot be empty.']); return; }

    $cache_key = 'aicb_query_v2_' . md5($user_query);
    $cached_response = get_transient($cache_key);
    if (false !== $cached_response) {
        wp_send_json_success($cached_response);
        return;
    }

    aicb_log_activity('search', $user_query);
    $options = get_option('aicb_settings');
    $ad_code = aicb_get_configured_ad($options);
    $knowledge_base_enabled = isset($options['aicb_enable_knowledge_base']) ? (bool)$options['aicb_enable_knowledge_base'] : true;
    if ($knowledge_base_enabled) {
        $knowledge_args = array('post_type' => 'aicb_knowledge', 'posts_per_page' => 1, 'title' => $user_query, 'post_status' => 'publish');
        $knowledge_query = new WP_Query($knowledge_args);
        if ($knowledge_query->have_posts()) {
            $knowledge_query->the_post();
            $answer = wpautop(get_the_content());
            $search_url = home_url('/?s=' . urlencode($user_query));
            $response_data = ['answer' => $answer, 'query' => $user_query, 'search_url' => $search_url, 'ad_code' => $ad_code];
            set_transient($cache_key, $response_data, 2 * HOUR_IN_SECONDS);
            wp_send_json_success($response_data);
            wp_reset_postdata();
            die();
        }
    }
    $api_key = isset($options['aicb_gemini_api_key']) ? $options['aicb_gemini_api_key'] : '';
    if (!empty($api_key) && !empty($options['aicb_enable_gemini'])) {
        aicb_perform_advanced_search($user_query, $api_key, $options, $ad_code);
    } else {
        aicb_perform_simple_search($user_query, $ad_code);
    }
}

function aicb_analyze_query_for_lead($user_query, $conversation_history, $api_key) {
    if (!aicb_can_use_feature('lead_analysis')) {
        return null;
    }

    $analysis_prompt = "Analyze the sentiment of the following user query and conversation. Determine if the user's query indicates a buying interest (e.g., asking about price, features, how to buy, or expressing strong interest). Respond ONLY with a JSON object in the format: {\"sentiment\": \"positive|negative|neutral\", \"is_lead\": true|false}. User Query: \"$user_query\". Conversation History: " . wp_json_encode($conversation_history);

    $api_url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    $request_body = json_encode([
        'contents' => [['role' => 'user', 'parts' => [['text' => $analysis_prompt]]]],
    ]);

    $response = wp_remote_post($api_url, ['body' => $request_body, 'headers' => ['Content-Type' => 'application/json'], 'timeout' => 20]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $text_response = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    $json_response = json_decode(trim(str_replace(['```json', '```'], '', $text_response)), true);

    if (json_last_error() === JSON_ERROR_NONE && isset($json_response['sentiment']) && isset($json_response['is_lead'])) {
        return $json_response;
    }
    
    return null;
}

function aicb_get_random_fallback_answer($user_query) {
    $fallbacks = [
        "I'm sorry, I couldn't find a specific answer for that on this website. Perhaps I can help with something else?",
        "That's a great question! While I don't have information on that from this website, you might find these other resources helpful.",
        "Hmm, I'm not sure about that one based on the site's content. Let me show you what I can help with.",
        "I'm designed to answer questions using this site's content, and I couldn't find information on that topic. Maybe one of these links will help?"
    ];
    $answer = $fallbacks[array_rand($fallbacks)];
    $answer .= aicb_get_fallback_suggestions($user_query);
    return $answer;
}


function aicb_perform_advanced_search($user_query, $api_key, $options = [], $ad_code = null) {
    $cache_key = 'aicb_query_v2_' . md5($user_query); 
    $history_json = isset($_POST['history']) ? stripslashes($_POST['history']) : '[]';
    $chat_history = json_decode($history_json, true);
    if (!is_array($chat_history)) { $chat_history = []; }

    $Browse_history = JSON_decode(stripslashes($_POST['Browse_history'] ?? '[]'), true);
    $Browse_context = '';
    if (!empty($Browse_history) && is_array($Browse_history)) {
        $viewed_pages = array_map(function($page) {
            return "'" . $page['title'] . "'";
        }, $Browse_history);
        $Browse_context = "To better understand the user's intent, know that they have recently viewed these pages: " . implode(', ', $viewed_pages) . ". ";
    }

    $search_args = ['post_type' => ['post', 'page'], 'posts_per_page' => 5, 's' => $user_query, 'post_status' => 'publish'];
    $search_query = new WP_Query($search_args);
    $context = '';
    $sources = [];
    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $context .= "Content from '" . get_the_title() . "': " . wp_strip_all_tags(get_the_content()) . "\n\n";
            $sources[] = ['id' => get_the_ID(), 'title' => get_the_title(), 'url' => get_permalink()];
        }
        wp_reset_postdata();
    }
    $inline_ad_text = isset($options['aicb_inline_ad_text']) ? trim($options['aicb_inline_ad_text']) : '';
    if (!empty($inline_ad_text) && !empty($options['aicb_inline_ad_url'])) {
        $ad_position = isset($options['aicb_inline_ad_position']) ? absint($options['aicb_inline_ad_position']) - 1 : 2;
        $ad_item = ['title' => $inline_ad_text, 'url' => $options['aicb_inline_ad_url'], 'is_ad' => true];
        array_splice($sources, $ad_position, 0, [$ad_item]);
    }
    $search_url = home_url('/?s=' . urlencode($user_query));
    $sources_html = null;
    
    $show_sources = isset($options['aicb_show_sources']) && $options['aicb_show_sources'];
    if ($show_sources && aicb_can_use_feature('show_sources')) {
        $sources_html = '<div class="aicb-sources-container"><h4>Sources:</h4><ul>';
        $button_text = !empty($options['aicb_chat_download_button_text']) ? esc_html($options['aicb_chat_download_button_text']) : 'Download';
        $button_icon = !empty($options['aicb_chat_download_button_icon']) ? esc_html($options['aicb_chat_download_button_icon']) : '✨';
        
        foreach ($sources as $source) {
            if (isset($source['is_ad'])) {
                 $sources_html .= '<li class="aicb-inline-ad"><a href="' . esc_url($source['url']) . '" target="_blank">' . esc_html($source['title']) . '</a></li>';
            } else {
                $download_link = get_post_meta($source['id'], '_aicb_download_link', true);
                $action_button = '';
                if (!empty($download_link)) {
                    if (aicb_is_premium_user()) {
                        $action_button = '<button class="aicb-link-action-btn" title="Download" data-download-url="' . esc_url($download_link) . '">' . $button_text . ' ' . $button_icon . '</button>';
                    } else {
                        $action_button = '<button class="aicb-link-action-btn" title="Upgrade to Download" data-premium-required="true">' . $button_text . ' ' . $button_icon . '</button>';
                    }
                }
                $sources_html .= '<li><a href="#" class="aicb-content-loader" data-post-id="' . esc_attr($source['id']) . '" data-permalink="' . esc_url($source['url']) . '">' . esc_html($source['title']) . '</a>' . $action_button . '</li>';
            }
        }
        $sources_html .= '</ul></div>';
    }
    
    $default_system_prompt = "You are a specialized, world-class assistant for the website '{site_name}'.

**Core Mission:** Your primary goal is to provide accurate, helpful, and well-structured answers to user questions.
**Website & Audience Context:**
-   **Website Specialty:** {specialty}
-   **Target Audience:** {audience}
-   **Communication Style:** {style}

**Rules of Engagement & Persona:**
1.  **Adopt the Persona:** Embody the chosen **Communication Style** in every response. Your tone should be consistent.
2.  **Use Provided Context First:** If context from the website is provided, you **must** base your answer on it. Synthesize the information into a single, coherent response.
3.  **Handling Lack of Context:** If no context is provided, or if the context does not contain the answer, you must try to answer using your general knowledge, while still maintaining your persona. If you cannot answer, politely state that you don't have information on that topic.
4.  **Structure and Proactivity:** Structure your response for maximum clarity using formatting like **bolding** for keywords and bullet points for lists. If you provide a good answer, suggest a logical next question the user might have.
5.  **No Self-Reference as 'AI':** Do not refer to yourself as an 'AI' or 'language model'. You are an assistant for this website.
";
    $system_prompt_template = isset($options['aicb_system_prompt']) ? $options['aicb_system_prompt'] : $default_system_prompt;

    $replacements = [
        '{site_name}' => get_bloginfo('name'),
        '{specialty}' => isset($options['aicb_website_specialty']) ? $options['aicb_website_specialty'] : 'general topics',
        '{audience}' => isset($options['aicb_target_audience']) ? $options['aicb_target_audience'] : 'general visitors',
        '{style}' => isset($options['aicb_communication_style']) ? $options['aicb_communication_style'] : 'Professional',
    ];
    $system_prompt = str_replace(array_keys($replacements), array_values($replacements), $system_prompt_template);

    $rag_prompt = $Browse_context;
    if (!empty($context)) {
        $rag_prompt .= "Please answer the user's question based on the following context from the website. If the answer isn't in the context, use your general knowledge but adhere to the persona. \n\n" . "--- CONTEXT ---\n" . $context . "\n--- END CONTEXT ---\n\n";
    }

    $api_contents = $chat_history;
    $last_user_message_index = -1;
    for ($i = count($api_contents) - 1; $i >= 0; $i--) {
        if ($api_contents[$i]['role'] === 'user') {
            $last_user_message_index = $i;
            break;
        }
    }
    if ($last_user_message_index !== -1) {
        $api_contents[$last_user_message_index]['parts'][0]['text'] = $rag_prompt . "\n\nUSER'S QUESTION: " . $user_query;
    } else {
        $api_contents[] = ['role' => 'user', 'parts' => [['text' => $rag_prompt . "\n\nUSER'S QUESTION: " . $user_query]]];
    }
    
    $tuned_model = isset($options['aicb_tuned_model_name']) ? trim($options['aicb_tuned_model_name']) : '';
    if (!empty($tuned_model)) {
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/tunedModels/' . $tuned_model . ':generateContent?key=' . $api_key;
    } else {
        $api_url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    }

    $request_body = json_encode(['contents' => $api_contents, 'systemInstruction' => ['parts' => [['text' => $system_prompt]]], 'safetySettings' => [['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'], ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH']]]);
    $response = wp_remote_post($api_url, ['body' => $request_body, 'headers' => ['Content-Type' => 'application/json'], 'timeout' => 60]);
    
    if (is_wp_error($response) || empty(json_decode(wp_remote_retrieve_body($response))->candidates[0]->content->parts[0]->text)) {
        $answer = "I apologize, but I encountered an issue while trying to generate a response. Please try rephrasing your question.";
        $answer .= aicb_get_fallback_suggestions($user_query);
        wp_send_json_success(['answer' => $answer, 'query' => $user_query, 'ad_code' => $ad_code, 'search_url' => $search_url]);
        return;
    }
    
    $data = json_decode(wp_remote_retrieve_body($response));
    $answer_text = $data->candidates[0]->content->parts[0]->text;
    aicb_log_training_data($user_query, $answer_text);
    
    $lead_analysis_enabled = isset($options['aicb_enable_lead_analysis']) && $options['aicb_enable_lead_analysis'];
    if ($lead_analysis_enabled) {
        $analysis_result = aicb_analyze_query_for_lead($user_query, $chat_history, $api_key);
        if ($analysis_result && $analysis_result['is_lead']) {
            aicb_log_lead($user_query, $chat_history, $analysis_result['sentiment'], $analysis_result['is_lead']);
        }
    }

    $answer = nl2br(esc_html($answer_text));
    $response_data = ['answer' => $answer, 'query' => $user_query, 'ad_code' => $ad_code, 'sources_html' => $sources_html, 'search_url' => $search_url];
    set_transient($cache_key, $response_data, 2 * HOUR_IN_SECONDS);
    wp_send_json_success($response_data);
}

function aicb_perform_simple_search($user_query, $ad_code = null) {
    $cache_key = 'aicb_query_v2_' . md5($user_query);
    $options = get_option('aicb_settings');
    $inline_ad_text = isset($options['aicb_inline_ad_text']) ? trim($options['aicb_inline_ad_text']) : '';
    $ad_position = isset($options['aicb_inline_ad_position']) ? absint($options['aicb_inline_ad_position']) : 3;
    $ad_injected = false;
    $search_args = array('post_type' => ['post', 'page'], 'posts_per_page' => 5, 's' => $user_query, 'post_status' => 'publish');
    $search_query = new WP_Query($search_args);
    if ($search_query->have_posts()) {
        $response_html = '<p>Here are some results from the website:</p><ul>';
        $counter = 1;
        $button_text = !empty($options['aicb_chat_download_button_text']) ? esc_html($options['aicb_chat_download_button_text']) : 'Download';
        $button_icon = !empty($options['aicb_chat_download_button_icon']) ? esc_html($options['aicb_chat_download_button_icon']) : '✨';
        
        while ($search_query->have_posts()) {
            $search_query->the_post();
            if (!empty($inline_ad_text) && !$ad_injected && $counter === $ad_position) {
                $response_html .= '<li class="aicb-inline-ad"><a href="' . esc_url($options['aicb_inline_ad_url']) . '" target="_blank">' . esc_html($inline_ad_text) . '</a></li>';
                $ad_injected = true;
            }
            $download_link = get_post_meta(get_the_ID(), '_aicb_download_link', true);
            $action_button = '';
            if (!empty($download_link)) {
                if (aicb_is_premium_user()) {
                    $action_button = '<button class="aicb-link-action-btn" title="Download" data-download-url="' . esc_url($download_link) . '">' . $button_text . ' ' . $button_icon . '</button>';
                } else {
                    $action_button = '<button class="aicb-link-action-btn" title="Upgrade to Download" data-premium-required="true">' . $button_text . ' ' . $button_icon . '</button>';
                }
            }
            $response_html .= '<li><a href="#" class="aicb-content-loader" data-post-id="' . get_the_ID() . '" data-permalink="' . get_permalink() . '">' . get_the_title() . '</a>' . $action_button . '</li>';
            $counter++;
        }
        $response_html .= '</ul>';
        wp_reset_postdata();
    } else { 
        $response_html = aicb_get_random_fallback_answer($user_query);
    }
    $response_data = ['answer' => $response_html, 'query' => $user_query, 'search_url' => home_url('/?s=' . urlencode($user_query)), 'ad_code' => $ad_code];
    set_transient($cache_key, $response_data, 2 * HOUR_IN_SECONDS);
    wp_send_json_success($response_data);
}

add_action('wp_ajax_aicb_test_api', 'aicb_test_api_callback');
function aicb_test_api_callback() {
    check_ajax_referer('aicb_admin_nonce', 'nonce');

    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API Key was not provided.']);
        return;
    }

    $api_url = 'https://generativelanguage.googleapis.com/v1/models?key=' . $api_key;

    $response = wp_remote_get($api_url, ['timeout' => 20]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Connection Error: ' . $response->get_error_message()]);
        return;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if ($response_code === 200 && !empty($data->models)) {
        wp_send_json_success(['message' => 'Success! Your API key is valid.']);
    } else {
        $error_message = 'Invalid API Key or API not enabled.';
        if (isset($data->error->message)) {
            $error_message = 'API Error: ' . $data->error->message;
        }
        wp_send_json_error(['message' => $error_message]);
    }
}


add_action('wp_ajax_aicb_get_post_content', 'aicb_get_post_content_callback');
add_action('wp_ajax_nopriv_aicb_get_post_content', 'aicb_get_post_content_callback');
function aicb_get_post_content_callback() {
    check_ajax_referer('aicb_chat_nonce', 'nonce');
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if ($post_id === 0) {
        wp_send_json_error(['message' => 'Invalid Post ID.']);
    }

    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
        wp_send_json_error(['message' => 'Content not found or not published.']);
    }

    $content = apply_filters('the_content', $post->post_content);

    $response_html = '<div class="aicb-content-view-header">';
    $response_html .= '<h2>' . esc_html($post->post_title) . '</h2>';
    $response_html .= '</div>';
    $response_html .= '<div class="aicb-content-view-body">';
    $response_html .= $content;
    $response_html .= '</div>';
    $response_html .= '<div class="aicb-content-ratings" data-post-id="' . esc_attr($post_id) . '"><span>Was this helpful?</span><span class="aicb-rating-thumb" data-rating="up">👍</span><span class="aicb-rating-thumb" data-rating="down">👎</span></div>';


    wp_send_json_success(['html' => $response_html]);
}


add_action('wp_ajax_aicb_rate_content', 'aicb_rate_content_callback');
add_action('wp_ajax_nopriv_aicb_rate_content', 'aicb_rate_content_callback');
function aicb_rate_content_callback() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aicb_chat_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }
    
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $rating = isset($_POST['rating']) ? sanitize_text_field($_POST['rating']) : 'N/A';
    $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';

    if($post_id > 0) {
        $post_title = get_the_title($post_id);
        $log_content = "Rating: " . $rating . " | Content: " . $post_title;
        aicb_log_activity('content_rating', $log_content, $post_id);
    } else {
        $log_content = "Rating: " . $rating . " | Question: " . $question;
        aicb_log_activity('rating', $log_content);
    }
    wp_send_json_success('Rating received.');
}

add_action('wp_ajax_aicb_get_welcome', 'aicb_get_welcome_callback');
add_action('wp_ajax_nopriv_aicb_get_welcome', 'aicb_get_welcome_callback');
function aicb_get_welcome_callback() {
    check_ajax_referer('aicb_chat_nonce', 'nonce');

    $options = get_option('aicb_settings');
    if (!isset($options['aicb_enable_personalized_welcome']) || !$options['aicb_enable_personalized_welcome']) {
        wp_send_json_error(['message' => 'Feature not enabled.']);
        return;
    }

    $history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : [];
    if (empty($history)) {
        wp_send_json_error(['message' => 'No history.']);
        return;
    }

    $welcome_template = !empty($options['aicb_welcome_prompt']) ? $options['aicb_welcome_prompt'] : "Hello! I see you're interested in our {categories} articles. How can I help you today?";

    $categories = [];
    foreach ($history as $page) {
        if (!empty($page['categories'])) {
            $categories = array_merge($categories, $page['categories']);
        }
    }
    $categories = array_unique($categories);
    $category_list = !empty($categories) ? implode(', ', $categories) : 'various';

    $personalized_message = str_replace('{categories}', $category_list, $welcome_template);

    $related_html = '';
    if (!empty($options['aicb_show_related_content']) && !empty($categories)) {
        $last_viewed_id = !empty($history) && isset($history[count($history) - 1]['id']) ? $history[count($history) - 1]['id'] : 0;
        $args = [
            'post_type' => 'post',
            'posts_per_page' => 3,
            'category_name' => implode(',', $categories),
            'orderby' => 'rand',
            'post__not_in' => [$last_viewed_id],
        ];
        $related_query = new WP_Query($args);
        if ($related_query->have_posts()) {
            $related_html .= '<div class="aicb-related-container" style="margin-top: 1rem;"><h4>Here are a few articles you might like:</h4><ul>';
            $button_text = !empty($options['aicb_chat_download_button_text']) ? esc_html($options['aicb_chat_download_button_text']) : 'Download';
            $button_icon = !empty($options['aicb_chat_download_button_icon']) ? esc_html($options['aicb_chat_download_button_icon']) : '✨';
            
            while ($related_query->have_posts()) {
                $related_query->the_post();
                $download_link = get_post_meta(get_the_ID(), '_aicb_download_link', true);
                $action_button = '';
                if (!empty($download_link)) {
                    if (aicb_is_premium_user()) {
                        $action_button = '<button class="aicb-link-action-btn" title="Download" data-download-url="' . esc_url($download_link) . '">' . $button_text . ' ' . $button_icon . '</button>';
                    } else {
                        $action_button = '<button class="aicb-link-action-btn" title="Upgrade to Download" data-premium-required="true">' . $button_text . ' ' . $button_icon . '</button>';
                    }
                }
                $related_html .= '<li><a href="#" class="aicb-content-loader" data-post-id="' . esc_attr(get_the_ID()) . '" data-permalink="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>' . $action_button . '</li>';
            }
            $related_html .= '</ul></div>';
            wp_reset_postdata();
        }
    }

    wp_send_json_success(['message' => $personalized_message, 'related_html' => $related_html]);
}

add_action('wp_ajax_aicb_rate_post', 'aicb_rate_post_callback');
add_action('wp_ajax_nopriv_aicb_rate_post', 'aicb_rate_post_callback');
function aicb_rate_post_callback() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aicb_chat_nonce')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
        return;
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $rating = isset($_POST['rating']) ? sanitize_text_field($_POST['rating']) : '';

    if (empty($post_id) || !in_array($rating, ['up', 'down'])) {
        wp_send_json_error(['message' => 'Invalid data.']);
        return;
    }

    $visitor_ip = aicb_get_visitor_ip();
    $rated_ips = get_post_meta($post_id, '_aicb_rated_ips', true);
    if (!is_array($rated_ips)) {
        $rated_ips = [];
    }

    $cookie_name = 'aicb_rated_post_' . $post_id;
    if (isset($_COOKIE[$cookie_name]) || in_array($visitor_ip, $rated_ips)) {
        wp_send_json_error(['message' => 'You have already rated this post.']);
        return;
    }

    $up_votes = get_post_meta($post_id, '_aicb_rating_up', true);
    $down_votes = get_post_meta($post_id, '_aicb_rating_down', true);

    $up_votes = $up_votes ? absint($up_votes) : 0;
    $down_votes = $down_votes ? absint($down_votes) : 0;

    if ($rating === 'up') {
        $up_votes++;
        update_post_meta($post_id, '_aicb_rating_up', $up_votes);
    } else {
        $down_votes++;
        update_post_meta($post_id, '_aicb_rating_down', $down_votes);
    }

    $rated_ips[] = $visitor_ip;
    update_post_meta($post_id, '_aicb_rated_ips', $rated_ips);

    aicb_log_activity('post_rating', "Rating: {$rating}", $post_id);

    setcookie($cookie_name, '1', time() + (YEAR_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);

    wp_send_json_success([
        'up_votes' => $up_votes,
        'down_votes' => $down_votes
    ]);
}


add_action('wp_ajax_aicb_handle_suggestion', 'aicb_handle_suggestion_callback');
function aicb_handle_suggestion_callback() {
    check_ajax_referer('aicb_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    global $wpdb;
    $suggestions_table = $wpdb->prefix . 'aicb_suggestions';
    $suggestion_id = isset($_POST['suggestion_id']) ? absint($_POST['suggestion_id']) : 0;
    $action = isset($_POST['suggestion_action']) ? sanitize_key($_POST['suggestion_action']) : '';

    $suggestion = $wpdb->get_row($wpdb->prepare("SELECT * FROM $suggestions_table WHERE id = %d", $suggestion_id));

    if (!$suggestion) {
        wp_send_json_error(['message' => 'Suggestion not found.']);
        return;
    }

    if ($action === 'approve') {
        $post_data = array(
            'post_title'    => wp_strip_all_tags($suggestion->original_question),
            'post_content'  => $suggestion->suggested_answer,
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
            'post_type'     => 'aicb_knowledge',
        );
        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            $wpdb->update($suggestions_table, ['status' => 'approved'], ['id' => $suggestion_id]);
            wp_send_json_success(['message' => 'Suggestion approved and added to Knowledge Base.']);
        } else {
            wp_send_json_error(['message' => 'Failed to create Knowledge Base entry.']);
        }
    } elseif ($action === 'delete') {
        $wpdb->delete($suggestions_table, ['id' => $suggestion_id]);
        wp_send_json_success(['message' => 'Suggestion deleted.']);
    } else {
        wp_send_json_error(['message' => 'Invalid action.']);
    }
}

add_action('wp_ajax_aicb_submit_suggestion', 'aicb_submit_suggestion_callback');
add_action('wp_ajax_nopriv_aicb_submit_suggestion', 'aicb_submit_suggestion_callback');
function aicb_submit_suggestion_callback() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aicb_chat_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    global $wpdb;
    $suggestions_table = $wpdb->prefix . 'aicb_suggestions';

    $original_question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
    $suggested_answer = isset($_POST['suggestion']) ? sanitize_textarea_field($_POST['suggestion']) : '';

    if (empty($original_question) || empty($suggested_answer)) {
        wp_send_json_error(['message' => 'Missing required data.']);
        return;
    }

    $wpdb->insert($suggestions_table, array(
        'time' => current_time('mysql'),
        'original_question' => $original_question,
        'suggested_answer' => $suggested_answer,
        'status' => 'pending',
        'visitor_ip' => aicb_get_visitor_ip(),
    ));

    if ($wpdb->insert_id) {
        wp_send_json_success(['message' => 'Thank you for your feedback!']);
    } else {
        wp_send_json_error(['message' => 'Could not save your suggestion.']);
    }
}

add_action('wp_ajax_aicb_generate_seo_tags', 'aicb_generate_seo_tags_callback');
function aicb_generate_seo_tags_callback() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aicb_admin_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    $options = get_option('aicb_settings');
    $api_key = isset($options['aicb_gemini_api_key']) ? $options['aicb_gemini_api_key'] : '';

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API key is not set.']);
        return;
    }

    $post_content = isset($_POST['content']) ? wp_strip_all_tags($_POST['content']) : '';

    if (empty($post_content)) {
        wp_send_json_error(['message' => 'Post content is empty.']);
        return;
    }

    $seo_prompt = "Based on the following article content, generate an SEO-friendly title (under 60 characters) and a meta description (under 160 characters). Respond ONLY with a JSON object in the format: {\"seo_title\": \"Your Generated Title\", \"meta_description\": \"Your generated meta description.\"}. Article Content: " . substr($post_content, 0, 4000);

    $api_url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    $request_body = json_encode([
        'contents' => [['role' => 'user', 'parts' => [['text' => $seo_prompt]]]],
    ]);

    $response = wp_remote_post($api_url, ['body' => $request_body, 'headers' => ['Content-Type' => 'application/json'], 'timeout' => 40]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API Connection Error: ' . $response->get_error_message()]);
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $text_response = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    $json_response = json_decode(trim(str_replace(['```json', '```'], '', $text_response)), true);

    if (json_last_error() === JSON_ERROR_NONE && isset($json_response['seo_title']) && isset($json_response['meta_description'])) {
        wp_send_json_success($json_response);
    } else {
        wp_send_json_error(['message' => 'Failed to parse a valid JSON response from the API.']);
    }
}

add_action('wp_ajax_aicb_analyze_page_seo', 'aicb_analyze_page_seo_callback');
function aicb_analyze_page_seo_callback() {
    check_ajax_referer('aicb_admin_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    $options = get_option('aicb_settings');
    $api_key = isset($options['aicb_gemini_api_key']) ? $options['aicb_gemini_api_key'] : '';

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API key is not set.']);
        return;
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if (empty($post_id)) {
        wp_send_json_error(['message' => 'Invalid Post ID.']);
        return;
    }

    $post = get_post($post_id);
    $post_content = wp_strip_all_tags($post->post_content);
    $post_title = $post->post_title;
    $seo_title = get_post_meta($post_id, '_aicb_seo_title', true);
    $meta_desc = get_post_meta($post_id, '_aicb_meta_description', true);

    $analysis_prompt = "
    Analyze the on-page SEO of the following article. Provide a detailed analysis in a structured JSON format.
    
    Article Title: \"$post_title\"
    Article Content (first 4000 chars): \"" . substr($post_content, 0, 4000) . "\"
    Current SEO Title: \"$seo_title\"
    Current Meta Description: \"$meta_desc\"

    Perform the following analysis:
    1.  **Readability**: Score from 0-100. Provide a brief analysis and one key suggestion.
    2.  **Keyword Analysis**: Identify the primary keyword/topic. Provide a brief analysis of its usage.
    3.  **SEO Title**: Score from 0-100 based on length, keyword placement, and appeal. Provide a suggestion if the score is below 85.
    4.  **Meta Description**: Score from 0-100 based on length, keyword placement, and call-to-action. Provide a suggestion if the score is below 85.

    Respond ONLY with a JSON object in the following format:
    {
      \"health_score\": Overall score from 0-100,
      \"readability\": {\"score\": 0-100, \"feedback\": \"...\", \"suggestion\": \"...\"},
      \"keyword_analysis\": {\"primary_keyword\": \"...\", \"feedback\": \"...\"},
      \"seo_title\": {\"score\": 0-100, \"feedback\": \"...\", \"suggestion\": \"...\"},
      \"meta_description\": {\"score\": 0-100, \"feedback\": \"...\", \"suggestion\": \"...\"}
    }
    ";

    $api_url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    $request_body = json_encode([
        'contents' => [['role' => 'user', 'parts' => [['text' => $analysis_prompt]]]],
        'generationConfig' => [ 'responseMimeType' => 'application/json' ]
    ]);

    $response = wp_remote_post($api_url, ['body' => $request_body, 'headers' => ['Content-Type' => 'application/json'], 'timeout' => 60]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API Connection Error: ' . $response->get_error_message()]);
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $text_response = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    $json_response = json_decode($text_response, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($json_response['health_score'])) {
        wp_send_json_success($json_response);
    } else {
        wp_send_json_error(['message' => 'Failed to parse a valid JSON response from the API. Please try again.']);
    }
}

add_action('wp_ajax_aicb_autofix_seo_issue', 'aicb_autofix_seo_issue_callback');
function aicb_autofix_seo_issue_callback() {
    check_ajax_referer('aicb_admin_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $issue_type = isset($_POST['issue_type']) ? sanitize_key($_POST['issue_type']) : '';
    $suggestion = isset($_POST['suggestion']) ? sanitize_text_field($_POST['suggestion']) : '';

    if (empty($post_id) || empty($issue_type) || empty($suggestion)) {
        wp_send_json_error(['message' => 'Invalid data provided.']);
        return;
    }

    $meta_key = '';
    switch ($issue_type) {
        case 'seo_title':
            $meta_key = '_aicb_seo_title';
            break;
        case 'meta_description':
            $meta_key = '_aicb_meta_description';
            break;
        default:
            wp_send_json_error(['message' => 'Invalid issue type.']);
            return;
    }

    if (update_post_meta($post_id, $meta_key, $suggestion)) {
        wp_send_json_success(['message' => ucfirst(str_replace('_', ' ', $issue_type)) . ' updated successfully.']);
    } else {
        wp_send_json_error(['message' => 'Could not update the ' . str_replace('_', ' ', $issue_type) . '.']);
    }
}

add_action('wp_ajax_aicb_get_autocomplete_suggestions', 'aicb_get_autocomplete_suggestions_callback');
add_action('wp_ajax_nopriv_aicb_get_autocomplete_suggestions', 'aicb_get_autocomplete_suggestions_callback');
function aicb_get_autocomplete_suggestions_callback() {
    check_ajax_referer('aicb_chat_nonce', 'nonce');

    if (!aicb_can_use_feature('autocomplete')) {
        wp_send_json_error(['suggestion' => '']);
        return;
    }

    $partial_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

    if (empty($partial_query)) {
        wp_send_json_error(['suggestion' => '']);
        return;
    }

    global $wpdb;
    $activity_table = $wpdb->prefix . 'aicb_activity_log';
    
    // Find the most popular search query that starts with the partial query
    $suggestion = $wpdb->get_var($wpdb->prepare(
        "SELECT content FROM {$activity_table} WHERE event_type = 'search' AND content LIKE %s GROUP BY content ORDER BY COUNT(*) DESC, content ASC LIMIT 1",
        $wpdb->esc_like($partial_query) . '%'
    ));

    if ($suggestion && strcasecmp($suggestion, $partial_query) !== 0) {
        wp_send_json_success(['suggestion' => $suggestion]);
    } else {
        wp_send_json_error(['suggestion' => '']);
    }
}
