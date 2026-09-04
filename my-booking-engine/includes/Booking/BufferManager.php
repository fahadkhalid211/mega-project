<?php
/**
 * Buffer & Turnaround Time Manager.
 * Handles prep and cleaning gap calculations.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Booking;

use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BufferManager
 */
class BufferManager {

	/**
	 * Calculate the buffered time range for an entity booking.
	 *
	 * @param BookingEntity $entity Entity object.
	 * @param int           $start_timestamp Slot start unix timestamp.
	 * @param int           $end_timestamp Slot end unix timestamp.
	 * @return array Array with 'buffered_start' and 'buffered_end' timestamps.
	 */
	public static function get_buffered_range( BookingEntity $entity, $start_timestamp, $end_timestamp ) {
		$buffer_before_minutes = $entity->get_buffer_before();
		$buffer_after_minutes  = $entity->get_buffer_after();

		$buffered_start = $start_timestamp - ( $buffer_before_minutes * 60 );
		$buffered_end   = $end_timestamp + ( $buffer_after_minutes * 60 );

		return array(
			'buffered_start' => $buffered_start,
			'buffered_end'   => $buffered_end,
		);
	}
}
