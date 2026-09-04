<?php
/**
 * Master Slot and Availability Calculation Engine.
 * Supports Hourly Appointments, Day Rentals, Night Stays, and Capacity Rosters.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Booking;

use MyBookingEngine\Models\BookingEntity;
use MyBookingEngine\Models\Availability;
use MyBookingEngine\Models\Booking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SlotEngine
 */
class SlotEngine {

	/**
	 * Get available slots or dates for an entity based on its booking model.
	 *
	 * @param int    $entity_id Entity post ID.
	 * @param string $start_date Query start date (Y-m-d).
	 * @param string $end_date Query end date (Y-m-d, optional).
	 * @param string $session_token User session lock token.
	 * @return array Calculated slots and availability payload.
	 */
	public static function get_slots( $entity_id, $start_date, $end_date = '', $session_token = '' ) {
		$entity = new BookingEntity( $entity_id );
		$model  = $entity->get_model_type();

		switch ( $model ) {
			case 'hourly_slot':
				return self::get_hourly_slots( $entity, $start_date, $session_token );

			case 'day_rental':
				return self::get_day_rental_availability( $entity, $start_date, $end_date, $session_token );

			case 'night_stay':
				return self::get_night_stay_availability( $entity, $start_date, $end_date, $session_token );

			case 'capacity_roster':
				return self::get_capacity_roster_status( $entity, $session_token );

			default:
				return array( 'error' => __( 'Unsupported booking model.', 'my-booking-engine' ) );
		}
	}

	/**
	 * Generate hourly / time-slot appointments for a single day.
	 *
	 * @param BookingEntity $entity Entity object.
	 * @param string        $date Date string (Y-m-d).
	 * @param string        $session_token Lock token.
	 * @return array
	 */
	private static function get_hourly_slots( BookingEntity $entity, $date, $session_token ) {
		$schedule = Availability::get_schedule_for_date( $entity->get_id(), $date );

		if ( ! $schedule['is_available'] || empty( $schedule['slots'] ) ) {
			return array(
				'model'     => 'hourly_slot',
				'date'      => $date,
				'available' => false,
				'slots'     => array(),
				'reason'    => $schedule['blackout'] ? __( 'Blackout date.', 'my-booking-engine' ) : __( 'Closed on this day.', 'my-booking-engine' ),
			);
		}

		$slot_duration = $entity->get_slot_duration(); // In minutes.
		$max_capacity  = $entity->get_capacity();
		$base_price    = $entity->get_base_price();
		$slots         = array();

		$now_ts = current_time( 'timestamp' );

		foreach ( $schedule['slots'] as $rule ) {
			$rule_start_ts = strtotime( "{$date} {$rule->start_time}" );
			$rule_end_ts   = strtotime( "{$date} {$rule->end_time}" );
			$rule_capacity = ! empty( $rule->capacity ) ? (int) $rule->capacity : $max_capacity;

			$cursor_ts = $rule_start_ts;

			while ( ( $cursor_ts + ( $slot_duration * 60 ) ) <= $rule_end_ts ) {
				$slot_start_ts = $cursor_ts;
				$slot_end_ts   = $cursor_ts + ( $slot_duration * 60 );

				// Skip past slots for today.
				if ( $slot_start_ts <= $now_ts ) {
					$cursor_ts += ( $slot_duration * 60 );
					continue;
				}

				$start_str = gmdate( 'Y-m-d H:i:s', $slot_start_ts );
				$end_str   = gmdate( 'Y-m-d H:i:s', $slot_end_ts );

				// Calculate buffers.
				$buffer_info    = BufferManager::get_buffered_range( $entity, $slot_start_ts, $slot_end_ts );
				$buffered_start = gmdate( 'Y-m-d H:i:s', $buffer_info['buffered_start'] );
				$buffered_end   = gmdate( 'Y-m-d H:i:s', $buffer_info['buffered_end'] );

				// Check overlapping capacity in database.
				$booked_capacity = Booking::get_overlapping_capacity( $entity->get_id(), $buffered_start, $buffered_end );

				// Check concurrency lock.
				$is_locked = MutexLock::is_locked( $entity->get_id(), $start_str, $end_str, $session_token );

				$available_spots = max( 0, $rule_capacity - $booked_capacity );
				$is_available    = ( $available_spots > 0 ) && ! $is_locked;

				$slots[] = array(
					'start_time'      => gmdate( 'H:i', $slot_start_ts ),
					'end_time'        => gmdate( 'H:i', $slot_end_ts ),
					'start_datetime'  => $start_str,
					'end_datetime'    => $end_str,
					'price'           => $base_price,
					'available_spots' => $available_spots,
					'is_available'    => $is_available,
					'is_locked'       => $is_locked,
				);

				$cursor_ts += ( $slot_duration * 60 );
			}
		}

		return array(
			'model'     => 'hourly_slot',
			'date'      => $date,
			'available' => ! empty( $slots ),
			'slots'     => $slots,
		);
	}

	/**
	 * Check day-based rental availability across a date range.
	 *
	 * @param BookingEntity $entity Entity object.
	 * @param string        $start_date Start date (Y-m-d).
	 * @param string        $end_date End date (Y-m-d).
	 * @param string        $session_token Lock token.
	 * @return array
	 */
	private static function get_day_rental_availability( BookingEntity $entity, $start_date, $end_date, $session_token ) {
		if ( empty( $end_date ) ) {
			$end_date = $start_date;
		}

		$start_ts = strtotime( $start_date . ' 00:00:00' );
		$end_ts   = strtotime( $end_date . ' 23:59:59' );

		if ( $start_ts > $end_ts ) {
			return array(
				'model'     => 'day_rental',
				'available' => false,
				'error'     => __( 'End date cannot be earlier than start date.', 'my-booking-engine' ),
			);
		}

		$days_count = max( 1, (int) round( ( $end_ts - $start_ts ) / 86400 ) );

		// Enforce min / max duration.
		if ( $days_count < $entity->get_min_duration() ) {
			return array(
				'model'     => 'day_rental',
				'available' => false,
				/* translators: %d: minimum days */
				'error'     => sprintf( __( 'Minimum rental period is %d days.', 'my-booking-engine' ), $entity->get_min_duration() ),
			);
		}
		if ( $days_count > $entity->get_max_duration() ) {
			return array(
				'model'     => 'day_rental',
				'available' => false,
				/* translators: %d: maximum days */
				'error'     => sprintf( __( 'Maximum rental period is %d days.', 'my-booking-engine' ), $entity->get_max_duration() ),
			);
		}

		$start_str = gmdate( 'Y-m-d 00:00:00', $start_ts );
		$end_str   = gmdate( 'Y-m-d 23:59:59', $end_ts );

		// Check capacity.
		$booked_capacity = Booking::get_overlapping_capacity( $entity->get_id(), $start_str, $end_str );
		$is_locked       = MutexLock::is_locked( $entity->get_id(), $start_str, $end_str, $session_token );
		$remaining       = max( 0, $entity->get_capacity() - $booked_capacity );
		$is_available    = ( $remaining > 0 ) && ! $is_locked;

		$total_price = self::calculate_price( $entity, $start_str, $end_str, 1 );

		return array(
			'model'           => 'day_rental',
			'start_date'      => $start_date,
			'end_date'        => $end_date,
			'start_datetime'  => $start_str,
			'end_datetime'    => $end_str,
			'days_count'      => $days_count,
			'available_spots' => $remaining,
			'is_available'    => $is_available,
			'total_price'     => $total_price,
		);
	}

	/**
	 * Check night-based stay availability (hotel / villa / property).
	 *
	 * @param BookingEntity $entity Entity object.
	 * @param string        $checkin_date Check-in date (Y-m-d).
	 * @param string        $checkout_date Check-out date (Y-m-d).
	 * @param string        $session_token Lock token.
	 * @return array
	 */
	private static function get_night_stay_availability( BookingEntity $entity, $checkin_date, $checkout_date, $session_token ) {
		if ( empty( $checkout_date ) ) {
			$checkout_date = gmdate( 'Y-m-d', strtotime( $checkin_date . ' +1 day' ) );
		}

		$checkin_time  = $entity->get_checkin_time() . ':00';
		$checkout_time = $entity->get_checkout_time() . ':00';

		$start_ts = strtotime( "{$checkin_date} {$checkin_time}" );
		$end_ts   = strtotime( "{$checkout_date} {$checkout_time}" );

		if ( $start_ts >= $end_ts ) {
			return array(
				'model'     => 'night_stay',
				'available' => false,
				'error'     => __( 'Check-out date must be after check-in date.', 'my-booking-engine' ),
			);
		}

		$nights_count = max( 1, (int) round( ( strtotime( $checkout_date ) - strtotime( $checkin_date ) ) / 86400 ) );

		if ( $nights_count < $entity->get_min_duration() ) {
			return array(
				'model'     => 'night_stay',
				'available' => false,
				/* translators: %d: minimum nights */
				'error'     => sprintf( __( 'Minimum stay is %d nights.', 'my-booking-engine' ), $entity->get_min_duration() ),
			);
		}
		if ( $nights_count > $entity->get_max_duration() ) {
			return array(
				'model'     => 'night_stay',
				'available' => false,
				/* translators: %d: maximum nights */
				'error'     => sprintf( __( 'Maximum stay is %d nights.', 'my-booking-engine' ), $entity->get_max_duration() ),
			);
		}

		$start_str = gmdate( 'Y-m-d H:i:s', $start_ts );
		$end_str   = gmdate( 'Y-m-d H:i:s', $end_ts );

		$booked_capacity = Booking::get_overlapping_capacity( $entity->get_id(), $start_str, $end_str );
		$is_locked       = MutexLock::is_locked( $entity->get_id(), $start_str, $end_str, $session_token );
		$remaining       = max( 0, $entity->get_capacity() - $booked_capacity );
		$is_available    = ( $remaining > 0 ) && ! $is_locked;

		$total_price = self::calculate_price( $entity, $start_str, $end_str, 1 );

		return array(
			'model'           => 'night_stay',
			'checkin_date'    => $checkin_date,
			'checkout_date'   => $checkout_date,
			'start_datetime'  => $start_str,
			'end_datetime'    => $end_str,
			'nights_count'    => $nights_count,
			'available_spots' => $remaining,
			'is_available'    => $is_available,
			'total_price'     => $total_price,
		);
	}

	/**
	 * Get capacity roster event status (ticket seats remaining).
	 *
	 * @param BookingEntity $entity Entity object.
	 * @param string        $session_token Lock token.
	 * @return array
	 */
	private static function get_capacity_roster_status( BookingEntity $entity, $session_token ) {
		$start_str = $entity->get_event_start();
		$end_str   = $entity->get_event_end();

		if ( empty( $start_str ) || empty( $end_str ) ) {
			return array(
				'model'     => 'capacity_roster',
				'available' => false,
				'error'     => __( 'Event dates are not configured.', 'my-booking-engine' ),
			);
		}

		$total_capacity  = $entity->get_capacity();
		$booked_capacity = Booking::get_overlapping_capacity( $entity->get_id(), $start_str, $end_str );
		$remaining       = max( 0, $total_capacity - $booked_capacity );

		return array(
			'model'           => 'capacity_roster',
			'start_datetime'  => $start_str,
			'end_datetime'    => $end_str,
			'total_capacity'  => $total_capacity,
			'booked_capacity' => $booked_capacity,
			'available_spots' => $remaining,
			'is_available'    => ( $remaining > 0 ),
			'ticket_price'    => $entity->get_base_price(),
		);
	}

	/**
	 * Calculate total price for a booking span including weekend logic.
	 *
	 * @param BookingEntity $entity Entity object.
	 * @param string        $start_datetime Datetime start.
	 * @param string        $end_datetime Datetime end.
	 * @param int           $capacity Number of spots/people.
	 * @return float
	 */
	public static function calculate_price( BookingEntity $entity, $start_datetime, $end_datetime, $capacity = 1 ) {
		$model         = $entity->get_model_type();
		$base_price    = $entity->get_base_price();
		$weekend_price = $entity->get_weekend_price();
		$capacity      = max( 1, absint( $capacity ) );

		if ( 'hourly_slot' === $model || 'capacity_roster' === $model ) {
			return round( $base_price * $capacity, 2 );
		}

		$start_ts = strtotime( $start_datetime );
		$end_ts   = strtotime( $end_datetime );
		$total    = 0.00;

		if ( 'night_stay' === $model ) {
			// Iterate each night.
			$current_ts = $start_ts;
			while ( $current_ts < ( $end_ts - 3600 ) ) {
				$day_of_week = (int) gmdate( 'w', $current_ts ); // 5 = Friday, 6 = Saturday.
				$rate        = ( 5 === $day_of_week || 6 === $day_of_week ) ? $weekend_price : $base_price;
				$total      += $rate;
				$current_ts += 86400;
			}
		} elseif ( 'day_rental' === $model ) {
			$current_ts = $start_ts;
			while ( $current_ts <= $end_ts ) {
				$day_of_week = (int) gmdate( 'w', $current_ts );
				$rate        = ( 0 === $day_of_week || 6 === $day_of_week ) ? $weekend_price : $base_price;
				$total      += $rate;
				$current_ts += 86400;
			}
		}

		return round( $total * $capacity, 2 );
	}
}
