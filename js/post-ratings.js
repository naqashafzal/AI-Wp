jQuery(document).ready(function($) {
    // Attach a click event listener to the rating buttons.
    $('.aicb-post-rating-container').on('click', '.aicb-post-rating-thumb', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const container = button.closest('.aicb-post-rating-container');
        const buttonsWrapper = container.find('.aicb-rating-buttons');

        // Prevent action if the user has already rated.
        if (buttonsWrapper.hasClass('rated')) {
            return;
        }

        const postId = container.data('post-id');
        const rating = button.data('rating');

        // Perform the AJAX request to submit the rating.
        $.ajax({
            url: aicb_settings.ajax_url,
            type: 'POST',
            data: {
                action: 'aicb_rate_post',
                nonce: aicb_settings.nonce,
                post_id: postId,
                rating: rating
            },
            beforeSend: function() {
                // Disable buttons immediately to prevent multiple clicks.
                buttonsWrapper.addClass('rated');
            },
            success: function(response) {
                if (response.success) {
                    // Update the vote counts and the rating bar on the page.
                    const upVotes = response.data.up_votes;
                    const downVotes = response.data.down_votes;
                    const totalVotes = upVotes + downVotes;
                    const percentage = totalVotes > 0 ? (upVotes / totalVotes) * 100 : 50;

                    container.find('.aicb-post-rating-thumb[data-rating="up"] .aicb-vote-count').text(upVotes);
                    container.find('.aicb-post-rating-thumb[data-rating="down"] .aicb-vote-count').text(downVotes);
                    container.find('.aicb-rating-bar-inner').css('width', percentage + '%');
                } else {
                    // If the server reports an error (e.g., already rated), re-enable buttons.
                    buttonsWrapper.removeClass('rated');
                    alert(response.data.message || 'An error occurred.');
                }
            },
            error: function() {
                // If the AJAX call itself fails, re-enable buttons.
                buttonsWrapper.removeClass('rated');
                alert('Could not submit rating. Please try again.');
            }
        });
    });
});
