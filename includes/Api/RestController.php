<?php
/**
 * Base REST Controller.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RestController
 */
abstract class RestController extends \WP_REST_Controller {

	/**
	 * Namespace for all endpoints.
	 *
	 * @var string
	 */
	protected $namespace = 'my-booking-engine/v1';

	/**
	 * Permission callback for public endpoints.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function public_permission_check( $request ) {
		return true;
	}

	/**
	 * Permission callback for authenticated users.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function user_permission_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You must be logged in to access this endpoint.', 'my-booking-engine' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Permission callback for admin managers.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function admin_permission_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Insufficient permissions to perform this action.', 'my-booking-engine' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}
}
