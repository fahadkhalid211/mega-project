<?php
/**
 * REST Endpoint for Fetching Slots and Real-time Availability.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Api;

use MyBookingEngine\Booking\SlotEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SlotsEndpoint
 */
class SlotsEndpoint extends RestController {

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/slots',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_slots' ),
					'permission_callback' => array( $this, 'public_permission_check' ),
					'args'                => array(
						'entity_id'     => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'date'          => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'end_date'      => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'session_token' => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
					),
				),
			)
		);
	}

	/**
	 * Handle slots request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_slots( $request ) {
		$entity_id     = absint( $request->get_param( 'entity_id' ) );
		$date          = sanitize_text_field( $request->get_param( 'date' ) );
		$end_date      = sanitize_text_field( $request->get_param( 'end_date' ) );
		$session_token = sanitize_text_field( $request->get_param( 'session_token' ) );

		if ( empty( $date ) ) {
			$date = current_time( 'Y-m-d' );
		}

		if ( ! $entity_id || 'mb_booking_entity' !== get_post_type( $entity_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => sprintf(
						/* translators: %d: entity ID received */
						__( 'Invalid booking entity (id received: %d).', 'my-booking-engine' ),
						$entity_id
					),
				),
				404
			);
		}

		// Never let a fatal in the slot-calculation chain surface as an
		// HTML error page — the frontend can't parse that as JSON and
		// just shows "could not check availability" with no clue why.
		// Catch it and return the real message instead.
		try {
			$result = SlotEngine::get_slots( $entity_id, $date, $end_date, $session_token );
		} catch ( \Throwable $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
					'debug'   => array(
						'file' => $e->getFile(),
						'line' => $e->getLine(),
					),
				),
				500
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $result,
			)
		);
	}
}
