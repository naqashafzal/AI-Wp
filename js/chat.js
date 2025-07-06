jQuery(document).ready(function($) {
    // --- Element Selectors ---
    const chatForm = $('#ai-chat-form');
    const userInput = $('#user-input');
    const chatWindow = $('.ai-chat-window');
    const viewContainer = $('.aicb-view-container');
    const messagesView = viewContainer.find('.ai-chat-messages-view');
    const messagesContainer = messagesView.find('.ai-chat-messages');
    const contentView = viewContainer.find('.aicb-content-view');
    let conversationHistory = [];
    const MAX_HISTORY = 6;

    // --- Helper function to scroll to the bottom ---
    function scrollToBottom() {
        // A short delay gives the browser time to render the new message
        setTimeout(() => {
            chatWindow.scrollTop(chatWindow.prop("scrollHeight"));
        }, 10);
    }

    // --- Content Loader Logic ---
    messagesContainer.on('click', '.aicb-content-loader', function(e) {
        e.preventDefault();
        const link = $(this);
        
        // Logic to open in a new tab if the setting is selected
        if (aicb_settings.content_display_mode === 'new_tab') {
            const permalink = link.data('permalink');
            if (permalink) {
                window.open(permalink, '_blank');
            }
            return; // Stop execution for new tab
        }

        // Existing logic for "in_chatbox" mode
        const postId = link.data('post-id');
        if (!postId) return;

        // Hide chat and show spinner
        messagesView.hide();
        contentView.html('<a href="#" class="aicb-content-back-button">← Back to Chat</a><div class="aicb-content-loader-spinner"></div>').show();
        chatWindow.scrollTop(0);

        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: { action: 'aicb_get_post_content', nonce: aicb_settings.nonce, post_id: postId },
            success: function(response) {
                contentView.find('.aicb-content-loader-spinner').remove();
                if (response.success) {
                    contentView.append(response.data.html);
                } else {
                    contentView.append('<p>Sorry, could not load content.</p>');
                }
            }
        });
    });

    // Handler for the back button
    viewContainer.on('click', '.aicb-content-back-button', function(e) {
        e.preventDefault();
        contentView.hide().html('');
        messagesView.show();
        scrollToBottom();
    });

    // --- Content Rating Logic ---
    viewContainer.on('click', '.aicb-content-ratings .aicb-rating-thumb', function() {
        const thumb = $(this);
        const wrapper = thumb.closest('.aicb-content-ratings');
        if (wrapper.hasClass('rated')) return;
        wrapper.addClass('rated');
        
        $.ajax({
            url: aicb_settings.ajax_url, type: 'POST',
            data: {
                action: 'aicb_rate_content', nonce: aicb_settings.nonce,
                rating: thumb.data('rating'), post_id: wrapper.data('post-id')
            },
            success: function() { 
                wrapper.find('span:not(.aicb-rating-thumb)').text('Thanks for your feedback!');
                thumb.css('opacity', 1);
             }
        });
    });

    // --- Form Submission Logic ---
    chatForm.on('submit', function(e) {
        e.preventDefault();
        const message = userInput.val().trim();
        if (message === '') return;

        if (contentView.is(':visible')) {
            $('.aicb-content-back-button').click();
        }

        messagesContainer.append(`<div class="message-wrapper user-message-wrapper"><div class="ai-chat-message user-message"><p>${message}</p></div></div>`);
        userInput.val('').css('height', 'auto');
        scrollToBottom();

        const thinkingIndicator = $(`<div class="message-wrapper ai-message-wrapper"><div class="ai-chat-message ai-message thinking-indicator"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div></div>`);
        messagesContainer.append(thinkingIndicator);
        scrollToBottom();

        if (aicb_settings.enable_memory) {
            conversationHistory.push({ role: 'user', parts: [{ text: message }] });
        }

        $.ajax({
            url: aicb_settings.ajax_url, type: 'POST',
            data: { 
                action: 'aicb_get_response', query: message, nonce: aicb_settings.nonce,
                history: aicb_settings.enable_memory ? JSON.stringify(conversationHistory) : null
            },
            success: function(response) {
                if (response.success) {
                    let finalHtml = response.data.answer;
                    if (response.data.sources_html) { finalHtml += response.data.sources_html; }
                    if (response.data.search_url) { finalHtml += `<div class="aicb-search-button-wrapper"><a href="${response.data.search_url}" target="_blank" class="aicb-search-button">View all results on site</a></div>`; }
                    
                    thinkingIndicator.find('.ai-chat-message').removeClass('thinking-indicator').html(finalHtml);
                    scrollToBottom();

                    if (aicb_settings.enable_memory) {
                        conversationHistory.push({ role: 'model', parts: [{ text: $(`<div>${response.data.answer}</div>`).text() }] });
                        if (conversationHistory.length > MAX_HISTORY) { conversationHistory = conversationHistory.slice(-MAX_HISTORY); }
                    }

                    if (response.data.ad_code) {
                        const adBubble = $(`<div class="message-wrapper ai-message-wrapper"><div class="ai-chat-message ai-message aicb-ad-message">${response.data.ad_code}</div></div>`);
                        messagesContainer.append(adBubble);
                        scrollToBottom();
                    }
                } else {
                    let errorMessage = 'Sorry, an error occurred.';
                    if (response.data && response.data.answer) { errorMessage = response.data.answer; }
                    thinkingIndicator.find('.ai-chat-message').removeClass('thinking-indicator').html(`<p>${errorMessage}</p>`);
                }
            },
            error: function() {
                thinkingIndicator.find('.ai-chat-message').removeClass('thinking-indicator').html('<p>CRITICAL ERROR: Could not connect to the server.</p>');
            }
        });
    });
    
    userInput.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.submit();
        }
    });

    getPersonalizedWelcome();
    initializeAutocomplete();
});
