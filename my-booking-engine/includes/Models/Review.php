<?php
/**
 * Review Model and Manager.
 * Handles customer ratings, reviews, and verified booking eligibility checks.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Models;

use MyBookingEngine\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Review
 */
class Review {

	/**
	 * Check if customer is eligible to leave a review for an entity.
	 * Requires at least one confirmed or completed booking.
	 *
	 * @param int $customer_id WordPress User ID or 0 for guest.
	 * @param int $entity_id Entity post ID.
	 * @param int $booking_id Specific booking ID (optional).
	 * @param string $customer_email Customer email (for guests).
	 * @return bool
	 */
	public static function is_eligible_to_review( $customer_id, $entity_id, $booking_id = 0, $customer_email = '' ) {
		global $wpdb;

		// Administrators can always review for testing/management.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$bookings_table = Schema::get_bookings_table();
		$entity_id      = absint( $entity_id );
		$customer_id    = absint( $customer_id );
		$booking_id     = absint( $booking_id );

		if ( $booking_id > 0 ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, status FROM {$bookings_table} WHERE id = %d AND entity_id = %d AND status IN ('confirmed', 'completed')",
					$booking_id,
					$entity_id
				)
			);
			return ! empty( $row );
		}

		// Check by customer user ID if logged in.
		if ( $customer_id > 0 ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$bookings_table} WHERE entity_id = %d AND customer_id = %d AND status IN ('confirmed', 'completed')",
					$entity_id,
					$customer_id
				)
			);
			if ( absint( $count ) > 0 ) {
				return true;
			}
		}

		// Check by customer email.
		if ( ! empty( $customer_email ) && is_email( $customer_email ) ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$bookings_table} WHERE entity_id = %d AND customer_email = %s AND status IN ('confirmed', 'completed')",
					$entity_id,
					sanitize_email( $customer_email )
				)
			);
			return absint( $count ) > 0;
		}

		return false;
	}

	/**
	 * Create a new review.
	 *
	 * @param array $data Review data.
	 * @return int|\WP_Error Inserted review ID or WP_Error.
	 */
	public static function create_review( array $data ) {
		global $wpdb;
		$table = Schema::get_reviews_table();

		$entity_id      = isset( $data['entity_id'] ) ? absint( $data['entity_id'] ) : 0;
		$customer_id    = isset( $data['customer_id'] ) ? absint( $data['customer_id'] ) : 0;
		$customer_name  = isset( $data['customer_name'] ) ? sanitize_text_field( $data['customer_name'] ) : '';
		$customer_email = isset( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : '';
		$booking_id     = isset( $data['booking_id'] ) ? absint( $data['booking_id'] ) : 0;
		$rating         = isset( $data['rating'] ) ? min( 5, max( 1, absint( $data['rating'] ) ) ) : 5;
		$title          = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
		$content        = isset( $data['content'] ) ? sanitize_textarea_field( $data['content'] ) : '';

		if ( ! $entity_id || empty( $customer_name ) || empty( $content ) ) {
			return new \WP_Error( 'invalid_data', __( 'Entity ID, customer name, and review content are required.', 'my-booking-engine' ) );
		}

		// Verify eligibility.
		$is_verified = self::is_eligible_to_review( $customer_id, $entity_id, $booking_id, $customer_email );
		if ( ! $is_verified ) {
			return new \WP_Error( 'not_eligible', __( 'Reviews are only permitted from verified customers with a completed booking.', 'my-booking-engine' ) );
		}

		// Prevent duplicate review for the same booking.
		if ( $booking_id > 0 ) {
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE booking_id = %d AND entity_id = %d",
					$booking_id,
					$entity_id
				)
			);
			if ( $existing ) {
				return new \WP_Error( 'already_reviewed', __( 'You have already submitted a review for this booking.', 'my-booking-engine' ) );
			}
		}

		$status = 'approved'; // Auto-approve verified bookings.

		$inserted = $wpdb->insert(
			$table,
			array(
				'entity_id'     => $entity_id,
				'customer_id'   => $customer_id,
				'customer_name' => $customer_name,
				'booking_id'    => $booking_id,
				'rating'        => $rating,
				'title'         => $title,
				'content'       => $content,
				'status'        => $status,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new \WP_Error( 'db_error', __( 'Failed to save review.', 'my-booking-engine' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get rating statistics for an entity.
	 *
	 * @param int $entity_id Entity post ID.
	 * @return array
	 */
	public static function get_stats( $entity_id ) {
		global $wpdb;
		$table = Schema::get_reviews_table();

		$entity_id = absint( $entity_id );
		$rows      = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT rating, COUNT(*) as count FROM {$table} WHERE entity_id = %d AND status = 'approved' GROUP BY rating",
				$entity_id
			)
		);

		$distribution = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
		$total_count  = 0;
		$sum_rating   = 0;

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$r = (int) $row->rating;
				$c = (int) $row->count;
				if ( isset( $distribution[ $r ] ) ) {
					$distribution[ $r ] = $c;
				}
				$total_count += $c;
				$sum_rating  += ( $r * $c );
			}
		}

		$average = $total_count > 0 ? round( $sum_rating / $total_count, 1 ) : 5.0;

		return array(
			'average'      => $average,
			'total'        => $total_count,
			'distribution' => $distribution,
		);
	}
}
