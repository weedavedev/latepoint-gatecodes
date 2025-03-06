<?php
/**
 * Plugin Name: LatePoint Addon - Gate Codes
 * Description: LatePoint Addon that adds a gate code to booking summary and confirmations
 * Version: 1.0.0
 * Author: Wallace Development
 * Plugin URI: https://wallacedevelopment.co.uk
 * Text Domain: latepoint-gate-codes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Main LatePoint Gate Codes class
 */
class LatePoint_Gate_Codes {
    /**
     * Instance of this class
     */
    protected static $instance = null;

    /**
     * Plugin version
     */
    const VERSION = '1.0.0';

    /**
     * Get plugin instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Define constants
        $this->define_constants();

        // Load required files
        $this->includes();

        // Actions and filters
        $this->init_hooks();
    }

    /**
     * Define constants
     */
    private function define_constants() {
        define('LATEPOINT_GATE_CODES_VERSION', self::VERSION);
        define('LATEPOINT_GATE_CODES_PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('LATEPOINT_GATE_CODES_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    /**
     * Include required files
     */
    private function includes() {
        // No additional files needed for now, but can be added later
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register styles
        add_action('wp_enqueue_scripts', array($this, 'register_styles'));

        // Add hooks for displaying gate codes
        add_action('latepoint_booking_full_summary_before', array($this, 'show_gate_code'), 10, 1);
        add_action('latepoint_step_confirmation_head_info_after', array($this, 'show_gate_code'), 10, 1);
    }

    /**
     * Register and enqueue styles
     */
    public function register_styles() {
        wp_register_style(
            'latepoint-gate-codes-styles',
            plugin_dir_url(__FILE__) . 'assets/css/latepoint-gate-codes.css',
            array(),
            LATEPOINT_GATE_CODES_VERSION
        );

        // Enqueue on all pages where LatePoint might be used
        wp_enqueue_style('latepoint-gate-codes-styles');

        // Debug - helps identify if the file is being found
        error_log('LatePoint Gate Codes: Attempting to load CSS from: ' . plugin_dir_url(__FILE__) . 'assets/css/latepoint-gate-codes.css');
    }

    /**
     * Main handler function that determines what to do based on the confirmation object type
     *
     * @param mixed $confirmation Either an OsOrderModel or booking object
     */
    public function show_gate_code($confirmation) {
        // Check if it's an order with multiple bookings
        if ($confirmation instanceof OsOrderModel) {
            $bookings = $confirmation->get_bookings_from_order_items();

            if (count($bookings) === 1) {
                // Single booking in order
                foreach ($bookings as $booking) {
                    $this->display_single_booking_gate_code($booking);
                    break;
                }
            } else if (count($bookings) > 1) {
                // Multiple bookings, show info message
                echo '<div class="os-gate-code os-gate-code-multiple">';
                echo '<div class="os-gate-code-label">GATE CODE</div>';
                echo '<div class="os-gate-code-value">Multiple bookings found<br>Check individual bookings for separate gate codes</div>';
                echo '</div>';
            }
        } else {
            // It's a single booking object
            $this->display_single_booking_gate_code($confirmation);
        }
    }

    /**
     * Displays gate code for a single booking if it's approved
     *
     * @param object $booking The booking object
     */
    private function display_single_booking_gate_code($booking) {
        // Check if booking exists and is approved
        if ($booking && strtolower($booking->status) === 'approved') {
            try {
                $booking_date = new DateTime($booking->start_date);
                $agent_id = intval($booking->agent_id);
                $gate_code = $this->generate_gate_code($agent_id, $booking_date);

                echo '<div class="os-gate-code">';
                    echo '<div class="os-gate-code-label">GATE CODE</div>';
                    echo '<div class="os-gate-code-value">' . esc_html($gate_code) . '</div>';
                    echo '<div class="os-gate-code-labels os-gate-code-email-reminder">Your gate code is also in an email confirmation!</div>';
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
    private function generate_gate_code($field, $date) {
        if (!$date instanceof DateTime || !is_int($field)) {
            return "#ERR";
        }
        return "#" . $field . $field . sprintf("%02d", $date->format("W"));
    }
}

// Initialize the plugin
function LatePoint_Gate_Codes() {
    return LatePoint_Gate_Codes::instance();
}

// Start the plugin
add_action('plugins_loaded', 'LatePoint_Gate_Codes');