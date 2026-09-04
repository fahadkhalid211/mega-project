<?php
/**
 * Plugin Name:       Booking Engine - Multi-Model Booking & Appointment System
 * Plugin URI:        https://github.com/fahadkhalid211/mega-project
 * Description:       High-performance booking system supporting hourly appointments, day rentals, night stays, and capacity events with worldwide postal code radius search and WooCommerce checkout.
 * Version:           1.3.2
 * Author:            Booking Engine Team
 * Author URI:        https://github.com/fahadkhalid211
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-booking-engine
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access.
}

// Plugin version and filesystem constants.
if ( ! defined( 'MB_ENGINE_VERSION' ) ) {
	define( 'MB_ENGINE_VERSION', '1.3.2' );
}
if ( ! defined( 'MB_ENGINE_FILE' ) ) {
	define( 'MB_ENGINE_FILE', __FILE__ );
}
if ( ! defined( 'MB_ENGINE_PATH' ) ) {
	define( 'MB_ENGINE_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'MB_ENGINE_URL' ) ) {
	define( 'MB_ENGINE_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'MB_ENGINE_BASENAME' ) ) {
	define( 'MB_ENGINE_BASENAME', plugin_basename( __FILE__ ) );
}

// Require PSR-4 Autoloader.
require_once MB_ENGINE_PATH . 'includes/Autoloader.php';

// Register Autoloader for MyBookingEngine namespace.
\MyBookingEngine\Autoloader::register();

/**
 * Plugin activation handler.
 * Performs database schema migration using dbDelta and installs default settings.
 */
function my_booking_engine_activate() {
	\MyBookingEngine\Plugin::activate();
}
register_activation_hook( __FILE__, 'my_booking_engine_activate' );

/**
 * Plugin deactivation handler.
 * Cleans up transient locks and flushes rewrite rules.
 */
function my_booking_engine_deactivate() {
	\MyBookingEngine\Plugin::deactivate();
}
register_deactivation_hook( __FILE__, 'my_booking_engine_deactivate' );

/**
 * Initialize the core plugin instance on plugins_loaded.
 */
function my_booking_engine_init() {
	\MyBookingEngine\Plugin::get_instance()->init();
}
add_action( 'plugins_loaded', 'my_booking_engine_init' );
