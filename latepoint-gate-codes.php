<?php
/**
 * Plugin Name: LatePoint Addon - Gate Codes
 * Description: LatePoint Addon that adds a gate code to booking summary and confirmations
 * Version: 1.0.8
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
    const VERSION = '1.0.8';

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
            // Use our custom logging function
            
            // Original logging code
            $sanatised_message = sanitize_text_field($message);

            if (is_object($message) || is_array($message)) {
                $sanatised_message = sanitize_text_field(print_r($message, true));
            }

            error_log('LATEPOINT_GATECODES: ' . $sanatised_message);
        }
    }

    private $kses_args = [
                            'div' => [
                                'class' => true
                            ],
                            'br' => []
                        ];
    /**
     * Admin notice for missing LatePoint
     */
    public function latepoint_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _ex('LatePoint Gate Codes addon requires the LatePoint plugin to be installed and activated.', 'Admin error notice', 'latepoint-gate-codes'); ?></p>
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
        // Your includes code
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
        
        
        try {
            wp_register_style(
                'latepoint-gate-codes-styles',
                LATEPOINT_GATE_CODES_PLUGIN_URL . 'assets/css/latepoint-gate-codes.css',
                array(),
                LATEPOINT_GATE_CODES_VERSION
            );

            // Enqueue on all pages where LatePoint might be used
            wp_enqueue_style('latepoint-gate-codes-styles');
        } catch (Exception $e) {
            $this->error_log($e);
        }
    }

    /**
     * Main handler function that determines what to do based on the confirmation object type
     *
     * @param mixed $confirmation Either an OsOrderModel or booking object
     */
    public function show_gate_code($confirmation) {
        
        try {
            if ($confirmation) {
            } else {
            }
            
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
                    printf(
                        '<div class="%s">%s%s</div>',
                        esc_attr('os-gate-code os-gate-code-multiple'),
                        wp_kses(
                            sprintf(
                                '<div class="os-gate-code-label">%s</div>',
                                _x('GATE CODE', 'Header label for gatecode', 'latepoint-gate-codes'),
                            ),
                            $this->kses_args
                        ), 
                        wp_kses(
                            sprintf(
                                '<div class="os-gate-code-value">%s<br>%s</div>', 
                                _x('Multiple bookings found', 'Warning message about multiple bookings', 'latepoint-gate-codes'),
                                _x('Check individual bookings for separate gate codes', 'Instructions for multiple bookings', 'latepoint-gate-codes'),
                            ),
                            $this->kses_args
                        )
                    );
                }
            } else {
                // It's a single booking object
                $this->display_single_booking_gate_code($confirmation);
            }
        } catch (Exception $e) {
            $this->error_log($e);
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
                if (!isset($booking->start_date)) {
                    $this->log_debug('Missing start date: ' . print_r($booking, true));
                    return;
                }

                
                // Get current date as DateTime object
                $current_date = new DateTime(current_time('Y-m-d'));
                
                // Convert booking date to DateTime safely
                try {
                    if ($booking->start_date instanceof DateTime) {
                        $booking_date = $booking->start_date;
                    } else {
                        // If it's a string, convert it
                        $booking_date = new DateTime($booking->start_date);
                    }
                } catch (Exception $e) {
                    $this->log_debug('Invalid start date passed for gatecode' . print_r($booking->start_date, true));
                    return;
                }

                // Calculate days difference
                $days_diff = (int)$current_date->diff($booking_date)->format('%r%a');

                $this->log_debug('Booking data - Start date: ' . $booking_date->format('Y-m-d') . 
                                ' Current date: ' . $current_date->format('Y-m-d') . 
                                ' Days difference: ' . $days_diff);
                
                // Only show gate code if booking is within 2 days (past or future)
                // FIXED LOGIC: If days_diff < -2 (more than 2 days in the past) OR days_diff > 2 (more than 2 days in the future)
                if (abs($days_diff) > 2) {
                    if ($days_diff < 0) {
                        if ($days_diff < 0) {
                            $this->log_debug('Not showing code as it\'s a past booking (more than 2 days ago)');
                        } else {
                            $this->log_debug('Not showing code as it\'s too far in future, see email');
                        }
                        return; // Return to jump out, and not show code
                    }
                }
                
                
                $agent_id = intval($booking->agent_id);
                
                $gate_code = $this->generate_gate_code($agent_id, $booking_date);

                printf(
                    '<div class="%s">%s%s%s</div>',
                    esc_attr('os-gate-code'),
                    wp_kses(
                        sprintf(
                            '<div class="os-gate-code-label">%s</div>',
                            _x('GATE CODE', 'Header label for gatecode', 'latepoint-gate-codes'),
                        ),
                        $this->kses_args
                    ),
                    wp_kses(
                        sprintf(
                            '<div class="os-gate-code-value">%s</div>',
                            esc_html($gate_code),
                        ),
                        $this->kses_args
                    ),
                    wp_kses(
                        sprintf(
                            '<div class="os-gate-code-email-reminder">%s</div>',
                            _x('Your gate code is also in an email confirmation!', 'Email reminder pop up', 'latepoint-gate-codes'),
                        ),
                        $this->kses_args
                    )
                );
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
            return _x('#ERR', 'Error placeholder for gatecode', 'latepoint-gate-codes');
        }
        
        $code = "#" . $field . $field . sprintf("%02d", $date->format("W"));
        return $code;
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
}

// Initialize the plugin
function LatePoint_Gate_Codes() {
    return LatePoint_Gate_Codes::instance();
}

// Start the plugin with the same hook you're using
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
