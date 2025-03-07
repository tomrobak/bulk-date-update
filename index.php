<?php
/*
 * Plugin Name: Bulk Date Update
 * Version: 1.5.0
 * Description: Change the Post Update date for all posts in one click. This will help your blog in search engines and your blog will look alive. Do this every week or month.
 * Author: wplove.co
 * Author URI: https://tomrobak.com
 * Plugin URI: https://wplove.co/bulk-date-update/
 * Text Domain: bulk-post-update-date
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 8.0
 * Requires at least: 6.7
 

    Bulk Date Update is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    any later version.
    
    Bulk Date Update is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with Bulk Date Update. If not, see {URI to Plugin License}.
*/

// Define plugin version constant
define('BULK_DATE_UPDATE_VERSION', '1.5.0');

// Define history constants
define('BULK_DATE_HISTORY_TABLE', 'bulk_date_update_history');
define('BULK_DATE_HISTORY_DEFAULT_RETENTION', 30); // 30 days default retention

/**
 * Add plugin menu item to WordPress admin
 * 
 * @since 1.0
 * @return void
 */
function bulk_post_update_date_menu(): void {
    // Create a standalone menu item instead of under Settings
    add_menu_page(
        'Bulk Date Update', 
        'Bulk Date Update', 
        'manage_options', 
        'bulk-post-update-date', 
        'bulk_post_update_date_options',
        'dashicons-calendar-alt',
        80
    );
}

add_action('admin_menu', 'bulk_post_update_date_menu');

/**
 * Initialize plugin text domain
 * 
 * @since 1.0
 * @return void
 */
function bulk_post_update_date_load_textdomain(): void {
    load_plugin_textdomain('bulk-post-update-date', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

add_action('plugins_loaded', 'bulk_post_update_date_load_textdomain');

/**
 * Register AJAX handlers
 * 
 * @since 1.1
 * @since 1.5.0 Added history infinite scroll handler
 * @return void
 */
function bulk_post_update_date_ajax_handlers(): void {
    add_action('wp_ajax_bulk_date_update_toggle_tab', 'bulk_post_update_date_toggle_tab');
    add_action('wp_ajax_bulk_date_update_load_more_history', 'bulk_date_update_load_more_history');
    add_action('wp_ajax_bulk_date_update_remove_history_record', 'bulk_date_update_remove_history_record');
}
add_action('admin_init', 'bulk_post_update_date_ajax_handlers');

/**
 * AJAX handler for removing a history record after restore
 * 
 * @since 1.5.0
 * @return void
 */
function bulk_date_update_remove_history_record(): void {
    // Check nonce for security
    check_ajax_referer('bulk_date_update_restore_record', 'nonce');
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have sufficient permissions to access this data.', 'bulk-post-update-date')]);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . BULK_DATE_HISTORY_TABLE;
    
    // Get record ID
    $record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
    
    if (!$record_id) {
        wp_send_json_error(['message' => __('Invalid record ID.', 'bulk-post-update-date')]);
        return;
    }
    
    // Delete the record
    $result = $wpdb->delete(
        $table_name,
        ['id' => $record_id],
        ['%d']
    );
    
    if ($result === false) {
        wp_send_json_error(['message' => __('Failed to delete history record.', 'bulk-post-update-date')]);
        return;
    }
    
    wp_send_json_success([
        'message' => __('Record successfully deleted from history.', 'bulk-post-update-date'),
        'record_id' => $record_id
    ]);
}

/**
 * AJAX handler for loading more history records
 * 
 * @since 1.5.0
 * @return void
 */
function bulk_date_update_load_more_history(): void {
    // Check nonce for security
    check_ajax_referer('bulk_date_update_infinite_scroll', 'nonce');
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have sufficient permissions to access this data.', 'bulk-post-update-date')]);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . BULK_DATE_HISTORY_TABLE;
    
    // Get pagination parameters
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // Get filter parameters
    $filter_post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
    $filter_date_field = isset($_POST['date_field']) ? sanitize_text_field($_POST['date_field']) : '';
    $filter_date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
    $filter_date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
    $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'modified_at';
    $sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'DESC';
    
    // Validate sort parameters
    $allowed_sort_fields = ['modified_at', 'previous_date', 'new_date'];
    $allowed_sort_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort_by, $allowed_sort_fields)) {
        $sort_by = 'modified_at';
    }
    
    if (!in_array($sort_order, $allowed_sort_orders)) {
        $sort_order = 'DESC';
    }
    
    // Build query
    $query = "SELECT * FROM $table_name WHERE 1=1";
    $query_args = [];
    
    // Apply filters
    if (!empty($filter_post_type)) {
        $query .= " AND post_type = %s";
        $query_args[] = $filter_post_type;
    }
    
    if (!empty($filter_date_field)) {
        $query .= " AND date_field = %s";
        $query_args[] = $filter_date_field;
    }
    
    if (!empty($filter_date_from)) {
        $query .= " AND modified_at >= %s";
        $query_args[] = date('Y-m-d 00:00:00', strtotime($filter_date_from));
    }
    
    if (!empty($filter_date_to)) {
        $query .= " AND modified_at <= %s";
        $query_args[] = date('Y-m-d 23:59:59', strtotime($filter_date_to));
    }
    
    // Add order
    $query .= " ORDER BY $sort_by $sort_order LIMIT %d OFFSET %d";
    $query_args[] = $per_page;
    $query_args[] = $offset;
    
    // Execute query
    $history_records = $wpdb->get_results(
        empty($query_args) ? $query : $wpdb->prepare($query, $query_args)
    );
    
    $html = '';
    
    if (empty($history_records)) {
        wp_send_json_success([
            'html' => '',
            'has_more' => false,
            'message' => __('No more records to load.', 'bulk-post-update-date')
        ]);
        return;
    }
    
    // Generate HTML for each record
    foreach ($history_records as $record) {
        // Get post type name instead of slug
        $post_type_obj = get_post_type_object($record->post_type);
        $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst($record->post_type);
        $date_field_label = $record->date_field === 'post_date' 
            ? esc_html__('Published Date', 'bulk-post-update-date') 
            : esc_html__('Modified Date', 'bulk-post-update-date');
        $record_date = get_date_from_gmt($record->modified_at, get_option('date_format') . ' ' . get_option('time_format'));
        $previous_date = get_date_from_gmt($record->previous_date, get_option('date_format') . ' ' . get_option('time_format'));
        $new_date = get_date_from_gmt($record->new_date, get_option('date_format') . ' ' . get_option('time_format'));
        
        $html .= '<div class="history-record-card" data-record-id="' . esc_attr($record->id) . '">';
        $html .= '<div class="history-record-header">';
        $html .= '<div class="history-record-title">';
        $html .= '<a href="' . esc_url(get_edit_post_link($record->post_id)) . '" title="' . esc_attr__('Edit Post', 'bulk-post-update-date') . '">';
        $html .= esc_html($record->post_title);
        $html .= '</a>';
        $html .= '<a href="' . esc_url(get_permalink($record->post_id)) . '" class="view-link" title="' . esc_attr__('View Post', 'bulk-post-update-date') . '" target="_blank">';
        $html .= '<span class="dashicons dashicons-visibility"></span>';
        $html .= '</a>';
        $html .= '</div>';
        $html .= '<div class="history-record-actions">';
        $html .= '<a href="' . esc_url(wp_nonce_url(
            add_query_arg('restore', $record->id),
            'bulk_date_update_restore_' . $record->id
        )) . '" class="btn btn-success btn-sm restore-button" data-record-id="' . esc_attr($record->id) . '" title="' . esc_attr__('Restore Previous Date', 'bulk-post-update-date') . '">';
        $html .= esc_html__('Restore', 'bulk-post-update-date');
        $html .= '</a>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="history-record-body">';
        $html .= '<div class="history-record-field">';
        $html .= '<div class="history-record-label">' . esc_html__('Date & Time', 'bulk-post-update-date') . '</div>';
        $html .= '<div class="history-record-value">' . esc_html($record_date) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="history-record-field">';
        $html .= '<div class="history-record-label">' . esc_html__('Post Type', 'bulk-post-update-date') . '</div>';
        $html .= '<div class="history-record-value">' . esc_html($post_type_name) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="history-record-field">';
        $html .= '<div class="history-record-label">' . esc_html__('Date Field', 'bulk-post-update-date') . '</div>';
        $html .= '<div class="history-record-value">' . esc_html($date_field_label) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="history-record-field">';
        $html .= '<div class="history-record-label">' . esc_html__('Previous Date', 'bulk-post-update-date') . '</div>';
        $html .= '<div class="history-record-value">' . esc_html($previous_date) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="history-record-field">';
        $html .= '<div class="history-record-label">' . esc_html__('New Date', 'bulk-post-update-date') . '</div>';
        $html .= '<div class="history-record-value">' . esc_html($new_date) . '</div>';
        $html .= '</div>';
        
        $html .= '</div>'; // end history-record-body
        $html .= '</div>'; // end history-record-card
    }
    
    // Get total count to determine if there are more records
    $count_query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
    $count_args = array_slice($query_args, 0, -2); // Remove LIMIT and OFFSET arguments
    
    if (!empty($filter_post_type)) {
        $count_query .= " AND post_type = %s";
    }
    
    if (!empty($filter_date_field)) {
        $count_query .= " AND date_field = %s";
    }
    
    if (!empty($filter_date_from)) {
        $count_query .= " AND modified_at >= %s";
    }
    
    if (!empty($filter_date_to)) {
        $count_query .= " AND modified_at <= %s";
    }
    
    $total_records = $wpdb->get_var(
        empty($count_args) ? $count_query : $wpdb->prepare($count_query, $count_args)
    );
    
    $total_pages = ceil($total_records / $per_page);
    $has_more = $page < $total_pages;
    
    wp_send_json_success([
        'html' => $html,
        'has_more' => $has_more,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'next_page' => $page + 1,
        'total_records' => $total_records
    ]);
}

/**
 * AJAX handler for toggling tabs
 * 
 * @since 1.1
 * @return void
 */
function bulk_post_update_date_toggle_tab(): void {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bulk_date_update_toggle_tab')) {
        wp_send_json_error([
            'message' => __('Security check failed.', 'bulk-post-update-date'),
            'debug' => [
                'nonce_provided' => isset($_POST['nonce']) ? 'yes' : 'no',
                'nonce_value' => isset($_POST['nonce']) ? substr($_POST['nonce'], 0, 5) . '...' : 'none',
                'action' => 'bulk_date_update_toggle_tab'
            ]
        ]);
        return;
    }
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('You do not have permission to update settings.', 'bulk-post-update-date')
        ]);
        return;
    }
    
    // Get tab and enabled status
    $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : '';
    $enabled = isset($_POST['enabled']) ? (bool) $_POST['enabled'] : false;
    
    if (empty($tab)) {
        wp_send_json_error([
            'message' => __('Invalid tab specified.', 'bulk-post-update-date')
        ]);
        return;
    }
    
    // Get current settings
    $enabled_tabs = get_option('bulk_date_update_tabs', [
        'posts' => true,
        'pages' => true
    ]);
    
    // Update settings
    $enabled_tabs[$tab] = $enabled;
    
    // Save updated settings
    $update_result = update_option('bulk_date_update_tabs', $enabled_tabs);
    
    // Send success response
    wp_send_json_success([
        'message' => sprintf(
            $enabled 
                ? __('The %s tab has been enabled.', 'bulk-post-update-date') 
                : __('The %s tab has been disabled.', 'bulk-post-update-date'), 
            ucfirst($tab)
        ),
        'tab' => $tab,
        'enabled' => $enabled,
        'update_result' => $update_result,
        'new_settings' => $enabled_tabs
    ]);
}

/**
 * Enqueue admin scripts and styles
 *
 * @since 1.1
 * @param string $hook Current admin page hook
 * @return void
 */
function bulk_post_update_date_admin_enqueue_scripts(string $hook): void {
    if ($hook !== 'toplevel_page_bulk-post-update-date') {
        return;
    }
    
    // Add flatpickr for modern date and time picker
    wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', ['jquery'], '4.6.13', true);
    wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', [], '4.6.13');
    
    // Add flatpickr range plugin for date range
    wp_enqueue_script('flatpickr-range', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js', ['flatpickr'], '4.6.13', true);
    
    // Plugin specific styles and scripts
    wp_enqueue_style('bulkupdatedate', plugins_url('/style.css', __FILE__), [], BULK_DATE_UPDATE_VERSION);
    wp_register_script('bulkupdatedate-admin', plugins_url('/js/admin.js', __FILE__), ['jquery', 'flatpickr', 'flatpickr-range', 'wp-util'], BULK_DATE_UPDATE_VERSION, true);
    
    // Localize script with settings and translatable strings
    wp_localize_script('bulkupdatedate-admin', 'bulkDateUpdate', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bulk_date_update_toggle_tab'),
        'restoreNonce' => wp_create_nonce('bulk_date_update_restore_record'),
        'currentTab' => isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings',
        'strings' => [
            'invalidTimeRange' => __('Start time cannot be later than end time.', 'bulk-post-update-date'),
            'updatingSettings' => __('Updating settings...', 'bulk-post-update-date'),
            'settingsUpdated' => __('Settings updated successfully.', 'bulk-post-update-date'),
            'errorUpdatingSettings' => __('Error updating settings. Please try again.', 'bulk-post-update-date'),
            'today' => __('Today', 'bulk-post-update-date'),
            'yesterday' => __('Yesterday', 'bulk-post-update-date'),
            'last7Days' => __('Last 7 Days', 'bulk-post-update-date'),
            'last30Days' => __('Last 30 Days', 'bulk-post-update-date'),
            'thisMonth' => __('This Month', 'bulk-post-update-date'),
            'lastMonth' => __('Last Month', 'bulk-post-update-date'),
            'custom' => __('Custom', 'bulk-post-update-date'),
            'invalidDate' => __('Invalid date provided', 'bulk-post-update-date'),
            'dateRestored' => __('Date restored successfully and record removed from history.', 'bulk-post-update-date'),
            'restoreError' => __('Error restoring date. Please try again.', 'bulk-post-update-date'),
            'noRecordsFound' => __('No history records found.', 'bulk-post-update-date')
        ],
        'dates' => [
            'today' => date('Y-m-d'),
            'yesterday' => date('Y-m-d', strtotime('-1 day')),
            'last7Start' => date('Y-m-d', strtotime('-7 days')),
            'last30Start' => date('Y-m-d', strtotime('-30 days')),
            'thisMonthStart' => date('Y-m-01'),
            'lastMonthStart' => date('Y-m-d', strtotime('first day of last month')),
            'lastMonthEnd' => date('Y-m-d', strtotime('last day of last month')),
            'format' => 'Y-m-d' // Add format info for JavaScript
        ]
    ]);
    
    wp_enqueue_script('bulkupdatedate-admin');
}

add_action('admin_enqueue_scripts', 'bulk_post_update_date_admin_enqueue_scripts');

/**
 * Plugin activation hook
 * 
 * Creates necessary database tables and sets default options
 *
 * @since 1.0
 * @since 1.5.0 Added history table creation
 * @return void
 */
function bulk_post_update_date_activate(): void {
    // Set default tab options if not already set
    if (!get_option('bulk_date_update_tabs')) {
        update_option('bulk_date_update_tabs', array(
            'posts' => true,
            'pages' => true,
            'comments' => true
        ));
    }
    
    // Set default history options
    if (!get_option('bulk_date_update_history_enabled')) {
        update_option('bulk_date_update_history_enabled', true);
    }
    
    if (!get_option('bulk_date_update_history_retention')) {
        update_option('bulk_date_update_history_retention', BULK_DATE_HISTORY_DEFAULT_RETENTION);
    }
    
    // Create history table
    create_history_table();
}

/**
 * Creates the history table for storing date update history
 *
 * @since 1.5.0
 * @return void
 */
function create_history_table(): void {
    global $wpdb;
    
    $table_name = $wpdb->prefix . BULK_DATE_HISTORY_TABLE;
    $charset_collate = $wpdb->get_charset_collate();
    
    // SQL to create the history table
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        post_title varchar(255) NOT NULL,
        post_type varchar(20) NOT NULL,
        previous_date datetime NOT NULL,
        new_date datetime NOT NULL,
        date_field varchar(20) NOT NULL COMMENT 'post_date or post_modified',
        modified_by bigint(20) NOT NULL,
        modified_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY post_type (post_type),
        KEY modified_at (modified_at)
    ) $charset_collate;";
    
    // Include WordPress database upgrade functions
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create the table
    dbDelta($sql);
}

/**
 * Plugin options page
 * 
 * @since 1.0
 * @return void
 */
function bulk_post_update_date_options(): void {
    global $wpdb;
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'bulk-post-update-date'));
    }
    
    $settings_saved = 0;
    
    // Get the current tab
    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';
    $type = $tab;

    // Default to settings tab
    if (!in_array($tab, ['settings', 'posts', 'pages', 'comments', 'history']) && !post_type_exists($tab)) {
        $tab = 'settings';
    }
    
    $now = current_time('timestamp', 0);

    // Handle form submission
    if (isset($_POST['tb_refresh']) && wp_verify_nonce($_POST['tb_refresh'], 'tb-refresh') && current_user_can('manage_options')) {

        try {
            if ($tab === 'comments') {
          include_once 'inc.php';
          $settings_saved = handleComments();
            } else if ($tab !== 'settings' && $tab !== 'history') {
                // Process post types (posts, pages, custom post types)
                $settings_saved = processPostTypeUpdate($type);
            }
        } catch (Exception $e) {
            // Handle any exceptions
            $error_message = $e->getMessage();
            add_settings_error(
                'bulk_date_update',
                'update_error',
                sprintf(__('Error updating dates: %s', 'bulk-post-update-date'), $error_message),
                'error'
            );
        }
    }
    
    // Include the appropriate template based on the tab
    if ($tab === 'history') {
        // Get post type object for display if applicable (for consistent template)
        $post_type_obj = null;
        
        // Flag for the settings template to know we're on the history tab
        $is_history_tab = true;
        
        // Display the settings form with history content
        include_once 'templates/settings-page.php';
    } else {
        // Get post type object for display if applicable
        $post_type_obj = null;
        if ($tab !== 'settings' && $tab !== 'comments') {
            $post_type_obj = get_post_type_object($tab);
        }
        
        // Flag for the settings template - we're not on history tab
        $is_history_tab = false;
        
        // Display the settings form for other tabs
        include_once 'templates/settings-page.php';
    }
}

/**
 * Process post type updates (posts, pages, or custom post types)
 *
 * @since 1.0
 * @param string $type The post type to update
 * @return int Number of updated posts
 */
function processPostTypeUpdate(string $type): int {
    global $wpdb;
    
    // Get post type object for nice name display
    $post_type_obj = get_post_type_object($type);
    $post_type_name = $post_type_obj ? $post_type_obj->labels->name : ucfirst($type);
    
    // Get field to update (published date, modified date, or both)
    $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : 'modified';
    if ($field !== 'date_both') {
                $field = $field == 'published' ? 'post_date' : 'post_modified';
            }

    $ids = [];

    // Handle regular posts
    if ($type == 'posts') {
        $params = [
            'numberposts' => -1,
                    'post_status' => 'publish',
                    'fields'      => 'ids'
        ];

        // Add category filter if specified
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            $params['cat'] = implode(',', array_map('intval', $_POST['categories']));
        }

        // Add tag filter if specified
        if (isset($_POST['tags']) && is_array($_POST['tags'])) {
            $params['tag'] = implode(',', array_map('sanitize_text_field', $_POST['tags']));
        }

        $ids = get_posts($params);
    } 
    // Handle pages
    else if ($type == 'pages') {
        if (isset($_POST['pages']) && is_array($_POST['pages'])) {
            $ids = array_map('intval', $_POST['pages']);
                } else {
            $pages = get_pages([
                'sort_column' => 'post_title',
                'post_status' => 'publish'
            ]);
            $ids = wp_list_pluck($pages, 'ID');
        }
    } 
    // Handle custom post types
    else {
        $params = [
            'numberposts' => -1,
                    'post_status' => 'publish',
                    'fields'      => 'ids',
            'post_type'   => sanitize_text_field($type)
        ];

        // Add taxonomy filters if specified
        if (isset($_POST['tax']) && is_array($_POST['tax'])) {
            foreach ($_POST['tax'] as $tax => $terms) {
                if (!is_array($terms)) continue;

                $params['tax_query'][] = [
                    'taxonomy' => sanitize_key($tax),
                            'field'    => 'term_id',
                    'terms'    => array_map('intval', $terms)
                ];
            }

            // Add tax query relation if there are multiple taxonomies
            $relation = isset($_POST['tax_relation']) ? sanitize_text_field($_POST['tax_relation']) : 'OR';
            $params['tax_query']['relation'] = in_array($relation, ['AND', 'OR']) ? $relation : 'OR';
        }

        $ids = get_posts($params);
    }

    // Get date range
    $from = isset($_POST['distribute']) ? intval($_POST['distribute']) : 0;
    $to   = current_time('timestamp', 0);
    $now  = current_time('timestamp', 0);

    // Handle custom date range
    if ($from == 0 && isset($_POST['range'])) {
        $range = explode('-', sanitize_text_field($_POST['range']));
        if (count($range) == 2) {
            $from = strtotime(trim($range[0]), $now);
            $to   = strtotime(trim($range[1]), $now);
            
            // Validate timestamps
            if (!$from || !$to) {
                $from = strtotime('-3 hours', $now);
                $to = $now;
            }
        } else {
            $from = strtotime('-3 hours', $now);
        }
    }

    // Handle custom time range
    $use_custom_time = isset($_POST['enable_time_range']) && $_POST['enable_time_range'] == 1;
    $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '00:00';
    $end_time = isset($_POST['end_time']) ? sanitize_text_field($_POST['end_time']) : '23:59';
    
    // Validate time format (should be in 24-hour format: HH:MM)
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time)) {
        $start_time = '00:00';
    }
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
        $end_time = '23:59';
    }
    
    // Convert times to seconds since midnight for easier random generation
    $start_time_seconds = strtotime($start_time) - strtotime('00:00');
    $end_time_seconds = strtotime($end_time) - strtotime('00:00');

    // Ensure from is before to
    if ($from > $to) {
        $temp = $from;
        $from = $to;
        $to = $temp;
    }

    // Process in smaller batches for better performance
    $batch_size = 50;
    $total_ids = count($ids);
    $processed = 0;
    
    for ($i = 0; $i < $total_ids; $i += $batch_size) {
        $batch_ids = array_slice($ids, $i, $batch_size);
        
        foreach ($batch_ids as $id) {
            // Generate random date within the specified range
            $time_timestamp = rand($from, $to);
            
            // If custom time range is enabled, adjust the time portion of the date
            if ($use_custom_time) {
                // Get the date portion without time
                $date_only = date('Y-m-d', $time_timestamp);
                
                // Generate random seconds between start and end time
                $random_seconds = rand($start_time_seconds, $end_time_seconds);
                
                // Create a new timestamp with the date and random time
                $time_timestamp = strtotime($date_only) + $random_seconds;
            }
            
            $time = date("Y-m-d H:i:s", $time_timestamp);
            $time_gmt = get_gmt_from_date($time);
            
            // Get current dates for history logging
            $post = get_post($id);
            $post_title = $post->post_title;
            $post_type = $post->post_type;
            $previous_date_published = $post->post_date;
            $previous_date_modified = $post->post_modified;
            
            if ($field === 'date_both') {
                $wpdb->update(
                    $wpdb->posts,
                    [
                        'post_date' => $time,
                        'post_date_gmt' => $time_gmt,
                        'post_modified' => $time,
                        'post_modified_gmt' => $time_gmt
                    ],
                    ['ID' => $id],
                    ['%s', '%s', '%s', '%s'],
                    ['%d']
                );
                
                // Log history for both date fields
                bulk_date_update_log_history($id, $post_title, $post_type, $previous_date_published, $time, 'post_date');
                bulk_date_update_log_history($id, $post_title, $post_type, $previous_date_modified, $time, 'post_modified');
            } else {
                $gmt_field = "{$field}_gmt";
                $data = [];
                $data[$field] = $time;
                $data[$gmt_field] = $time_gmt;
                
                $wpdb->update(
                    $wpdb->posts,
                    $data,
                    ['ID' => $id],
                    ['%s', '%s'],
                    ['%d']
                );
                
                // Log history for the updated field
                $previous_date = ($field === 'post_date') ? $previous_date_published : $previous_date_modified;
                bulk_date_update_log_history($id, $post_title, $post_type, $previous_date, $time, $field);
            }
            
            $processed++;
            
            // Clear object cache periodically
            if ($processed % 10 === 0) {
                clean_post_cache($id);
            }
        }
        
        // Free up memory after each batch
        wp_cache_flush();
    }
    
    return $processed;
}

register_activation_hook(__FILE__, 'bulk_post_update_date_activate');

/**
 * Register settings
 * 
 * @since 1.5.0
 * @return void
 */
function bulk_date_update_register_settings(): void {
    register_setting(
        'bulk_date_update_history_settings',
        'bulk_date_update_history_enabled',
        [
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => function($value) {
                return (bool) $value;
            }
        ]
    );
    
    register_setting(
        'bulk_date_update_history_settings',
        'bulk_date_update_history_retention',
        [
            'type' => 'integer',
            'default' => BULK_DATE_HISTORY_DEFAULT_RETENTION,
            'sanitize_callback' => function($value) {
                $value = (int) $value;
                // Make sure the value is one of our allowed options
                if (!in_array($value, [7, 14, 30, 60])) {
                    return BULK_DATE_HISTORY_DEFAULT_RETENTION;
                }
                return $value;
            }
        ]
    );
}
add_action('admin_init', 'bulk_date_update_register_settings');

/**
 * Log post date update to history
 *
 * @since 1.5.0
 * @param int $post_id The post ID
 * @param string $post_title The post title
 * @param string $post_type The post type
 * @param string $previous_date The previous date value
 * @param string $new_date The new date value
 * @param string $date_field Which date field was updated (post_date or post_modified)
 * @return bool|int False on failure, record ID on success
 */
function bulk_date_update_log_history(int $post_id, string $post_title, string $post_type, string $previous_date, string $new_date, string $date_field): bool|int {
    // Check if history is enabled
    if (!get_option('bulk_date_update_history_enabled', true)) {
        return false;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . BULK_DATE_HISTORY_TABLE;
    
    // Current user ID
    $user_id = get_current_user_id();
    
    // Insert history record
    $result = $wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'post_title' => $post_title,
            'post_type' => $post_type,
            'previous_date' => $previous_date,
            'new_date' => $new_date,
            'date_field' => $date_field,
            'modified_by' => $user_id,
            'modified_at' => current_time('mysql', true)
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
    );
    
    if ($result === false) {
        return false;
    }
    
    // Clean up old records
    bulk_date_update_clean_history();
    
    return $wpdb->insert_id;
}

/**
 * Clean up old history records based on retention setting
 *
 * @since 1.5.0
 * @return int Number of records deleted
 */
function bulk_date_update_clean_history(): int {
    global $wpdb;
    $table_name = $wpdb->prefix . BULK_DATE_HISTORY_TABLE;
    
    // Get retention period in days
    $retention_days = (int) get_option('bulk_date_update_history_retention', BULK_DATE_HISTORY_DEFAULT_RETENTION);
    
    // Delete records older than retention period
    $date_threshold = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
    
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE modified_at < %s",
            $date_threshold
        )
    );
    
    return (int) $deleted;
}