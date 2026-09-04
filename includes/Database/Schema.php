<?php
/**
 * Database Schema and Migration Manager.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Schema
 */
class Schema {

	const DB_VERSION = '1.2.0';

	/**
	 * Get locations table name with WP prefix.
	 *
	 * @return string
	 */
	public static function get_locations_table() {
		global $wpdb;
		return $wpdb->prefix . 'mb_locations';
	}

	/**
	 * Get availabilities table name with WP prefix.
	 *
	 * @return string
	 */
	public static function get_availabilities_table() {
		global $wpdb;
		return $wpdb->prefix . 'mb_availabilities';
	}

	/**
	 * Get bookings table name with WP prefix.
	 *
	 * @return string
	 */
	public static function get_bookings_table() {
		global $wpdb;
		return $wpdb->prefix . 'mb_bookings';
	}

	/**
	 * Get reviews table name with WP prefix.
	 *
	 * @return string
	 */
	public static function get_reviews_table() {
		global $wpdb;
		return $wpdb->prefix . 'mb_reviews';
	}

	/**
	 * Get reminder log table name with WP prefix.
	 *
	 * @return string
	 */
	public static function get_reminders_table() {
		global $wpdb;
		return $wpdb->prefix . 'mb_reminders_log';
	}

	/**
	 * Run dbDelta database installation and migrations.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();

		$locations_table      = self::get_locations_table();
		$availabilities_table = self::get_availabilities_table();
		$bookings_table       = self::get_bookings_table();
		$reviews_table        = self::get_reviews_table();
		$reminders_table      = self::get_reminders_table();

		// Notice: dbDelta requires two spaces after PRIMARY KEY and lowercase types.
		$sql_locations = "CREATE TABLE {$locations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			postal_code varchar(30) NOT NULL,
			postal_code_clean varchar(30) NOT NULL DEFAULT '',
			city varchar(100) NOT NULL,
			country_code varchar(10) NOT NULL,
			latitude decimal(10,8) NOT NULL,
			longitude decimal(11,8) NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY geo_coords (latitude,longitude),
			KEY postal_code (postal_code),
			KEY postal_code_clean (postal_code_clean)
		) {$charset_collate};";

		$sql_availabilities = "CREATE TABLE {$availabilities_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entity_id bigint(20) unsigned NOT NULL,
			rule_type varchar(50) NOT NULL DEFAULT 'weekly_recurring',
			day_of_week tinyint(1) DEFAULT NULL,
			start_date date DEFAULT NULL,
			end_date date DEFAULT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			capacity int(11) unsigned NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			KEY entity_id (entity_id),
			KEY rule_type (rule_type),
			KEY rule_dates (start_date,end_date)
		) {$charset_collate};";

		$sql_bookings = "CREATE TABLE {$bookings_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entity_id bigint(20) unsigned NOT NULL,
			customer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			customer_name varchar(100) NOT NULL,
			customer_email varchar(150) NOT NULL,
			customer_phone varchar(50) DEFAULT NULL,
			booking_start datetime NOT NULL,
			booking_end datetime NOT NULL,
			capacity_booked int(11) unsigned NOT NULL DEFAULT 1,
			status varchar(30) NOT NULL DEFAULT 'pending',
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			total_price decimal(10,2) NOT NULL DEFAULT 0.00,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY entity_id (entity_id),
			KEY booking_dates (booking_start,booking_end),
			KEY status (status),
			KEY order_id (order_id)
		) {$charset_collate};";

		$sql_reviews = "CREATE TABLE {$reviews_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entity_id bigint(20) unsigned NOT NULL,
			customer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			customer_name varchar(100) NOT NULL,
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			rating tinyint(1) unsigned NOT NULL DEFAULT 5,
			title varchar(200) NOT NULL DEFAULT '',
			content text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'approved',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY entity_id (entity_id),
			KEY customer_id (customer_id),
			KEY booking_id (booking_id),
			KEY status (status)
		) {$charset_collate};";

		$sql_reminders = "CREATE TABLE {$reminders_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL,
			reminder_type varchar(20) NOT NULL,
			sent_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY booking_reminder (booking_id,reminder_type)
		) {$charset_collate};";

		dbDelta( $sql_locations );
		dbDelta( $sql_availabilities );
		dbDelta( $sql_bookings );
		dbDelta( $sql_reviews );
		dbDelta( $sql_reminders );

		// Backfill postal_code_clean for any existing rows if empty.
		$wpdb->query( "UPDATE {$wpdb->prefix}mb_locations SET postal_code_clean = UPPER(REPLACE(REPLACE(REPLACE(postal_code, ' ', ''), '-', ''), '.', '')) WHERE postal_code_clean = '' AND postal_code != ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		update_option( 'mb_engine_db_version', self::DB_VERSION );
	}
}
