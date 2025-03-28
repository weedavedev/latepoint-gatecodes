<?php
/**
 * Plugin Name: LatePoint Addon - Gate Codes
 * Description: LatePoint Addon that adds a gate code to booking summary and confirmations
 * Version: 1.0.1
 * Author: Wallace Development
 * Plugin URI: https://wallacedevelopment.co.uk
 * Text Domain: latepoint-gate-codes
 * Requires at least: 5.0
 * Requires PHP: 7.0
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
    const VERSION = '1.0.1';

    /**
     * Debug mode
     */
    const DEBUG = false;

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
        // Check if LatePoint is active
        if (!$this->check_dependencies()) {
            return;
        }

        // Define constants
        $this->define_constants();

        // Load required files
        $this->includes();

        // Actions and filters
        $this->init_hooks();
    }

    /**
     * Check if LatePoint plugin is active
     *
     * @return bool True is deps are met, false otherwise
     * @since 1.0.0
     */
    private function check_dependencies() {
        if (!class_exists('OsOrderModel')) {
            add_action('admin_notices', array($this, 'latepoint_missing_notice'));
            $this->log_error('Latepoint plugin is not active or installed');
            return false;
        }
        return true;
    }

    /**
     * Admin notice for missing LatePoint
     *
     * @since 1.0.0
     */
    public function latepoint_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('LatePoint Gate Codes addon requires the LatePoint plugin to be installed and activated.', 'latepoint-gate-codes'); ?></p>
        </div>
        <?php
    }

    /**
     * Define constants
     * 
     * @since 1.0.0
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
        if (defined('WP_TESTS_DOMAIN') && WP_TESTS_DOMAIN){
            //Load test files only when running tests
            include_once LATEPOINT_GATE_CODES_PLUGIN_PATH . 'tests/test-gate-codes.php';
        }
    }

    /**
     * Initialize hooks
     * 
     * @since 1.0.0
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
            LATEPOINT_GATE_CODES_PLUGIN_URL . 'assets/css/latepoint-gate-codes.css',
            array(),
            LATEPOINT_GATE_CODES_VERSION
        );

        // Enqueue on all pages where LatePoint might be used
        wp_enqueue_style('latepoint-gate-codes-styles');

        // Debug logging
        if (self::DEBUG) {
            error_log('LatePoint Gate Codes: Attempting to load CSS from: ' . LATEPOINT_GATE_CODES_PLUGIN_URL . 'assets/css/latepoint-gate-codes.css');
        }
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
                echo '<div class="os-gate-code-label">' . esc_html__('GATE CODE', 'latepoint-gate-codes') . '</div>';
                echo '<div class="os-gate-code-value">' . esc_html__('Multiple bookings found', 'latepoint-gate-codes') .
                    '<br>' . esc_html__('Check individual bookings for separate gate codes', 'latepoint-gate-codes') . '</div>';
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
                echo '<div class="os-gate-code-label">' . esc_html__('GATE CODE', 'latepoint-gate-codes') . '</div>';
                echo '<div class="os-gate-code-value">' . esc_html($gate_code) . '</div>';
                echo '<div class="os-gate-code-email-reminder">' .
                    esc_html__('Your gate code is also in an email confirmation!', 'latepoint-gate-codes') . '</div>';
                echo '</div>';
            } catch (Exception $e) {
                if (self::DEBUG) {
                    error_log('Error creating gate code: ' . $e->getMessage());
                }
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

    /**
     * Public method to get a gate code from agent ID and date string
     *
     * @param int $agent_id The agent ID to use in the gate code
     * @param string $date_string A date string that can be converted to DateTime
     * @return string The formatted gate code
     */
    public function get_gate_code($agent_id, $date_string) {
        try {
            $agent_id = intval($agent_id);
            $date = new DateTime($date_string);
            return $this->generate_gate_code($agent_id, $date);
        } catch (Exception $e) {
            if (self::DEBUG) {
                error_log('Error in get_gate_code: ' . $e->getMessage());
            }
            return "#ERR";
        }
    }
}

// Initialize the plugin
function LatePoint_Gate_Codes() {
    return LatePoint_Gate_Codes::instance();
}

// Start the plugin
add_action('plugins_loaded', 'LatePoint_Gate_Codes');

/**
 * Global function to get a gate code
 * This allows the function to be called from anywhere without having to directly access the class instance
 *
 * @param int $agent_id The agent ID to use in the gate code
 * @param string $date_string A date string that can be converted to DateTime
 * @return string The formatted gate code
 */
function get_gate_code($agent_id, $date_string) {
    $plugin = LatePoint_Gate_Codes();
    return $plugin->get_gate_code($agent_id, $date_string);
}
