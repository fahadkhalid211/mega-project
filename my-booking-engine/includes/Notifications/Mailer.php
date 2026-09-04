<?php
/**
 * Booking Email Notifications.
 * Handles booking-received, booking-confirmed, and reminder emails.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Notifications;

use MyBookingEngine\Models\Booking;
use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Mailer
 */
class Mailer {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'mb_engine_booking_created', array( __CLASS__, 'send_booking_received' ) );
		add_action( 'mb_engine_booking_status_changed', array( __CLASS__, 'maybe_send_confirmation' ), 10, 2 );
	}

	/**
	 * Send the initial "booking received" email right after a booking row
	 * is created (regardless of pending/confirmed status).
	 *
	 * @param int $booking_id Booking ID.
	 * @return void
	 */
	public static function send_booking_received( $booking_id ) {
		$booking = Booking::get( $booking_id );
		if ( ! $booking || empty( $booking->customer_email ) ) {
			return;
		}

		$entity_title = get_the_title( $booking->entity_id );
		$is_confirmed = ( 'confirmed' === $booking->status );

		/* translators: %s: site name */
		$subject = sprintf( __( '[%s] Booking Received', 'my-booking-engine' ), get_bloginfo( 'name' ) );

		$body  = self::greeting( $booking->customer_name );
		$body .= $is_confirmed
			/* translators: %s: listing title */
			? sprintf( __( "Your booking for \"%s\" is confirmed! Here are your details:", 'my-booking-engine' ), $entity_title )
			/* translators: %s: listing title */
			: sprintf( __( "We've received your booking for \"%s\". It's currently pending confirmation — we'll email you as soon as it's confirmed.", 'my-booking-engine' ), $entity_title );
		$body .= "\n\n" . self::booking_details_block( $booking, $entity_title );
		$body .= "\n" . self::footer();

		self::send( $booking->customer_email, $subject, $body );
	}

	/**
	 * Send a "your booking is confirmed" email when status flips to confirmed.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status New status.
	 * @return void
	 */
	public static function maybe_send_confirmation( $booking_id, $status ) {
		if ( 'confirmed' !== $status ) {
			return;
		}

		$booking = Booking::get( $booking_id );
		if ( ! $booking || empty( $booking->customer_email ) ) {
			return;
		}

		$entity_title = get_the_title( $booking->entity_id );

		/* translators: %s: site name */
		$subject = sprintf( __( '[%s] Your Booking is Confirmed', 'my-booking-engine' ), get_bloginfo( 'name' ) );

		$body  = self::greeting( $booking->customer_name );
		/* translators: %s: listing title */
		$body .= sprintf( __( "Good news — your booking for \"%s\" is now confirmed.", 'my-booking-engine' ), $entity_title );
		$body .= "\n\n" . self::booking_details_block( $booking, $entity_title );
		$body .= "\n" . self::footer();

		self::send( $booking->customer_email, $subject, $body );
	}

	/**
	 * Send an upcoming-booking reminder email.
	 *
	 * @param object $booking Booking row.
	 * @param string $reminder_type One of '1_hour', '1_day', '1_week'.
	 * @return void
	 */
	public static function send_reminder( $booking, $reminder_type ) {
		if ( empty( $booking->customer_email ) ) {
			return;
		}

		$entity_title = get_the_title( $booking->entity_id );

		$labels = array(
			'1_hour' => __( 'in 1 hour', 'my-booking-engine' ),
			'1_day'  => __( 'tomorrow', 'my-booking-engine' ),
			'1_week' => __( 'in 1 week', 'my-booking-engine' ),
		);
		$when = isset( $labels[ $reminder_type ] ) ? $labels[ $reminder_type ] : __( 'soon', 'my-booking-engine' );

		/* translators: %s: site name */
		$subject = sprintf( __( '[%s] Reminder: Upcoming Booking', 'my-booking-engine' ), get_bloginfo( 'name' ) );

		$body  = self::greeting( $booking->customer_name );
		/* translators: 1: listing title, 2: relative time (e.g. "in 1 hour") */
		$body .= sprintf( __( "Just a reminder that your booking for \"%1\$s\" is coming up %2\$s.", 'my-booking-engine' ), $entity_title, $when );
		$body .= "\n\n" . self::booking_details_block( $booking, $entity_title );
		$body .= "\n" . self::footer();

		self::send( $booking->customer_email, $subject, $body );
	}

	/**
	 * Shared greeting line.
	 *
	 * @param string $name Customer name.
	 * @return string
	 */
	private static function greeting( $name ) {
		/* translators: %s: customer first name */
		return sprintf( __( "Hi %s,\n\n", 'my-booking-engine' ), $name ? $name : __( 'there', 'my-booking-engine' ) );
	}

	/**
	 * Shared plain-text booking details block.
	 *
	 * @param object $booking Booking row.
	 * @param string $entity_title Listing title.
	 * @return string
	 */
	private static function booking_details_block( $booking, $entity_title ) {
		$settings = get_option( 'mb_engine_settings', array() );
		$sym      = $settings['currency_symbol'] ?? '$';

		$lines   = array();
		$lines[] = __( 'Booking Details', 'my-booking-engine' );
		$lines[] = '----------------------';
		/* translators: %s: listing title */
		$lines[] = sprintf( __( 'Listing: %s', 'my-booking-engine' ), $entity_title );
		/* translators: %s: booking start datetime */
		$lines[] = sprintf( __( 'Start: %s', 'my-booking-engine' ), $booking->booking_start );
		/* translators: %s: booking end datetime */
		$lines[] = sprintf( __( 'End: %s', 'my-booking-engine' ), $booking->booking_end );
		/* translators: %d: number of spots/guests */
		$lines[] = sprintf( __( 'Spots: %d', 'my-booking-engine' ), (int) $booking->capacity_booked );
		/* translators: %s: formatted total price */
		$lines[] = sprintf( __( 'Total: %s', 'my-booking-engine' ), $sym . number_format( (float) $booking->total_price, 2 ) );
		/* translators: %d: booking reference number */
		$lines[] = sprintf( __( 'Booking Reference: #%d', 'my-booking-engine' ), (int) $booking->id );

		return implode( "\n", $lines );
	}

	/**
	 * Shared footer.
	 *
	 * @return string
	 */
	private static function footer() {
		/* translators: %s: site name */
		return "\n" . sprintf( __( "Thanks,\n%s", 'my-booking-engine' ), get_bloginfo( 'name' ) );
	}

	/**
	 * Send a plain-text email.
	 *
	 * @param string $to Recipient email.
	 * @param string $subject Subject line.
	 * @param string $body Message body.
	 * @return void
	 */
	private static function send( $to, $subject, $body ) {
		wp_mail( $to, $subject, $body );
	}
}
