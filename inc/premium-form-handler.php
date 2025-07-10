<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_aicb_get_premium_form', 'aicb_get_premium_form_callback');
add_action('wp_ajax_nopriv_aicb_get_premium_form', 'aicb_get_premium_form_callback');

function aicb_get_premium_form_callback() {
    check_ajax_referer('aicb_chat_nonce', 'nonce');

    ob_start();
    ?>
    <div class="aicb-premium-form-container">
        <a href="#" class="aicb-content-back-button">← Back to Chat</a>
        <h2>Premium Membership</h2>
        <p>Unlock premium features by signing up for a premium membership.</p>
        <form id="aicb-premium-form" method="post">
            <?php wp_nonce_field('aicb_premium_nonce', 'aicb_premium_nonce_field'); ?>
            <div class="form-row">
                <label for="aicb-premium-username">Username</label>
                <input type="text" id="aicb-premium-username" name="username" required />
            </div>
            <div class="form-row">
                <label for="aicb-premium-email">Email</label>
                <input type="email" id="aicb-premium-email" name="email" required />
            </div>
            <div class="form-row">
                <label for="aicb-premium-password">Password</label>
                <input type="password" id="aicb-premium-password" name="password" required />
            </div>
            <div class="form-row">
                <button type="submit">Register</button>
            </div>
        </form>
    </div>
    <?php
    $form_html = ob_get_clean();

    wp_send_json_success(['html' => $form_html]);
}

add_action('wp_ajax_aicb_premium_register', 'aicb_premium_register_callback');
add_action('wp_ajax_nopriv_aicb_premium_register', 'aicb_premium_register_callback');

function aicb_premium_register_callback() {
    check_ajax_referer('aicb_premium_nonce', 'aicb_premium_nonce_field');

    $username = sanitize_user($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }

    if (username_exists($username)) {
        wp_send_json_error(['message' => 'Username already exists.']);
    }

    if (email_exists($email)) {
        wp_send_json_error(['message' => 'Email already exists.']);
    }

    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
    }

    // Send verification email
    aicb_send_verification_email($user_id, $email);

    wp_send_json_success(['message' => 'Registration successful. Please check your email to verify your account.']);
}