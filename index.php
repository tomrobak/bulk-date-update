<?php
/*
 * Plugin Name: Bulk Date Update
 * Version: 1.2
 * Description: Change the Post Update date for all posts in one click. This will help your blog in search engines and your blog will look alive. Do this every week or month.
 * Author: wplove.co
 * Author URI: https://tomrobak.com
 * Plugin URI: https://wplove.co
 * Text Domain: bulk-post-update-date
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 8.0
 * Requires at least: 5.0
 

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
define('BULK_DATE_UPDATE_VERSION', '1.2');

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
 * Register Ajax handlers for tab toggling functionality
 * 
 * @since 1.1
 * @return void
 */
function bulk_post_update_date_ajax_handlers(): void {
    add_action('wp_ajax_bulk_date_update_toggle_tab', 'bulk_post_update_date_toggle_tab');
}

add_action('admin_init', 'bulk_post_update_date_ajax_handlers');

/**
 * Ajax handler for enabling/disabling tabs
 * 
 * @since 1.1
 * @return void
 */
function bulk_post_update_date_toggle_tab(): void {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bulk_date_update_toggle_tab')) {
        wp_send_json_error([
            'message' => __('Security check failed.', 'bulk-post-update-date')
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
    $tab = sanitize_key($_POST['tab']);
    $enabled = (bool) $_POST['enabled'];
    
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
    update_option('bulk_date_update_tabs', $enabled_tabs);
    
    // Send success response
    wp_send_json_success([
        'message' => sprintf(
            $enabled 
                ? __('The %s tab has been enabled.', 'bulk-post-update-date') 
                : __('The %s tab has been disabled.', 'bulk-post-update-date'), 
            ucfirst($tab)
        ),
        'tab' => $tab,
        'enabled' => $enabled
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
    
    // Enqueue scripts with proper dependency management and versioning
    wp_enqueue_script('momentjs', 'https://cdn.jsdelivr.net/momentjs/latest/moment.min.js', [], '2.29.4', true);
    wp_enqueue_script('daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', ['jquery', 'momentjs'], '3.1.0', true);
    wp_enqueue_style('daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', [], '3.1.0');
    wp_enqueue_style('bulkupdatedate', plugins_url('/style.css', __FILE__), [], BULK_DATE_UPDATE_VERSION);
    
    // Add inline script to improve tab switching performance
    wp_add_inline_script('jquery', '
        // Pre-fetch tab content
        jQuery(document).ready(function($) {
            var tabCache = {};
            
            // Intercept tab clicks to store tab selection
            $(".nav-tab-wrapper a").on("click", function(e) {
                // Store the clicked tab in sessionStorage
                sessionStorage.setItem("bulkDateUpdateActiveTab", $(this).attr("href"));
            });
            
            // Check if there is a saved tab
            var activeTab = sessionStorage.getItem("bulkDateUpdateActiveTab");
            if (activeTab) {
                // If the current URL does not match the saved tab, do not restore
                var currentTab = window.location.href.split("tab=")[1];
                if (currentTab && activeTab.indexOf(currentTab) !== -1) {
                    // Tab is already correctly set
                } else if (!currentTab && activeTab.indexOf("tab=settings") !== -1) {
                    // We are on the main page and settings was selected
                } else {
                    // Restore the saved tab
                    window.location.href = activeTab;
                }
            }
        });
    ');
}

add_action('admin_enqueue_scripts', 'bulk_post_update_date_admin_enqueue_scripts');

/**
 * Plugin activation hook to set default tab settings
 *
 * @since 1.1
 * @return void
 */
function bulk_post_update_date_activate(): void {
    // Set default tab settings if not already set
    if (!get_option('bulk_date_update_tabs')) {
        $default_tabs = [
            'posts' => true,
            'pages' => true,
            'comments' => false
        ];
        
        update_option('bulk_date_update_tabs', $default_tabs);
    }
}

register_activation_hook(__FILE__, 'bulk_post_update_date_activate');

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
    if (!in_array($tab, ['settings', 'posts', 'pages', 'comments']) && !post_type_exists($tab)) {
        $tab = 'settings';
    }
    
    $now = current_time('timestamp', 0);

    // Handle form submission
    if (isset($_POST['tb_refresh']) && wp_verify_nonce($_POST['tb_refresh'], 'tb-refresh') && current_user_can('manage_options')) {

        try {
            if ($tab === 'comments') {
                include_once 'inc.php';
                $settings_saved = handleComments();
            } else if ($tab !== 'settings') {
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
    
    // Display the settings form
    include_once 'templates/settings-page.php';
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