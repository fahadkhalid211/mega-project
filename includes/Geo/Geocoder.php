<?php
/**
 * Geocoder Service.
 * Supports OpenStreetMap (Nominatim) and Google Maps Geocoding API with transient caching.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Geo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Geocoder
 */
class Geocoder {

	/**
	 * Geocode a location string, postal code, or city.
	 *
	 * @param string $postal_code Postal or ZIP code.
	 * @param string $city City name.
	 * @param string $country_code Country code or name.
	 * @return array|false Array with 'lat' and 'lng', or false on failure.
	 */
	public static function geocode( $postal_code, $city = '', $country_code = '' ) {
		$postal_code  = trim( $postal_code );
		$city         = trim( $city );
		$country_code = trim( $country_code );

		if ( empty( $postal_code ) && empty( $city ) ) {
			return false;
		}

		// Create normalized cache key.
		$cache_key = 'mb_geo_' . md5( strtolower( "{$postal_code}|{$city}|{$country_code}" ) );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$settings = get_option( 'mb_engine_settings', array() );
		$provider = isset( $settings['geocoder_provider'] ) ? $settings['geocoder_provider'] : 'nominatim';

		$result = false;
		if ( 'google' === $provider && ! empty( $settings['google_api_key'] ) ) {
			$result = self::geocode_google( $postal_code, $city, $country_code, $settings['google_api_key'] );
		} else {
			$result = self::geocode_nominatim( $postal_code, $city, $country_code );
		}

		if ( $result && isset( $result['lat'], $result['lng'] ) ) {
			// Cache for 30 days.
			set_transient( $cache_key, $result, 30 * DAY_IN_SECONDS );
			return $result;
		}

		return false;
	}

	/**
	 * Geocode via OpenStreetMap (Nominatim).
	 *
	 * @param string $postal_code Postal code.
	 * @param string $city City name.
	 * @param string $country_code Country code.
	 * @return array|false
	 */
	private static function geocode_nominatim( $postal_code, $city, $country_code ) {
		$query_parts = array();
		if ( ! empty( $postal_code ) ) {
			$query_parts[] = $postal_code;
		}
		if ( ! empty( $city ) ) {
			$query_parts[] = $city;
		}
		if ( ! empty( $country_code ) ) {
			$query_parts[] = $country_code;
		}

		$query = implode( ', ', $query_parts );
		$url   = add_query_arg(
			array(
				'q'              => rawurlencode( $query ),
				'format'         => 'json',
				'addressdetails' => 1,
				'limit'          => 1,
			),
			'https://nominatim.openstreetmap.org/search'
		);

		// Respect Nominatim Usage Policy by sending descriptive User-Agent.
		$site_url = home_url();
		$args     = array(
			'timeout'    => 10,
			'user-agent' => 'BookingEngine/1.0.0 (' . esc_url_raw( $site_url ) . ')',
			'headers'    => array(
				'Accept' => 'application/json',
			),
		);

		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data ) && isset( $data[0]['lat'], $data[0]['lon'] ) ) {
			return array(
				'lat'          => floatval( $data[0]['lat'] ),
				'lng'          => floatval( $data[0]['lon'] ),
				'display_name' => isset( $data[0]['display_name'] ) ? sanitize_text_field( $data[0]['display_name'] ) : '',
			);
		}

		return false;
	}

	/**
	 * Geocode via Google Maps Geocoding API.
	 *
	 * @param string $postal_code Postal code.
	 * @param string $city City name.
	 * @param string $country_code Country code.
	 * @param string $api_key Google Maps API key.
	 * @return array|false
	 */
	private static function geocode_google( $postal_code, $city, $country_code, $api_key ) {
		$address_parts = array_filter( array( $postal_code, $city, $country_code ) );
		$address       = implode( ', ', $address_parts );

		$url = add_query_arg(
			array(
				'address' => rawurlencode( $address ),
				'key'     => sanitize_text_field( $api_key ),
			),
			'https://maps.googleapis.com/maps/api/geocode/json'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data['results'][0]['geometry']['location'] ) ) {
			$loc = $data['results'][0]['geometry']['location'];
			return array(
				'lat'          => floatval( $loc['lat'] ),
				'lng'          => floatval( $loc['lng'] ),
				'display_name' => isset( $data['results'][0]['formatted_address'] ) ? sanitize_text_field( $data['results'][0]['formatted_address'] ) : '',
			);
		}

		return false;
	}
}
