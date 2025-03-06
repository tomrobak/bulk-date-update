/**
 * Bulk Date Update - Admin JavaScript
 * 
 * Handles all the admin interactions including modern date/time pickers
 * and performance optimizations.
 * 
 * @since 1.4.1
 */

(function($) {
    'use strict';

    // Main admin object
    const BulkDateAdmin = {
        /**
         * Initialize the admin functionality
         */
        init: function() {
            this.setupDatePickers();
            this.setupTimePickers();
            this.setupDistributeToggle();
            this.setupTimeRangeToggle();
            this.setupDateRangePresets();
            this.setupTabs();
            this.setupFormValidation();
        },

        /**
         * Setup modern date pickers using Flatpickr
         */
        setupDatePickers: function() {
            if ($('#start_date').length && $('#end_date').length) {
                // Start date picker
                const startDatePicker = flatpickr('#start_date', {
                    dateFormat: 'm/d/y',
                    maxDate: 'today',
                    onClose: function(selectedDates, dateStr) {
                        // Update the end date min when start date changes
                        if (selectedDates[0]) {
                            endDatePicker.set('minDate', selectedDates[0]);
                            
                            // Update the hidden range field
                            BulkDateAdmin.updateRangeField();
                        }
                    }
                });
                
                // End date picker
                const endDatePicker = flatpickr('#end_date', {
                    dateFormat: 'm/d/y',
                    maxDate: 'today',
                    onClose: function(selectedDates, dateStr) {
                        // Update the start date max when end date changes
                        if (selectedDates[0]) {
                            startDatePicker.set('maxDate', selectedDates[0]);
                            
                            // Update the hidden range field
                            BulkDateAdmin.updateRangeField();
                        }
                    }
                });
                
                // Set initial dates from the range field
                const rangeParts = $('#range').val().split(' - ');
                if (rangeParts.length === 2) {
                    startDatePicker.setDate(rangeParts[0]);
                    endDatePicker.setDate(rangeParts[1]);
                } else {
                    // Set default values: 3 days ago to today
                    const today = new Date();
                    const threeDaysAgo = new Date();
                    threeDaysAgo.setDate(today.getDate() - 3);
                    
                    startDatePicker.setDate(threeDaysAgo);
                    endDatePicker.setDate(today);
                    
                    // Update the hidden range field
                    BulkDateAdmin.updateRangeField();
                }
            }
        },

        /**
         * Update the hidden range field with current date picker values
         */
        updateRangeField: function() {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            
            if (startDate && endDate) {
                $('#range').val(startDate + ' - ' + endDate);
            }
        },

        /**
         * Setup date range presets
         */
        setupDateRangePresets: function() {
            $('.date-preset').on('click', function() {
                const preset = $(this).data('preset');
                let startDate, endDate;
                
                const startDatePicker = $('#start_date')[0]._flatpickr;
                const endDatePicker = $('#end_date')[0]._flatpickr;
                
                switch(preset) {
                    case 'today':
                        startDate = bulkDateUpdate.dates.today;
                        endDate = bulkDateUpdate.dates.today;
                        break;
                    case 'yesterday':
                        startDate = bulkDateUpdate.dates.yesterday;
                        endDate = bulkDateUpdate.dates.yesterday;
                        break;
                    case 'last7Days':
                        startDate = bulkDateUpdate.dates.last7Start;
                        endDate = bulkDateUpdate.dates.today;
                        break;
                    case 'last30Days':
                        startDate = bulkDateUpdate.dates.last30Start;
                        endDate = bulkDateUpdate.dates.today;
                        break;
                    case 'thisMonth':
                        startDate = bulkDateUpdate.dates.thisMonthStart;
                        endDate = bulkDateUpdate.dates.today;
                        break;
                    case 'lastMonth':
                        startDate = bulkDateUpdate.dates.lastMonthStart;
                        endDate = bulkDateUpdate.dates.lastMonthEnd;
                        break;
                }
                
                if (startDate && endDate) {
                    startDatePicker.setDate(startDate);
                    endDatePicker.setDate(endDate);
                    
                    // Update the hidden range field
                    BulkDateAdmin.updateRangeField();
                    
                    // Visual feedback for selected preset
                    $('.date-preset').removeClass('active');
                    $(this).addClass('active');
                }
            });
        },

        /**
         * Setup modern time pickers using Flatpickr
         */
        setupTimePickers: function() {
            // Get user's time format preference from WordPress
            const is24Hour = true; // Default to 24-hour format, adjust if needed

            // Configure time picker options
            const timePickerConfig = {
                enableTime: true,
                noCalendar: true,
                dateFormat: is24Hour ? "H:i" : "h:i K",
                time_24hr: is24Hour,
                minuteIncrement: 15,
                defaultHour: 12,
                disableMobile: false, // Enable native pickers on mobile
                allowInput: true
            };

            // Initialize start time picker
            if ($('#start_time').length) {
                const startPicker = flatpickr('#start_time', {
                    ...timePickerConfig,
                    defaultHour: 0,
                    defaultMinute: 0,
                    onChange: function(selectedDates, dateStr) {
                        BulkDateAdmin.validateTimeRange();
                    }
                });
            }

            // Initialize end time picker
            if ($('#end_time').length) {
                const endPicker = flatpickr('#end_time', {
                    ...timePickerConfig,
                    defaultHour: 23,
                    defaultMinute: 59,
                    onChange: function(selectedDates, dateStr) {
                        BulkDateAdmin.validateTimeRange();
                    }
                });
            }
        },

        /**
         * Setup the distribute dropdown toggle
         */
        setupDistributeToggle: function() {
            $('#distribute').on('change', function() {
                const val = $(this).val();
                if (val == 0) {
                    $('#range_row').fadeIn(300);
                    
                    // If custom range is enabled, check if time range was previously enabled
                    if ($('#enable_time_range').is(':checked')) {
                        $('#time_range_controls').fadeIn(300);
                    }
                } else {
                    $('#range_row').fadeOut(200);
                    $('#time_range_controls').fadeOut(200);
                }
            });
        },

        /**
         * Setup the time range toggle checkbox
         */
        setupTimeRangeToggle: function() {
            $('#enable_time_range').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#time_range_controls').slideDown(300);
                } else {
                    $('#time_range_controls').slideUp(200);
                }
            });
            
            // Handle the time preset buttons
            $('.time-preset').on('click', function() {
                const startTime = $(this).data('start');
                const endTime = $(this).data('end');
                
                // Update the time pickers
                if ($('#start_time')[0]._flatpickr) {
                    $('#start_time')[0]._flatpickr.setDate(startTime);
                } else {
                    $('#start_time').val(startTime);
                }
                
                if ($('#end_time')[0]._flatpickr) {
                    $('#end_time')[0]._flatpickr.setDate(endTime);
                } else {
                    $('#end_time').val(endTime);
                }
                
                // Visual feedback for selected preset
                $('.time-preset').removeClass('active');
                $(this).addClass('active');
            });
        },

        /**
         * Setup tabs functionality
         */
        setupTabs: function() {
            // Handle tab click events without auto-navigation or caching
            $('.nav-tab-wrapper a').on('click', function(e) {
                // Don't interrupt normal navigation
                // Only handle settings tab toggle via JS
                const tabId = $(this).data('tab');
                
                if (tabId === 'settings') {
                    e.preventDefault();
                    
                    // Update active tab UI
                    $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    
                    // Toggle content visibility
                    $('#main-tab-content').addClass('hidden');
                    $('#settings-tab-content').removeClass('hidden');
                    
                    // Update URL without page reload
                    if (window.history && window.history.pushState) {
                        window.history.pushState(null, null, $(this).attr('href'));
                    }
                }
                
                // For other tabs, let the default navigation happen
            });
            
            // Handle checkbox changes for tab toggling via AJAX
            $('.bulkud-tab-toggle').on('change', function() {
                const checkbox = $(this);
                const tabId = checkbox.data('tab');
                const isChecked = checkbox.is(':checked');
                const tabToggleNonce = $('#settings-form-nonce input[name="tab_toggle_nonce"]').val();
                
                console.log('Sending AJAX request to toggle tab. TabID: ' + tabId + ', Enabled: ' + isChecked + ', Nonce: ' + tabToggleNonce);
                
                $.ajax({
                    url: bulkDateUpdate.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'bulk_date_update_toggle_tab',
                        tab: tabId,
                        enabled: isChecked ? 1 : 0,
                        nonce: tabToggleNonce
                    },
                    success: function(response) {
                        console.log('AJAX response:', response);
                        if (response.success) {
                            BulkDateAdmin.showNotice(response.data.message, 'success');
                        } else {
                            BulkDateAdmin.showNotice(response.data.message, 'error');
                            // Revert checkbox to previous state
                            checkbox.prop('checked', !isChecked);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error, xhr.responseText);
                        BulkDateAdmin.showNotice('Failed to update settings: ' + error, 'error');
                        // Revert checkbox to previous state
                        checkbox.prop('checked', !isChecked);
                    }
                });
            });
        },

        /**
         * Validate the time range selection
         */
        validateTimeRange: function() {
            const startTime = $('#start_time').val();
            const endTime = $('#end_time').val();
            
            if (startTime && endTime) {
                const startParts = startTime.split(':');
                const endParts = endTime.split(':');
                
                if (startParts.length === 2 && endParts.length === 2) {
                    const startHour = parseInt(startParts[0], 10);
                    const startMinute = parseInt(startParts[1], 10);
                    const endHour = parseInt(endParts[0], 10);
                    const endMinute = parseInt(endParts[1], 10);
                    
                    const startValue = startHour * 60 + startMinute;
                    const endValue = endHour * 60 + endMinute;
                    
                    if (startValue > endValue) {
                        alert(bulkDateUpdate.strings.invalidTimeRange);
                        $('#end_time').val('23:59');
                        if ($('#end_time')[0]._flatpickr) {
                            $('#end_time')[0]._flatpickr.setDate('23:59');
                        }
                    }
                }
            }
        },

        /**
         * Setup form validation
         */
        setupFormValidation: function() {
            $('#bulk-update-form').on('submit', function(e) {
                // Make sure the hidden range field is updated with current values
                BulkDateAdmin.updateRangeField();
                
                // Show loading state during form submission
                const $submitButton = $(this).find('input[type="submit"]');
                $submitButton.prop('disabled', true);
                $submitButton.after('<span class="spinner is-active" style="float: none; margin: 0 5px;"></span>');
                
                // Form will continue to submit normally
            });
        },

        /**
         * Show notice to the user
         */
        showNotice: function(message, type) {
            const $notice = $('#settings-response');
            
            // Remove existing classes and add appropriate ones
            $notice.removeClass('hidden notice-success notice-error')
                .addClass('notice notice-' + type)
                .html('<p>' + message + '</p>')
                .show();
            
            // Hide message after 3 seconds
            setTimeout(function() {
                $notice.fadeOut();
            }, 3000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        BulkDateAdmin.init();
    });

})(jQuery); 