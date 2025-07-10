jQuery(document).ready(function($) {
    // Use event delegation to handle clicks on the download button
    $('body').on('click', '.aicb-download-button', function(e) {
        const button = $(this);
        const isPremiumRequired = button.data('premium-required');

        // If the button has the 'data-premium-required' attribute
        if (isPremiumRequired) {
            // Prevent the default link action (e.g., navigating to '#')
            e.preventDefault(); 
            
            // Call the global modal function with the customized text
            aicb_show_modal(
                aicb_settings.premium_modal_title,
                aicb_settings.premium_modal_message,
                aicb_settings.premium_modal_button_text,
                aicb_settings.premium_modal_button_url
            );
        }
        // If the attribute is not present, the button will function as a normal download link.
    });
});
