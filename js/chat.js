jQuery(document).ready(function($) {
    // --- Element Selectors ---
    const chatWindow = $('.ai-chat-window');
    const messagesView = $('.ai-chat-messages-view');
    const viewContainer = $('.aicb-view-container');
    const actionButtonsContainer = $('#aicb-action-buttons');
    const chatForm = $('#ai-chat-form');
    const userInput = $('#user-input');
    const messagesContainer = $('.ai-chat-messages');
    let conversationHistory = [];
    const MAX_HISTORY = 6;

    // --- Function to get personalized welcome message ---
    function getPersonalizedWelcome() {
        if (aicb_settings.enable_personalized_welcome) {
            const BrowseHistory = localStorage.getItem('aicb_visitor_history') || '[]';
            const historyData = JSON.parse(BrowseHistory);

            if (historyData.length > 0) {
                $.ajax({
                    url: aicb_settings.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aicb_get_welcome',
                        nonce: aicb_settings.nonce,
                        history: BrowseHistory
                    },
                    success: function(response) {
                        if (response.success) {
                            let welcomeHtml = `<p>${response.data.message}</p>`;
                            if (response.data.related_html) {
                                welcomeHtml += response.data.related_html;
                            }
                            $('#aicb-main-welcome-message .ai-chat-message').html(welcomeHtml);
                        }
                    }
                });
            }
        }
    }
    getPersonalizedWelcome();


    // --- Helper function to scroll to the bottom of the chat window ---
    function scrollToBottom() {
        setTimeout(() => {
            chatWindow.scrollTop(chatWindow.prop("scrollHeight"));
        }, 10);
    }

    // --- Helper function to switch between different views (chat, login, account, etc.) ---
    function switchView(viewToShow) {
        $('.aicb-view').removeClass('active');
        viewToShow.addClass('active');
        chatWindow.scrollTop(0);
    }
    
    // --- Load a view (like login form, account page) via AJAX ---
    function loadView(viewName) {
        const targetView = $(`.aicb-${viewName}-view`);
        targetView.html('<div class="aicb-content-loader-spinner"></div>');
        switchView(targetView);

        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: {
                action: 'aicb_membership_action',
                nonce: aicb_settings.nonce,
                view: viewName
            },
            success: function(response) {
                if (response.success) {
                    targetView.html(response.data.html);
                } else {
                    targetView.html('<p>Error loading view.</p>');
                }
            }
        });
    }

    // --- Membership: Action Buttons Click Handler ---
    actionButtonsContainer.on('click', '.action-btn', function(e) {
        e.preventDefault();
        const view = $(this).data('view');

        if (view === 'logout_user') {
            $.ajax({
                url: aicb_settings.ajax_url,
                type: 'POST',
                data: { action: 'aicb_membership_action', nonce: aicb_settings.nonce, view: 'logout_user' },
                success: function() {
                    window.location.reload();
                }
            });
        } else {
            loadView(view);
        }
    });

    // --- Membership: Back to Chat Button ---
    viewContainer.on('click', '.aicb-content-back-button', function(e) {
        e.preventDefault();
        switchView(messagesView);
    });

    // --- Membership: Login Form Submission ---
    viewContainer.on('submit', '#aicb-login-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const feedbackDiv = form.siblings('.aicb-form-feedback');
        feedbackDiv.text('Logging in...').removeClass('error success').show();

        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=aicb_membership_action&view=login_user&nonce=' + aicb_settings.nonce,
            success: function(response) {
                if (response.success) {
                    feedbackDiv.text(response.data.message).addClass('success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    feedbackDiv.text(response.data.message).addClass('error');
                }
            }
        });
    });

    // --- Membership: Registration Form Submission ---
    viewContainer.on('submit', '#aicb-register-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const feedbackDiv = form.siblings('.aicb-form-feedback');
        feedbackDiv.text('Registering...').removeClass('error success').show();

        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=aicb_membership_action&view=register_user&nonce=' + aicb_settings.nonce,
            success: function(response) {
                if (response.success) {
                    feedbackDiv.text(response.data.message).addClass('success');
                     setTimeout(() => loadView('login_form'), 2000);
                } else {
                    feedbackDiv.text(response.data.message).addClass('error');
                }
            }
        });
    });
    
    // --- Membership: View Subscriptions from Account Page ---
    viewContainer.on('click', '#aicb-view-subscriptions-btn', function(e){
        e.preventDefault();
        loadView('subscriptions_page');
    });

    // --- Membership: Subscribe Button & Payment Placeholder ---
    viewContainer.on('click', '.aicb-subscribe-btn', function(e) {
        e.preventDefault();
        $('#aicb-payment-placeholder').slideDown();
    });
    
    // --- Membership: Test Payment Confirmation ---
    viewContainer.on('click', '#aicb-confirm-payment-btn', function(e) {
        e.preventDefault();
        aicb_show_modal('Payment Test', 'This is a placeholder for a real payment gateway. In a real scenario, after successful payment, you would grant the user premium access.');
    });

    // --- Main Chat Form Submission Logic ---
    chatForm.on('submit', function(e) {
        e.preventDefault();
        const message = userInput.val().trim();
        if (message === '') return;

        // 1. Sanitize and display the user's message immediately.
        const escapedMessage = $('<div />').text(message).html();
        const userMessageHtml = `<div class="message-wrapper user-message-wrapper"><div class="ai-chat-message user-message"><p>${escapedMessage}</p></div></div>`;
        messagesContainer.append(userMessageHtml);
        userInput.val('');
        scrollToBottom();
        
        // 2. Display the thinking indicator.
        const thinkingIndicatorHtml = `<div class="message-wrapper ai-message-wrapper" id="thinking-indicator-wrapper"><div class="ai-chat-message ai-message thinking-indicator"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div></div>`;
        messagesContainer.append(thinkingIndicatorHtml);
        scrollToBottom();

        // 3. Add to conversation history for context.
        conversationHistory.push({ role: 'user', parts: [{ text: message }] });

        // 4. Send the AJAX request to get the AI response.
        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: { 
                action: 'aicb_get_response', 
                query: message, 
                nonce: aicb_settings.nonce,
                history: JSON.stringify(conversationHistory)
            },
            success: function(response) {
                const thinkingWrapper = $('#thinking-indicator-wrapper');
                if (response.success) {
                    // Replace thinking indicator with the actual response
                    thinkingWrapper.find('.ai-chat-message').removeClass('thinking-indicator').html(response.data.answer);
                    conversationHistory.push({ role: 'model', parts: [{ text: response.data.answer }] });
                    // Prune history to prevent it from getting too long
                    if (conversationHistory.length > MAX_HISTORY * 2) {
                        conversationHistory.splice(0, 2); 
                    }

                    if (response.data.ad_code) {
                        const adBubble = $(`<div class="message-wrapper ai-message-wrapper"><div class="ai-chat-message ai-message">${response.data.ad_code}</div></div>`);
                        messagesContainer.append(adBubble);
                        scrollToBottom();
                    }

                } else {
                    // Show an error message if the AJAX call fails
                    thinkingWrapper.find('.ai-chat-message').removeClass('thinking-indicator').html('<p>Sorry, an error occurred. Please try again.</p>');
                }
                // Remove the temporary ID after use
                thinkingWrapper.removeAttr('id');
            },
            error: function() {
                // Show a more specific error if the connection fails
                const thinkingWrapper = $('#thinking-indicator-wrapper');
                thinkingWrapper.find('.ai-chat-message').removeClass('thinking-indicator').html('<p>Error: Could not connect to the server.</p>');
                thinkingWrapper.removeAttr('id');
            },
            complete: function() {
                scrollToBottom();
            }
        });
    });

    // --- Keydown event handler for submitting with Enter key ---
    userInput.on('keydown', function(e) {
        // Submit form on Enter key press (but not Shift+Enter for new lines)
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.submit();
        }
    });

    // --- Content Loader Logic for main chat view ---
    messagesContainer.on('click', '.aicb-content-loader', function(e) {
        e.preventDefault();
        const postId = $(this).data('post-id');
        const permalink = $(this).data('permalink');

        if (aicb_settings.content_display_mode === 'new_tab') {
            window.open(permalink, '_blank');
            return;
        }

        // For the main chat, we switch to the dedicated content view
        const targetView = $('.aicb-content-view');
        targetView.html('<div class="aicb-content-loader-spinner"></div>');
        switchView(targetView);

        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: {
                action: 'aicb_get_post_content',
                nonce: aicb_settings.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    let backButton = '<a href="#" class="aicb-content-back-button">← Back to Chat</a>';
                    targetView.html('<div class="aicb-full-content-view">' + backButton + response.data.html + '</div>');
                } else {
                    targetView.html('<p>Error loading content.</p><a href="#" class="aicb-content-back-button">← Back to Chat</a>');
                }
            },
            error: function() {
                targetView.html('<p>Error loading content.</p><a href="#" class="aicb-content-back-button">← Back to Chat</a>');
            }
        });
    });

    // --- Content View Rating Logic for main chat view ---
    viewContainer.on('click', '.aicb-content-ratings .aicb-rating-thumb', function() {
        const thumb = $(this);
        const wrapper = thumb.closest('.aicb-content-ratings');
        if (wrapper.hasClass('rated')) return;
        wrapper.addClass('rated');

        const rating = thumb.data('rating');
        const postId = wrapper.data('post-id');

        $.ajax({
            url: aicb_settings.ajax_url, type: 'POST',
            data: {
                action: 'aicb_rate_content', nonce: aicb_settings.nonce,
                rating: rating, post_id: postId
            }
        });

        wrapper.html('<em>Thanks for your feedback!</em>');
    });

    messagesContainer.on('click', '.aicb-link-action-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const downloadUrl = button.data('download-url');
        const isPremiumRequired = button.data('premium-required');
    
        if (isPremiumRequired) {
            // Call the new modal with settings from WordPress
            aicb_show_modal(
                aicb_settings.premium_modal_title,
                aicb_settings.premium_modal_message,
                aicb_settings.premium_modal_button_text,
                aicb_settings.premium_modal_button_url
            );
        } else if (downloadUrl) {
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.setAttribute('download', ''); 
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });
    
});
