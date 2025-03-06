/**
 * Bulk Date Update - Admin JavaScript
 * 
 * Handles all the admin interactions including modern time picker
 * and performance optimizations for tab switching.
 * 
 * @since 1.3
 */

(function($) {
    'use strict';

    // Main admin object
    const BulkDateAdmin = {
        /**
         * Initialize the admin functionality
         */
        init: function() {
            this.setupDateRangePicker();
            this.setupTimePickers();
            this.setupDistributeToggle();
            this.setupTimeRangeToggle();
            this.setupTabs();
            this.setupFormValidation();
        },

        /**
         * Setup the date range picker
         */
        setupDateRangePicker: function() {
            $('input[name="range"]').daterangepicker({
                maxDate: new Date(),
                locale: {
                    format: 'MM/DD/YY'
                },
                autoUpdateInput: true,
                autoApply: true
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
         * FIXED: Removed the problematic tab caching and auto-navigation
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
            $('input[type="checkbox"][id^="tab_"]').on('change', function() {
                const tabId = $(this).attr('id').replace('tab_', '');
                const isChecked = $(this).is(':checked');
                
                // Show loading feedback
                $('#settings-response')
                    .removeClass('hidden notice-success notice-error')
                    .addClass('notice')
                    .html('<p><span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> ' + bulkDateUpdate.strings.updatingSettings + '</p>')
                    .show();
                
                // Send AJAX request to update tab visibility
                $.ajax({
                    url: bulkDateUpdate.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'bulk_date_update_toggle_tab',
                        tab: tabId,
                        enabled: isChecked ? 1 : 0,
                        nonce: bulkDateUpdate.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#settings-response')
                                .removeClass('notice-error')
                                .addClass('notice-success')
                                .html('<p>' + response.data.message + '</p>');
                                
                            // Update tab visibility in real-time
                            if (isChecked) {
                                if ($('#bulk-date-tabs a[data-tab="' + tabId + '"]').length === 0) {
                                    location.reload(); // Reload if tab doesn't exist in DOM yet
                                } else {
                                    $('#bulk-date-tabs a[data-tab="' + tabId + '"]').removeClass('hidden');
                                }
                            } else {
                                $('#bulk-date-tabs a[data-tab="' + tabId + '"]').addClass('hidden');
                            }
                        } else {
                            $('#settings-response')
                                .removeClass('notice-success')
                                .addClass('notice-error')
                                .html('<p>' + (response.data ? response.data.message : bulkDateUpdate.strings.errorUpdatingSettings) + '</p>');
                        }
                        
                        // Hide message after 3 seconds
                        setTimeout(function() {
                            $('#settings-response').fadeOut();
                        }, 3000);
                    },
                    error: function() {
                        $('#settings-response')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + bulkDateUpdate.strings.errorUpdatingSettings + '</p>');
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
                // Additional validation can be added here if needed
                
                // Show loading state during form submission
                const $submitButton = $(this).find('input[type="submit"]');
                $submitButton.prop('disabled', true);
                $submitButton.after('<span class="spinner is-active" style="float: none; margin: 0 5px;"></span>');
                
                // Form will continue to submit normally
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        BulkDateAdmin.init();
    });

})(jQuery); 