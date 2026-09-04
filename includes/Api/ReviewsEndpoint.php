<?php
/**
 * REST Endpoint for Reviews and Ratings.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Api;

use MyBookingEngine\Models\Review;
use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ReviewsEndpoint
 */
class ReviewsEndpoint extends RestController {

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/reviews',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_reviews' ),
					'permission_callback' => array( $this, 'public_permission_check' ),
					'args'                => array(
						'entity_id' => array(
							'required'          => true,
							'validate_callback' => function( $param ) {
								return is_numeric( $param ) && absint( $param ) > 0;
							},
						),
						'limit'     => array(
							'default'           => 10,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'submit_review' ),
					'permission_callback' => array( $this, 'public_permission_check' ),
					'args'                => array(
						'entity_id'      => array(
							'required' => true,
							'validate_callback' => function( $param ) {
								return is_numeric( $param ) && absint( $param ) > 0;
							},
						),
						'customer_name'  => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'customer_email' => array(
							'sanitize_callback' => 'sanitize_email',
						),
						'booking_id'     => array(
							'sanitize_callback' => 'absint',
						),
						'rating'         => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'title'          => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'content'        => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Get reviews and statistics for an entity.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_reviews( $request ) {
		$entity_id = absint( $request->get_param( 'entity_id' ) );
		$limit     = absint( $request->get_param( 'limit' ) );
		if ( $limit <= 0 ) {
			$limit = 10;
		}

		$entity = new BookingEntity( $entity_id );
		$stats  = Review::get_stats( $entity_id );
		$items  = $entity->get_reviews( $limit );

		$formatted_items = array();
		foreach ( $items as $rev ) {
			$formatted_items[] = array(
				'id'            => (int) $rev->id,
				'customer_name' => esc_html( $rev->customer_name ),
				'rating'        => (int) $rev->rating,
				'title'         => esc_html( $rev->title ),
				'content'       => esc_html( $rev->content ),
				'created_at'    => esc_html( date_i18n( get_option( 'date_format' ), strtotime( $rev->created_at ) ) ),
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'stats'   => $stats,
				'items'   => $formatted_items,
			)
		);
	}

	/**
	 * Submit a verified customer review.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function submit_review( $request ) {
		$entity_id      = absint( $request->get_param( 'entity_id' ) );
		$customer_name  = sanitize_text_field( $request->get_param( 'customer_name' ) );
		$customer_email = sanitize_email( $request->get_param( 'customer_email' ) );
		$booking_id     = absint( $request->get_param( 'booking_id' ) );
		$rating         = absint( $request->get_param( 'rating' ) );
		$title          = sanitize_text_field( $request->get_param( 'title' ) );
		$content        = sanitize_textarea_field( $request->get_param( 'content' ) );

		$customer_id = get_current_user_id();

		$result = Review::create_review(
			array(
				'entity_id'      => $entity_id,
				'customer_id'    => $customer_id,
				'customer_name'  => $customer_name,
				'customer_email' => $customer_email,
				'booking_id'     => $booking_id,
				'rating'         => $rating,
				'title'          => $title,
				'content'        => $content,
			)
		);

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		return rest_ensure_response(
			array(
				'success'   => true,
				'message'   => __( 'Thank you! Your verified review has been published.', 'my-booking-engine' ),
				'review_id' => $result,
			)
		);
	}
}
