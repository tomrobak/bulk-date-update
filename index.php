<?php
/*
 * Plugin Name: Bulk Date Update
 * Version: 1.0
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
    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'posts';
    $type = $tab;

    // Extra Check for url bug
    $tab = (in_array($tab, ['pages', 'posts'])) ? $tab : 'custom';

    $now = current_time('timestamp', 0);

    if (isset($_GET['tab']) && $_GET['tab'] === 'comments') {
        $type = $tab = 'comments';
    }

    // Handle form submission
    if (isset($_POST['tb_refresh']) && wp_verify_nonce($_POST['tb_refresh'], 'tb-refresh') && current_user_can('manage_options')) {

        try {
            if ($tab === 'comments') {
                include_once 'inc.php';
                $settings_saved = handleComments();
            } else {
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

    // Enqueue necessary scripts and styles
    wp_enqueue_script('momentjs', 'https://cdn.jsdelivr.net/momentjs/latest/moment.min.js', [], '2.29.4', true);
    wp_enqueue_script('daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', ['jquery', 'momentjs'], '3.1.0', true);
    wp_enqueue_style('daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', [], '3.1.0');
    wp_enqueue_style('bulkupdatedate', plugins_url('/style.css', __FILE__), [], '1.0.0');
    
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
            $time = rand($from, $to);
            $time = date("Y-m-d H:i:s", $time);
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