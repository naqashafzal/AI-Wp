/**
 * AI Chatbox Modern Modal/Popup with Optional Action Button
 */
function aicb_show_modal(title, message, buttonText = '', buttonUrl = '') {
    // Clean up any existing modals first
    const existingOverlay = document.querySelector('.aicb-modal-overlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }

    // Conditionally create the action button
    let actionButtonHtml = '';
    if (buttonText && buttonUrl) {
        actionButtonHtml = `<a href="${buttonUrl}" class="aicb-modal-action-btn">${buttonText}</a>`;
    }

    // Construct the modal HTML
    const modalHtml = `
        <div class="aicb-modal-overlay">
            <div class="aicb-modal-box">
                <button class="aicb-modal-close-btn" title="Close">&times;</button>
                <h3 class="aicb-modal-title">${title}</h3>
                <p class="aicb-modal-message">${message}</p>
                <div class="aicb-modal-actions">
                    ${actionButtonHtml}
                </div>
            </div>
        </div>
    `;

    // Append to the body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Get the new modal elements
    const overlay = document.querySelector('.aicb-modal-overlay');
    const closeBtn = overlay.querySelector('.aicb-modal-close-btn');

    // Function to close the modal
    const closeModal = () => {
        overlay.remove();
    };

    // Event listeners
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeModal();
        }
    });
}
