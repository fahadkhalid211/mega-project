<?php
/**
 * WooCommerce Custom Product Type Integration.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Integrations\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProductType
 */
class ProductType {

	/**
	 * Initialize WooCommerce Product Type hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'product_type_selector', array( __CLASS__, 'add_product_type' ) );
		add_action( 'init', array( __CLASS__, 'register_wc_product_class' ) );
	}

	/**
	 * Add 'booking_entity' to WooCommerce product types dropdown.
	 *
	 * @param array $types Product types.
	 * @return array
	 */
	public static function add_product_type( $types ) {
		$types['booking_entity'] = __( 'Booking Entity', 'my-booking-engine' );
		return $types;
	}

	/**
	 * Register the WC_Product_Booking_Entity class if WooCommerce is active.
	 *
	 * PHP does not allow a class to be declared inside another class's
	 * method body, so the actual declaration lives at the bottom of this
	 * file, guarded so it only loads once WooCommerce's WC_Product exists.
	 *
	 * @return void
	 */
	public static function register_wc_product_class() {
		mb_engine_register_wc_product_booking_entity_class();
	}

	/**
	 * Get or create a shadow WooCommerce product ID for an entity.
	 *
	 * @param int $entity_id Entity post ID.
	 * @return int WooCommerce product ID.
	 */
	public static function get_or_create_product( $entity_id ) {
		$product_id = get_post_meta( $entity_id, '_mb_wc_product_id', true );
		if ( ! empty( $product_id ) && 'product' === get_post_type( $product_id ) ) {
			return absint( $product_id );
		}

		// Create a virtual WooCommerce product linked to this booking entity.
		$entity = get_post( $entity_id );
		if ( ! $entity ) {
			return 0;
		}

		$new_product = new \WC_Product_Simple();
		$new_product->set_name( $entity->post_title );
		$new_product->set_status( 'publish' );
		$new_product->set_catalog_visibility( 'hidden' );
		$new_product->set_virtual( true );
		$new_product->set_sold_individually( true );
		$new_product->set_price( floatval( get_post_meta( $entity_id, '_mb_base_price', true ) ) );
		$new_product->set_regular_price( floatval( get_post_meta( $entity_id, '_mb_base_price', true ) ) );

		$new_id = $new_product->save();
		if ( $new_id ) {
			update_post_meta( $entity_id, '_mb_wc_product_id', $new_id );
			update_post_meta( $new_id, '_mb_linked_entity_id', $entity_id );
			return $new_id;
		}

		return 0;
	}
}

/**
 * Declare the WC_Product_Booking_Entity class at file scope.
 *
 * A class cannot be declared inside another class's method body (PHP fatal
 * error: "Class declarations may not be nested"), so this lazy-loader lives
 * outside the ProductType class and is only invoked once WooCommerce is
 * confirmed active.
 *
 * @return void
 */
function mb_engine_register_wc_product_booking_entity_class() {
	if ( ! class_exists( 'WC_Product' ) || class_exists( 'WC_Product_Booking_Entity' ) ) {
		return;
	}

	/**
	 * Class WC_Product_Booking_Entity
	 */
	class WC_Product_Booking_Entity extends \WC_Product {

		/**
		 * Get internal product type.
		 *
		 * @return string
		 */
		public function get_type() {
			return 'booking_entity';
		}

		/**
		 * Booking products are virtual by default.
		 *
		 * @return bool
		 */
		public function is_virtual() {
			return true;
		}

		/**
		 * Booking products are sold individually (1 slot per cart line).
		 *
		 * @return bool
		 */
		public function is_sold_individually() {
			return true;
		}
	}
}
