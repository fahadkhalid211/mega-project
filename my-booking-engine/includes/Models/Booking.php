<?php
/**
 * Booking Record Model.
 * Handles persistence and query logic for customer reservations.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Models;

use MyBookingEngine\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Booking
 */
class Booking {

	/**
	 * Create a new booking record.
	 *
	 * @param array $data Booking data.
	 * @return int|false Booking ID on success, false on failure.
	 */
	public static function create( array $data ) {
		global $wpdb;
		$table = Schema::get_bookings_table();

		$insert_data = array(
			'entity_id'       => absint( $data['entity_id'] ),
			'customer_id'     => isset( $data['customer_id'] ) ? absint( $data['customer_id'] ) : get_current_user_id(),
			'customer_name'   => sanitize_text_field( $data['customer_name'] ),
			'customer_email'  => sanitize_email( $data['customer_email'] ),
			'customer_phone'  => isset( $data['customer_phone'] ) ? sanitize_text_field( $data['customer_phone'] ) : '',
			'booking_start'   => sanitize_text_field( $data['booking_start'] ),
			'booking_end'     => sanitize_text_field( $data['booking_end'] ),
			'capacity_booked' => isset( $data['capacity_booked'] ) ? max( 1, absint( $data['capacity_booked'] ) ) : 1,
			'status'          => isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'pending',
			'order_id'        => isset( $data['order_id'] ) ? absint( $data['order_id'] ) : 0,
			'total_price'     => isset( $data['total_price'] ) ? floatval( $data['total_price'] ) : 0.00,
			'created_at'      => current_time( 'mysql' ),
		);

		$format = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%f', '%s' );

		$result = $wpdb->insert( $table, $insert_data, $format );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Retrieve a booking by ID.
	 *
	 * @param int $booking_id Booking ID.
	 * @return object|false
	 */
	public static function get( $booking_id ) {
		global $wpdb;
		$table = Schema::get_bookings_table();

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mb_bookings WHERE id = %d LIMIT 1",
				absint( $booking_id )
			)
		);
	}

	/**
	 * Find booking by WooCommerce Order ID.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return object|false
	 */
	public static function get_by_order_id( $order_id ) {
		global $wpdb;
		$table = Schema::get_bookings_table();

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mb_bookings WHERE order_id = %d LIMIT 1",
				absint( $order_id )
			)
		);
	}

	/**
	 * Update booking status.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status New status ('pending', 'confirmed', 'cancelled', 'completed').
	 * @return bool
	 */
	public static function update_status( $booking_id, $status ) {
		global $wpdb;
		$table = Schema::get_bookings_table();

		$allowed = array( 'pending', 'confirmed', 'cancelled', 'completed' );
		$status  = sanitize_key( $status );

		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$result = $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'id' => absint( $booking_id ) ),
			array( '%s' ),
			array( '%d' )
		);

		do_action( 'mb_engine_booking_status_changed', $booking_id, $status );

		return false !== $result;
	}

	/**
	 * Update booking WooCommerce order ID.
	 *
	 * @param int $booking_id Booking ID.
	 * @param int $order_id WooCommerce order ID.
	 * @return bool
	 */
	public static function set_order_id( $booking_id, $order_id ) {
		global $wpdb;
		$table = Schema::get_bookings_table();

		return false !== $wpdb->update(
			$table,
			array( 'order_id' => absint( $order_id ) ),
			array( 'id' => absint( $booking_id ) ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Calculate total capacity currently booked in a given time interval.
	 *
	 * @param int    $entity_id Entity ID.
	 * @param string $start Interval start datetime (Y-m-d H:i:s).
	 * @param string $end Interval end datetime (Y-m-d H:i:s).
	 * @param int    $exclude_booking_id Optional booking ID to exclude.
	 * @return int Total seats/capacity already booked.
	 */
	public static function get_overlapping_capacity( $entity_id, $start, $end, $exclude_booking_id = 0 ) {
		global $wpdb;
		$table = Schema::get_bookings_table();

		$sql = "SELECT COALESCE(SUM(capacity_booked), 0) FROM {$wpdb->prefix}mb_bookings
			WHERE entity_id = %d
			  AND status IN ('pending', 'confirmed')
			  AND booking_start < %s
			  AND booking_end > %s";

		$params = array( absint( $entity_id ), $end, $start );

		if ( $exclude_booking_id > 0 ) {
			$sql     .= ' AND id != %d';
			$params[] = absint( $exclude_booking_id );
		}

		$prepared = $wpdb->prepare( $sql, $params );
		$total    = $wpdb->get_var( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return absint( $total );
	}

	/**
	 * Query multiple bookings with filtering and pagination.
	 *
	 * @param array $args Query parameters.
	 * @return array Array with 'items' and 'total'.
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		$table = Schema::get_bookings_table();

		$defaults = array(
			'entity_id' => 0,
			'status'    => '',
			'search'    => '',
			'orderby'   => 'id',
			'order'     => 'DESC',
			'per_page'  => 20,
			'page'      => 1,
		);

		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['entity_id'] ) ) {
			$where[]  = 'entity_id = %d';
			$params[] = absint( $args['entity_id'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_key( $args['status'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$search_like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]     = '(customer_name LIKE %s OR customer_email LIKE %s OR id = %d)';
			$params[]    = $search_like;
			$params[]    = $search_like;
			$params[]    = absint( $args['search'] );
		}

		$where_clause = implode( ' AND ', $where );

		$allowed_order = array( 'id', 'booking_start', 'created_at', 'total_price', 'status' );
		$orderby       = in_array( $args['orderby'], $allowed_order, true ) ? $args['orderby'] : 'id';
		$order         = ( 'ASC' === strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';

		$per_page = absint( $args['per_page'] );
		$offset   = ( absint( $args['page'] ) - 1 ) * $per_page;

		$count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}mb_bookings WHERE {$where_clause}";
		if ( ! empty( $params ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $params );
		}
		$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$items_sql = "SELECT * FROM {$wpdb->prefix}mb_bookings WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[]  = $per_page;
		$params[]  = $offset;
		$items     = $wpdb->get_results( $wpdb->prepare( $items_sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}
}
