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
            //for each to access any array type.
            foreach ($bookings as $booking) {
                display_single_booking_gate_code($booking);
                break;
            }
        } else if (count($bookings) > 1) {
            // Multiple bookings, show info message
            echo '<div style="margin-top: 15px; padding: 15px; background: #f7f9fc; border-radius: 4px; text-align: center;">';
            echo '<div style="color: #666; font-size: 12px; margin-bottom: 5px;">GATE CODE</div>';
            echo '<div style="font-weight: bold; color: #2d54de; font-size: 20px;">>Multiple bookings found <br> Check individual bookings for separate gate codes</div>';
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
// Add the hook correctly
add_action('latepoint_booking_full_summary_before', 'show_gate_code', 10, 1);
//add_action('latepoint_step_confirmation_head_info_before', 'show_gate_code', 10, 1);
add_action('latepoint_step_confirmation_head_info_after', 'show_gate_code', 10, 1);

//// Keep the footer display
//add_action('wp_footer', function () {
//    $gate_code = generate_gate_code(1, new DateTime());
//
//    echo '<div style="position: fixed; bottom: 10px; left: 10px; background: #fff; border: 2px solid #2d54de; padding: 15px; z-index: 9999; text-align: center;">';
//    echo '<div style="color: #666; font-size: 12px; margin-bottom: 5px;">GATE CODE</div>';
//    echo '<div style="color: #2d54de; font-size: 20px; font-weight: bold;">' . $gate_code . '</div>';
//    echo '</div>';
//});