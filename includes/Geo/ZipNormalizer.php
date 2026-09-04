<?php
/**
 * ZIP / Postal Code Normalizer Utility.
 * Normalizes international postal codes for consistent database indexing and search matching.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Geo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ZipNormalizer
 */
class ZipNormalizer {

	/**
	 * Normalize a postal code string.
	 * Strips spaces, hyphens, punctuation, and converts to uppercase.
	 *
	 * @param string $postal_code Raw postal code.
	 * @return string Normalized alphanumeric postal code.
	 */
	public static function normalize( $postal_code ) {
		if ( empty( $postal_code ) || ! is_string( $postal_code ) ) {
			return '';
		}

		// Trim and uppercase.
		$cleaned = strtoupper( trim( $postal_code ) );

		// Remove all spaces, hyphens, dots, and common separators.
		$cleaned = preg_replace( '/[\s\-\.\_\,\/]+/', '', $cleaned );

		return $cleaned;
	}

	/**
	 * Extract main postal code prefix for fuzzy matching (e.g. '90210-1234' -> '90210', 'SW1A 1AA' -> 'SW1A').
	 *
	 * @param string $postal_code Raw postal code.
	 * @return string
	 */
	public static function get_prefix( $postal_code ) {
		$postal_code = trim( $postal_code );

		// For US 5+4 format: '90210-1234' -> '90210'.
		if ( preg_match( '/^([0-9]{5})(-[0-9]{4})?$/', $postal_code, $matches ) ) {
			return $matches[1];
		}

		// For UK format: 'SW1A 1AA' -> 'SW1A'.
		if ( preg_match( '/^([A-Z]{1,2}[0-9][A-Z0-9]?)/i', $postal_code, $matches ) ) {
			return strtoupper( $matches[1] );
		}

		return self::normalize( $postal_code );
	}
}
