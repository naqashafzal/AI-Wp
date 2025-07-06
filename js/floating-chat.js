jQuery(document).ready(function($) {
    const widget = $('#aicb-floating-widget');
    if (widget.length === 0) return;

    const launcher = $('#aicb-launcher');
    const form = widget.find('.aicb-float-form');
    const input = widget.find('textarea');
    const messagesArea = widget.find('.aicb-float-messages-area');
    const welcomeWrapper = widget.find('#aicb-float-welcome-message .aicb-float-ai-message');
    let conversationHistory = [];
    const MAX_HISTORY = 6; // Keeps the last 3 pairs of user/AI messages

    // --- Toggle Widget ---
    launcher.on('click', function() {
        widget.toggleClass('active');
        if (widget.hasClass('active')) {
            setTimeout(function() { input.focus(); }, 300); // Wait for animation
        }
    });

    // --- Advanced: Proactive Trigger (Exit-Intent) ---
    let exitIntentTriggered = false;
    $(document).on('mouseleave', function(e) {
        if (e.clientY < 0 && !widget.hasClass('active') && !exitIntentTriggered) {
            exitIntentTriggered = true;
            launcher.click();
            const proactiveMessage = `
                <div class="aicb-float-message-wrapper aicb-float-ai-wrapper">
                    <div class="aicb-float-message aicb-float-ai-message">
                        <p>Leaving so soon? Can I help you find anything?</p>
                    </div>
                </div>`;
            messagesArea.append(proactiveMessage);
            messagesArea.scrollTop(messagesArea.prop("scrollHeight"));
        }
    });
    
    // --- Advanced: Conversation Ratings ---
    messagesArea.on('click', '.aicb-rating-thumb', function() {
        const thumb = $(this);
        const wrapper = thumb.closest('.aicb-ratings');
        const rating = thumb.data('rating');

        // Prevent double-rating
        if (wrapper.hasClass('rated')) return;
        wrapper.addClass('rated');
        
        // You would typically store the question and answer as data attributes on the wrapper
        // For simplicity, we are just sending the rating for now.
        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: {
                action: 'aicb_rate_answer',
                nonce: aicb_settings.nonce,
                rating: rating
                // question: wrapper.data('question'), 
                // answer: wrapper.data('answer')
            },
            success: function(response) {
                // You could show a "Thanks for your feedback!" message
                thumb.css('opacity', 1);
            }
        });
    });

    // --- Personalized Welcome Message ---
    function getPersonalizedWelcome() {
        if (!aicb_settings.enable_personalized_welcome) {
            return;
        }
        const history = JSON.parse(localStorage.getItem('aicb_visitor_history')) || [];
        if (history.length === 0) {
            return;
        }
        welcomeWrapper.find('p').text('Crafting a special welcome for you...');
        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: {
                action: 'aicb_get_welcome',
                nonce: aicb_settings.nonce,
                history: history
            },
            success: function(response) {
                if (response.success) {
                    let finalWelcomeHtml = `<p>${response.data.message}</p>`;
                    if (response.data.related_html) {
                        finalWelcomeHtml += response.data.related_html;
                    }
                    welcomeWrapper.html(finalWelcomeHtml);
                } else {
                    welcomeWrapper.find('p').text('Hello! How can I help?');
                }
            },
            error: function() {
                welcomeWrapper.find('p').text('Hello! How can I help?');
            }
        });
    }

    // --- Form Submission ---
    form.on('submit', function(e) {
        e.preventDefault();
        const message = input.val().trim();
        if (message === '') return;

        messagesArea.append(`<div class="aicb-float-message-wrapper aicb-float-user-wrapper"><div class="aicb-float-message aicb-float-user-message"><p>${message}</p></div></div>`);
        input.val('').css('height', 'auto');
        messagesArea.scrollTop(messagesArea.prop("scrollHeight"));

        const thinkingIndicator = $(`<div class="aicb-float-message-wrapper aicb-float-ai-wrapper"><div class="aicb-float-message aicb-float-ai-message thinking-indicator"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div></div>`);
        messagesArea.append(thinkingIndicator);
        messagesArea.scrollTop(messagesArea.prop("scrollHeight"));

        if (aicb_settings.enable_memory) {
            conversationHistory.push({ role: 'user', parts: [{ text: message }] });
        }

        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: { 
                action: 'aicb_get_response', 
                query: message, 
                nonce: aicb_settings.nonce,
                history: aicb_settings.enable_memory ? JSON.stringify(conversationHistory) : null
            },
            success: function(response) {
                if (response.success) {
                    let finalHtml = response.data.answer;
                    // Add rating buttons
                    finalHtml += `<div class="aicb-ratings"><span class="aicb-rating-thumb" data-rating="up">👍</span><span class="aicb-rating-thumb" data-rating="down">👎</span></div>`;

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

    // --- Content Loader Logic for Floating Widget ---
    messagesArea.on('click', '.aicb-content-loader', function(e) {
        e.preventDefault();
        const link = $(this);
        const permalink = link.data('permalink');
        if (permalink) {
            window.open(permalink, '_blank');
        }
    });
    
    // Auto-resize textarea
    input.on('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Keyboard Handler for Enter key
    input.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.submit();
        }
    });

    // Run the welcome message function on page load
    getPersonalizedWelcome();
});
