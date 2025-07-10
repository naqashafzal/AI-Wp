jQuery(document).ready(function($) {
    const widget = $('#aicb-floating-widget');
    if (widget.length === 0) return;

    const launcher = $('#aicb-launcher');
    const form = widget.find('.aicb-float-form');
    const input = widget.find('textarea');
    const messagesArea = widget.find('.aicb-float-messages-area');
    let conversationHistory = [];
    const MAX_HISTORY = 6;
    let autocompleteTimeout;

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
                            $('#aicb-float-welcome-message .aicb-float-ai-message').html(welcomeHtml);
                        }
                    }
                });
            }
        }
    }
    getPersonalizedWelcome();

    // --- Autocomplete Logic ---
    function handleAutocomplete() {
        if (!aicb_settings.autocomplete_enabled) return;

        const currentText = input.val();
        if (currentText.length < 3) {
            input.data('suggestion', '');
            return;
        }

        clearTimeout(autocompleteTimeout);
        autocompleteTimeout = setTimeout(() => {
            $.ajax({
                url: aicb_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'aicb_get_autocomplete_suggestions',
                    nonce: aicb_settings.nonce,
                    query: currentText
                },
                success: function(response) {
                    if (response.success && response.data.suggestion) {
                        input.data('suggestion', response.data.suggestion);
                    } else {
                        input.data('suggestion', '');
                    }
                }
            });
        }, 250);
    }
    
    input.on('input', handleAutocomplete);

    launcher.on('click', function() {
        widget.toggleClass('active');
        if (widget.hasClass('active')) {
            setTimeout(function() { input.focus(); }, 300);
        }
    });

    form.on('submit', function(e) {
        e.preventDefault();
        input.data('suggestion', '');
        const message = input.val().trim();
        if (message === '') return;

        const escapedMessage = $('<div />').text(message).html();
        messagesArea.append(`<div class="aicb-float-message-wrapper aicb-float-user-wrapper"><div class="aicb-float-message aicb-float-user-message"><p>${escapedMessage}</p></div></div>`);
        input.val('').css('height', 'auto');
        messagesArea.scrollTop(messagesArea.prop("scrollHeight"));

        const thinkingIndicator = $(`<div class="aicb-float-message-wrapper aicb-float-ai-wrapper"><div class="aicb-float-message aicb-float-ai-message thinking-indicator"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div></div>`);
        messagesArea.append(thinkingIndicator);
        messagesArea.scrollTop(messagesArea.prop("scrollHeight"));

        if (aicb_settings.enable_memory) {
            conversationHistory.push({ role: 'user', parts: [{ text: message }] });
        }
        
        const BrowseHistory = localStorage.getItem('aicb_visitor_history') || '[]';

        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: { 
                action: 'aicb_get_response', 
                query: message, 
                nonce: aicb_settings.nonce,
                history: aicb_settings.enable_memory ? JSON.stringify(conversationHistory) : null,
                Browse_history: BrowseHistory
            },
            success: function(response) {
                if (response.success) {
                    let finalHtml = response.data.answer;
                    finalHtml += `<div class="aicb-ratings" data-question="${message}"><span class="aicb-rating-thumb" data-rating="up">👍</span><span class="aicb-rating-thumb" data-rating="down">👎</span></div>`;

                    if (response.data.search_url) { finalHtml += `<div style="margin-top: 1rem; text-align: right;"><a href="${response.data.search_url}" target="_blank" style="font-size: 0.8rem; text-decoration: underline;">View all results</a></div>`; }
                    if (response.data.sources_html) { finalHtml += response.data.sources_html; }
                    thinkingIndicator.find('.aicb-float-ai-message').removeClass('thinking-indicator').html(finalHtml);

                    if (aicb_settings.enable_memory) {
                        conversationHistory.push({ role: 'model', parts: [{ text: $(`<div>${response.data.answer}</div>`).text() }] });
                        if (conversationHistory.length > MAX_HISTORY) {
                            conversationHistory = conversationHistory.slice(-MAX_HISTORY);
                        }
                    }

                    if (response.data.ad_code) {
                        const adBubble = $(`<div class="aicb-float-message-wrapper aicb-float-ai-wrapper"><div class="aicb-float-message aicb-float-ai-message">${response.data.ad_code}</div></div>`);
                        messagesArea.append(adBubble);
                        messagesArea.scrollTop(messagesArea.prop("scrollHeight"));
                    }
                } else {
                    thinkingIndicator.find('.aicb-float-ai-message').removeClass('thinking-indicator').html('<p>Sorry, an error occurred.</p>');
                }
            },
            error: function() {
                thinkingIndicator.find('.aicb-float-ai-message').removeClass('thinking-indicator').html('<p>Error: Could not connect.</p>');
            }
        });
    });
    
    // --- Handle Suggestion Form ---
    messagesArea.on('submit', '.aicb-suggestion-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const suggestion = form.find('textarea').val().trim();
        const question = form.data('question');

        if (suggestion === '') return;

        form.find('button').text('Submitting...').prop('disabled', true);

        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: {
                action: 'aicb_submit_suggestion',
                nonce: aicb_settings.nonce,
                question: question,
                suggestion: suggestion
            },
            success: function(response) {
                if (response.success) {
                    form.closest('.aicb-suggestion-wrapper').html('<p><em>Thank you for your feedback!</em></p>');
                } else {
                    form.find('button').text('Submit').prop('disabled', false);
                    alert(response.data.message || 'Could not submit suggestion.');
                }
            }
        });
    });

    // --- Rating Logic ---
    messagesArea.on('click', '.aicb-rating-thumb', function() {
        const thumb = $(this);
        const wrapper = thumb.closest('.aicb-ratings');
        if (wrapper.hasClass('rated')) return;
        wrapper.addClass('rated');
        
        const rating = thumb.data('rating');
        const question = wrapper.data('question');

        $.ajax({
            url: aicb_settings.ajax_url, type: 'POST',
            data: {
                action: 'aicb_rate_content', nonce: aicb_settings.nonce,
                rating: rating, question: question
            }
        });

        if (rating === 'down') {
            const feedbackHtml = `
                <div class="aicb-suggestion-wrapper">
                    <p>Sorry I couldn't help. What would have been a better answer?</p>
                    <form class="aicb-suggestion-form" data-question="${question}">
                        <textarea placeholder="Your suggestion..." rows="3"></textarea>
                        <button type="submit">Submit Feedback</button>
                    </form>
                </div>
            `;
            wrapper.after(feedbackHtml);
        } else {
            wrapper.html('<em>Thanks for your feedback!</em>');
        }
    });

    // --- Keydown Logic (Enter & Tab) ---
    input.on('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    input.on('keydown', function(e) {
        if (e.key === 'Tab' && input.data('suggestion')) {
            e.preventDefault();
            input.val(input.data('suggestion'));
            input.data('suggestion', '');
        }
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.submit();
        }
    });

    // --- NEW: Content Loader Logic ---
    messagesArea.on('click', '.aicb-content-loader', function(e) {
        e.preventDefault();
        const postId = $(this).data('post-id');
        const permalink = $(this).data('permalink');

        if (aicb_settings.content_display_mode === 'new_tab') {
            window.open(permalink, '_blank');
            return;
        }

        // For the floating widget, we'll replace the content of the messages area.
        const originalContent = messagesArea.html();
        messagesArea.html('<div class="aicb-content-loader-spinner"></div>');

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
                    let backButton = '<a href="#" class="aicb-float-content-back-button">← Back to Chat</a>';
                    messagesArea.html('<div class="aicb-float-content-view">' + backButton + response.data.html + '</div>');
                    messagesArea.data('original-content', originalContent);
                } else {
                    messagesArea.html('<p>Error loading content.</p>');
                }
            }
        });
    });

    // --- NEW: Back to Chat Button Logic ---
    messagesArea.on('click', '.aicb-float-content-back-button', function(e) {
        e.preventDefault();
        if (messagesArea.data('original-content')) {
            messagesArea.html(messagesArea.data('original-content'));
        }
    });

    // --- NEW: Content View Rating Logic ---
    messagesArea.on('click', '.aicb-content-ratings .aicb-rating-thumb', function() {
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

// --- Download Button Click Handler ---
messagesArea.on('click', '.aicb-link-action-btn', function(e) {
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
