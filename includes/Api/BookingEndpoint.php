<?php
/**
 * REST Endpoint for Initiating and Confirming Bookings.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Api;

use MyBookingEngine\Booking\MutexLock;
use MyBookingEngine\Booking\SlotEngine;
use MyBookingEngine\Models\BookingEntity;
use MyBookingEngine\Models\Booking;
use MyBookingEngine\Accounts\CustomerAccounts;
use MyBookingEngine\Integrations\WooCommerce\ProductType as WcProductType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BookingEndpoint
 */
class BookingEndpoint extends RestController {

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/book',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process_booking' ),
					'permission_callback' => array( $this, 'public_permission_check' ),
					'args'                => array(
						'entity_id'      => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'start_time'     => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'booking_start'  => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'end_time'       => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'booking_end'    => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'capacity'       => array(
							'sanitize_callback' => 'absint',
							'default'           => 1,
						),
						'customer_name'  => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'customer_email' => array(
							'sanitize_callback' => 'sanitize_email',
							'default'           => '',
						),
						'customer_phone' => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'session_token'  => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
					),
				),
			)
		);
	}

	/**
	 * Process incoming booking reservation.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function process_booking( $request ) {
		$entity_id      = absint( $request->get_param( 'entity_id' ) );
		$start_time     = sanitize_text_field( $request->get_param( 'start_time' ) ?: $request->get_param( 'booking_start' ) );
		$end_time       = sanitize_text_field( $request->get_param( 'end_time' ) ?: $request->get_param( 'booking_end' ) );
		$capacity       = max( 1, absint( $request->get_param( 'capacity' ) ) );
		$customer_name  = sanitize_text_field( $request->get_param( 'customer_name' ) );
		$customer_email = sanitize_email( $request->get_param( 'customer_email' ) );
		$customer_phone = sanitize_text_field( $request->get_param( 'customer_phone' ) );
		$session_token  = sanitize_text_field( $request->get_param( 'session_token' ) );

		if ( empty( $start_time ) || empty( $end_time ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Please select valid start and end dates/times for your reservation.', 'my-booking-engine' ),
				),
				400
			);
		}

		if ( 'mb_booking_entity' !== get_post_type( $entity_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid booking entity specified.', 'my-booking-engine' ),
				),
				400
			);
		}

		if ( empty( $session_token ) ) {
			$session_token = wp_generate_password( 24, false );
		}

		$entity = new BookingEntity( $entity_id );

		// 1. Verify availability and capacity.
		$already_booked = Booking::get_overlapping_capacity( $entity_id, $start_time, $end_time );
		$max_capacity   = $entity->get_capacity();

		if ( ( $already_booked + $capacity ) > $max_capacity ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Selected slot is no longer available. Please select another time.', 'my-booking-engine' ),
				),
				409
			);
		}

		// 2. Concurrency Mutex Lock check and acquire.
		$locked = MutexLock::acquire_lock( $entity_id, $start_time, $end_time, $session_token );
		if ( ! $locked ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Another user is currently booking this slot. Please try again shortly.', 'my-booking-engine' ),
				),
				409
			);
		}

		$calculated_price = SlotEngine::calculate_price( $entity, $start_time, $end_time, $capacity );

		// Resolve (or auto-create) a WordPress account for this customer if email is provided.
		$customer_id = get_current_user_id();
		if ( ! $customer_id && ! empty( $customer_email ) ) {
			$account     = CustomerAccounts::get_or_create_customer( $customer_name, $customer_email );
			$customer_id = $account['user_id'];
		}

		// 3. Immediately persist booking into mb_bookings with status 'pending' (or 'confirmed' if free)
		// so it instantly appears on the WordPress Admin Bookings page under All Bookings and Pending,
		// regardless of whether WooCommerce checkout is completed, abandoned, or bypassed.
		$booking_id = Booking::create(
			array(
				'entity_id'       => $entity_id,
				'customer_id'     => $customer_id,
				'customer_name'   => ! empty( $customer_name ) ? $customer_name : __( 'Guest', 'my-booking-engine' ),
				'customer_email'  => ! empty( $customer_email ) ? sanitize_email( $customer_email ) : '',
				'customer_phone'  => ! empty( $customer_phone ) ? sanitize_text_field( $customer_phone ) : '',
				'booking_start'   => $start_time,
				'booking_end'     => $end_time,
				'capacity_booked' => $capacity,
				'status'          => ( $calculated_price > 0 ) ? 'pending' : 'confirmed',
				'order_id'        => 0,
				'total_price'     => $calculated_price,
			)
		);

		if ( ! $booking_id ) {
			MutexLock::release_lock( $entity_id, $start_time, $end_time, $session_token );
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to record booking. Please try again.', 'my-booking-engine' ),
				),
				500
			);
		}

		do_action( 'mb_engine_booking_created', $booking_id );

		// 4. WooCommerce Integration check.
		$settings = get_option( 'mb_engine_settings', array() );
		$wc_mode  = ( 'yes' === ( $settings['enable_woocommerce'] ?? 'yes' ) ) && class_exists( 'WooCommerce' );

		if ( $wc_mode && function_exists( 'wc_load_cart' ) ) {
			// WooCommerce only auto-initializes WC()->cart / WC()->session on
			// a normal frontend page load (via the 'wp_loaded' hook). REST
			// API requests never trigger that, so WC()->cart is null here
			// and the check below would silently fail. wc_load_cart() is
			// WooCommerce's own helper for bringing session/cart up.
			wc_load_cart();
		}

		if ( $wc_mode && function_exists( 'WC' ) && WC()->cart && $calculated_price > 0 ) {
			$wc_product_id = WcProductType::get_or_create_product( $entity_id );
			if ( $wc_product_id ) {
				// Attach complete booking metadata directly into cart item data.
				$cart_item_data = array(
					'mb_booking' => array(
						'booking_id'    => $booking_id,
						'entity_id'     => $entity_id,
						'title'         => $entity->get_title(),
						'model'         => $entity->get_model_type(),
						'booking_start' => $start_time,
						'booking_end'   => $end_time,
						'capacity'      => $capacity,
						'price'         => $calculated_price,
						'token'         => $session_token,
					),
					'unique_key' => md5( "{$entity_id}_{$start_time}_{$end_time}_{$session_token}" ),
				);

				// Inject booking data into $_POST for legacy hook compatibility.
				$_POST['mb_booking_id']        = $booking_id;
				$_POST['mb_booking_entity_id'] = $entity_id;
				$_POST['mb_booking_start']     = $start_time;
				$_POST['mb_booking_end']       = $end_time;
				$_POST['mb_booking_capacity']  = $capacity;
				$_POST['mb_session_token']     = $session_token;

				$cart_item_key = WC()->cart->add_to_cart( $wc_product_id, 1, 0, array(), $cart_item_data );

				if ( $cart_item_key ) {
					return rest_ensure_response(
						array(
							'success'       => true,
							'booking_id'    => $booking_id,
							'redirect_type' => 'woocommerce_checkout',
							'redirect_url'  => esc_url_raw( wc_get_checkout_url() ),
							'cart_url'      => esc_url_raw( wc_get_cart_url() ),
							'message'       => __( 'Booking reserved. Redirecting to checkout...', 'my-booking-engine' ),
						)
					);
				}

				// If add_to_cart() failed, surface reason.
				$wc_errors = array();
				if ( function_exists( 'wc_get_notices' ) ) {
					foreach ( wc_get_notices( 'error' ) as $notice ) {
						$wc_errors[] = is_array( $notice ) ? ( $notice['notice'] ?? '' ) : $notice;
					}
					wc_clear_notices();
				}

				return new \WP_REST_Response(
					array(
						'success'    => false,
						'booking_id' => $booking_id,
						'message'    => ! empty( $wc_errors )
							? implode( ' ', $wc_errors )
							: __( 'Could not add this booking to the WooCommerce cart.', 'my-booking-engine' ),
					),
					500
				);
			}
		}

		// 5. Standalone Confirmation Mode (if WooCommerce is not enabled or free booking).
		return rest_ensure_response(
			array(
				'success'       => true,
				'booking_id'    => $booking_id,
				'redirect_type' => 'confirmation',
				'message'       => __( 'Your booking has been received successfully!', 'my-booking-engine' ),
			)
		);
	}

	/**
	 * AJAX fallback endpoint for booking creation (handles hosts where REST is cached or blocked).
	 *
	 * @return void
	 */
	public static function ajax_create_booking() {
		$raw_body = file_get_contents( 'php://input' );
		$payload  = array();
		if ( ! empty( $raw_body ) ) {
			$payload = json_decode( $raw_body, true ) ?: array();
		}
		if ( empty( $payload ) && ! empty( $_POST ) ) {
			$payload = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		$request = new \WP_REST_Request( 'POST', '/my-booking-engine/v1/book' );
		foreach ( $payload as $k => $v ) {
			$request->set_param( $k, $v );
		}

		$endpoint = new self();
		$response = $endpoint->process_booking( $request );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ), 400 );
		}

		$status   = $response->get_status();
		$res_data = $response->get_data();

		if ( $status >= 200 && $status < 300 && ( ! isset( $res_data['success'] ) || $res_data['success'] ) ) {
			wp_send_json_success( $res_data );
		} else {
			wp_send_json_error( $res_data, $status );
		}
	}
}
