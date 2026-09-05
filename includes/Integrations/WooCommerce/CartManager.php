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
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hide_internal_order_itemmeta' ) );
		add_filter( 'woocommerce_order_item_display_meta_key', array( __CLASS__, 'filter_display_meta_key' ), 10, 3 );
		add_filter( 'woocommerce_order_item_display_meta_value', array( __CLASS__, 'filter_display_meta_value' ), 10, 3 );
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
		// If booking data was already passed directly via WC()->cart->add_to_cart($id, 1, 0, [], $cart_item_data).
		if ( ! empty( $cart_item_data['mb_booking'] ) ) {
			$b = $cart_item_data['mb_booking'];
			if ( ! empty( $b['entity_id'] ) && ! empty( $b['booking_start'] ) && ! empty( $b['booking_end'] ) && ! empty( $b['token'] ) ) {
				MutexLock::acquire_lock( $b['entity_id'], $b['booking_start'], $b['booking_end'], $b['token'] );
			}
			return $cart_item_data;
		}

		if ( ! isset( $_POST['mb_booking_entity_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $cart_item_data;
		}

		$entity_id  = absint( $_POST['mb_booking_entity_id'] );
		$booking_id = isset( $_POST['mb_booking_id'] ) ? absint( $_POST['mb_booking_id'] ) : 0;
		$start      = sanitize_text_field( wp_unslash( $_POST['mb_booking_start'] ?? '' ) );
		$end        = sanitize_text_field( wp_unslash( $_POST['mb_booking_end'] ?? '' ) );
		$capacity   = max( 1, absint( $_POST['mb_booking_capacity'] ?? 1 ) );
		$token      = sanitize_text_field( wp_unslash( $_POST['mb_session_token'] ?? '' ) );

		if ( empty( $token ) && function_exists( 'WC' ) && WC()->session ) {
			$token = WC()->session->get_customer_id();
		}

		$entity = new BookingEntity( $entity_id );
		$price  = SlotEngine::calculate_price( $entity, $start, $end, $capacity );

		// Acquire mutex lock.
		MutexLock::acquire_lock( $entity_id, $start, $end, $token );

		$cart_item_data['mb_booking'] = array(
			'booking_id'    => $booking_id,
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
	 * Display human-readable booking metadata in Cart and Checkout.
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
			'name'  => __( 'Listing', 'my-booking-engine' ),
			'value' => esc_html( $b['title'] ),
		);

		$item_data[] = array(
			'name'  => __( 'Booking Start', 'my-booking-engine' ),
			'value' => esc_html( $b['booking_start'] ),
		);

		$item_data[] = array(
			'name'  => __( 'Booking End', 'my-booking-engine' ),
			'value' => esc_html( $b['booking_end'] ),
		);

		$cap_num   = max( 1, absint( $b['capacity'] ?? 1 ) );
		$cap_label = ( 1 === $cap_num ) ? __( 'Spot / Guest', 'my-booking-engine' ) : __( 'Spots / Guests', 'my-booking-engine' );
		$item_data[] = array(
			'name'  => __( 'Capacity', 'my-booking-engine' ),
			'value' => esc_html( $cap_num . ' ' . $cap_label ),
		);

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
	 * Persist booking metadata to WooCommerce order item with human-readable labels.
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

		// Internal technical meta keys (hidden from default displays).
		if ( ! empty( $b['booking_id'] ) ) {
			$item->add_meta_data( '_mb_booking_id', absint( $b['booking_id'] ) );
		}
		$item->add_meta_data( '_mb_entity_id', $b['entity_id'] );
		$item->add_meta_data( '_mb_booking_start', $b['booking_start'] );
		$item->add_meta_data( '_mb_booking_end', $b['booking_end'] );
		$item->add_meta_data( '_mb_capacity', max( 1, absint( $b['capacity'] ) ) );
		$item->add_meta_data( '_mb_total_price', $b['price'] );

		// Clean, human-readable visible order item metadata.
		$cap_num   = max( 1, absint( $b['capacity'] ) );
		$cap_label = ( 1 === $cap_num ) ? __( 'Spot / Guest', 'my-booking-engine' ) : __( 'Spots / Guests', 'my-booking-engine' );
		$item->add_meta_data( __( 'Capacity', 'my-booking-engine' ), $cap_num . ' ' . $cap_label );
		$item->add_meta_data( __( 'Booking Start', 'my-booking-engine' ), $b['booking_start'] );
		$item->add_meta_data( __( 'Booking End', 'my-booking-engine' ), $b['booking_end'] );
	}

	/**
	 * Hide internal technical meta keys from WooCommerce order item displays.
	 *
	 * @param array $hidden Existing hidden meta keys.
	 * @return array
	 */
	public static function hide_internal_order_itemmeta( $hidden ) {
		$hidden[] = '_mb_booking_id';
		$hidden[] = '_mb_entity_id';
		$hidden[] = '_mb_booking_start';
		$hidden[] = '_mb_booking_end';
		$hidden[] = '_mb_capacity';
		$hidden[] = '_mb_total_price';
		$hidden[] = '_mb_session_token';
		return $hidden;
	}

	/**
	 * Transform any internal technical meta keys into human-readable labels
	 * in case an admin screen, email, or third-party extension displays raw line item keys.
	 *
	 * @param string        $display_key Displayed key string.
	 * @param \WC_Meta_Data|null $meta Meta object.
	 * @param \WC_Order_Item|null $item Order item.
	 * @return string
	 */
	public static function filter_display_meta_key( $display_key, $meta = null, $item = null ) {
		$raw_key = ( $meta && is_object( $meta ) && isset( $meta->key ) ) ? $meta->key : $display_key;

		switch ( $raw_key ) {
			case '_mb_capacity':
			case 'mb_capacity':
			case 'capacity':
				return __( 'Capacity', 'my-booking-engine' );

			case '_mb_booking_start':
			case 'mb_booking_start':
			case 'booking_start':
				return __( 'Booking Start', 'my-booking-engine' );

			case '_mb_booking_end':
			case 'mb_booking_end':
			case 'booking_end':
				return __( 'Booking End', 'my-booking-engine' );

			case '_mb_entity_id':
			case 'mb_entity_id':
			case 'entity_id':
				return __( 'Listing', 'my-booking-engine' );

			case '_mb_total_price':
			case 'mb_total_price':
			case 'total_price':
				return __( 'Total Price', 'my-booking-engine' );

			case '_mb_booking_id':
			case 'mb_booking_id':
			case 'booking_id':
				return __( 'Booking ID', 'my-booking-engine' );

			default:
				return $display_key;
		}
	}

	/**
	 * Format technical meta values into human-readable text.
	 *
	 * @param string        $display_value Displayed value string.
	 * @param \WC_Meta_Data|null $meta Meta object.
	 * @param \WC_Order_Item|null $item Order item.
	 * @return string
	 */
	public static function filter_display_meta_value( $display_value, $meta = null, $item = null ) {
		if ( ! $meta || ! is_object( $meta ) || ! isset( $meta->key ) ) {
			return $display_value;
		}

		$raw_key = $meta->key;

		if ( in_array( $raw_key, array( '_mb_capacity', 'mb_capacity', 'capacity' ), true ) ) {
			$cap_num   = max( 1, absint( $meta->value ) );
			$cap_label = ( 1 === $cap_num ) ? __( 'Spot / Guest', 'my-booking-engine' ) : __( 'Spots / Guests', 'my-booking-engine' );
			return $cap_num . ' ' . $cap_label;
		}

		if ( in_array( $raw_key, array( '_mb_entity_id', 'mb_entity_id', 'entity_id' ), true ) ) {
			$title = get_the_title( absint( $meta->value ) );
			return ! empty( $title ) ? $title : '#' . absint( $meta->value );
		}

		return $display_value;
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
