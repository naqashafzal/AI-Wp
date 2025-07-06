jQuery(document).ready(function($) {
    
    // --- Active Menu Handler for Settings Page ---
    if ($('.aicb-settings-wrap').length) {
        function setActiveMenuItem() {
            // Get the 'tab' parameter from the current URL
            const urlParams = new URLSearchParams(window.location.search);
            const currentTab = urlParams.get('tab') || 'api'; // Default to 'api' if no tab is set

            $('.aicb-settings-menu a').removeClass('active');
            
            // Find the link whose href contains the current tab name
            const activeLink = $('.aicb-settings-menu a[href*="tab=' + currentTab + '"]');
            
            if (activeLink.length) {
                activeLink.addClass('active');
            } else {
                // As a fallback, activate the API settings link
                $('.aicb-settings-menu a[href*="tab=api"]').addClass('active');
            }
        }
        // Set the active item when the page loads
        setActiveMenuItem();
    }

    // --- Logic for the Gemini Enable/Disable Switch ---
    function toggleApiKeyField() {
        const isChecked = $('#aicb_enable_gemini_checkbox').is(':checked');
        $('#aicb_api_key_wrapper').closest('tr').toggle(isChecked);
        $('#aicb_tuned_model_wrapper').closest('tr').toggle(isChecked);
    }
    toggleApiKeyField();
    $('#aicb_enable_gemini_checkbox').on('change', function() {
        toggleApiKeyField();
    });

    // --- Analytics Dashboard Chart Logic ---
    if ($('#aicb-searches-by-day-chart').length) {
        let searchesByDayChart, searchesByDeviceChart, searchesByCountryChart;
        function loadAnalyticsData() {
            $.ajax({
                url: aicb_dashboard_obj.ajax_url,
                type: 'POST',
                data: { action: 'aicb_get_dashboard_data', nonce: aicb_dashboard_obj.nonce },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        if (searchesByDayChart) searchesByDayChart.destroy();
                        if (searchesByDeviceChart) searchesByDeviceChart.destroy();
                        if (searchesByCountryChart) searchesByCountryChart.destroy();
                        searchesByDayChart = renderBarChart('aicb-searches-by-day-chart', data.searches_by_day.labels, data.searches_by_day.data, 'Searches per Day', 'No search data yet.');
                        searchesByDeviceChart = renderPieChart('aicb-searches-by-device-chart', data.searches_by_device.labels, data.searches_by_device.data, 'Searches by Device', 'No device data yet.');
                        searchesByCountryChart = renderDoughnutChart('aicb-searches-by-country-chart', data.searches_by_country.labels, data.searches_by_country.data, 'Searches by Country', 'No country data yet.');
                    }
                }
            });
        }
        function drawNoDataMessage(canvasId, message) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.textAlign = 'center';
            ctx.fillStyle = '#666';
            ctx.font = '16px Arial';
            ctx.fillText(message, canvas.width / 2, canvas.height / 2);
        }
        function renderBarChart(canvasId, labels, data, label, noDataMessage) {
            if (!labels || labels.length === 0) { drawNoDataMessage(canvasId, noDataMessage); return null; }
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, { type: 'bar', data: { labels: labels, datasets: [{ label: label, data: data, backgroundColor: 'rgba(54, 162, 235, 0.6)' }] }, options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, maintainAspectRatio: false } });
        }
        function renderDoughnutChart(canvasId, labels, data, label, noDataMessage) {
             if (!labels || labels.length === 0) { drawNoDataMessage(canvasId, noDataMessage); return null; }
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, { type: 'doughnut', data: { labels: labels, datasets: [{ label: label, data: data, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'] }] }, options: { maintainAspectRatio: false, plugins: { legend: { position: 'right' } } } });
        }
        function renderPieChart(canvasId, labels, data, label, noDataMessage) {
             if (!labels || labels.length === 0) { drawNoDataMessage(canvasId, noDataMessage); return null; }
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, { type: 'pie', data: { labels: labels, datasets: [{ label: label, data: data, backgroundColor: ['#36A2EB', '#FF6384', '#9966FF'] }] }, options: { maintainAspectRatio: false, plugins: { legend: { position: 'right' } } } });
        }
        loadAnalyticsData();
        // --- Performance: Increased refresh interval ---
        setInterval(loadAnalyticsData, 30000); // Changed from 7000 to 30000 (30 seconds)
    }

    // --- API Key Test Button Logic ---
    if ($('#aicb-test-api-button').length) {
        $('#aicb-test-api-button').on('click', function() {
            const apiKey = $('#aicb-gemini-api-key-field').val();
            const resultSpan = $('#aicb-api-test-result');
            const button = $(this);
            if (!apiKey) {
                resultSpan.text('Please enter an API key.').css('color', '#dc3232').show();
                return;
            }
            resultSpan.text('Testing...').css('color', '').show();
            button.prop('disabled', true);
            $.ajax({
                url: aicb_dashboard_obj.ajax_url, type: 'POST',
                data: { action: 'aicb_test_api', api_key: apiKey, nonce: aicb_dashboard_obj.nonce },
                success: function(response) {
                    if (response.success) {
                        resultSpan.text(response.data.message).css('color', '#2271b1');
                        $('#aicb-api-status-indicator').removeClass('status-failed').addClass('status-success').text('Active');
                    } else {
                        resultSpan.text(response.data.message).css('color', '#dc3232');
                        $('#aicb-api-status-indicator').removeClass('status-success').addClass('status-failed').text('Failed');
                    }
                },
                error: function() { 
                    resultSpan.text('Request failed. Check browser console.').css('color', '#dc3232');
                    $('#aicb-api-status-indicator').removeClass('status-success').addClass('status-failed').text('Failed');
                 },
                complete: function() { button.prop('disabled', false); }
            });
        });
    }

    // --- Training Data Page Logic ---
    if ($('#training-data-form').length) {
        $('#aicb-export-button').on('click', function(e) {
            e.preventDefault();
            window.location.href = aicb_dashboard_obj.ajax_url + '?action=aicb_export_data&nonce=' + aicb_dashboard_obj.nonce;
        });
        $('#training-data-form').on('click', '.aicb-delete-entry', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this entry? This cannot be undone.')) { return; }
            const link = $(this);
            const entryId = link.data('id');
            link.text('Deleting...');
            $.ajax({
                url: aicb_dashboard_obj.ajax_url,
                type: 'POST',
                data: { action: 'aicb_delete_training_entry', nonce: aicb_dashboard_obj.nonce, entry_id: entryId },
                success: function(response) {
                    if (response.success) {
                        link.closest('tr').fadeOut(500, function() { $(this).remove(); });
                    } else {
                        alert('Error: Could not delete entry.');
                        link.text('Delete');
                    }
                }
            });
        });
    }

    // --- Link Reports Page Logic ---
    if ($('.aicb-delete-report').length) {
        $('body').on('click', '.aicb-delete-report', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to dismiss this report?')) { return; }
            const link = $(this);
            const reportId = link.data('id');
            link.text('Dismissing...');
            $.ajax({
                url: aicb_dashboard_obj.ajax_url,
                type: 'POST',
                data: { action: 'aicb_delete_report', nonce: aicb_dashboard_obj.nonce, report_id: reportId },
                success: function(response) {
                    if (response.success) {
                        link.closest('tr').fadeOut(500, function() { $(this).remove(); });
                    } else {
                        alert('Error: Could not dismiss report.');
                        link.text('Dismiss');
                    }
                }
            });
        });
    }
});