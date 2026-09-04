<?php
/**
 * Booking Reminder Scheduler.
 *
 * Runs hourly via WP-Cron and emails customers ahead of their booking,
 * based on which reminder windows the admin has enabled in Settings.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Notifications;

use MyBookingEngine\Models\Booking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ReminderScheduler
 */
class ReminderScheduler {

	const CRON_HOOK = 'mb_engine_check_reminders';

	/**
	 * Reminder window definitions: how far ahead of the booking start each
	 * type fires, matched against the hourly cron tick (a ~65 minute-wide
	 * window so no booking slips through between ticks).
	 *
	 * @return array
	 */
	private static function windows() {
		return array(
			'1_hour' => HOUR_IN_SECONDS,
			'1_day'  => DAY_IN_SECONDS,
			'1_week' => WEEK_IN_SECONDS,
		);
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_schedule' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run' ) );
	}

	/**
	 * Ensure the hourly cron event is scheduled.
	 *
	 * @return void
	 */
	public static function maybe_schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule on deactivation.
	 *
	 * @return void
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * The cron tick: for each enabled reminder type, find confirmed
	 * bookings whose start time falls in that type's window from now, and
	 * email any that haven't already had that reminder sent.
	 *
	 * @return void
	 */
	public static function run() {
		$settings = get_option( 'mb_engine_settings', array() );
		$enabled  = isset( $settings['reminders'] ) && is_array( $settings['reminders'] ) ? $settings['reminders'] : array();

		$now = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		foreach ( self::windows() as $type => $offset_seconds ) {
			if ( empty( $enabled[ $type ] ) ) {
				continue;
			}

			// Bookings starting between (now + offset) and (now + offset + 1
			// cron tick) — i.e. bookings that are about to enter this
			// reminder's window since the last time this ran.
			$window_start = gmdate( 'Y-m-d H:i:s', $now + $offset_seconds );
			$window_end   = gmdate( 'Y-m-d H:i:s', $now + $offset_seconds + HOUR_IN_SECONDS );

			$bookings = Booking::get_upcoming_between( $window_start, $window_end );

			foreach ( $bookings as $booking ) {
				if ( Booking::reminder_already_sent( $booking->id, $type ) ) {
					continue;
				}

				Mailer::send_reminder( $booking, $type );
				Booking::log_reminder_sent( $booking->id, $type );
			}
		}
	}
}
