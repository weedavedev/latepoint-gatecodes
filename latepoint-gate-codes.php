<?php
/**
 * Plugin Name: LatePoint Addon - Gate Codes
 * Description: LatePoint Addon that adds a gate code to booking summary and confirmations
 * Version: 1.0.4
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
    const VERSION = '1.0.4';

    /**
     * Debug mode
     */
    const DEBUG = true;

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
     */
    private function check_dependencies() {
        if (!class_exists('OsOrderModel')) {
            add_action('admin_notices', array($this, 'latepoint_missing_notice'));
            $this->log_debug('Latepoint plugin is not active or installed');
            return false;
        }
        return true;
    }

    /**
     * Log debug message if debug is enabled
     *
     * @param string $message Message to log
     */
    private function log_debug($message) {
        if (self::DEBUG) {
            error_log('LATEPOINT_GATECODES: ' . $message);
        }
    }

    /**
     * Admin notice for missing LatePoint
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
    }

    /**
     * Initialize hooks
     * 
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
     *
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
        $this->log_debug('Attempting to load CSS from: ' . LATEPOINT_GATE_CODES_PLUGIN_URL . 'assets/css/latepoint-gate-codes.css');
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
                // Check if booking is for the current day
                if (isset($booking->start_date)) {
                    // Get current date as DateTime object
                    $current_date = new DateTime(current_time('Y-m-d'));
                    
                    // Make sure booking date is also a DateTime object
                    $booking_date = null;
                    if ($booking->start_date instanceof DateTime) {
                        $booking_date = $booking->start_date;
                    } else {
                        // If it's a string, convert it
                        $booking_date = new DateTime($booking->start_date);
                    }

                    // Calculate days difference
                    $days_diff = (int)$current_date->diff($booking_date)->format('%r%a');

                    $this->log_debug('Booking data - Start date: ' . $booking_date->format('Y-m-d') . 
                                    ' Current date: ' . $current_date->format('Y-m-d') . 
                                    ' Days difference: ' . $days_diff);
                    
                    // Only show gate code if booking is within 2 days (past or future)
                    // FIXED LOGIC: If days_diff < -2 (more than 2 days in the past) OR days_diff > 2 (more than 2 days in the future)
                    if ($days_diff < -2 || $days_diff > 2) {
                        if ($days_diff < 0) {
                            $this->log_debug('Not showing code as it\'s a past booking (more than 2 days ago)');
                        } else {
                            $this->log_debug('Not showing code as it\'s too far in future, see email');
                        }
                        return; // Return to jump out, and not show code
                    }

                } else {
                    $this->log_debug('Missing start date: ' . print_r($booking, true));
                    return;
                }
                
                $agent_id = intval($booking->agent_id);
                $gate_code = $this->generate_gate_code($agent_id, $booking_date);

                echo '<div class="os-gate-code">';
                echo '<div class="os-gate-code-label">' . esc_html__('GATE CODE', 'latepoint-gate-codes') . '</div>';
                echo '<div class="os-gate-code-value">' . esc_html($gate_code) . '</div>';
                echo '<div class="os-gate-code-email-reminder">' .
                    esc_html__('Your gate code is also in an email confirmation!', 'latepoint-gate-codes') . '</div>';
                echo '</div>';
            } catch (Exception $e) {
                $this->log_debug('Error creating gate code: ' . $e->getMessage());
            }
        } else {
            $this->log_debug('Not an approved booking or missing booking data: ' . print_r($booking, true));
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
            $this->log_debug('Error in get_gate_code: ' . $e->getMessage());
            return "#ERR";
        }
    }

    /**
     * Generate HTML for gate code to be used in email templates
     * 
     * @param int $agent_id The agent ID to use in the gate code
     * @param string|DateTime $date_string A date string or DateTime object
     * @param bool $return Whether to return or echo the HTML
     * @return string|void HTML output if $return is true, otherwise echoes HTML
     */
    public function get_gate_code_email_html($agent_id, $date_string, $return = false) {
        // Convert to string if it's a DateTime object
        if ($date_string instanceof DateTime) {
            $date_string = $date_string->format('Y-m-d');
        }
        
        $gate_code = $this->get_gate_code($agent_id, $date_string);
        
        $html = '<div style="background-color: #f7f9fc; border-radius: 4px; padding: 20px; margin: 25px 0; ' .
                'text-align: center; border: 2px dashed #2d54de; font-family: sans-serif;">' .
                '<div style="font-size: 14px; font-weight: bold; color: #6d6d6d; text-transform: uppercase; ' .
                'letter-spacing: 1.5px; margin-bottom: 10px;">' . 
                esc_html__('GATE CODE', 'latepoint-gate-codes') . '</div>' .
                '<div style="font-size: 32px; font-weight: 700; color: #2d54de; line-height: 1.2; ' .
                'letter-spacing: 1px;">' . esc_html($gate_code) . '</div>' .
                '</div>';
        
        if ($return) {
            return $html;
        }
        
        echo $html;
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

/**
 * Global function to get HTML for gate code
 * This allows the function to be called from anywhere without having to directly access the class instance
 *
 * @param int $agent_id The agent ID to use in the gate code
 * @param string $date_string A date string that can be converted to DateTime
 * @param bool $return Whether to return or echo the HTML
 * @return string|void HTML output if $return is true, otherwise echoes HTML
 */
function get_gate_code_email_html($agent_id, $date_string, $return = false) {
    $plugin = LatePoint_Gate_Codes();
    return $plugin->get_gate_code_email_html($agent_id, $date_string, $return);
}
