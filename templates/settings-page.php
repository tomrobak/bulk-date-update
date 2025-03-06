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
    if ($settings_saved > 0) : ?>
        <div id="message" class="updated fade">
            <p><strong><?php echo sprintf(__('%d %s dates successfully updated.', 'bulk-post-update-date'), $settings_saved, ucfirst($tab)); ?></strong></p>
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

    <h2 class="nav-tab-wrapper" id="bulk-date-tabs">
        <a href="?page=bulk-post-update-date&tab=settings" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>" data-tab="settings">
            <span class="dashicons dashicons-admin-settings" style="padding-top: 2px;"></span> <?php _e('Settings', 'bulk-post-update-date'); ?>
        </a>
        <?php if(isset($enabled_tabs['posts']) && $enabled_tabs['posts']): ?>
        <a href="?page=bulk-post-update-date&tab=posts" class="nav-tab <?php echo $tab == 'posts' ? 'nav-tab-active' : ''; ?>" data-tab="posts">
            <span class="dashicons dashicons-admin-post" style="padding-top: 2px;"></span> <?php _e('Posts', 'bulk-post-update-date'); ?>
        </a>
        <?php endif; ?>
        
        <?php if(isset($enabled_tabs['pages']) && $enabled_tabs['pages']): ?>
        <a href="?page=bulk-post-update-date&tab=pages" class="nav-tab <?php echo $tab == 'pages' ? 'nav-tab-active' : ''; ?>" data-tab="pages">
            <span class="dashicons dashicons-admin-page" style="padding-top: 2px;"></span> <?php _e('Pages', 'bulk-post-update-date'); ?>
        </a>
        <?php endif; ?>
    
        <?php
        // Get all public custom post types
        if ($post_types) {
            foreach ($post_types as $post_type) {
                // Skip if not enabled
                if(!isset($enabled_tabs[$post_type->name]) || !$enabled_tabs[$post_type->name]) {
                    continue;
                }
                
                $menu_icon = '';
                
                if (isset($post_type->menu_icon) && !empty($post_type->menu_icon)) {
                    if (strpos($post_type->menu_icon, 'dashicon') !== false) { 
                        $menu_icon = sprintf(
                            '<span class="dashicons %s" style="padding-top: 2px;"></span>', 
                            esc_attr($post_type->menu_icon)
                        );
                    } else {
                        $menu_icon = sprintf(
                            '<img src="%s" style="vertical-align: middle;margin-right: 3px;margin-top: -2px;width: 16px;height: 16px;">', 
                            esc_url($post_type->menu_icon)
                        );
                    }
                } else {
                    $menu_icon = '<span class="dashicons dashicons-admin-generic" style="padding-top: 2px;"></span>';
                }
                
                printf(
                    '<a href="?page=bulk-post-update-date&tab=%s" class="nav-tab %s" data-tab="%s">%s %s</a>',
                    esc_attr($post_type->name),
                    $type == $post_type->name ? 'nav-tab-active' : '',
                    esc_attr($post_type->name),
                    $menu_icon,
                    esc_html($post_type->label)
                );
            }
        }
        ?>
        
        <?php if(isset($enabled_tabs['comments']) && $enabled_tabs['comments']): ?>
        <a href="?page=bulk-post-update-date&tab=comments" class="nav-tab <?php echo $tab == 'comments' ? 'nav-tab-active' : ''; ?>" data-tab="comments">
            <span class="dashicons dashicons-admin-comments" style="padding-top: 2px;"></span> <?php _e('Post Comments', 'bulk-post-update-date'); ?>
        </a>
        <?php endif; ?>
    </h2>

    <div class="settings-container <?php echo $tab == 'settings' ? '' : 'hidden'; ?>" id="settings-tab-content">
        <h3><?php _e('Tab Settings', 'bulk-post-update-date'); ?></h3>
        <p><?php _e('Select which tabs you want to display in the navigation menu. Changes take effect immediately.', 'bulk-post-update-date'); ?></p>
        
        <div id="settings-response" class="hidden"></div>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Built-in Content Types', 'bulk-post-update-date'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="tab_posts" id="tab_posts" value="1" <?php checked(isset($enabled_tabs['posts']) && $enabled_tabs['posts']); ?>>
                        <?php _e('Posts', 'bulk-post-update-date'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="tab_pages" id="tab_pages" value="1" <?php checked(isset($enabled_tabs['pages']) && $enabled_tabs['pages']); ?>>
                        <?php _e('Pages', 'bulk-post-update-date'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="tab_comments" id="tab_comments" value="1" <?php checked(isset($enabled_tabs['comments']) && $enabled_tabs['comments']); ?>>
                        <?php _e('Comments', 'bulk-post-update-date'); ?>
                    </label>
                </td>
            </tr>
            <?php if (!empty($post_types)): ?>
            <tr>
                <th scope="row"><?php _e('Custom Post Types', 'bulk-post-update-date'); ?></th>
                <td>
                    <?php foreach ($post_types as $post_type): ?>
                    <label>
                        <input type="checkbox" name="tab_<?php echo esc_attr($post_type->name); ?>" 
                               id="tab_<?php echo esc_attr($post_type->name); ?>" 
                               value="1" 
                               <?php checked(isset($enabled_tabs[$post_type->name]) && $enabled_tabs[$post_type->name]); ?>>
                        <?php echo esc_html($post_type->label); ?>
                    </label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <div id="main-tab-content" class="<?php echo $tab == 'settings' ? 'hidden' : ''; ?>">
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
                    <th scope="row"><label for="range"><?php _e('Custom Date Range', 'bulk-post-update-date'); ?></label></th>
                    <td>
                        <input type="text" id="range" name="range" value="<?php echo date('m/d/y', strtotime('-3 days', $now)); ?> - <?php echo date('m/d/y', $now); ?>" />
                        <p class="description">
                            <?php _e('Select range of date in which you want to spread the dates', 'bulk-post-update-date'); ?>
                        </p>
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

<script>
    jQuery(document).ready(function($){
        // Initialize date range picker
        $('input[name="range"]').daterangepicker({
            maxDate: '<?php echo date('m/d/y'); ?>',
            locale: {
                format: 'MM/DD/YY'
            }
        });

        // Show/hide custom range input based on selection
        $('#distribute').on('change', function(){
            let val = $(this).val();
            if(val == 0) {
                $('#range_row').fadeIn();
            } else {
                $('#range_row').fadeOut();
            }
        });
        
        // Tab settings checkbox handling via AJAX
        $('input[type="checkbox"][id^="tab_"]').on('change', function() {
            var tabId = $(this).attr('id').replace('tab_', '');
            var isChecked = $(this).is(':checked');
            
            // Show loading feedback
            $('#settings-response')
                .removeClass('hidden notice-success notice-error')
                .addClass('notice')
                .html('<p><span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> <?php _e('Updating settings...', 'bulk-post-update-date'); ?></p>')
                .show();
            
            // Send AJAX request to update tab visibility
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bulk_date_update_toggle_tab',
                    tab: tabId,
                    enabled: isChecked ? 1 : 0,
                    nonce: '<?php echo wp_create_nonce('bulk_date_update_toggle_tab'); ?>'
                },
                success: function(response) {
                    if(response.success) {
                        $('#settings-response')
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>');
                            
                        // Update tab visibility in real-time
                        if(isChecked) {
                            if($('#bulk-date-tabs a[data-tab="' + tabId + '"]').length === 0) {
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
                            .html('<p>' + response.data.message + '</p>');
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
                        .html('<p><?php _e('Error updating settings. Please try again.', 'bulk-post-update-date'); ?></p>');
                }
            });
        });
    });
</script> 