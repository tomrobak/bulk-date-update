<?php
/**
 * History tab content for Bulk Date Update
 * 
 * Displays history of date updates with filtering and restore options
 * This template is included within the main settings-page.php
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
$sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'modified_at';
$sort_order = isset($_GET['sort_order']) ? sanitize_text_field($_GET['sort_order']) : 'DESC';

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
$query .= " ORDER BY $sort_by $sort_order";

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
        
        // Delete the history record after successful restore
        $wpdb->delete(
            $table_name,
            ['id' => $record_id],
            ['%d']
        );
        
        echo '<div class="notice notice-success"><p>' . sprintf(
            esc_html__('Date for "%s" has been restored to %s and record removed from history.', 'bulk-post-update-date'),
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
?>

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
                
                <div class="filter-field">
                    <label for="sort_by" class="form-label"><?php esc_html_e('Sort By', 'bulk-post-update-date'); ?></label>
                    <select name="sort_by" id="sort_by" class="form-select">
                        <option value="modified_at" <?php selected($sort_by, 'modified_at'); ?>><?php esc_html_e('Date & Time (when changed)', 'bulk-post-update-date'); ?></option>
                        <option value="previous_date" <?php selected($sort_by, 'previous_date'); ?>><?php esc_html_e('Previous Date', 'bulk-post-update-date'); ?></option>
                        <option value="new_date" <?php selected($sort_by, 'new_date'); ?>><?php esc_html_e('New Date', 'bulk-post-update-date'); ?></option>
                    </select>
                </div>
                
                <div class="filter-field">
                    <label for="sort_order" class="form-label"><?php esc_html_e('Sort Order', 'bulk-post-update-date'); ?></label>
                    <select name="sort_order" id="sort_order" class="form-select">
                        <option value="DESC" <?php selected($sort_order, 'DESC'); ?>><?php esc_html_e('Newest First', 'bulk-post-update-date'); ?></option>
                        <option value="ASC" <?php selected($sort_order, 'ASC'); ?>><?php esc_html_e('Oldest First', 'bulk-post-update-date'); ?></option>
                    </select>
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

<!-- History Records -->
<div class="history-records-container" id="history-records-container">
    <?php if (empty($history_records)): ?>
        <div class="empty-state">
            <p class="text-center"><?php esc_html_e('No history records found.', 'bulk-post-update-date'); ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($history_records as $record): 
            // Get post type name instead of slug
            $post_type_obj = get_post_type_object($record->post_type);
            $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst($record->post_type);
            $date_field_label = $record->date_field === 'post_date' 
                ? esc_html__('Published Date', 'bulk-post-update-date') 
                : esc_html__('Modified Date', 'bulk-post-update-date');
            $record_date = get_date_from_gmt($record->modified_at, get_option('date_format') . ' ' . get_option('time_format'));
            $previous_date = get_date_from_gmt($record->previous_date, get_option('date_format') . ' ' . get_option('time_format'));
            $new_date = get_date_from_gmt($record->new_date, get_option('date_format') . ' ' . get_option('time_format'));
        ?>
            <div class="history-record-card" data-record-id="<?php echo esc_attr($record->id); ?>">
                <div class="history-record-header">
                    <div class="history-record-title">
                        <a href="<?php echo esc_url(get_edit_post_link($record->post_id)); ?>" title="<?php esc_attr_e('Edit Post', 'bulk-post-update-date'); ?>">
                            <?php echo esc_html($record->post_title); ?>
                        </a>
                        <a href="<?php echo esc_url(get_permalink($record->post_id)); ?>" class="view-link" title="<?php esc_attr_e('View Post', 'bulk-post-update-date'); ?>" target="_blank">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                    </div>
                    <div class="history-record-actions">
                        <a href="<?php echo esc_url(wp_nonce_url(
                            add_query_arg('restore', $record->id),
                            'bulk_date_update_restore_' . $record->id
                        )); ?>" class="btn btn-success btn-sm restore-button" data-record-id="<?php echo esc_attr($record->id); ?>" title="<?php esc_attr_e('Restore Previous Date', 'bulk-post-update-date'); ?>">
                            <?php esc_html_e('Restore', 'bulk-post-update-date'); ?>
                        </a>
                    </div>
                </div>
                <div class="history-record-body">
                    <div class="history-record-field">
                        <div class="history-record-label"><?php esc_html_e('Date & Time', 'bulk-post-update-date'); ?></div>
                        <div class="history-record-value"><?php echo esc_html($record_date); ?></div>
                    </div>
                    <div class="history-record-field">
                        <div class="history-record-label"><?php esc_html_e('Post Type', 'bulk-post-update-date'); ?></div>
                        <div class="history-record-value"><?php echo esc_html($post_type_name); ?></div>
                    </div>
                    <div class="history-record-field">
                        <div class="history-record-label"><?php esc_html_e('Date Field', 'bulk-post-update-date'); ?></div>
                        <div class="history-record-value"><?php echo esc_html($date_field_label); ?></div>
                    </div>
                    <div class="history-record-field">
                        <div class="history-record-label"><?php esc_html_e('Previous Date', 'bulk-post-update-date'); ?></div>
                        <div class="history-record-value"><?php echo esc_html($previous_date); ?></div>
                    </div>
                    <div class="history-record-field">
                        <div class="history-record-label"><?php esc_html_e('New Date', 'bulk-post-update-date'); ?></div>
                        <div class="history-record-value"><?php echo esc_html($new_date); ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
     data-date-to="<?php echo esc_attr($filter_date_to); ?>"
     data-sort-by="<?php echo esc_attr($sort_by); ?>"
     data-sort-order="<?php echo esc_attr($sort_order); ?>">
</div>

<style>
/* Bootstrap-like styling with responsive flex improvements */
.history-filters.card {
    width: 100%;
    max-width: 100%;
}
.filter-form {
    width: 100%;
    max-width: 100%;
}
.filter-row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -12px;
    margin-left: -12px;
    gap: 24px;
    width: 100%;
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

/* Table improvements */
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
    max-width: 100%;
}
.table-responsive {
    display: block;
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.table {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
    border-collapse: collapse;
    table-layout: auto;
}
.table th, .table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
    white-space: nowrap;
}
.post-title-cell {
    white-space: normal;
    word-break: normal;
    word-wrap: break-word;
    -webkit-hyphens: none;
    -ms-hyphens: none;
    hyphens: none;
}
.post-title-link {
    display: block;
    width: 100%;
    margin-bottom: 5px;
    word-break: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 1;
    text-align: left;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.05);
}
.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.075);
}

/* Utility classes */
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
.p-0 {
    padding: 0 !important;
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
.text-center {
    text-align: center !important;
}
.mb-0 {
    margin-bottom: 0 !important;
}

/* Buttons & Links */
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

/* Loading spinner */
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

@media (max-width: 1024px) {
    .table th, .table td {
        padding: 0.5rem;
        font-size: 0.9rem;
    }
    .post-title-cell {
        max-width: 200px;
    }
    .btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.8rem;
    }
    .history-record-body {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
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
    
    /* Table responsiveness for tablets */
    .table-responsive {
        overflow-x: auto;
    }
    .post-title-cell {
        max-width: 150px;
    }
}

@media (max-width: 576px) {
    /* Enhanced mobile styles */
    #main-tab-content {
        padding: 0;
    }
    .card {
        border-radius: 0;
        border-left: none;
        border-right: none;
    }
    .history-filters.card {
        margin-left: -10px;
        margin-right: -10px;
        width: calc(100% + 20px);
    }
    .post-title-cell {
        min-width: 120px;
        max-width: 140px;
    }
    .table th, .table td {
        padding: 0.4rem;
        font-size: 0.8rem;
    }
    .history-record-body {
        grid-template-columns: 1fr 1fr;
    }
    
    .history-record-label {
        font-size: 0.8em;
    }
    
    .history-record-value {
        font-size: 0.9em;
    }
}

/* Card-based records styling */
.history-records-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    width: 100%;
}

.history-record-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    padding: 15px;
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.08);
}

.history-record-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.history-record-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
    flex-wrap: wrap;
    gap: 10px;
}

.history-record-title {
    font-weight: 600;
    font-size: 1.1em;
    color: #333;
    word-break: normal;
    word-wrap: break-word;
    flex: 1;
    min-width: 200px;
}

.history-record-title a {
    display: inline-block;
    max-width: 100%;
    text-decoration: none;
}

.history-record-title .view-link {
    margin-left: 8px;
    vertical-align: middle;
}

.history-record-actions {
    display: flex;
    gap: 8px;
}

.history-record-body {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.history-record-field {
    display: flex;
    flex-direction: column;
}

.history-record-label {
    font-size: 0.85em;
    color: #666;
    margin-bottom: 5px;
    font-weight: 500;
}

.history-record-value {
    font-size: 0.95em;
    color: #333;
}
</style> 