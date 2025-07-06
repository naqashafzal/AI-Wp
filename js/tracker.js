/**
 * This script tracks a visitor's browsing history on the site.
 * It stores the title, URL, timestamp, and categories of recently viewed pages
 * in the browser's Local Storage. This data is then used for personalization.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Maximum number of pages to remember in the history.
    const MAX_HISTORY_LENGTH = 5;
    // The key used to store the history in Local Storage.
    const storageKey = 'aicb_visitor_history';

    // Get existing history from localStorage or initialize a new empty array.
    let history = JSON.parse(localStorage.getItem(storageKey)) || [];

    // Create an object for the current page being viewed.
    const newPage = {
        title: document.title,
        url: window.location.href,
        timestamp: new Date().getTime(),
        // Get the categories for the current page, passed from WordPress via aicb_page_data.
        categories: (typeof aicb_page_data !== 'undefined' && aicb_page_data.post_categories) ? aicb_page_data.post_categories : []
    };

    // Prevent adding the same page twice in a row (e.g., on a page refresh).
    if (history.length > 0 && history[history.length - 1].url === newPage.url) {
        return;
    }

    // Add the new page to the history array.
    history.push(newPage);

    // If the history is too long, remove the oldest item.
    if (history.length > MAX_HISTORY_LENGTH) {
        history.shift();
    }

    // Save the updated history back to Local Storage.
    localStorage.setItem(storageKey, JSON.stringify(history));
});