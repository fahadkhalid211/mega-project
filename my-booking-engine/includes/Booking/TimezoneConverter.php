<?php
/**
 * Timezone Converter Helper.
 * Converts datetimes between site timezone and client/browser timezone.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Booking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TimezoneConverter
 */
class TimezoneConverter {

	/**
	 * Get WordPress site timezone.
	 *
	 * @return \DateTimeZone
	 */
	public static function get_site_timezone() {
		return wp_timezone();
	}

	/**
	 * Convert a site-local datetime string to a client timezone.
	 *
	 * @param string $datetime_str Datetime in 'Y-m-d H:i:s'.
	 * @param string $target_timezone Target timezone string (e.g. 'America/New_York').
	 * @param string $format Output format.
	 * @return string Formatted converted date/time.
	 */
	public static function to_client_time( $datetime_str, $target_timezone, $format = 'Y-m-d H:i:s' ) {
		if ( empty( $target_timezone ) ) {
			return $datetime_str;
		}

		try {
			$site_tz   = self::get_site_timezone();
			$client_tz = new \DateTimeZone( $target_timezone );

			$dt = new \DateTime( $datetime_str, $site_tz );
			$dt->setTimezone( $client_tz );

			return $dt->format( $format );
		} catch ( \Exception $e ) {
			return $datetime_str;
		}
	}

	/**
	 * Convert client-input datetime back to site timezone for database storage.
	 *
	 * @param string $datetime_str Datetime in 'Y-m-d H:i:s'.
	 * @param string $client_timezone Client timezone string.
	 * @param string $format Output format.
	 * @return string
	 */
	public static function to_site_time( $datetime_str, $client_timezone, $format = 'Y-m-d H:i:s' ) {
		if ( empty( $client_timezone ) ) {
			return $datetime_str;
		}

		try {
			$client_tz = new \DateTimeZone( $client_timezone );
			$site_tz   = self::get_site_timezone();

			$dt = new \DateTime( $datetime_str, $client_tz );
			$dt->setTimezone( $site_tz );

			return $dt->format( $format );
		} catch ( \Exception $e ) {
			return $datetime_str;
		}
	}
}
