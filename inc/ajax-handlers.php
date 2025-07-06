<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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
        foreach ($posts_array as $post_item) {
            if (isset($post_item['is_ad'])) {
                $suggestions_html .= '<li class="aicb-inline-ad"><a href="' . esc_url($post_item['url']) . '" target="_blank">' . esc_html($post_item['title']) . '</a></li>';
            } else {
                $suggestions_html .= '<li><a href="#" class="aicb-content-loader" data-post-id="' . esc_attr($post_item['id']) . '" data-permalink="' . esc_url($post_item['url']) . '">' . esc_html($post_item['title']) . '</a></li>';
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

// --- AJAX HANDLERS ---
add_action('wp_ajax_aicb_get_response', 'aicb_handle_ai_query');
add_action('wp_ajax_nopriv_aicb_get_response', 'aicb_handle_ai_query');
function aicb_handle_ai_query() {
    check_ajax_referer('aicb_chat_nonce', 'nonce');
    $user_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    if (empty($user_query)) { wp_send_json_error(['answer' => 'Query cannot be empty.']); return; }

    $cache_key = 'aicb_query_' . md5($user_query);
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


function aicb_perform_advanced_search($user_query, $api_key, $options = [], $ad_code = null) {
    $cache_key = 'aicb_query_' . md5($user_query); 
    $history_json = isset($_POST['history']) ? stripslashes($_POST['history']) : '[]';
    $chat_history = json_decode($history_json, true);
    if (!is_array($chat_history)) { $chat_history = []; }
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
    if (empty($context)) {
        $answer = "<p>Sorry, I couldn't find any information about that. Please try a different question.</p>";
        $answer .= aicb_get_fallback_suggestions($user_query);
        $response_data = ['answer' => $answer, 'query' => $user_query, 'ad_code' => $ad_code, 'search_url' => $search_url];
        set_transient($cache_key, $response_data, 2 * HOUR_IN_SECONDS);
        wp_send_json_success($response_data);
        return;
    }
    $show_sources = isset($options['aicb_show_sources']) && $options['aicb_show_sources'];
    if ($show_sources && !empty($sources)) {
        $sources_html = '<div class="aicb-sources-container"><h4>Sources:</h4><ul>';
        foreach ($sources as $source) {
            if (isset($source['is_ad'])) {
                 $sources_html .= '<li class="aicb-inline-ad"><a href="' . esc_url($source['url']) . '" target="_blank">' . esc_html($source['title']) . '</a></li>';
            } else {
                $sources_html .= '<li><a href="#" class="aicb-content-loader" data-post-id="' . esc_attr($source['id']) . '" data-permalink="' . esc_url($source['url']) . '">' . esc_html($source['title']) . '</a></li>';
            }
        }
        $sources_html .= '</ul></div>';
    }
    $default_system_prompt = "You are a helpful assistant for a website named " . get_bloginfo('name') . ". Your primary goal is to answer user questions based on the context provided from the website's content. Be friendly, concise, and helpful.";
    $system_prompt = isset($options['aicb_system_prompt']) ? $options['aicb_system_prompt'] : $default_system_prompt;
    $rag_prompt = "Based ONLY on the following context, provide a conversational answer to the user's question. Do not use any external knowledge. If the context does not contain the answer, state that you could not find the information on this website.\n\n" . "--- CONTEXT ---\n" . $context . "\n--- END CONTEXT ---";
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
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $api_key;
    }
    $request_body = json_encode(['contents' => $api_contents, 'systemInstruction' => ['parts' => [['text' => $system_prompt]]], 'safetySettings' => [['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'], ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH']]]);
    $response = wp_remote_post($api_url, ['body' => $request_body, 'headers' => ['Content-Type' => 'application/json'], 'timeout' => 60]);
    if (is_wp_error($response)) { wp_send_json_error(['answer' => 'Connection Error: ' . $response->get_error_message()]); return; }
    $data = json_decode(wp_remote_retrieve_body($response));
    if (!empty($data->candidates[0]->content->parts[0]->text)) {
        $answer_text = $data->candidates[0]->content->parts[0]->text;
        aicb_log_training_data($user_query, $answer_text);
        $answer = nl2br(esc_html($answer_text));
        $response_data = ['answer' => $answer, 'query' => $user_query, 'ad_code' => $ad_code, 'sources_html' => $sources_html, 'search_url' => $search_url];
        set_transient($cache_key, $response_data, 2 * HOUR_IN_SECONDS);
        wp_send_json_success($response_data);
    } else {
        $error_message = 'An unknown API error occurred while generating the answer.';
        if (isset($data->promptFeedback->blockReason)) { $error_message = 'The request was blocked by the API. Reason: ' . str_replace('_', ' ', $data->promptFeedback->blockReason); }
        elseif (isset($data->error->message)) { $error_message = $data->error->message; }
        wp_send_json_error(['answer' => 'API Error: ' . esc_html($error_message)]);
    }
}

function aicb_perform_simple_search($user_query, $ad_code = null) {
    $cache_key = 'aicb_query_' . md5($user_query);
    $options = get_option('aicb_settings');
    $inline_ad_text = isset($options['aicb_inline_ad_text']) ? trim($options['aicb_inline_ad_text']) : '';
    $ad_position = isset($options['aicb_inline_ad_position']) ? absint($options['aicb_inline_ad_position']) : 3;
    $ad_injected = false;
    $search_args = array('post_type' => ['post', 'page'], 'posts_per_page' => 5, 's' => $user_query, 'post_status' => 'publish');
    $search_query = new WP_Query($search_args);
    if ($search_query->have_posts()) {
        $response_html = '<p>Here are some results from the website:</p><ul>';
        $counter = 1;
        while ($search_query->have_posts()) {
            $search_query->the_post();
            if (!empty($inline_ad_text) && !$ad_injected && $counter === $ad_position) {
                $response_html .= '<li class="aicb-inline-ad"><a href="' . esc_url($options['aicb_inline_ad_url']) . '" target="_blank">' . esc_html($inline_ad_text) . '</a></li>';
                $ad_injected = true;
            }
            $response_html .= '<li><a href="#" class="aicb-content-loader" data-post-id="' . get_the_ID() . '" data-permalink="' . get_permalink() . '">' . get_the_title() . '</a></li>';
            $counter++;
        }
        $response_html .= '</ul>';
        wp_reset_postdata();
    } else { 
        $response_html = '<p>Sorry, I couldn\'t find any information about that. Please try a different question.</p>';
        $response_html .= aicb_get_fallback_suggestions($user_query);
    }
    $response_data = ['answer' => $response_html, 'query' => $user_query, 'search_url' => home_url('/?s=' . urlencode($user_query)), 'ad_code' => $ad_code];
    set_transient($cache_key, $response_data, 2 * HOUR_IN_SECONDS);
    wp_send_json_success($response_data);
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
    check_ajax_referer('aicb_chat_nonce', 'nonce');
    
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $rating = isset($_POST['rating']) ? sanitize_text_field($_POST['rating']) : 'N/A';

    if($post_id > 0) {
        $post_title = get_the_title($post_id);
        $log_content = "Rating: " . $rating . " | Content: " . $post_title;
        aicb_log_activity('content_rating', $log_content, $post_id);
        wp_send_json_success('Rating received.');
    } else {
        wp_send_json_error('Invalid Post ID.');
    }
}
