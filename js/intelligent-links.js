/**
 * This script powers the [intelligent_content_links] shortcode.
 * It periodically checks for the shortcode's placeholder on the page.
 * Once found, it fetches personalized recommendations based on the visitor's
 * browsing history stored in Local Storage.
 */
function initializeIntelligentLinks() {
    // Find all containers where the shortcode was placed
    const containers = document.querySelectorAll('.aicb-intelligent-links-container');

    // If no containers are found yet, do nothing. The interval will try again.
    if (containers.length === 0) {
        return false; // Indicate that we haven't found it yet
    }

    // Get the visitor's browsing history from Local Storage
    const history = JSON.parse(localStorage.getItem('aicb_visitor_history')) || [];
    
    // Check for the settings object passed from WordPress
    if (typeof aicb_settings === 'undefined' || !aicb_settings.ajax_url) {
        console.error('AICB Critical Error: aicb_settings object not found for intelligent links.');
        // Stop trying if the settings object is missing
        return true; 
    }

    // Prepare the data for the AJAX request
    const data = new URLSearchParams();
    data.append('action', 'aicb_get_intelligent_links');
    data.append('nonce', aicb_settings.nonce);
    data.append('history', JSON.stringify(history)); // Send history, even if empty, to get random posts

    // Fetch the personalized links from the server
    fetch(aicb_settings.ajax_url, {
        method: 'POST',
        body: data
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        if (result.success && result.data.html) {
            // If successful, inject the returned HTML into all shortcode containers
            containers.forEach(container => {
                container.innerHTML = result.data.html;
            });
        } else {
            // If the server returns no links, clear the "Loading..." message
            containers.forEach(container => {
                container.innerHTML = '';
            });
        }
    })
    .catch(error => {
        console.error('Error fetching intelligent links:', error);
        containers.forEach(container => {
            container.innerHTML = '<p><em>Could not load recommendations.</em></p>';
        });
    });

    return true; // Indicate that the process has run and should stop the interval
}

// Set up a recurring check for the shortcode container
const intelligentLinksInterval = setInterval(function() {
    // tryToInitialize will return true if it finds the container and runs, or if it errors out.
    const isDone = initializeIntelligentLinks();
    if (isDone) {
        // Once the container is found and processed, stop checking for it.
        clearInterval(intelligentLinksInterval);
    }
}, 500); // Check every 500 milliseconds (half a second)