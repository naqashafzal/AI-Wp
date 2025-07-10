<?php
/**
 * Membership System Handler
 *
 * This file contains all the backend logic for the membership system,
 * including user registration, login, account management, and subscription
 * displays, all handled via AJAX for a seamless in-chat experience.
 *
 * @package AI-Wp
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Main AJAX router for all membership-related actions.
 *
 * This function is hooked into WordPress's AJAX API for both logged-in and
 * non-logged-in users. It uses a 'view' parameter to determine which
 * specific action or form to render.
 */
add_action('wp_ajax_aicb_membership_action', 'aicb_membership_action_callback');
add_action('wp_ajax_nopriv_aicb_membership_action', 'aicb_membership_action_callback');
function aicb_membership_action_callback() {
    // Verify the nonce for security to prevent CSRF attacks.
    check_ajax_referer('aicb_chat_nonce', 'nonce');

    // Sanitize the requested view to ensure it's a valid key.
    $view = isset($_POST['view']) ? sanitize_key($_POST['view']) : '';

    // Route the request to the appropriate function based on the 'view' parameter.
    switch ($view) {
        case 'login_form':
            aicb_render_login_form();
            break;
        case 'register_form':
            aicb_render_register_form();
            break;
        case 'account_page':
            aicb_render_account_page();
            break;
        case 'subscriptions_page':
            aicb_render_subscriptions_page();
            break;
        case 'login_user':
            aicb_handle_login();
            break;
        case 'register_user':
            aicb_handle_registration();
            break;
        case 'logout_user':
            wp_logout();
            wp_send_json_success(['message' => 'You have been logged out.']);
            break;
    }

    // Terminate the script to ensure a clean AJAX response.
    wp_die();
}

/**
 * Renders and returns the HTML for the login form.
 */
function aicb_render_login_form() {
    ob_start();
    ?>
    <div class="aicb-form-container">
        <a href="#" class="aicb-content-back-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>Back to Chat</a>
        <h3>Login</h3>
        <form id="aicb-login-form">
            <p class="form-row">
                <label for="aicb_user_login">Username or Email</label>
                <input type="text" name="log" id="aicb_user_login" class="input" value="" size="20">
            </p>
            <p class="form-row">
                <label for="aicb_user_pass">Password</label>
                <input type="password" name="pwd" id="aicb_user_pass" class="input" value="" size="20">
            </p>
            <p class="form-row">
                <button type="submit" class="button button-primary">Log In</button>
            </p>
        </form>
        <div class="aicb-form-feedback"></div>
    </div>
    <?php
    wp_send_json_success(['html' => ob_get_clean()]);
}

/**
 * Renders and returns the HTML for the user registration form.
 */
function aicb_render_register_form() {
    ob_start();
    ?>
    <div class="aicb-form-container">
        <a href="#" class="aicb-content-back-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>Back to Chat</a>
        <h3>Register</h3>
        <form id="aicb-register-form">
            <p class="form-row">
                <label for="aicb_reg_user">Username</label>
                <input type="text" name="user_login" id="aicb_reg_user" class="input" value="">
            </p>
            <p class="form-row">
                <label for="aicb_reg_email">Email</label>
                <input type="email" name="user_email" id="aicb_reg_email" class="input" value="">
            </p>
            <p class="form-row">
                <label for="aicb_reg_pass">Password</label>
                <input type="password" name="user_pass" id="aicb_reg_pass" class="input" value="">
            </p>
            <p class="form-row">
                <button type="submit" class="button button-primary">Register</button>
            </p>
        </form>
        <div class="aicb-form-feedback"></div>
    </div>
    <?php
    wp_send_json_success(['html' => ob_get_clean()]);
}

/**
 * Renders and returns the HTML for the "My Account" page.
 */
function aicb_render_account_page() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['html' => '<p>You must be logged in to view this page.</p>']);
    }
    $current_user = wp_get_current_user();
    ob_start();
    ?>
    <div class="aicb-account-container">
        <a href="#" class="aicb-content-back-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>Back to Chat</a>
        <h3>My Account</h3>
        <div class="aicb-account-details">
            <p><strong>Username:</strong> <?php echo esc_html($current_user->user_login); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
            <p><strong>Subscription Status:</strong> <span class="aicb-subscription-status">Free Member</span></p>
        </div>
        <a href="#" class="button" id="aicb-view-subscriptions-btn">View Subscriptions</a>
    </div>
    <?php
    wp_send_json_success(['html' => ob_get_clean()]);
}

/**
 * Renders and returns the HTML for the subscription packages showcase.
 */
function aicb_render_subscriptions_page() {
    global $wpdb;
    $packages_table = $wpdb->prefix . 'aicb_membership_packages';
    $packages = $wpdb->get_results("SELECT * FROM $packages_table ORDER BY price ASC");
    ob_start();
    ?>
    <div class="aicb-subscriptions-container">
        <a href="#" class="aicb-content-back-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>Back to Chat</a>
        <h3>Subscription Plans</h3>
        <div class="aicb-packages-grid">
            <?php if (!empty($packages)): ?>
                <?php foreach ($packages as $package): ?>
                    <div class="aicb-package-card">
                        <div class="aicb-package-header">
                            <div class="aicb-package-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                            </div>
                            <h4><?php echo esc_html($package->name); ?></h4>
                        </div>
                        <p class="price">$<?php echo esc_html(floor($package->price)); ?><span class="duration">/Per Month</span></p>
                        
                        <ul class="aicb-package-features">
                            <?php
                            $features = unserialize($package->features);
                            if (!empty($features) && is_array($features)) {
                                foreach ($features as $feature) {
                                    echo '<li>' . esc_html($feature) . '</li>';
                                }
                            }
                            ?>
                        </ul>
                        <button class="button aicb-subscribe-btn" data-package-id="<?php echo esc_attr($package->id); ?>">GET STARTED</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No subscription packages are available at the moment.</p>
            <?php endif; ?>
        </div>
        <div id="aicb-payment-placeholder" style="display:none; margin-top: 20px;">
            <h4>Complete Your Payment</h4>
            <p>To complete your subscription, please proceed with the payment. This is a placeholder for your actual payment gateway integration (e.g., Stripe, PayPal).</p>
            <button class="button" id="aicb-confirm-payment-btn">Confirm Payment (Test)</button>
        </div>
    </div>
    <?php
    wp_send_json_success(['html' => ob_get_clean()]);
}

/**
 * Handles user login attempts using WordPress's built-in `wp_signon`.
 */
function aicb_handle_login() {
    $creds = array(
        'user_login'    => sanitize_user($_POST['log']),
        'user_password' => $_POST['pwd'],
        'remember'      => true
    );
    $user = wp_signon($creds, false);
    if (is_wp_error($user)) {
        wp_send_json_error(array('message' => $user->get_error_message()));
    } else {
        wp_send_json_success(array('message' => 'Login successful!'));
    }
}

/**
 * Handles new user registration using WordPress's built-in `register_new_user`.
 */
function aicb_handle_registration() {
    $user_login = sanitize_user($_POST['user_login']);
    $user_email = sanitize_email($_POST['user_email']);
    $user_pass  = $_POST['user_pass'];

    // Use WordPress's core function to handle registration.
    $errors = register_new_user($user_login, $user_email);

    if (!is_wp_error($errors)) {
        // `register_new_user` sends an email with a password link.
        // To set the password directly, we do this:
        $user_id = $errors;
        wp_set_password($user_pass, $user_id);
        
        // Optionally, log the user in immediately after registration.
        // wp_set_current_user($user_id, $user_login);
        // wp_set_auth_cookie($user_id);
        
        wp_send_json_success(array('message' => 'Registration complete. You can now log in.'));
    } else {
        wp_send_json_error(array('message' => $errors->get_error_message()));
    }
}
