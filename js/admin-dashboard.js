jQuery(document).ready(function($) {
    
    // --- Active Menu Handler for Settings Page ---
    if ($('.aicb-settings-wrap').length) {
        function setActiveMenuItem() {
            const urlParams = new URLSearchParams(window.location.search);
            let currentTab = urlParams.get('tab');
            // If no tab is set, but the page is aicb-settings, default to 'api'
            if (!currentTab && window.location.search.includes('page=aicb-settings')) {
                currentTab = 'api';
            }
            $('.aicb-settings-menu a').removeClass('active');
            if (currentTab) {
                const activeLink = $('.aicb-settings-menu a[href*="tab=' + currentTab + '"]');
                activeLink.addClass('active');
            }
        }
        setActiveMenuItem();
    }

    // --- Logic for the Gemini Enable/Disable Switch ---
    if ($('.aicb-switch input[name="aicb_settings[aicb_enable_gemini]"]').length) {
        function toggleApiKeyField() {
            const isChecked = $('.aicb-switch input[name="aicb_settings[aicb_enable_gemini]"]').is(':checked');
            // Find the parent table row `<tr>` to toggle it
            $('#aicb-gemini-api-key-field').closest('tr').toggle(isChecked);
            $('input[name="aicb_settings[aicb_tuned_model_name]"]').closest('tr').toggle(isChecked);
        }
        // Run on page load
        toggleApiKeyField();
        // Bind to change event
        $('.aicb-switch input[name="aicb_settings[aicb_enable_gemini]"]').on('change', toggleApiKeyField);
    }

    // --- Analytics Dashboard Chart Logic ---
    if ($('#aicb-searches-by-day-chart').length) {
        $.ajax({
            url: aicb_dashboard_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'aicb_get_chart_data',
                nonce: aicb_dashboard_obj.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;

                    // Searches by Day Chart
                    new Chart(document.getElementById('aicb-searches-by-day-chart'), {
                        type: 'line',
                        data: {
                            labels: data.searches_by_day.map(item => item.date),
                            datasets: [{
                                label: 'Searches',
                                data: data.searches_by_day.map(item => item.count),
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: { maintainAspectRatio: false }
                    });

                    // Searches by Device Chart
                    new Chart(document.getElementById('aicb-searches-by-device-chart'), {
                        type: 'doughnut',
                        data: {
                            labels: data.searches_by_device.map(item => item.device),
                            datasets: [{
                                data: data.searches_by_device.map(item => item.count),
                                backgroundColor: ['#2563eb', '#f59e0b', '#10b981']
                            }]
                        },
                        options: { maintainAspectRatio: false }
                    });

                    // Searches by Country Chart
                    new Chart(document.getElementById('aicb-searches-by-country-chart'), {
                        type: 'bar',
                        data: {
                            labels: data.searches_by_country.map(item => item.country),
                            datasets: [{
                                label: 'Searches',
                                data: data.searches_by_country.map(item => item.count),
                                backgroundColor: '#1d4ed8'
                            }]
                        },
                        options: { maintainAspectRatio: false, indexAxis: 'y' }
                    });

                    // Top Searched Keywords Report
                    let keywordsHtml = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Keyword</th><th>Count</th></tr></thead><tbody>';
                    data.top_keywords.forEach(item => {
                        keywordsHtml += `<tr><td>${item.content}</td><td>${item.count}</td></tr>`;
                    });
                    keywordsHtml += '</tbody></table>';
                    $('#aicb-top-keywords-report').html(keywordsHtml);

                    // Feedback Report
                    new Chart(document.getElementById('aicb-feedback-chart'), {
                        type: 'pie',
                        data: {
                            labels: ['Positive', 'Negative'],
                            datasets: [{
                                data: [data.feedback.up, data.feedback.down],
                                backgroundColor: ['#10b981', '#ef4444']
                            }]
                        },
                        options: { maintainAspectRatio: false }
                    });

                    // Top Clicked Links Report
                    let linksHtml = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Link Text</th><th>Count</th></tr></thead><tbody>';
                    data.top_link_clicks.forEach(item => {
                        linksHtml += `<tr><td>${item.content}</td><td>${item.count}</td></tr>`;
                    });
                    linksHtml += '</tbody></table>';
                    $('#aicb-link-click-report').html(linksHtml);
                }
            }
        });
    }

    // --- API Key Test Button Logic ---
    if ($('#aicb-test-api-button').length) {
        $('#aicb-test-api-button').on('click', function() {
            const apiKey = $('#aicb-gemini-api-key-field').val();
            const resultSpan = $('#aicb-api-test-result');
            const button = $(this);
            if (!apiKey) {
                resultSpan.text('Please enter an API key.').css('color', '#f87171').show();
                return;
            }
            resultSpan.text('Testing...').css('color', '').show();
            button.prop('disabled', true);
            $.ajax({
                url: aicb_dashboard_obj.ajax_url, type: 'POST',
                data: { action: 'aicb_test_api', api_key: apiKey, nonce: aicb_dashboard_obj.nonce },
                success: function(response) {
                    if (response.success) {
                        resultSpan.text(response.data.message).css('color', '#4ade80');
                    } else {
                        resultSpan.text(response.data.message).css('color', '#f87171');
                    }
                },
                error: function() { 
                    resultSpan.text('Request failed. Check browser console.').css('color', '#f87171');
                 },
                complete: function() { button.prop('disabled', false); }
            });
        });
    }

    // --- Training Data Page Logic ---
    if ($('#training-data-form').length) {
        // Delete entry
        $('.aicb-delete-entry').on('click', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this entry?')) return;
            
            const link = $(this);
            const id = link.data('id');
            
            $.ajax({
                url: aicb_dashboard_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'aicb_delete_training_entry',
                    nonce: aicb_dashboard_obj.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        $('#entry-' + id).fadeOut();
                    }
                }
            });
        });

        // Export data
        $('#aicb-export-button').on('click', function(e) {
            e.preventDefault();
            const exportUrl = aicb_dashboard_obj.ajax_url + '?action=aicb_export_training_data&nonce=' + aicb_dashboard_obj.nonce;
            window.location.href = exportUrl;
        });
    }
});