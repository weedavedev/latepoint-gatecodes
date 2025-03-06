<?php
/**
 * Plugin Name: LatePoint Addon - Gatecodes
 * Description: LatePoint Addon that adds a gate code to booking summary, and confirmation's.
 * Version: 1.0.0
 * Author: Wallace Development
 * Plugin URI: https://wallacedevelopment.co.uk
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates a gate code in the format of #[agentID][agentID][weeknum padded to 2 numbers]
 *
 * @param int $field The agent ID to use in the gate code
 * @param DateTime $date The date to extract the week number from
 * @return string The formatted gate code or "#ERR" if invalid parameters are provided
 */
function generate_gate_code($field, $date)
{
    if (!$date instanceof DateTime || !is_int($field)) {
        return "#ERR";
    }
    return "#" . $field . $field . sprintf("%02d", $date->format("W"));
}

/**
 * Main handler function that determines what to do based on the confirmation object type
 *
 * @param mixed $confirmation Either an OsOrderModel or booking object
 * @return void
 */
function show_gate_code($confirmation) {
    // Check if it's an order with multiple bookings
    if ($confirmation instanceof OsOrderModel) {
        $bookings = $confirmation->get_bookings_from_order_items();

        if (count($bookings) === 1) {
            // Single booking in the order, use it
            display_single_booking_gate_code($bookings[0]);
        } else if (count($bookings) > 1) {
            // Multiple bookings, show info message
            echo '<div style="margin-top: 15px; padding: 15px; background: #f7f9fc; border-radius: 4px; text-align: center;">';
            echo '<div style="color: #666; font-size: 12px; margin-bottom: 5px;">Multiple bookings found <br> Check individual bookings for separate gate codes</div>';
            echo '</div>';
        }
    } else {
        // It's a single booking object
        display_single_booking_gate_code($confirmation);
    }
}

/**
 * Displays gate code for a single booking if it's approved
 *
 * @param object $booking The booking object
 * @return void
 */
function display_single_booking_gate_code($booking) {
    // Debug log
    error_log('Processing booking ID: ' . ($booking->id ?? 'unknown') . ', status: ' . ($booking->status ?? 'unknown'));

    // Check if booking exists and is approved
    if ($booking && strtolower($booking->status) === 'approved') { // Note: fixed spelling from 'aprooved' to 'approved'
        try {
            $booking_date = new DateTime($booking->start_date);
            $agent_id = intval($booking->agent_id);
            $gate_code = generate_gate_code($agent_id, $booking_date);

            echo '<div style="margin-top: 15px; padding: 15px; background: #f7f9fc; border-radius: 4px; text-align: center;">';
            echo '<div style="color: #666; font-size: 12px; margin-bottom: 5px;">GATE CODE</div>';
            echo '<div style="font-weight: bold; color: #2d54de; font-size: 20px;">' . $gate_code . '</div>';
            echo '</div>';
        } catch (Exception $e) {
            error_log('Error creating gate code: ' . $e->getMessage());
        }
    }
}

// Add the hook
add_action('latepoint_booking_full_summary_before', 'show_gate_code', 10, 1);

// Add the hook correctly
add_action('latepoint_booking_full_summary_before', 'show_gate_code', 10, 1);
add_action('latepoint_step_confirmation_head_info_after', 'show_gate_code', 10, 1);



//// Add gate code only to a specific part of the summary
//// Using a safer hook that comes later in the process
//add_action('latepoint_booking_summary_after_summary_box', function ($booking) {
//
//    error_log($booking . " Received");
//
//    if ($booking->status != 'approved') return;
//
//    $agent_id = $booking->agent_id ?? 0;
//
//    $gate_code = generate_gate_code($agent_id, $booking->start_datetime_utc);
//
//    echo '<div style="margin-top: 15px; padding: 15px; background: #f7f9fc; border-radius: 4px; text-align: center;">';
//    echo '<div style="color: #666; font-size: 12px; margin-bottom: 5px;">GATE ACCESS CODE</div>';
//    echo '<div style="font-weight: bold; color: #2d54de; font-size: 20px;">' . $gate_code . '</div>';
//    echo '</div>';
//});
//
//
//// Add gate code to calendar description but avoid interfering with other parameters
//add_filter('latepoint_build_add_to_calendar_link_params', function ($params, $booking) {
////    error_log($booking . " Received with params " . print_r($params));
//
//    // Only proceed if we have valid parameters and booking
//    if (!is_array($params) || !isset($params['description']) || !is_object($booking)) {
//        return $params;
//    }
//
//    $agent_id = $booking->agent_id ?? 0;
//    $gate_code = generate_gate_code($agent_id, $booking->start_datetime_utc);
//
//    // Safely append gate code to description
//    $params['description'] .= "\n\nGATE CODE: " . $gate_code;
//
//    return $params;
//}, 15, 2); // Lower priority (higher number) to run after other filters
//
//// Keep the footer display
//add_action('wp_footer', function () {
//    $gate_code = generate_gate_code(1, new DateTime());
//
//    echo '<div style="position: fixed; bottom: 10px; left: 10px; background: #fff; border: 2px solid #2d54de; padding: 15px; z-index: 9999; text-align: center;">';
//    echo '<div style="color: #666; font-size: 12px; margin-bottom: 5px;">GATE CODE</div>';
//    echo '<div style="color: #2d54de; font-size: 20px; font-weight: bold;">' . $gate_code . '</div>';
//    echo '</div>';
//});