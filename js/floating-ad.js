jQuery(document).ready(function($) {
    const adBar = $('#aicb-floating-ad-bar');
    if (adBar.length === 0) {
        return;
    }

    const closeButton = $('#aicb-close-floating-ad');

    // Show the ad with a slight delay to allow for rendering
    setTimeout(function() {
        adBar.addClass('aicb-ad-visible');
    }, 100);

    // Handle the close button click
    closeButton.on('click', function() {
        adBar.remove(); // Completely remove the ad from the page
    });
});
