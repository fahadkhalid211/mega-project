<?php
/**
 * WooCommerce Order Synchronization Listener.
 * Synchronizes WC order statuses with custom database bookings table.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Integrations\WooCommerce;

use MyBookingEngine\Models\Booking;
use MyBookingEngine\Booking\MutexLock;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OrderSync
 */
class OrderSync {

	/**
	 * Initialize order hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// Classic WooCommerce checkout
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_checkout_order_processed' ), 10, 3 );
		// Modern WooCommerce Blocks checkout (Store API)
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'on_store_api_order_processed' ), 10, 1 );
		// Universal new order hook (all gateways, REST API, manual orders)
		add_action( 'woocommerce_new_order', array( __CLASS__, 'on_new_order' ), 10, 2 );
		// Thank you page backup sync
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'on_thankyou' ), 10, 1 );

		// Status changes
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'on_order_confirmed' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'on_order_confirmed' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'on_order_cancelled' ), 10, 1 );
		add_action( 'woocommerce_order_status_refunded', array( __CLASS__, 'on_order_cancelled' ), 10, 1 );
		add_action( 'woocommerce_order_status_failed', array( __CLASS__, 'on_order_cancelled' ), 10, 1 );
	}

	/**
	 * Handler for woocommerce_checkout_order_processed.
	 *
	 * @param int            $order_id WooCommerce Order ID.
	 * @param array          $posted_data Form POST data.
	 * @param \WC_Order|null $order WC Order object.
	 * @return void
	 */
	public static function on_checkout_order_processed( $order_id, $posted_data = array(), $order = null ) {
		self::sync_order( $order_id, $order );
	}

	/**
	 * Handler for modern WooCommerce Blocks checkout.
	 *
	 * @param \WC_Order $order WC Order object.
	 * @return void
	 */
	public static function on_store_api_order_processed( $order ) {
		if ( $order && is_a( $order, 'WC_Order' ) ) {
			self::sync_order( $order->get_id(), $order );
		}
	}

	/**
	 * Handler for universal new order hook.
	 *
	 * @param int            $order_id WooCommerce Order ID.
	 * @param \WC_Order|null $order WC Order object.
	 * @return void
	 */
	public static function on_new_order( $order_id, $order = null ) {
		self::sync_order( $order_id, $order );
	}

	/**
	 * Handler for thank you page.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return void
	 */
	public static function on_thankyou( $order_id ) {
		self::sync_order( $order_id );
	}

	/**
	 * Synchronize WooCommerce order with the custom database bookings table.
	 *
	 * @param int            $order_id WooCommerce Order ID.
	 * @param \WC_Order|null $order WC Order object.
	 * @return void
	 */
	public static function sync_order( $order_id, $order = null ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			if ( ! function_exists( 'wc_get_order' ) ) {
				return;
			}
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		$order_status   = $order->get_status();
		$booking_status = in_array( $order_status, array( 'processing', 'completed' ), true )
			? 'confirmed'
			: ( in_array( $order_status, array( 'cancelled', 'refunded', 'failed' ), true ) ? 'cancelled' : 'pending' );

		$customer_id    = $order->get_customer_id();
		$customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$customer_email = $order->get_billing_email();
		$customer_phone = $order->get_billing_phone();

		foreach ( $order->get_items() as $item_id => $item ) {
			$entity_id = $item->get_meta( '_mb_entity_id' );
			if ( empty( $entity_id ) ) {
				continue;
			}

			$existing_booking_id = absint( $item->get_meta( '_mb_booking_id' ) );
			$start    = $item->get_meta( '_mb_booking_start' );
			$end      = $item->get_meta( '_mb_booking_end' );
			$capacity = max( 1, absint( $item->get_meta( '_mb_capacity' ) ) );
			$price    = floatval( $item->get_meta( '_mb_total_price' ) ?: $item->get_total() );

			// 1. If this booking was pre-created in process_booking(), update it with order_id and billing customer.
			if ( $existing_booking_id ) {
				global $wpdb;
				$table = \MyBookingEngine\Database\Schema::get_bookings_table();
				$update_fields = array(
					'order_id' => absint( $order_id ),
					'status'   => $booking_status,
				);
				$update_formats = array( '%d', '%s' );

				if ( ! empty( $customer_name ) ) {
					$update_fields['customer_name'] = $customer_name;
					$update_formats[] = '%s';
				}
				if ( ! empty( $customer_email ) ) {
					$update_fields['customer_email'] = sanitize_email( $customer_email );
					$update_formats[] = '%s';
				}
				if ( ! empty( $customer_phone ) ) {
					$update_fields['customer_phone'] = sanitize_text_field( $customer_phone );
					$update_formats[] = '%s';
				}
				if ( $customer_id ) {
					$update_fields['customer_id'] = absint( $customer_id );
					$update_formats[] = '%d';
				}

				$wpdb->update(
					$table,
					$update_fields,
					array( 'id' => $existing_booking_id ),
					$update_formats,
					array( '%d' )
				);

				MutexLock::release_lock( absint( $entity_id ), $start, $end );
				continue;
			}

			// 2. Check if a booking already exists for this order.
			$already = Booking::get_by_order_id( $order_id );
			if ( $already ) {
				Booking::update_status( $already->id, $booking_status );
				MutexLock::release_lock( $already->entity_id, $already->booking_start, $already->booking_end );
				continue;
			}

			// 3. Fallback: Create the booking record if not pre-created.
			$booking_id = Booking::create(
				array(
					'entity_id'       => absint( $entity_id ),
					'customer_id'     => absint( $customer_id ),
					'customer_name'   => ! empty( $customer_name ) ? $customer_name : __( 'Guest', 'my-booking-engine' ),
					'customer_email'  => sanitize_email( $customer_email ),
					'customer_phone'  => sanitize_text_field( $customer_phone ),
					'booking_start'   => $start,
					'booking_end'     => $end,
					'capacity_booked' => $capacity,
					'status'          => $booking_status,
					'order_id'        => absint( $order_id ),
					'total_price'     => $price,
				)
			);

			if ( $booking_id ) {
				$item->update_meta_data( '_mb_booking_id', $booking_id );
				$item->save();
				MutexLock::release_lock( absint( $entity_id ), $start, $end );
				do_action( 'mb_engine_booking_created', $booking_id );
			}
		}
	}

	/**
	 * Mark booking confirmed when order is paid/processing/completed.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return void
	 */
	public static function on_order_confirmed( $order_id ) {
		$booking = Booking::get_by_order_id( $order_id );
		if ( $booking ) {
			Booking::update_status( $booking->id, 'confirmed' );
			MutexLock::release_lock( $booking->entity_id, $booking->booking_start, $booking->booking_end );
		}
	}

	/**
	 * Mark booking cancelled when order is cancelled or refunded.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return void
	 */
	public static function on_order_cancelled( $order_id ) {
		$booking = Booking::get_by_order_id( $order_id );
		if ( $booking ) {
			Booking::update_status( $booking->id, 'cancelled' );
			MutexLock::release_lock( $booking->entity_id, $booking->booking_start, $booking->booking_end );
		}
	}
}
