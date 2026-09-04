<?php
/**
 * Concurrency Mutex Locking Engine.
 * Prevents double bookings and race conditions during user checkout.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Booking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MutexLock
 */
class MutexLock {

	/**
	 * Build transient lock key.
	 *
	 * @param int    $entity_id Entity ID.
	 * @param string $start Datetime start.
	 * @param string $end Datetime end.
	 * @return string
	 */
	private static function get_lock_key( $entity_id, $start, $end ) {
		$hash = md5( "{$start}_{$end}" );
		return "mb_lock_{$entity_id}_{$hash}";
	}

	/**
	 * Attempt to acquire an exclusive lock on a booking slot.
	 *
	 * @param int    $entity_id Entity ID.
	 * @param string $start Datetime start string.
	 * @param string $end Datetime end string.
	 * @param string $token Unique session or cart token.
	 * @param int    $duration_minutes Lock expiration in minutes (default from settings).
	 * @return bool True if lock was acquired, false if held by another user.
	 */
	public static function acquire_lock( $entity_id, $start, $end, $token, $duration_minutes = 0 ) {
		if ( $duration_minutes <= 0 ) {
			$settings         = get_option( 'mb_engine_settings', array() );
			$duration_minutes = isset( $settings['lock_duration'] ) ? absint( $settings['lock_duration'] ) : 10;
		}

		$lock_key     = self::get_lock_key( $entity_id, $start, $end );
		$current_lock = get_transient( $lock_key );

		// If currently locked by someone else, deny.
		if ( false !== $current_lock && $current_lock !== $token ) {
			return false;
		}

		// Acquire or refresh lock for this token.
		set_transient( $lock_key, sanitize_text_field( $token ), $duration_minutes * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Check if a slot is locked by someone else.
	 *
	 * @param int    $entity_id Entity ID.
	 * @param string $start Datetime start.
	 * @param string $end Datetime end.
	 * @param string $token Unique session or cart token.
	 * @return bool True if locked by someone else, false if free or owned by token.
	 */
	public static function is_locked( $entity_id, $start, $end, $token = '' ) {
		$lock_key     = self::get_lock_key( $entity_id, $start, $end );
		$current_lock = get_transient( $lock_key );

		if ( false === $current_lock ) {
			return false;
		}

		return ( $current_lock !== $token );
	}

	/**
	 * Release a previously acquired lock.
	 *
	 * @param int    $entity_id Entity ID.
	 * @param string $start Datetime start.
	 * @param string $end Datetime end.
	 * @param string $token Unique session or cart token.
	 * @return bool
	 */
	public static function release_lock( $entity_id, $start, $end, $token = '' ) {
		$lock_key     = self::get_lock_key( $entity_id, $start, $end );
		$current_lock = get_transient( $lock_key );

		if ( false === $current_lock ) {
			return true;
		}

		// Only the owner or an admin/system call can release the lock.
		if ( empty( $token ) || $current_lock === $token ) {
			return delete_transient( $lock_key );
		}

		return false;
	}
}
