<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hooks into the WordPress footer to add the floating chatbox HTML.
 */
add_action('wp_footer', 'aicb_add_floating_chatbox_html');
function aicb_add_floating_chatbox_html() {
    $options = get_option('aicb_settings');
    
    $is_floating_enabled = isset($options['aicb_enable_floating_chatbox']) && $options['aicb_enable_floating_chatbox'];
    $is_takeover_active = isset($options['aicb_front_page_takeover']) && $options['aicb_front_page_takeover'];

    if ($is_floating_enabled && (!is_front_page() || !$is_takeover_active)) {
        $notification_badge = isset($options['aicb_launcher_notification']) ? trim($options['aicb_launcher_notification']) : '';
        $cta_text = isset($options['aicb_launcher_cta_text']) ? trim($options['aicb_launcher_cta_text']) : '';
        $is_fullscreen_enabled = isset($options['aicb_enable_floating_fullscreen']) && $options['aicb_enable_floating_fullscreen'];
        ?>
        <div id="aicb-floating-widget">
            <div class="aicb-float-window">
                <div class="aicb-float-header">
                    <?php if ($is_fullscreen_enabled) : ?>
                        <button id="aicb-fullscreen-button" title="Toggle Fullscreen">
                            <svg class="icon-expand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3a1 1 0 00-1 1v2.25a.75.75 0 001.5 0V5h1.25a.75.75 0 000-1.5H4a1 1 0 00-1 1zM16 3a1 1 0 011 1v1.25a.75.75 0 01-1.5 0V5h-1.25a.75.75 0 010-1.5H16zM3 17a1 1 0 011-1h1.25a.75.75 0 010 1.5H5v1.25a.75.75 0 01-1.5 0V17zM17 16a1 1 0 00-1 1v1.25a.75.75 0 001.5 0V17h-1.25a.75.75 0 000-1.5H17z"/></svg>
                            <svg class="icon-compress" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.25 3.75a.75.75 0 00-1.5 0V5H3.5a.75.75 0 000 1.5H5v1.25a.75.75 0 001.5 0V5h.25a.75.75 0 000-1.5H6.25V3.75zM13.75 3.75a.75.75 0 00-1.5 0V5h-1.25a.75.75 0 000 1.5H12.5v1.25a.75.75 0 001.5 0V5h.25a.75.75 0 000-1.5H13.75V3.75zM3.5 13.75a.75.75 0 000 1.5H5v.25a.75.75 0 001.5 0v-1.25H5a.75.75 0 00-1.5 0zM12.5 13.75a.75.75 0 000 1.5h1.25v.25a.75.75 0 001.5 0v-1.25H13.75a.75.75 0 00-1.25 0z"/></svg>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="aicb-float-messages-area">
                    <div class="aicb-float-message-wrapper aicb-float-ai-wrapper" id="aicb-float-welcome-message">
                        <div class="aicb-float-message aicb-float-ai-message"><p>Hello! How can I help?</p></div>
                    </div>
                </div>
                <div class="aicb-float-input-container">
                    <form class="aicb-float-form">
                        <div class="aicb-float-input-wrapper">
                            <textarea placeholder="Ask me anything..." rows="1"></textarea>
                            <button type="submit" class="aicb-float-send-button">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.949a.75.75 0 00.95.539h4.002a.75.75 0 010 1.5H4.643a.75.75 0 00-.95.539l-1.414 4.949a.75.75 0 00.826.95 28.896 28.896 0 0015.293-7.154.75.75 0 000-1.115A28.897 28.897 0 003.105 2.289z" /></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ( ! empty( $cta_text ) ) : ?>
                <div class="aicb-launcher-text-bubble"><?php echo esc_html( $cta_text ); ?></div>
            <?php endif; ?>

            <div id="aicb-launcher">
                <?php if ( ! empty( $notification_badge ) ) : ?>
                    <span class="aicb-launcher-badge"><?php echo esc_html( $notification_badge ); ?></span>
                <?php endif; ?>
                
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 10.875L13.125 12L12 13.125L10.875 12L12 10.875Z" fill="white"/>
                    <path d="M12 4.25L14.0083 8.26667L18.025 10.275L14.0083 12.2833L12 16.3L9.99167 12.2833L5.975 10.275L9.99167 8.26667L12 4.25Z" fill="white"/>
                </svg>

            </div>
        </div>
        <?php
    }
}
