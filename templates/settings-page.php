<?php
/**
 * Plugin settings page template
 *
 * @package Bulk Date Update
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get user settings for tabs
$enabled_tabs = get_option('bulk_date_update_tabs', array('posts' => true, 'pages' => true));

// Get available post types for settings tab
$post_type_args = array(
    'public'   => true,
    '_builtin' => false
);

$post_types = get_post_types($post_type_args, 'objects', 'and');

?>
<div class="wrap">
    <h1 class="title"><?php _e('Bulk Date Update', 'bulk-post-update-date'); ?></h1>
    <div>
        <?php _e('Change the Post Update date for all posts in one click. This will help your blog in search engines and your blog will look alive. Do this every week or month.', 'bulk-post-update-date'); ?>
    </div>
    
    <?php 
    // Show success message
    if ($settings_saved > 0) : 
        // Get post type name for display
        $post_type_name = ucfirst($tab);
        if (isset($post_type_obj) && $post_type_obj) {
            $post_type_name = $post_type_obj->labels->name;
        }
    ?>
        <div id="message" class="updated fade">
            <p><strong><?php echo sprintf(__('%d %s dates successfully updated.', 'bulk-post-update-date'), $settings_saved, $post_type_name); ?></strong></p>
        </div>
    <?php endif; ?>
    
    <?php
    // Show any error messages
    settings_errors('bulk_date_update');
    ?>

    <hr/>

    <div class="top-sharebar">
        <a class="share-btn" href="https://wplove.co/community/space/plugins-themes/home" target="_blank" style="background-color: #4caf50;">
            <span class="dashicons dashicons-sos"></span> <?php _e('Plugin Support', 'bulk-post-update-date'); ?>
        </a>
    </div>

    <?php
    
    // Available tabs
    $available_tabs = array(
        'posts' => __('Posts', 'bulk-post-update-date'),
        'pages' => __('Pages', 'bulk-post-update-date'),
        'comments' => __('Comments', 'bulk-post-update-date')
    );
    
    // Add custom post types to tabs
    if (count($post_types) > 0) {
        foreach ($post_types as $post_type) {
            $available_tabs[$post_type->name] = $post_type->labels->name;
        }
    }
    
    // Add history tab (always last)
    $available_tabs['history'] = __('History', 'bulk-post-update-date');
    
    // Add settings tab (always first)
    $available_tabs = array('settings' => __('Settings', 'bulk-post-update-date')) + $available_tabs;
    
    // Tab icons
    $tab_icons = array(
        'settings' => 'dashicons-admin-settings',
        'posts' => 'dashicons-admin-post',
        'pages' => 'dashicons-admin-page',
        'comments' => 'dashicons-admin-comments',
        'history' => 'dashicons-backup'
    );
    
    // Get history settings
    $history_enabled = get_option('bulk_date_update_history_enabled', true);
    $history_retention = get_option('bulk_date_update_history_retention', BULK_DATE_HISTORY_DEFAULT_RETENTION);
    
    ?>

    <h2 class="nav-tab-wrapper" id="bulk-date-tabs">
        <?php foreach ($available_tabs as $tab_key => $tab_name): 
            // Skip tabs that are not enabled, except settings and history
            if (!in_array($tab_key, array('settings', 'history')) && (!isset($enabled_tabs[$tab_key]) || !$enabled_tabs[$tab_key])) {
                continue;
            }
            
            // Get icon
            $icon = isset($tab_icons[$tab_key]) ? $tab_icons[$tab_key] : 'dashicons-admin-generic';
            
            // For custom post types, try to get their icon
            if (!isset($tab_icons[$tab_key]) && isset($post_types[$tab_key]) && isset($post_types[$tab_key]->menu_icon)) {
                $icon = $post_types[$tab_key]->menu_icon;
            }
        ?>
        <a href="?page=bulk-post-update-date&tab=<?php echo esc_attr($tab_key); ?>" class="nav-tab <?php echo $tab == $tab_key ? 'nav-tab-active' : ''; ?>" data-tab="<?php echo esc_attr($tab_key); ?>">
            <span class="dashicons <?php echo esc_attr($icon); ?>" style="padding-top: 2px;"></span> <?php echo esc_html($tab_name); ?>
        </a>
        <?php endforeach; ?>
    </h2>

    <div class="settings-container <?php echo $tab == 'settings' ? '' : 'hidden'; ?>" id="settings-tab-content">
        <h3><?php _e('Tab Settings', 'bulk-post-update-date'); ?></h3>
        <p><?php _e('Select which tabs you want to display in the navigation menu. Changes take effect immediately.', 'bulk-post-update-date'); ?></p>
        
        <div id="settings-response" class="hidden"></div>
        
        <!-- Settings page tab toggles -->
        <div id="settings-form-nonce" style="display:none;">
            <?php wp_nonce_field('bulk_date_update_toggle_tab', 'tab_toggle_nonce'); ?>
        </div>
        
        <form method="post" id="tab-settings-form">
            <table class="form-table" role="presentation">
                <tbody>
                <?php foreach ($available_tabs as $tab_key => $tab_name): 
                    // Skip settings tab itself and history tab (they can't be disabled)
                    if (in_array($tab_key, ['settings', 'history'])) {
                        continue;
                    }
                    
                    // Check if the tab is enabled
                    $is_enabled = isset($enabled_tabs[$tab_key]) && $enabled_tabs[$tab_key];
                ?>
                    <tr>
                        <th scope="row">
                            <label for="tab_<?php echo esc_attr($tab_key); ?>">
                                <?php echo esc_html($tab_name); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                id="tab_<?php echo esc_attr($tab_key); ?>" 
                                name="tab_<?php echo esc_attr($tab_key); ?>" 
                                class="tab-toggle" 
                                value="1" 
                                data-tab="<?php echo esc_attr($tab_key); ?>"
                                <?php checked($is_enabled, true); ?>>
                            <label for="tab_<?php echo esc_attr($tab_key); ?>">
                                <?php printf(__('Show %s tab', 'bulk-post-update-date'), esc_html($tab_name)); ?>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        
        <h3><?php _e('History Settings', 'bulk-post-update-date'); ?></h3>
        <p><?php _e('Configure how the plugin tracks and stores update history.', 'bulk-post-update-date'); ?></p>
        
        <form method="post" action="options.php">
            <?php settings_fields('bulk_date_update_history_settings'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="bulk_date_update_history_enabled">
                                <?php _e('Enable History Tracking', 'bulk-post-update-date'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   id="bulk_date_update_history_enabled" 
                                   name="bulk_date_update_history_enabled" 
                                   value="1" 
                                   <?php checked($history_enabled, true); ?>>
                            <label for="bulk_date_update_history_enabled">
                                <?php _e('Track date update history', 'bulk-post-update-date'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, the plugin will keep a record of all date changes.', 'bulk-post-update-date'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bulk_date_update_history_retention">
                                <?php _e('History Retention', 'bulk-post-update-date'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="bulk_date_update_history_retention" name="bulk_date_update_history_retention">
                                <option value="7" <?php selected($history_retention, 7); ?>>
                                    <?php _e('7 days', 'bulk-post-update-date'); ?>
                                </option>
                                <option value="14" <?php selected($history_retention, 14); ?>>
                                    <?php _e('14 days', 'bulk-post-update-date'); ?>
                                </option>
                                <option value="30" <?php selected($history_retention, 30); ?>>
                                    <?php _e('30 days', 'bulk-post-update-date'); ?>
                                </option>
                                <option value="60" <?php selected($history_retention, 60); ?>>
                                    <?php _e('60 days', 'bulk-post-update-date'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('How long to keep history records before automatically removing them.', 'bulk-post-update-date'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save History Settings', 'bulk-post-update-date'); ?>">
            </p>
        </form>
        
        <hr>
    </div>

    <div id="main-tab-content" class="<?php echo $tab == 'settings' ? 'hidden' : ''; ?>">
        <?php if (isset($is_history_tab) && $is_history_tab === true): ?>
            <?php 
            // Include history tab content directly
            include_once dirname(__FILE__) . '/history-tab-content.php'; 
            ?>
        <?php else: ?>
        <form method="post" action="" id="bulk-update-form">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="distribute"><?php _e('Distribute into Last', 'bulk-post-update-date'); ?></label></th>
                    <td>
                        <select type="text" id="distribute" name="distribute">
                            <option value="<?php echo strtotime('-1 hour', $now); ?>"><?php _e('1 hour', 'bulk-post-update-date'); ?></option>
                            <option value="<?php echo strtotime('-3 days', $now); ?>"><?php _e('3 days', 'bulk-post-update-date'); ?></option>
                            <option value="<?php echo strtotime('-7 days', $now); ?>"><?php _e('7 days', 'bulk-post-update-date'); ?></option>
                            <option value="<?php echo strtotime('-15 days', $now); ?>"><?php _e('15 Days', 'bulk-post-update-date'); ?></option>
                            <option value="<?php echo strtotime('-1 month', $now); ?>"><?php _e('1 Month', 'bulk-post-update-date'); ?></option>
                            <option value="<?php echo strtotime('-2 month', $now); ?>"><?php _e('2 Months', 'bulk-post-update-date'); ?></option>
                            <option value="<?php echo strtotime('-3 month', $now); ?>"><?php _e('3 Months', 'bulk-post-update-date'); ?></option>
                            <option value="<?php echo strtotime('-6 month', $now); ?>"><?php _e('6 Months', 'bulk-post-update-date'); ?></option>
                            <option value="0"><?php _e('Custom Range', 'bulk-post-update-date'); ?></option>
                        </select>
                        <p class="description">
                            <?php _e('Select range of date in which you want to spread the dates', 'bulk-post-update-date'); ?>
                        </p>
                    </td>
                </tr>
                <tr id="range_row" valign="top" style="display: none;">
                    <th scope="row"><label for="date_range"><?php _e('Custom Date Range', 'bulk-post-update-date'); ?></label></th>
                    <td>
                        <div class="date-range-container">
                            <div class="date-inputs">
                                <div class="date-input-wrapper">
                                    <label for="start_date"><?php _e('Start Date:', 'bulk-post-update-date'); ?></label>
                                    <input type="text" id="start_date" name="start_date" class="date-picker" placeholder="<?php _e('Select start date', 'bulk-post-update-date'); ?>" />
                                </div>
                                
                                <div class="date-input-wrapper">
                                    <label for="end_date"><?php _e('End Date:', 'bulk-post-update-date'); ?></label>
                                    <input type="text" id="end_date" name="end_date" class="date-picker" placeholder="<?php _e('Select end date', 'bulk-post-update-date'); ?>" />
                                </div>
                            </div>
                            
                            <input type="hidden" id="range" name="range" value="<?php echo date('m/d/y', strtotime('-3 days', $now)); ?> - <?php echo date('m/d/y', $now); ?>" />
                            
                            <div class="date-presets">
                                <span class="date-preset-label"><?php _e('Quick presets:', 'bulk-post-update-date'); ?></span>
                                <button type="button" class="button date-preset" data-preset="today"><?php _e('Today', 'bulk-post-update-date'); ?></button>
                                <button type="button" class="button date-preset" data-preset="yesterday"><?php _e('Yesterday', 'bulk-post-update-date'); ?></button>
                                <button type="button" class="button date-preset" data-preset="last7Days"><?php _e('Last 7 Days', 'bulk-post-update-date'); ?></button>
                                <button type="button" class="button date-preset" data-preset="last30Days"><?php _e('Last 30 Days', 'bulk-post-update-date'); ?></button>
                                <button type="button" class="button date-preset" data-preset="thisMonth"><?php _e('This Month', 'bulk-post-update-date'); ?></button>
                                <button type="button" class="button date-preset" data-preset="lastMonth"><?php _e('Last Month', 'bulk-post-update-date'); ?></button>
                            </div>
                        </div>
                        
                        <p class="description">
                            <?php _e('Select range of date in which you want to spread the dates', 'bulk-post-update-date'); ?>
                        </p>
                        
                        <div class="time-range-toggle">
                            <label>
                                <input type="checkbox" id="enable_time_range" name="enable_time_range" value="1">
                                <?php _e('Enable Custom Time Range', 'bulk-post-update-date'); ?>
                            </label>
                        </div>
                        
                        <div id="time_range_controls" class="time-range-controls" style="display: none;">
                            <div class="time-range-row">
                                <div class="time-input-wrapper">
                                    <label for="start_time"><?php _e('Start Time:', 'bulk-post-update-date'); ?></label>
                                    <input type="text" id="start_time" name="start_time" value="00:00" class="time-picker" placeholder="<?php _e('Select start time', 'bulk-post-update-date'); ?>" />
                                </div>
                                
                                <div class="time-input-wrapper">
                                    <label for="end_time"><?php _e('End Time:', 'bulk-post-update-date'); ?></label>
                                    <input type="text" id="end_time" name="end_time" value="23:59" class="time-picker" placeholder="<?php _e('Select end time', 'bulk-post-update-date'); ?>" />
                                </div>
                            </div>
                            <p class="description">
                                <?php _e('Specify a time range for your date updates. The plugin will randomly generate times within this range.', 'bulk-post-update-date'); ?>
                            </p>
                            <div class="time-presets">
                                <span class="time-preset-label"><?php _e('Quick presets:', 'bulk-post-update-date'); ?></span>
                                <button type="button" class="button time-preset" data-start="09:00" data-end="17:00"><?php _e('Business Hours (9AM-5PM)', 'bulk-post-update-date'); ?></button>
                                <button type="button" class="button time-preset" data-start="08:00" data-end="12:00"><?php _e('Morning (8AM-12PM)', 'bulk-post-update-date'); ?></button>
                                <button type="button" class="button time-preset" data-start="13:00" data-end="18:00"><?php _e('Afternoon (1PM-6PM)', 'bulk-post-update-date'); ?></button>
                                <button type="button" class="button time-preset" data-start="19:00" data-end="23:00"><?php _e('Evening (7PM-11PM)', 'bulk-post-update-date'); ?></button>
                            </div>
                        </div>
                    </td>
                </tr>

                <?php
                // Include tab specific content
                if ($tab !== 'settings' && file_exists(dirname(__DIR__) . "/{$tab}.php")) {
                    include_once dirname(__DIR__) . "/{$tab}.php";
                }
                ?>
                
                <?php if ($tab !== 'comments' && $tab !== 'settings') : ?>
                <tr id="field_row" valign="top">
                    <th scope="row"><label for="field"><?php _e('Date field to update', 'bulk-post-update-date'); ?></label></th>
                    <td>
                        <input type="radio" id="published" name="field" value="published">
                        <label for="published"><?php _e('Published Date', 'bulk-post-update-date'); ?></label>
                        
                        <input type="radio" id="modified" name="field" value="modified" checked>
                        <label for="modified"><?php _e('Modified Date', 'bulk-post-update-date'); ?></label>

                        <input type="radio" id="date_both" name="field" value="date_both">
                        <label for="date_both"><?php _e('Both Dates Equal', 'bulk-post-update-date'); ?></label>

                        <p class="description">
                            <?php _e('Updating modified date is recommended.', 'bulk-post-update-date'); ?>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>
            </table>

            <p class="submit">
                <input name="tb_refresh" type="hidden" value="<?php echo wp_create_nonce('tb-refresh'); ?>" />
                <input class="button-primary" name="do" type="submit" value="<?php _e('Update Post Dates', 'bulk-post-update-date'); ?>" />
            </p>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="coffee-box">
    <div class="coffee-amt-wrap">
        <a class="button button-primary join-community-btn" href="https://wplove.co/community" target="_blank">
            <span class="dashicons dashicons-groups"></span> <?php _e('Join wplove community', 'bulk-post-update-date'); ?>
        </a>
    </div>
    <span class="coffee-heading"><?php _e('Join wplove community', 'bulk-post-update-date'); ?></span>
    <p style="text-align: justify;"><?php _e('If wplove.co plugin helped you, imagine what it can do for your friends. Spread the word! 🔥 Tell your friends to join wplove.co', 'bulk-post-update-date'); ?></p>
</div>

<div class="wplove-resources">
    <h3><?php _e('More from wplove.co', 'bulk-post-update-date'); ?></h3>
    <div class="resource-cards">
        <div class="resource-card">
            <h4><span class="dashicons dashicons-camera"></span> <?php _e('WordPress for Photographers', 'bulk-post-update-date'); ?></h4>
            <p><?php _e('Photographers community focused on WordPress & Marketing - tutorials and AI automations to simplify website management.', 'bulk-post-update-date'); ?></p>
            <a href="https://wplove.co/community" target="_blank" class="button"><?php _e('Join Community', 'bulk-post-update-date'); ?></a>
        </div>
        <div class="resource-card">
            <h4><span class="dashicons dashicons-chart-line"></span> <?php _e('Posts Remastered', 'bulk-post-update-date'); ?></h4>
            <p><?php _e('Google ignoring your posts? Let\'s change that. Watch how I made full post SEO makeover to finally rank. No fluff. No BS. Just real results. And yeah, it\'s FREE.', 'bulk-post-update-date'); ?></p>
            <a href="https://wplove.co/posts-remastered/" target="_blank" class="button"><?php _e('Learn More', 'bulk-post-update-date'); ?></a>
        </div>
        <div class="resource-card">
            <h4><span class="dashicons dashicons-welcome-write-blog"></span> <?php _e('Read wplove Blog', 'bulk-post-update-date'); ?></h4>
            <p><?php _e('Learn best practices, tips, and tricks for WordPress from our expert tutorials.', 'bulk-post-update-date'); ?></p>
            <a href="https://wplove.co/articles/" target="_blank" class="button"><?php _e('Read Articles', 'bulk-post-update-date'); ?></a>
        </div>
    </div>
</div> 