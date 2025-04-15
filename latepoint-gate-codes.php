<?php
/**
 * Plugin Name: LatePoint Addon - Gate Codes
 * Description: LatePoint Addon that adds a gate code to booking summary and confirmations
 * Version: 1.0.7
 * Author: Wallace Development
 * Plugin URI: https://wallacedevelopment.co.uk
 * Text Domain: latepoint-gate-codes
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Custom logging function
function latepoint_gate_codes_log($message) {
    // Create logs directory if it doesn't exist
    $logs_dir = plugin_dir_path(__FILE__) . 'logs';
    if (!file_exists($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }
    
    // Define log file path
    $log_file = $logs_dir . '/gate-codes-debug.log';
    
    // Format the message
    $formatted_message = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    
    // Write to log file
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        latepoint_gate_codes_log('FATAL ERROR: ' . print_r($error, true));
    }
});

// Log initialization
latepoint_gate_codes_log('Plugin initialization started');

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
    const VERSION = '1.0.7';

    /**
     * Debug mode
     */
    const DEBUG = true;

    /**
     * Get plugin instance
     */
    public static function instance() {
        latepoint_gate_codes_log('Instance method called');
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        latepoint_gate_codes_log('Constructor started');
        
        // Check if LatePoint is active
        if (!$this->check_dependencies()) {
            latepoint_gate_codes_log('Dependencies check failed');
            return;
        }
        latepoint_gate_codes_log('Dependencies check passed');
        
        // Define constants
        $this->define_constants();
        latepoint_gate_codes_log('Constants defined');

        // Load required files
        $this->includes();
        latepoint_gate_codes_log('Required files included');

        // Actions and filters
        $this->init_hooks();
        latepoint_gate_codes_log('Hooks initialized');
    }

    /**
     * Check if LatePoint plugin is active
     *
     * @return bool True is deps are met, false otherwise
     */
    private function check_dependencies() {
        latepoint_gate_codes_log('Checking dependencies');
        
        if (!class_exists('OsOrderModel')) {
            latepoint_gate_codes_log('Latepoint plugin is not active or installed');
            add_action('admin_notices', array($this, 'latepoint_missing_notice'));
            $this->log_debug('Latepoint plugin is not active or installed');
            return false;
        }
        
        latepoint_gate_codes_log('OsOrderModel class exists');
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
            latepoint_gate_codes_log('DEBUG: ' . $message);
            
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
        latepoint_gate_codes_log('Displaying missing dependency notice');
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
        
        latepoint_gate_codes_log('Constants defined: ' . LATEPOINT_GATE_CODES_PLUGIN_PATH);
    }

    /**
     * Include required files
     */
    private function includes() {
        // Your includes code
        latepoint_gate_codes_log('No additional files to include');
    }

    /**
     * Initialize hooks
     * 
     */
    private function init_hooks() {
        latepoint_gate_codes_log('Initializing hooks');
        
        // Register styles
        add_action('wp_enqueue_scripts', array($this, 'register_styles'));
        latepoint_gate_codes_log('Added wp_enqueue_scripts hook');

        // Add hooks for displaying gate codes
        add_action('latepoint_booking_full_summary_before', array($this, 'show_gate_code'), 10, 1);
        add_action('latepoint_step_confirmation_head_info_after', array($this, 'show_gate_code'), 10, 1);
        latepoint_gate_codes_log('Added LatePoint hooks for showing gate code');
    }
    
    /**
     * Register and enqueue styles
     *
     */
    public function register_styles() {
        latepoint_gate_codes_log('Registering styles');
        
        $css_path = LATEPOINT_GATE_CODES_PLUGIN_PATH . 'assets/css/latepoint-gate-codes.css';
        $css_url = LATEPOINT_GATE_CODES_PLUGIN_URL . 'assets/css/latepoint-gate-codes.css';
        
        latepoint_gate_codes_log('CSS path: ' . $css_path);
        latepoint_gate_codes_log('CSS exists: ' . (file_exists($css_path) ? 'Yes' : 'No'));
        
        try {
            wp_register_style(
                'latepoint-gate-codes-styles',
                LATEPOINT_GATE_CODES_PLUGIN_URL . 'assets/css/latepoint-gate-codes.css',
                array(),
                LATEPOINT_GATE_CODES_VERSION
            );

            // Enqueue on all pages where LatePoint might be used
            wp_enqueue_style('latepoint-gate-codes-styles');
            latepoint_gate_codes_log('Styles registered and enqueued successfully');
        } catch (Exception $e) {
            latepoint_gate_codes_log('Error registering styles: ' . $e->getMessage());
        }
    }

    /**
     * Main handler function that determines what to do based on the confirmation object type
     *
     * @param mixed $confirmation Either an OsOrderModel or booking object
     */
    public function show_gate_code($confirmation) {
        latepoint_gate_codes_log('show_gate_code called');
        
        try {
            if ($confirmation) {
                latepoint_gate_codes_log('Confirmation object type: ' . (is_object($confirmation) ? get_class($confirmation) : gettype($confirmation)));
            } else {
                latepoint_gate_codes_log('Confirmation is null or empty');
            }
            
            // Check if it's an order with multiple bookings
            if ($confirmation instanceof OsOrderModel) {
                latepoint_gate_codes_log('Confirmation is an OsOrderModel');
                $bookings = $confirmation->get_bookings_from_order_items();
                
                latepoint_gate_codes_log('Number of bookings: ' . count($bookings));

                if (count($bookings) === 1) {
                    // Single booking in order
                    foreach ($bookings as $booking) {
                        latepoint_gate_codes_log('Processing single booking from order');
                        $this->display_single_booking_gate_code($booking);
                        break;
                    }
                } else if (count($bookings) > 1) {
                    // Multiple bookings, show info message
                    latepoint_gate_codes_log('Multiple bookings found, showing info message');
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
                latepoint_gate_codes_log('Confirmation is a single booking object');
                $this->display_single_booking_gate_code($confirmation);
            }
        } catch (Exception $e) {
            latepoint_gate_codes_log('Error in show_gate_code: ' . $e->getMessage());
            latepoint_gate_codes_log('Error trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Displays gate code for a single booking if it's approved
     *
     * @param object $booking The booking object
     */
    private function display_single_booking_gate_code($booking) {
        latepoint_gate_codes_log('display_single_booking_gate_code called');
        
        // Check if booking exists and is approved
        if ($booking && strtolower($booking->status) === 'approved') {
            latepoint_gate_codes_log('Booking is approved');
            
            try {
                // Check if booking is for the current day
                if (!isset($booking->start_date)) {
                    latepoint_gate_codes_log('Missing start date');
                    $this->log_debug('Missing start date: ' . print_r($booking, true));
                    return;
                }

                latepoint_gate_codes_log('Booking start date exists');
                
                // Get current date as DateTime object
                $current_date = new DateTime(current_time('Y-m-d'));
                latepoint_gate_codes_log('Current date: ' . $current_date->format('Y-m-d'));
                
                // Convert booking date to DateTime safely
                try {
                    if ($booking->start_date instanceof DateTime) {
                        $booking_date = $booking->start_date;
                    } else {
                        // If it's a string, convert it
                        $booking_date = new DateTime($booking->start_date);
                    }
                    latepoint_gate_codes_log('Booking date: ' . $booking_date->format('Y-m-d'));
                } catch (Exception $e) {
                    latepoint_gate_codes_log('Invalid start date: ' . print_r($booking->start_date, true));
                    latepoint_gate_codes_log('Error: ' . $e->getMessage());
                    $this->log_debug('Invalid start date passed for gatecode' . print_r($booking->start_date, true));
                    return;
                }

                // Calculate days difference
                $days_diff = (int)$current_date->diff($booking_date)->format('%r%a');
                latepoint_gate_codes_log('Days difference: ' . $days_diff);

                $this->log_debug('Booking data - Start date: ' . $booking_date->format('Y-m-d') . 
                                ' Current date: ' . $current_date->format('Y-m-d') . 
                                ' Days difference: ' . $days_diff);
                
                // Only show gate code if booking is within 2 days (past or future)
                // FIXED LOGIC: If days_diff < -2 (more than 2 days in the past) OR days_diff > 2 (more than 2 days in the future)
                if (abs($days_diff) > 2) {
                    if ($days_diff < 0) {
                        if ($days_diff < 0) {
                            latepoint_gate_codes_log('Not showing code - past booking (more than 2 days ago)');
                            $this->log_debug('Not showing code as it\'s a past booking (more than 2 days ago)');
                        } else {
                            latepoint_gate_codes_log('Not showing code - future booking (more than 2 days ahead)');
                            $this->log_debug('Not showing code as it\'s too far in future, see email');
                        }
                        return; // Return to jump out, and not show code
                    }
                }
                
                latepoint_gate_codes_log('Booking is within date range, generating gate code');
                
                $agent_id = intval($booking->agent_id);
                latepoint_gate_codes_log('Agent ID: ' . $agent_id);
                
                $gate_code = $this->generate_gate_code($agent_id, $booking_date);
                latepoint_gate_codes_log('Generated gate code: ' . $gate_code);

                latepoint_gate_codes_log('Displaying gate code');
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
                latepoint_gate_codes_log('Gate code displayed successfully');
            } catch (Exception $e) {
                latepoint_gate_codes_log('Error creating gate code: ' . $e->getMessage());
                latepoint_gate_codes_log('Error trace: ' . $e->getTraceAsString());
                $this->log_debug('Error creating gate code: ' . $e->getMessage());
            }
        } else {
            latepoint_gate_codes_log('Booking not approved or missing: ' . print_r($booking, true));
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
        latepoint_gate_codes_log('generate_gate_code called');
        
        if (!$date instanceof DateTime || !is_int($field)) {
            latepoint_gate_codes_log('Invalid parameters for gate code generation');
            return _x('#ERR', 'Error placeholder for gatecode', 'latepoint-gate-codes');
        }
        
        $code = "#" . $field . $field . sprintf("%02d", $date->format("W"));
        latepoint_gate_codes_log('Generated code: ' . $code);
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
        latepoint_gate_codes_log('get_gate_code called with agent_id: ' . $agent_id . ', date: ' . $date_string);
        
        try {
            $agent_id = intval($agent_id);
            $date = new DateTime($date_string);
            return $this->generate_gate_code($agent_id, $date);
        } catch (Exception $e) {
            latepoint_gate_codes_log('Error in get_gate_code: ' . $e->getMessage());
            $this->log_debug('Error in get_gate_code: ' . $e->getMessage());
            return "#ERR";
        }
    }
}

// Initialize the plugin
function LatePoint_Gate_Codes() {
    latepoint_gate_codes_log('LatePoint_Gate_Codes function called');
    return LatePoint_Gate_Codes::instance();
}

// Start the plugin with the same hook you're using
add_action('plugins_loaded', 'LatePoint_Gate_Codes');
latepoint_gate_codes_log('Added plugins_loaded hook');

/**
 * Global function to get a gate code
 * This allows the function to be called from anywhere without having to directly access the class instance
 *
 * @param int $agent_id The agent ID to use in the gate code
 * @param string $date_string A date string that can be converted to DateTime
 * @return string The formatted gate code
 */
function get_gate_code($agent_id, $date_string) {
    latepoint_gate_codes_log('Global get_gate_code function called');
    $plugin = LatePoint_Gate_Codes();
    return $plugin->get_gate_code($agent_id, $date_string);
}
