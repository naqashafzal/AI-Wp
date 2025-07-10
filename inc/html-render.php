<?php
/**
 * HTML Rendering
 *
 * This file is responsible for rendering the HTML structure for the chat
 * interface, both for the full-page takeover and for shortcode embedding.
 *
 * @package AI-Wp
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Renders the complete HTML page for the "Chat Mode on Front Page" feature.
 *
 * This function creates a dedicated, clean HTML document for the chatbox,
 * ensuring that it takes over the full page without interference from the
 * active theme or other plugins.
 */
function aicb_render_chat_page() {
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        
        <?php 
        // This prints essential <head> elements like styles and title.
        wp_head(); 
        ?>
        
        <style>
            .aicb-view { display: none; }
            .aicb-view.active { display: flex; flex-direction: column; }
        </style>
    </head>
    <body <?php body_class("aicb-body"); ?>>
        <?php 
        // This renders the core chatbox HTML structure.
        aicb_render_chat_html(); 
        ?>

        <?php 
        /**
         * REVERTED: Now using wp_footer() to allow third-party plugins
         * to enqueue their scripts and styles, including floating ads.
         */
        wp_footer();
        ?>
    </body>
    </html>
    <?php
}

/**
 * Renders the core HTML structure of the chat application.
 * This function is used by both the full-page mode and the shortcode.
 */
function aicb_render_chat_html() {
    $options = get_option('aicb_settings');
    $footer_text = isset($options['aicb_footer_text']) ? $options['aicb_footer_text'] : 'AI Chatbox can make mistakes.';
    $logo_url = isset($options['aicb_sidebar_logo_url']) ? esc_url($options['aicb_sidebar_logo_url']) : '';
    $membership_enabled = isset($options['aicb_enable_membership']) && $options['aicb_enable_membership'];
    ?>
    <div id="ai-chat-app-container">
        <aside class="ai-chat-sidebar">
            <div class="site-branding">
                <?php 
                if ( ! empty( $logo_url ) ) : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                        <img src="<?php echo $logo_url; ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> Logo" />
                    </a>
                <?php elseif ( function_exists( 'the_custom_logo' ) ) :
                    the_custom_logo();
                endif; 
                ?>
            </div>
            <div class="ai-chat-widget-area">
                <?php 
                if ( is_active_sidebar( 'aicb-chat-sidebar' ) ) {
                    dynamic_sidebar( 'aicb-chat-sidebar' );
                } 
                ?>
            </div>
        </aside>
        <main class="ai-chat-area">
            <div class="ai-chat-window">
                <div class="aicb-view-container">
                    <div class="aicb-view ai-chat-messages-view active">
                        <div class="ai-chat-messages">
                            <div class="message-wrapper ai-message-wrapper" id="aicb-main-welcome-message">
                                <div class="ai-chat-message ai-message">
                                    <p>Hello! How can I help?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="aicb-view aicb-content-view"></div>
                    <div class="aicb-view aicb-login_form-view"></div>
                    <div class="aicb-view aicb-register_form-view"></div>
                    <div class="aicb-view aicb-account_page-view"></div>
                    <div class="aicb-view aicb-subscriptions_page-view"></div>
                </div>
            </div>
            <div class="ai-chat-input-container">
                <form id="ai-chat-form" class="ai-chat-form">
                    <div class="ai-chat-input-wrapper">
                        <textarea id="user-input" placeholder="Ask me anything..." rows="1"></textarea>
                        <div id="suggestion-ghost"></div>
                        <div class="input-actions">
                            <button type="submit" class="send-button">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                                    <path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.949a.75.75 0 00.95.539h4.002a.75.75 0 010 1.5H4.643a.75.75 0 00-.95.539l-1.414 4.949a.75.75 0 00.826.95 28.896 28.896 0 0015.293-7.154.75.75 0 000-1.115A28.897 28.897 0 003.105 2.289z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="action-buttons" id="aicb-action-buttons">
                        <?php if ($membership_enabled) : ?>
                            <?php if(is_user_logged_in()): ?>
                                <button type="button" class="action-btn" data-view="account_page"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><span>My Account</span></button>
                                <button type="button" class="action-btn" data-view="logout_user"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg><span>Logout</span></button>
                            <?php else: ?>
                                <button type="button" class="action-btn" data-view="login_form"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg><span>Login</span></button>
                                <button type="button" class="action-btn" data-view="register_form"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="17" y1="11" x2="23" y2="11"></line></svg><span>Register</span></button>
                            <?php endif; ?>
                             <button type="button" class="action-btn" data-view="subscriptions_page"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V8H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v4"></path><path d="M18 12a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2z"></path><path d="M4 6v12a2 2 0 0 0 2 2h9"></path></svg><span>Subscriptions</span></button>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="ai-chat-footer">
                    <p><?php echo esc_html( $footer_text ); ?></p>
                </div>
            </div>
        </main>
    </div>
    <?php
}
