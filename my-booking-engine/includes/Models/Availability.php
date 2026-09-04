<?php
/**
 * Availability Rule Model.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Models;

use MyBookingEngine\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Availability
 */
class Availability {

	/**
	 * Get working hours and blackout status for a specific date.
	 *
	 * @param int    $entity_id Entity ID.
	 * @param string $date Date in 'Y-m-d' format.
	 * @return array Array containing 'is_available' (bool), 'slots' (array), and 'blackout' (bool).
	 */
	public static function get_schedule_for_date( $entity_id, $date ) {
		global $wpdb;
		$table = Schema::get_availabilities_table();

		$entity_id = absint( $entity_id );
		$timestamp = strtotime( $date );
		if ( ! $timestamp ) {
			return array(
				'is_available' => false,
				'slots'        => array(),
				'blackout'     => false,
			);
		}

		$day_of_week = (int) gmdate( 'w', $timestamp ); // 0 = Sunday, 6 = Saturday.

		// Check for blackout dates first.
		$blackout = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE entity_id = %d
				  AND rule_type = 'blackout'
				  AND start_date <= %s
				  AND end_date >= %s
				LIMIT 1",
				$entity_id,
				$date,
				$date
			)
		);

		if ( $blackout ) {
			return array(
				'is_available' => false,
				'slots'        => array(),
				'blackout'     => true,
			);
		}

		// Check for specific date overrides.
		$custom_rules = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE entity_id = %d
				  AND rule_type = 'custom_date'
				  AND start_date = %s
				ORDER BY start_time ASC",
				$entity_id,
				$date
			)
		);

		if ( ! empty( $custom_rules ) ) {
			return array(
				'is_available' => true,
				'slots'        => $custom_rules,
				'blackout'     => false,
			);
		}

		// Check for weekly recurring rules.
		$weekly_rules = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE entity_id = %d
				  AND rule_type = 'weekly_recurring'
				  AND day_of_week = %d
				ORDER BY start_time ASC",
				$entity_id,
				$day_of_week
			)
		);

		if ( ! empty( $weekly_rules ) ) {
			return array(
				'is_available' => true,
				'slots'        => $weekly_rules,
				'blackout'     => false,
			);
		}

		// Default fallback if no rules configured: open 09:00 - 17:00 on weekdays (Mon-Fri).
		if ( $day_of_week >= 1 && $day_of_week <= 5 ) {
			return array(
				'is_available' => true,
				'slots'        => array(
					(object) array(
						'start_time' => '09:00:00',
						'end_time'   => '17:00:00',
						'capacity'   => 1,
					),
				),
				'blackout'     => false,
			);
		}

		return array(
			'is_available' => false,
			'slots'        => array(),
			'blackout'     => false,
		);
	}
}
