<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Send the verification email to a new user.
 *
 * @param int $user_id The new user's ID.
 * @param string $email The new user's email address.
 */
function aicb_send_verification_email($user_id, $email) {
    $verification_token = bin2hex(random_bytes(32));
    update_user_meta($user_id, 'aicb_verification_token', $verification_token);

    $verification_link = add_query_arg([
        'action' => 'aicb_verify_email',
        'user_id' => $user_id,
        'token' => $verification_token
    ], home_url('/'));

    $subject = 'Verify your email for ' . get_bloginfo('name');
    $message = 'Please click the following link to verify your email address: ' . $verification_link;

    wp_mail($email, $subject, $message);
}

add_action('init', 'aicb_handle_email_verification');
function aicb_handle_email_verification() {
    if (isset($_GET['action']) && $_GET['action'] === 'aicb_verify_email' && isset($_GET['user_id']) && isset($_GET['token'])) {
        $user_id = absint($_GET['user_id']);
        $token = sanitize_text_field($_GET['token']);

        $stored_token = get_user_meta($user_id, 'aicb_verification_token', true);

        if ($stored_token === $token) {
            update_user_meta($user_id, 'aicb_email_verified', true);
            delete_user_meta($user_id, 'aicb_verification_token');

            // Redirect to payment page
            $options = get_option('aicb_settings');
            $payment_url = isset($options['aicb_payment_url']) ? $options['aicb_payment_url'] : home_url('/');
            wp_redirect($payment_url);
            exit;
        } else {
            wp_die('Invalid verification link.');
        }
    }
}