<?php
/**
 * Uninstallation routine for Booking Engine.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'mb_engine_settings' );
delete_option( 'mb_engine_db_version' );

// Clean up custom transients.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mb_%' OR option_name LIKE '_transient_timeout_mb_%'" );

// Note: To preserve user data, custom tables (wp_mb_locations, wp_mb_availabilities, wp_mb_bookings)
// are not automatically dropped unless an explicit "Wipe all data on uninstall" setting is enabled.
