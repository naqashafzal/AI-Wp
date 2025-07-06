<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Renders the complete HTML page for the "Chat Mode on Front Page" feature.
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
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        <?php wp_head(); ?>
    </head>
    <body <?php body_class("aicb-body"); ?>>
        <?php aicb_render_chat_html(); ?>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
}

/**
 * Renders the core HTML structure of the chat application.
 */
function aicb_render_chat_html() {
    $options = get_option('aicb_settings');
    $footer_text = isset($options['aicb_footer_text']) ? $options['aicb_footer_text'] : 'AI Chatbox can make mistakes.';
    $logo_url = isset($options['aicb_sidebar_logo_url']) ? esc_url($options['aicb_sidebar_logo_url']) : '';
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
                            <div class="message-wrapper ai-message-wrapper" id="welcome-message">
                                <div class="ai-chat-message ai-message">
                                    <p>Hello! How can I help?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="aicb-view aicb-content-view">
                        
                    </div>
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
                    <div class="action-buttons">
                        <?php 
                        for ($i = 1; $i <= 4; $i++): 
                            $label = isset($options["aicb_button_{$i}_label"]) ? $options["aicb_button_{$i}_label"] : '';
                            $url = isset($options["aicb_button_{$i}_url"]) ? $options["aicb_button_{$i}_url"] : '#';
                            if (!empty($label)): ?>
                                <a href="<?php echo esc_url($url); ?>" class="action-btn" target="_blank"><?php echo esc_html($label); ?></a>
                            <?php endif;
                        endfor; 
                        ?>
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
