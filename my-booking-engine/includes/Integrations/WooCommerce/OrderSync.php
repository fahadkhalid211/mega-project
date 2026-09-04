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
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_order_created' ), 10, 3 );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'on_order_confirmed' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'on_order_confirmed' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'on_order_cancelled' ), 10, 1 );
		add_action( 'woocommerce_order_status_refunded', array( __CLASS__, 'on_order_cancelled' ), 10, 1 );
	}

	/**
	 * Handle order creation at checkout.
	 *
	 * @param int       $order_id WooCommerce Order ID.
	 * @param array     $posted_data Form POST data.
	 * @param \WC_Order $order WC Order object.
	 * @return void
	 */
	public static function on_order_created( $order_id, $posted_data, $order ) {
		foreach ( $order->get_items() as $item_id => $item ) {
			$entity_id = $item->get_meta( '_mb_entity_id' );
			if ( empty( $entity_id ) ) {
				continue;
			}

			$start    = $item->get_meta( '_mb_booking_start' );
			$end      = $item->get_meta( '_mb_booking_end' );
			$capacity = $item->get_meta( '_mb_capacity' );
			$price    = $item->get_meta( '_mb_total_price' );

			$customer_id    = $order->get_customer_id();
			$customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			$customer_email = $order->get_billing_email();
			$customer_phone = $order->get_billing_phone();

			Booking::create(
				array(
					'entity_id'       => absint( $entity_id ),
					'customer_id'     => absint( $customer_id ),
					'customer_name'   => ! empty( $customer_name ) ? $customer_name : __( 'Guest', 'my-booking-engine' ),
					'customer_email'  => sanitize_email( $customer_email ),
					'customer_phone'  => sanitize_text_field( $customer_phone ),
					'booking_start'   => $start,
					'booking_end'     => $end,
					'capacity_booked' => max( 1, absint( $capacity ) ),
					'status'          => 'pending',
					'order_id'        => absint( $order_id ),
					'total_price'     => floatval( $price ),
				)
			);
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
