<?php
/**
 * History tab template for Bulk Date Update
 * 
 * Displays history of date updates with filtering and restore options
 * 
 * @since 1.5.0
 */

defined('ABSPATH') || exit;

// Get pagination parameters
$page = isset($_GET['history_page']) ? max(1, intval($_GET['history_page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$filter_post_type = isset($_GET['filter_post_type']) ? sanitize_text_field($_GET['filter_post_type']) : '';
$filter_date_field = isset($_GET['filter_date_field']) ? sanitize_text_field($_GET['filter_date_field']) : '';
$filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
$filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';

global $wpdb;
$table_name = $wpdb->prefix . BULK_DATE_HISTORY_TABLE;

// Build query
$query = "SELECT * FROM $table_name WHERE 1=1";
$count_query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
$query_args = [];

// Apply filters
if (!empty($filter_post_type)) {
    $query .= " AND post_type = %s";
    $count_query .= " AND post_type = %s";
    $query_args[] = $filter_post_type;
}

if (!empty($filter_date_field)) {
    $query .= " AND date_field = %s";
    $count_query .= " AND date_field = %s";
    $query_args[] = $filter_date_field;
}

if (!empty($filter_date_from)) {
    $query .= " AND modified_at >= %s";
    $count_query .= " AND modified_at >= %s";
    $query_args[] = date('Y-m-d 00:00:00', strtotime($filter_date_from));
}

if (!empty($filter_date_to)) {
    $query .= " AND modified_at <= %s";
    $count_query .= " AND modified_at <= %s";
    $query_args[] = date('Y-m-d 23:59:59', strtotime($filter_date_to));
}

// Add order
$query .= " ORDER BY modified_at DESC";

// Get total count for pagination
$total_records = $wpdb->get_var(
    empty($query_args) ? $count_query : $wpdb->prepare($count_query, $query_args)
);

// Add limit for current page
$query .= " LIMIT %d OFFSET %d";
$final_args = $query_args;
$final_args[] = $per_page;
$final_args[] = $offset;

// Prepare and execute query
$history_records = $wpdb->get_results(
    empty($final_args) ? $query : $wpdb->prepare($query, $final_args)
);

$total_pages = ceil($total_records / $per_page);

// Get post types for filter - use proper post type objects for names
$post_type_slugs = $wpdb->get_col("SELECT DISTINCT post_type FROM $table_name ORDER BY post_type");
$post_types_for_filter = [];
foreach ($post_type_slugs as $slug) {
    $post_type_obj = get_post_type_object($slug);
    $post_types_for_filter[$slug] = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst($slug);
}

// Handle history record deletion
if (isset($_POST['clear_history']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'bulk_date_update_clear_history')) {
    $wpdb->query("TRUNCATE TABLE $table_name");
    echo '<div class="notice notice-success"><p>' . esc_html__('History has been cleared successfully.', 'bulk-post-update-date') . '</p></div>';
    $history_records = [];
    $total_records = 0;
    $total_pages = 0;
}

// Handle restore action
if (isset($_GET['restore']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'bulk_date_update_restore_' . $_GET['restore'])) {
    $record_id = intval($_GET['restore']);
    $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $record_id));
    
    if ($record) {
        $gmt_field = "{$record->date_field}_gmt";
        $previous_date_gmt = get_gmt_from_date($record->previous_date);
        
        $wpdb->update(
            $wpdb->posts,
            [
                $record->date_field => $record->previous_date,
                $gmt_field => $previous_date_gmt
            ],
            ['ID' => $record->post_id],
            ['%s', '%s'],
            ['%d']
        );
        
        echo '<div class="notice notice-success"><p>' . sprintf(
            esc_html__('Date for "%s" has been restored to %s.', 'bulk-post-update-date'),
            esc_html($record->post_title),
            esc_html(get_date_from_gmt($record->previous_date, get_option('date_format') . ' ' . get_option('time_format')))
        ) . '</p></div>';
        
        // Clean post cache
        clean_post_cache($record->post_id);
        
        // Redirect to remove the restore parameter from URL
        echo '<script>window.history.replaceState({}, document.title, "' . esc_url(remove_query_arg(['restore', '_wpnonce'])) . '");</script>';
    }
}

// Get history settings
$history_enabled = get_option('bulk_date_update_history_enabled', true);
$retention_days = get_option('bulk_date_update_history_retention', BULK_DATE_HISTORY_DEFAULT_RETENTION);

// Generate a nonce for AJAX pagination
$infinite_scroll_nonce = wp_create_nonce('bulk_date_update_infinite_scroll');

// Get the current tab from the URL for proper navigation
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'history';
?>

<div class="history-container">
    <h2><?php esc_html_e('Date Update History', 'bulk-post-update-date'); ?></h2>
    
    <?php if (!$history_enabled): ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e('History tracking is currently disabled. Enable it in the Settings tab to start recording date changes.', 'bulk-post-update-date'); ?></p>
        </div>
    <?php else: ?>
        <div class="history-info">
            <p>
                <?php printf(
                    esc_html__('History records are being kept for %d days. You can change this in the Settings tab.', 'bulk-post-update-date'),
                    $retention_days
                ); ?>
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="history-filters card mb-4">
        <div class="card-header">
            <h3 class="m-0"><?php esc_html_e('Filter History', 'bulk-post-update-date'); ?></h3>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="filter-form">
                <input type="hidden" name="page" value="bulk-post-update-date">
                <input type="hidden" name="tab" value="history">
                
                <div class="filter-row">
                    <div class="filter-field">
                        <label for="filter_post_type" class="form-label"><?php esc_html_e('Post Type', 'bulk-post-update-date'); ?></label>
                        <select name="filter_post_type" id="filter_post_type" class="form-select">
                            <option value=""><?php esc_html_e('All Post Types', 'bulk-post-update-date'); ?></option>
                            <?php foreach ($post_types_for_filter as $slug => $name): ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($filter_post_type, $slug); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-field">
                        <label for="filter_date_field" class="form-label"><?php esc_html_e('Date Field', 'bulk-post-update-date'); ?></label>
                        <select name="filter_date_field" id="filter_date_field" class="form-select">
                            <option value=""><?php esc_html_e('All Fields', 'bulk-post-update-date'); ?></option>
                            <option value="post_date" <?php selected($filter_date_field, 'post_date'); ?>><?php esc_html_e('Published Date', 'bulk-post-update-date'); ?></option>
                            <option value="post_modified" <?php selected($filter_date_field, 'post_modified'); ?>><?php esc_html_e('Modified Date', 'bulk-post-update-date'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-field">
                        <label for="filter_date_from" class="form-label"><?php esc_html_e('From Date', 'bulk-post-update-date'); ?></label>
                        <input type="date" name="filter_date_from" id="filter_date_from" class="form-control" value="<?php echo esc_attr($filter_date_from); ?>">
                    </div>
                    
                    <div class="filter-field">
                        <label for="filter_date_to" class="form-label"><?php esc_html_e('To Date', 'bulk-post-update-date'); ?></label>
                        <input type="date" name="filter_date_to" id="filter_date_to" class="form-control" value="<?php echo esc_attr($filter_date_to); ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Apply Filters', 'bulk-post-update-date'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bulk-post-update-date&tab=history')); ?>" class="button"><?php esc_html_e('Reset', 'bulk-post-update-date'); ?></a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Clear History Form -->
    <form method="post" action="" class="mb-4">
        <?php wp_nonce_field('bulk_date_update_clear_history'); ?>
        <button type="submit" name="clear_history" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all history records? This action cannot be undone.', 'bulk-post-update-date'); ?>');">
            <?php esc_html_e('Clear All History', 'bulk-post-update-date'); ?>
        </button>
    </form>
    
    <!-- History Records Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover history-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php esc_html_e('Date & Time', 'bulk-post-update-date'); ?></th>
                            <th><?php esc_html_e('Post', 'bulk-post-update-date'); ?></th>
                            <th><?php esc_html_e('Post Type', 'bulk-post-update-date'); ?></th>
                            <th><?php esc_html_e('Date Field', 'bulk-post-update-date'); ?></th>
                            <th><?php esc_html_e('Previous Date', 'bulk-post-update-date'); ?></th>
                            <th><?php esc_html_e('New Date', 'bulk-post-update-date'); ?></th>
                            <th><?php esc_html_e('Actions', 'bulk-post-update-date'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="history-records-container">
                        <?php if (empty($history_records)): ?>
                            <tr>
                                <td colspan="7" class="text-center"><?php esc_html_e('No history records found.', 'bulk-post-update-date'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history_records as $record): 
                                // Get post type name instead of slug
                                $post_type_obj = get_post_type_object($record->post_type);
                                $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst($record->post_type);
                            ?>
                                <tr>
                                    <td><?php echo esc_html(get_date_from_gmt($record->modified_at, get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                                    <td class="post-title-cell">
                                        <a href="<?php echo esc_url(get_edit_post_link($record->post_id)); ?>" title="<?php esc_attr_e('Edit Post', 'bulk-post-update-date'); ?>">
                                            <?php echo esc_html($record->post_title); ?>
                                        </a>
                                        <a href="<?php echo esc_url(get_permalink($record->post_id)); ?>" class="view-link" title="<?php esc_attr_e('View Post', 'bulk-post-update-date'); ?>" target="_blank">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($post_type_name); ?></td>
                                    <td>
                                        <?php echo $record->date_field === 'post_date' 
                                            ? esc_html__('Published Date', 'bulk-post-update-date') 
                                            : esc_html__('Modified Date', 'bulk-post-update-date'); ?>
                                    </td>
                                    <td><?php echo esc_html(get_date_from_gmt($record->previous_date, get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                                    <td><?php echo esc_html(get_date_from_gmt($record->new_date, get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(wp_nonce_url(
                                            add_query_arg('restore', $record->id),
                                            'bulk_date_update_restore_' . $record->id
                                        )); ?>" class="btn btn-success btn-sm restore-button" title="<?php esc_attr_e('Restore Previous Date', 'bulk-post-update-date'); ?>">
                                            <?php esc_html_e('Restore', 'bulk-post-update-date'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Loading indicator for infinite scroll -->
    <div id="history-loading" class="text-center mt-3 mb-3" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden"><?php esc_html_e('Loading...', 'bulk-post-update-date'); ?></span>
        </div>
    </div>
    
    <!-- No more records message -->
    <div id="no-more-records" class="text-center mt-3 mb-3" style="display: none;">
        <p><?php esc_html_e('No more records to load.', 'bulk-post-update-date'); ?></p>
    </div>
    
    <!-- Hidden pagination data for infinite scroll -->
    <div id="pagination-data" 
         data-total-pages="<?php echo esc_attr($total_pages); ?>" 
         data-current-page="<?php echo esc_attr($page); ?>"
         data-nonce="<?php echo esc_attr($infinite_scroll_nonce); ?>"
         data-post-type="<?php echo esc_attr($filter_post_type); ?>"
         data-date-field="<?php echo esc_attr($filter_date_field); ?>"
         data-date-from="<?php echo esc_attr($filter_date_from); ?>"
         data-date-to="<?php echo esc_attr($filter_date_to); ?>">
    </div>
</div>

<style>
    /* Bootstrap-like styling with responsive flex improvements */
    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 1px solid rgba(0,0,0,.125);
        border-radius: 0.25rem;
        width: 100%;
    }
    .card-header {
        padding: 0.75rem 1.25rem;
        margin-bottom: 0;
        background-color: rgba(0,0,0,.03);
        border-bottom: 1px solid rgba(0,0,0,.125);
    }
    .card-body {
        flex: 1 1 auto;
        padding: 1.25rem;
    }
    .mb-4 {
        margin-bottom: 1.5rem !important;
    }
    .m-0 {
        margin: 0 !important;
    }
    .mt-3 {
        margin-top: 1rem !important;
    }
    .mb-3 {
        margin-bottom: 1rem !important;
    }
    
    /* Improved filter form with flex layout */
    .filter-form {
        width: 100%;
    }
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -12px;
        margin-left: -12px;
        gap: 24px;
    }
    .filter-field {
        flex: 1;
        min-width: 200px;
        padding-right: 12px;
        padding-left: 12px;
    }
    .filter-actions {
        margin-top: 24px;
        display: flex;
        gap: 12px;
    }
    .form-label {
        margin-bottom: 0.5rem;
        display: block;
        font-weight: 500;
    }
    .form-control, .form-select {
        display: block;
        width: 100%;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        height: 38px;
    }
    .form-select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    
    /* Improved table with proper cell sizing */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        border-collapse: collapse;
        table-layout: auto;
    }
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0,0,0,.05);
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,.075);
    }
    .table th, .table td {
        padding: 0.75rem;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
        white-space: nowrap;
    }
    .post-title-cell {
        white-space: normal;
        min-width: 200px;
        max-width: 300px;
    }
    .table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .table-light {
        background-color: #f8f9fa;
    }
    .mb-0 {
        margin-bottom: 0 !important;
    }
    .text-center {
        text-align: center !important;
    }
    .p-0 {
        padding: 0 !important;
    }
    
    /* Improved links and buttons */
    .view-link {
        margin-left: 0.5rem;
        text-decoration: none;
    }
    .view-link .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
        vertical-align: text-bottom;
    }
    
    /* Bootstrap button styles */
    .btn {
        display: inline-block;
        font-weight: 400;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        user-select: none;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }
    .btn-success {
        color: #fff;
        background-color: #28a745;
        border-color: #28a745;
    }
    .btn-success:hover {
        color: #fff;
        background-color: #218838;
        border-color: #1e7e34;
    }
    
    /* Spinner for loading indicator */
    .spinner-border {
        display: inline-block;
        width: 2rem;
        height: 2rem;
        vertical-align: -0.125em;
        border: 0.25em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border .75s linear infinite;
    }
    @keyframes spinner-border {
        to { transform: rotate(360deg); }
    }
    .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
    
    @media (max-width: 768px) {
        .filter-field {
            flex: 0 0 100%;
            margin-bottom: 1rem;
        }
        .history-table {
            font-size: 0.875rem;
        }
        .filter-row {
            gap: 0;
        }
    }
</style> 