/**
 * Admin JavaScript for Numerology Compatibility Plugin
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {

        // Initialize charts if on statistics page
        if ($('#nc-calculations-chart').length) {
            initializeCharts();
        }

        // View calculation modal
        $('.nc-view-calculation').on('click', function(e) {
            e.preventDefault();
            var calculationId = $(this).data('id');
            viewCalculation(calculationId);
        });

        // Export CSV
        $('#nc-export-csv').on('click', function() {
            exportData('csv');
        });

        // Export PDF
        $('#nc-export-pdf').on('click', function() {
            exportData('pdf');
        });

        // Delete confirmation
        $('.nc-delete-item').on('click', function(e) {
            if (!confirm(nc_admin.i18n.confirm_delete)) {
                e.preventDefault();
            }
        });

        // Live search for users/calculations
        $('#nc-search').on('keyup', debounce(function() {
            var searchTerm = $(this).val();
            if (searchTerm.length >= 3) {
                performSearch(searchTerm);
            }
        }, 300));

        // Settings tab switching
        $('.nav-tab').on('click', function(e) {
            if (!$(this).hasClass('nav-tab-active')) {
                // Let default behavior handle tab switching
            }
        });

        // Bulk actions
        $('#nc-bulk-action-submit').on('click', function() {
            var action = $('#bulk-action-selector').val();
            var selected = $('.nc-bulk-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (action && selected.length > 0) {
                performBulkAction(action, selected);
            }
        });

        // Select all checkboxes
        $('#nc-select-all').on('change', function() {
            $('.nc-bulk-checkbox').prop('checked', $(this).prop('checked'));
        });
    });

    /**
     * Initialize charts using Chart.js
     */
    function initializeCharts() {
        // Daily Calculations Chart
        if (typeof dailyStatsData !== 'undefined') {
            var calculationsCtx = document.getElementById('nc-calculations-chart').getContext('2d');
            new Chart(calculationsCtx, {
                type: 'line',
                data: {
                    labels: dailyStatsData.map(item => item.date),
                    datasets: [{
                        label: 'Calculations',
                        data: dailyStatsData.map(item => item.count),
                        borderColor: '#6B46C1',
                        backgroundColor: 'rgba(107, 70, 193, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Daily Revenue Chart
        if (typeof dailyRevenueData !== 'undefined') {
            var revenueCtx = document.getElementById('nc-revenue-chart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: dailyRevenueData.map(item => item.date),
                    datasets: [{
                        label: 'Revenue ($)',
                        data: dailyRevenueData.map(item => item.revenue),
                        backgroundColor: '#F59E0B',
                        borderColor: '#D97706',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Package Distribution Pie Chart
        if (typeof packageData !== 'undefined') {
            var packageCtx = document.getElementById('nc-package-pie-chart').getContext('2d');
            new Chart(packageCtx, {
                type: 'doughnut',
                data: {
                    labels: packageData.map(item => item.package_type),
                    datasets: [{
                        data: packageData.map(item => item.count),
                        backgroundColor: [
                            '#10B981',
                            '#6B46C1',
                            '#F59E0B'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    /**
     * View calculation details
     */
    function viewCalculation(calculationId) {
        $.ajax({
            url: nc_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nc_get_calculation',
                calculation_id: calculationId,
                nonce: nc_admin.nonce
            },
            beforeSend: function() {
                showLoading();
            },
            success: function(response) {
                if (response.success) {
                    showCalculationModal(response.data);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError(nc_admin.i18n.error);
            },
            complete: function() {
                hideLoading();
            }
        });
    }

    /**
     * Show calculation modal
     */
    function showCalculationModal(data) {
        // Create and show modal with calculation details
        var modal = $('<div class="nc-modal">').html(
            '<div class="nc-modal-content">' +
            '<h2>Calculation Details</h2>' +
            '<pre>' + JSON.stringify(data, null, 2) + '</pre>' +
            '<button class="button nc-modal-close">Close</button>' +
            '</div>'
        );

        $('body').append(modal);

        modal.find('.nc-modal-close').on('click', function() {
            modal.remove();
        });
    }

    /**
     * Export data
     */
    function exportData(format) {
        var params = new URLSearchParams(window.location.search);
        params.append('export', format);

        window.location.href = nc_admin.ajax_url + '?' + params.toString();
    }

    /**
     * Perform search
     */
    function performSearch(term) {
        $.ajax({
            url: nc_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nc_admin_search',
                search: term,
                nonce: nc_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateSearchResults(response.data);
                }
            }
        });
    }

    /**
     * Update search results
     */
    function updateSearchResults(results) {
        var $container = $('#nc-search-results');
        $container.empty();

        if (results.length > 0) {
            results.forEach(function(item) {
                $container.append(
                    '<div class="nc-search-item">' +
                    '<a href="' + item.url + '">' + item.title + '</a>' +
                    '</div>'
                );
            });
        } else {
            $container.html('<p>No results found</p>');
        }
    }

    /**
     * Perform bulk action
     */
    function performBulkAction(action, items) {
        if (!confirm('Are you sure you want to perform this action on ' + items.length + ' items?')) {
            return;
        }

        $.ajax({
            url: nc_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nc_bulk_action',
                bulk_action: action,
                items: items,
                nonce: nc_admin.nonce
            },
            beforeSend: function() {
                showLoading();
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError(nc_admin.i18n.error);
            },
            complete: function() {
                hideLoading();
            }
        });
    }

    /**
     * Show loading indicator
     */
    function showLoading() {
        $('body').append('<div class="nc-loading"><span class="spinner is-active"></span></div>');
    }

    /**
     * Hide loading indicator
     */
    function hideLoading() {
        $('.nc-loading').remove();
    }

    /**
     * Show error message
     */
    function showError(message) {
        alert(message); // Replace with better notification system
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

})(jQuery);