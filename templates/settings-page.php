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
        <a class="share-btn rate-btn" href="https://wordpress.org/support/plugin/bulk-post-update-date/reviews/?filter=5#new-post" target="_blank" title="<?php _e('Please rate 5 stars if you like Bulk Date Update', 'bulk-post-update-date'); ?>">
            <span class="dashicons dashicons-star-filled"></span> <?php _e('Rate 5 stars', 'bulk-post-update-date'); ?>
        </a>
        <a class="share-btn twitter" href="https://twitter.com/intent/tweet?text=Checkout%20Bulk%20Date%20Update,%20a%20%23WordPress%20plugin%20that%20updates%20last%20modified%20date%20and%20time%20on%20pages%20and%20posts%20very%20easily.&amp;tw_p=tweetbutton&amp;url=https://wplove.co&amp;via=wplove" target="_blank">
            <span class="dashicons dashicons-twitter"></span> <?php _e('Tweet about Bulk Date Update', 'bulk-post-update-date'); ?>
        </a>
        <a class="share-btn" href="https://wplove.co/plugins" target="_blank" style="background-color: #4caf50;">
            <span class="dashicons dashicons-admin-plugins"></span> <?php _e('More plugins', 'bulk-post-update-date'); ?>
        </a>
    </div>

    <h2 class="nav-tab-wrapper">
        <a href="?page=bulk-post-update-date&tab=posts" class="nav-tab <?php echo $tab == 'posts' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-post" style="padding-top: 2px;"></span> <?php _e('Posts', 'bulk-post-update-date'); ?>
        </a>
        <a href="?page=bulk-post-update-date&tab=pages" class="nav-tab <?php echo $tab == 'pages' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-page" style="padding-top: 2px;"></span> <?php _e('Pages', 'bulk-post-update-date'); ?>
        </a>
    
        <?php
        // Get all public custom post types
        $args = [
            'public'   => true,
            '_builtin' => false
        ];
        
        $post_types = get_post_types($args, 'objects', 'and');
        
        if ($post_types) {
            foreach ($post_types as $post_type) {
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
                    '<a href="?page=bulk-post-update-date&tab=%s" class="nav-tab %s">%s %s</a>',
                    esc_attr($post_type->name),
                    $type == $post_type->name ? 'nav-tab-active' : '',
                    $menu_icon,
                    esc_html($post_type->label)
                );
            }
        }
        ?>
        <a href="?page=bulk-post-update-date&tab=comments" class="nav-tab <?php echo $tab == 'comments' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-comments" style="padding-top: 2px;"></span> <?php _e('Post Comments', 'bulk-post-update-date'); ?>
        </a>
    </h2>

    <form method="post" action="">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="distribute"><?php _e('Distribute into Last', 'bulk-post-update-date'); ?></label></th>
                <td>
                    <select type="text" id="distribute" name="distribute">
                        <option value="<?php echo strtotime('-1 hour', $now); ?>"><?php _e('1 hour', 'bulk-post-update-date'); ?></option>
                        <option value="<?php echo strtotime('-1 day', $now); ?>"><?php _e('1 Day', 'bulk-post-update-date'); ?></option>
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
            if (file_exists(dirname(__DIR__) . "/{$tab}.php")) {
                include_once dirname(__DIR__) . "/{$tab}.php";
            }
            ?>
            
            <?php if ($tab !== 'comments') : ?>
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

<div class="coffee-box">
    <div class="coffee-amt-wrap">
        <a class="button button-primary join-community-btn" style="margin-left: 2px;" href="https://wplove.co/community" target="_blank"><?php _e('Join wplove community', 'bulk-post-update-date'); ?></a>
    </div>
    <span class="coffee-heading"><?php _e('Join wplove community', 'bulk-post-update-date'); ?></span>
    <p style="text-align: justify;"><?php _e('If wplove.co plugin helped you, imagine what it can do for your friends. Spread the word! 🔥 Tell your friends to join wplove.co', 'bulk-post-update-date'); ?></p>
    <p style="text-align: justify;"><?php _e('Get access to exclusive WordPress resources, tutorials, and premium plugins at', 'bulk-post-update-date'); ?> <a href="https://wplove.co" target="_blank">wplove.co</a>.</p>
    <p style="text-align: justify; font-size: 12px; font-style: italic;"><?php _e('Originally developed by Atiq Samtia. Now maintained by', 'bulk-post-update-date'); ?> <a href="https://wplove.co" target="_blank" style="font-weight: 500;">wplove.co</a> | <a href="https://tomrobak.com" target="_blank" style="font-weight: 500;">Tom Robak</a> | <a href="https://wordpress.org/support/plugin/bulk-post-update-date/reviews/?rate=5#new-post" target="_blank" style="font-weight: 500;"><?php _e('Rate it', 'bulk-post-update-date'); ?></a> (<span style="color:#ffa000;">★★★★★</span>) <?php _e('on WordPress.org, if you like this plugin.', 'bulk-post-update-date'); ?></p>
</div>

<div class="wplove-resources">
    <h3><?php _e('More from wplove.co', 'bulk-post-update-date'); ?></h3>
    <div class="resource-cards">
        <div class="resource-card">
            <h4><span class="dashicons dashicons-book"></span> <?php _e('WordPress Tutorials', 'bulk-post-update-date'); ?></h4>
            <p><?php _e('Learn best practices, tips, and tricks for WordPress from our expert tutorials.', 'bulk-post-update-date'); ?></p>
            <a href="https://wplove.co/tutorials" target="_blank" class="button"><?php _e('Read Tutorials', 'bulk-post-update-date'); ?></a>
        </div>
        <div class="resource-card">
            <h4><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Premium Plugins', 'bulk-post-update-date'); ?></h4>
            <p><?php _e('Explore our collection of powerful, well-coded WordPress plugins to enhance your site.', 'bulk-post-update-date'); ?></p>
            <a href="https://wplove.co/plugins" target="_blank" class="button"><?php _e('View Plugins', 'bulk-post-update-date'); ?></a>
        </div>
        <div class="resource-card">
            <h4><span class="dashicons dashicons-groups"></span> <?php _e('WordPress Support', 'bulk-post-update-date'); ?></h4>
            <p><?php _e('Need help with WordPress? Join our community for expert support and advice.', 'bulk-post-update-date'); ?></p>
            <a href="https://wplove.co/support" target="_blank" class="button"><?php _e('Get Support', 'bulk-post-update-date'); ?></a>
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
    });
</script> 