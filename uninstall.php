<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://wallacedevelopment.co.uk
 * @since      1.0.2
 *
 * @package    LatePoint_Gate_Codes
 */

// If uninstall is not called from WordPress or we're in development mode, exit
if (!defined('WP_UNINSTALL_PLUGIN') || 
    (isset($_SERVER['WORDPRESS_DEVELOPMENT_MODE']) && $_SERVER['WORDPRESS_DEVELOPMENT_MODE'] == 1 )) {
        exit;
}
/**
 * For safety in development environments, add this to wp-config.php:
 * 
 * define('WP_DEVELOPMENT_MODE', true);
 */

// Remove plugin options if any
delete_option('latepoint_gate_codes_settings');
delete_site_option('latepoint_gate_codes_settings');
