<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Handle comments date update
 * 
 * Updates the dates of all approved comments to random dates within 
 * the specified time range, never making them earlier than their post date.
 * 
 * @since 1.0
 * @return int Number of updated comments
 */
function handleComments(): int {
	global $wpdb;
	
	// Get all approved comments on published posts
	$comments = $wpdb->get_results( 
		"SELECT c.comment_ID, c.comment_post_ID, c.comment_date, p.post_date 
		FROM $wpdb->comments c 
		JOIN $wpdb->posts p ON c.comment_post_ID = p.ID 
		WHERE c.comment_approved = 1 AND p.post_status = 'publish'"
	);

	if (!$comments) {
		return 0;
	}

	[$from, $to] = getFromAndToDates();
	$total = 0;
	
	// Get custom time range settings if enabled
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
	
	// Process in smaller batches for better performance
	$batch_size = 50;
	$total_comments = count($comments);
	$batches = array_chunk($comments, $batch_size);
	
	foreach ($batches as $batch) {
		foreach ($batch as $comment) {
			// Never set comment date earlier than post date
			$post_date = strtotime($comment->post_date);
			$_from = max($from, $post_date);
			
			$_to = $to;
			if ($to < $post_date) {
				$_to = $post_date + 60; // Add 1 minute if post is newer than max date
			}

			// Generate random time between adjusted dates
			$time_timestamp = rand($_from, $_to);
			
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
			
			// Update the comment date
			$wpdb->update(
				$wpdb->comments,
				[
					'comment_date' => $time,
					'comment_date_gmt' => $time_gmt
				],
				['comment_ID' => $comment->comment_ID],
				['%s', '%s'],
				['%d']
			);
			
			$total++;
		}
		
		// Free up memory after each batch
		wp_cache_flush();
	}

	return $total;
}

/**
 * Get the from and to dates from the form submission
 * 
 * Parses the form data to determine the date range for updates.
 * 
 * @since 1.0
 * @return array Array with from and to timestamp
 */
function getFromAndToDates(): array {
	$from = isset($_POST['distribute']) ? intval($_POST['distribute']) : 0;
	$to   = current_time('timestamp', 0);
	$now  = current_time('timestamp', 0);

	if ($from === 0 && isset($_POST['range'])) {
		$range = explode('-', sanitize_text_field($_POST['range']));
		if (count($range) === 2) {
			$from = strtotime(trim($range[0]), $now);
			$to   = strtotime(trim($range[1]), $now);
			
			// Ensure we have valid timestamps
			if (!$from || !$to) {
				$from = strtotime('-3 hours', $now);
				$to = $now;
			}
		} else {
			$from = strtotime('-3 hours', $now);
		}
	}

	// Ensure from is never after to
	if ($from > $to) {
		$temp = $from;
		$from = $to;
		$to = $temp;
	}

	return [$from, $to];
}