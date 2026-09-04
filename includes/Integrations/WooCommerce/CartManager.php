<?php
/**
 * WooCommerce Cart and Checkout Manager.
 * Attaches booking parameters, applies dynamic pricing, and locks slots.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Integrations\WooCommerce;

use MyBookingEngine\Booking\MutexLock;
use MyBookingEngine\Booking\SlotEngine;
use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CartManager
 */
class CartManager {

	/**
	 * Initialize cart hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'set_custom_cart_price' ), 10, 1 );
		add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'validate_cart_items' ) );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_order_line_item_meta' ), 10, 4 );
		add_action( 'woocommerce_remove_cart_item', array( __CLASS__, 'on_remove_cart_item' ), 10, 2 );
	}

	/**
	 * Attach booking data to cart item.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id Product ID.
	 * @param int   $variation_id Variation ID.
	 * @return array
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( ! isset( $_POST['mb_booking_entity_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $cart_item_data;
		}

		$entity_id = absint( $_POST['mb_booking_entity_id'] );
		$start     = sanitize_text_field( wp_unslash( $_POST['mb_booking_start'] ?? '' ) );
		$end       = sanitize_text_field( wp_unslash( $_POST['mb_booking_end'] ?? '' ) );
		$capacity  = max( 1, absint( $_POST['mb_booking_capacity'] ?? 1 ) );
		$token     = sanitize_text_field( wp_unslash( $_POST['mb_session_token'] ?? '' ) );

		if ( empty( $token ) ) {
			$token = WC()->session->get_customer_id();
		}

		$entity = new BookingEntity( $entity_id );
		$price  = SlotEngine::calculate_price( $entity, $start, $end, $capacity );

		// Acquire mutex lock.
		MutexLock::acquire_lock( $entity_id, $start, $end, $token );

		$cart_item_data['mb_booking'] = array(
			'entity_id'     => $entity_id,
			'title'         => $entity->get_title(),
			'model'         => $entity->get_model_type(),
			'booking_start' => $start,
			'booking_end'   => $end,
			'capacity'      => $capacity,
			'price'         => $price,
			'token'         => $token,
		);

		// Make item unique in cart so multiple slots don't merge into quantity.
		$cart_item_data['unique_key'] = md5( "{$entity_id}_{$start}_{$end}_{$token}" );

		return $cart_item_data;
	}

	/**
	 * Display booking metadata in Cart and Checkout.
	 *
	 * @param array $item_data Existing item data.
	 * @param array $cart_item Cart item row.
	 * @return array
	 */
	public static function display_cart_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['mb_booking'] ) ) {
			return $item_data;
		}

		$b = $cart_item['mb_booking'];

		$item_data[] = array(
			'name'  => __( 'Booking Item', 'my-booking-engine' ),
			'value' => esc_html( $b['title'] ),
		);

		$item_data[] = array(
			'name'  => __( 'Starts', 'my-booking-engine' ),
			'value' => esc_html( $b['booking_start'] ),
		);

		$item_data[] = array(
			'name'  => __( 'Ends', 'my-booking-engine' ),
			'value' => esc_html( $b['booking_end'] ),
		);

		if ( $b['capacity'] > 1 ) {
			$item_data[] = array(
				'name'  => __( 'Guests / Spots', 'my-booking-engine' ),
				'value' => esc_html( $b['capacity'] ),
			);
		}

		return $item_data;
	}

	/**
	 * Override cart item price with calculated booking rate.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public static function set_custom_cart_price( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! empty( $cart_item['mb_booking']['price'] ) ) {
				$cart_item['data']->set_price( floatval( $cart_item['mb_booking']['price'] ) );
			}
		}
	}

	/**
	 * Validate cart items before checkout (verify lock and capacity).
	 *
	 * @return void
	 */
	public static function validate_cart_items() {
		if ( ! WC()->cart ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( empty( $cart_item['mb_booking'] ) ) {
				continue;
			}

			$b = $cart_item['mb_booking'];

			if ( MutexLock::is_locked( $b['entity_id'], $b['booking_start'], $b['booking_end'], $b['token'] ) ) {
				wc_add_notice(
					/* translators: %s: item title */
					sprintf( __( 'The slot for "%s" is no longer available. Please select another time.', 'my-booking-engine' ), $b['title'] ),
					'error'
				);
			}
		}
	}

	/**
	 * Persist booking metadata to WooCommerce order item.
	 *
	 * @param \WC_Order_Item_Product $item Order line item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values Cart item values.
	 * @param \WC_Order              $order Order object.
	 * @return void
	 */
	public static function save_order_line_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['mb_booking'] ) ) {
			return;
		}

		$b = $values['mb_booking'];

		$item->add_meta_data( '_mb_entity_id', $b['entity_id'] );
		$item->add_meta_data( '_mb_booking_start', $b['booking_start'] );
		$item->add_meta_data( '_mb_booking_end', $b['booking_end'] );
		$item->add_meta_data( '_mb_capacity', $b['capacity'] );
		$item->add_meta_data( '_mb_total_price', $b['price'] );

		// User-friendly visible order item meta.
		$item->add_meta_data( __( 'Booking Start', 'my-booking-engine' ), $b['booking_start'] );
		$item->add_meta_data( __( 'Booking End', 'my-booking-engine' ), $b['booking_end'] );
		if ( $b['capacity'] > 1 ) {
			$item->add_meta_data( __( 'Spots', 'my-booking-engine' ), $b['capacity'] );
		}
	}

	/**
	 * Release mutex lock when an item is removed from the cart.
	 *
	 * @param string   $cart_item_key Cart item key.
	 * @param \WC_Cart $cart Cart instance.
	 * @return void
	 */
	public static function on_remove_cart_item( $cart_item_key, $cart ) {
		$cart_item = $cart->get_cart_item( $cart_item_key );
		if ( ! empty( $cart_item['mb_booking'] ) ) {
			$b = $cart_item['mb_booking'];
			MutexLock::release_lock( $b['entity_id'], $b['booking_start'], $b['booking_end'], $b['token'] );
		}
	}
}
