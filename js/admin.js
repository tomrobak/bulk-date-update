/**
 * Bulk Date Update - Admin JavaScript
 * 
 * Handles all the admin interactions including modern date/time pickers
 * and performance optimizations.
 * 
 * @since 1.4.7
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
                // Common configuration for date pickers
                const datePickerConfig = {
                    dateFormat: 'Y-m-d', // ISO format to match PHP
                    maxDate: 'today',
                    showMonths: 1,
                    animate: true,
                    static: false,
                    closeOnSelect: true,
                    disableMobile: false,
                    locale: {
                        firstDayOfWeek: 1, // Monday
                        weekdays: {
                            shorthand: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
                            longhand: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]
                        },
                        months: {
                            shorthand: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                            longhand: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]
                        }
                    },
                    onMonthChange: function() {
                        // Ensure the calendar redraws properly
                        setTimeout(function() {
                            if (typeof window.dispatchEvent === 'function') {
                                window.dispatchEvent(new Event('resize'));
                            }
                        }, 100);
                    }
                };
                
                // Start date picker
                const startDatePicker = flatpickr('#start_date', {
                    ...datePickerConfig,
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
                    ...datePickerConfig,
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
                try {
                    const rangeField = $('#range').val();
                    if (rangeField && rangeField.includes(' - ')) {
                        const rangeParts = rangeField.split(' - ');
                        
                        if (rangeParts.length === 2) {
                            // Try to convert dates to ISO format if they're not already
                            let startDate = this.convertToISODate(rangeParts[0]);
                            let endDate = this.convertToISODate(rangeParts[1]);
                            
                            console.log('Converting date range:', rangeParts[0], '->', startDate, rangeParts[1], '->', endDate);
                            
                            // Only set if we got valid dates
                            if (startDate && endDate) {
                                startDatePicker.setDate(startDate);
                                endDatePicker.setDate(endDate);
                            } else {
                                this.setDefaultDateRange(startDatePicker, endDatePicker);
                            }
                        } else {
                            this.setDefaultDateRange(startDatePicker, endDatePicker);
                        }
                    } else {
                        this.setDefaultDateRange(startDatePicker, endDatePicker);
                    }
                } catch (error) {
                    console.error('Error setting initial dates:', error);
                    this.setDefaultDateRange(startDatePicker, endDatePicker);
                }
            }
        },
        
        /**
         * Set default date range (3 days ago to today)
         */
        setDefaultDateRange: function(startDatePicker, endDatePicker) {
            console.log('Setting default date range');
            const today = new Date();
            const threeDaysAgo = new Date();
            threeDaysAgo.setDate(today.getDate() - 3);
            
            startDatePicker.setDate(threeDaysAgo);
            endDatePicker.setDate(today);
            
            // Update the hidden range field
            this.updateRangeField();
        },
        
        /**
         * Attempt to convert a date string to ISO format (YYYY-MM-DD)
         */
        convertToISODate: function(dateStr) {
            try {
                // Check if it's already in ISO format
                if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                    return dateStr;
                }
                
                // Try to parse MM/DD/YY format
                const parts = dateStr.split('/');
                if (parts.length === 3) {
                    const month = parseInt(parts[0], 10);
                    const day = parseInt(parts[1], 10);
                    let year = parseInt(parts[2], 10);
                    
                    // Fix two-digit year
                    if (year < 100) {
                        year += year < 50 ? 2000 : 1900;
                    }
                    
                    // Validate parts
                    if (month < 1 || month > 12 || day < 1 || day > 31) {
                        return null;
                    }
                    
                    // Format as ISO
                    return `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                }
                
                // If all else fails, try native Date parsing as a last resort
                const date = new Date(dateStr);
                if (!isNaN(date.getTime())) {
                    return date.toISOString().split('T')[0];
                }
                
                return null;
            } catch (e) {
                console.error('Error converting date:', e);
                return null;
            }
        },

        /**
         * Update the hidden range field with current date picker values
         */
        updateRangeField: function() {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            
            if (startDate && endDate) {
                // Format consistently in ISO format
                $('#range').val(startDate + ' - ' + endDate);
                
                console.log('Range field updated with date range:', startDate + ' - ' + endDate);
            }
        },

        /**
         * Setup date range presets
         */
        setupDateRangePresets: function() {
            $('.date-preset').on('click', function() {
                const preset = $(this).data('preset');
                
                // Get the Flatpickr instances
                const startDatePicker = $('#start_date')[0]._flatpickr;
                const endDatePicker = $('#end_date')[0]._flatpickr;
                
                // Make sure the date pickers exist
                if (!startDatePicker || !endDatePicker) {
                    console.error('Date pickers not initialized');
                    return;
                }
                
                // Parse dates from the global object safely
                let startDate, endDate;
                
                try {
                    switch(preset) {
                        case 'today':
                            startDate = new Date(bulkDateUpdate.dates.today);
                            endDate = new Date(bulkDateUpdate.dates.today);
                            break;
                        case 'yesterday':
                            startDate = new Date(bulkDateUpdate.dates.yesterday);
                            endDate = new Date(bulkDateUpdate.dates.yesterday);
                            break;
                        case 'last7Days':
                            startDate = new Date(bulkDateUpdate.dates.last7Start);
                            endDate = new Date(bulkDateUpdate.dates.today);
                            break;
                        case 'last30Days':
                            startDate = new Date(bulkDateUpdate.dates.last30Start);
                            endDate = new Date(bulkDateUpdate.dates.today);
                            break;
                        case 'thisMonth':
                            startDate = new Date(bulkDateUpdate.dates.thisMonthStart);
                            endDate = new Date(bulkDateUpdate.dates.today);
                            break;
                        case 'lastMonth':
                            startDate = new Date(bulkDateUpdate.dates.lastMonthStart);
                            endDate = new Date(bulkDateUpdate.dates.lastMonthEnd);
                            break;
                        default:
                            console.error('Unknown preset:', preset);
                            return;
                    }
                    
                    // Validate the dates
                    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                        console.error('Invalid date format from server:', startDate, endDate);
                        return;
                    }
                    
                    // Set the dates in the date pickers
                    startDatePicker.setDate(startDate);
                    endDatePicker.setDate(endDate);
                    
                    // Update the hidden range field
                    BulkDateAdmin.updateRangeField();
                    
                    // Visual feedback for selected preset
                    $('.date-preset').removeClass('active');
                    $(this).addClass('active');
                    
                    console.log('Date preset applied:', preset, 'Start:', startDate, 'End:', endDate);
                    
                } catch (e) {
                    console.error('Error applying date preset:', e);
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
                disableMobile: false, // Enable native pickers on mobile
                allowInput: true,
                static: false,
                animate: true
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
            
            // MAJOR FIX: Ensure the tab toggle checkboxes have their change event properly bound
            console.log('Setting up tab toggles. Found ' + $('.bulkud-tab-toggle').length + ' toggle checkboxes.');
            
            // Handle checkbox changes for tab toggling via AJAX
            $(document).on('change', '.bulkud-tab-toggle', function() {
                const checkbox = $(this);
                const tabId = checkbox.data('tab');
                const isChecked = checkbox.is(':checked');
                
                // First, get the nonce from the hidden field
                let tabToggleNonce = '';
                if ($('#settings-form-nonce input[name="tab_toggle_nonce"]').length > 0) {
                    tabToggleNonce = $('#settings-form-nonce input[name="tab_toggle_nonce"]').val();
                } else {
                    // As a fallback, use the one provided in the global object
                    tabToggleNonce = bulkDateUpdate.nonce;
                }
                
                console.log('Sending AJAX request to toggle tab. TabID: ' + tabId + ', Enabled: ' + isChecked + ', Nonce: ' + tabToggleNonce);
                
                // Show loading feedback
                BulkDateAdmin.showNotice(bulkDateUpdate.strings.updatingSettings, 'info');
                
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
                            
                            // Update tab visibility in real-time if available in DOM
                            if (isChecked) {
                                // If the tab is enabled, make it visible immediately
                                const $tab = $('#bulk-date-tabs a[data-tab="' + tabId + '"]');
                                if ($tab.length) {
                                    // If tab exists but is hidden, show it
                                    $tab.removeClass('hidden');
                                } else {
                                    // If tab doesn't exist in DOM yet, reload the page
                                    // This is necessary for newly added tabs to appear
                                    window.location.reload();
                                }
                            } else {
                                // If the tab is disabled, hide it immediately
                                $('#bulk-date-tabs a[data-tab="' + tabId + '"]').addClass('hidden');
                            }
                        } else {
                            BulkDateAdmin.showNotice(response.data.message || bulkDateUpdate.strings.errorUpdatingSettings, 'error');
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
            $notice.removeClass('hidden notice-success notice-error notice-info')
                .addClass('notice notice-' + type)
                .html('<p>' + message + '</p>')
                .show();
            
            // Only auto-hide success and info messages
            if (type === 'success' || type === 'info') {
                // Hide message after 3 seconds
                setTimeout(function() {
                    $notice.fadeOut();
                }, 3000);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        BulkDateAdmin.init();
    });

})(jQuery); 