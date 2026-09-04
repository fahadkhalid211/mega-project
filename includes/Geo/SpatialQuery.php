<?php
/**
 * Spatial Haversine distance calculator and database query manager.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Geo;

use MyBookingEngine\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SpatialQuery
 */
class SpatialQuery {

	const EARTH_RADIUS_KM    = 6371.0;
	const EARTH_RADIUS_MILES = 3958.8;

	/**
	 * Find booking entities within a radius of given coordinates.
	 *
	 * @param float  $latitude Center latitude.
	 * @param float  $longitude Center longitude.
	 * @param float  $radius Maximum distance radius.
	 * @param string $unit 'km' or 'miles'.
	 * @param int    $limit Maximum results to return.
	 * @return array Array of objects with post_id, distance, city, postal_code, etc.
	 */
	public static function find_entities_within_radius( $latitude, $longitude, $radius, $unit = 'km', $limit = 50 ) {
		global $wpdb;

		$latitude  = floatval( $latitude );
		$longitude = floatval( $longitude );
		$radius    = floatval( $radius );
		$limit     = absint( $limit );

		if ( $radius <= 0 ) {
			$radius = 25.0;
		}

		$earth_radius = ( 'miles' === $unit ) ? self::EARTH_RADIUS_MILES : self::EARTH_RADIUS_KM;

		// Calculate rough bounding box to optimize SQL index scan before trigonometry.
		// 1 degree of latitude is approx 111.045 km (or 69 miles).
		$deg_lat_distance = ( 'miles' === $unit ) ? 69.0 : 111.045;
		$delta_lat        = $radius / $deg_lat_distance;
		$min_lat          = $latitude - $delta_lat;
		$max_lat          = $latitude + $delta_lat;

		$cos_lat   = cos( deg2rad( $latitude ) );
		$delta_lng = ( $cos_lat > 0.0001 ) ? ( $radius / ( $deg_lat_distance * $cos_lat ) ) : 180.0;
		$min_lng   = $longitude - $delta_lng;
		$max_lng   = $longitude + $delta_lng;

		$table_name = Schema::get_locations_table();

		// Transient query cache.
		$cache_key = 'mb_spatial_' . md5( "{$latitude}_{$longitude}_{$radius}_{$unit}_{$limit}" );
		$cached    = wp_cache_get( $cache_key, 'mb_engine' );
		if ( false !== $cached ) {
			return $cached;
		}

		// Haversine formula SQL query.
		$sql = $wpdb->prepare(
			"SELECT loc.post_id, loc.postal_code, loc.city, loc.country_code, loc.latitude, loc.longitude,
			( %f * acos(
				LEAST( 1.0, GREATEST( -1.0,
					cos( radians(%f) ) *
					cos( radians( loc.latitude ) ) *
					cos( radians( loc.longitude ) - radians(%f) ) +
					sin( radians(%f) ) *
					sin( radians( loc.latitude ) )
				) )
			) ) AS distance
			FROM {$wpdb->prefix}mb_locations loc
			INNER JOIN {$wpdb->posts} p ON (loc.post_id = p.ID AND p.post_status = 'publish')
			WHERE loc.latitude BETWEEN %f AND %f
			  AND loc.longitude BETWEEN %f AND %f
			HAVING distance <= %f
			ORDER BY distance ASC
			LIMIT %d",
			$earth_radius,
			$latitude,
			$longitude,
			$latitude,
			$min_lat,
			$max_lat,
			$min_lng,
			$max_lng,
			$radius,
			$limit
		);

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		if ( ! is_array( $results ) ) {
			$results = array();
		}

		wp_cache_set( $cache_key, $results, 'mb_engine', 3600 );

		return $results;
	}

	/**
	 * Search locations directly in database by postal code or city text.
	 * Guarantees that existing database listings are found immediately without external network dependencies.
	 *
	 * @param string $query_str Postal code or city search query.
	 * @param int    $limit Maximum results.
	 * @return array Array of location rows.
	 */
	public static function search_by_postal_or_city( $query_str, $limit = 50 ) {
		global $wpdb;

		$query_str = trim( (string) $query_str );
		if ( '' === $query_str ) {
			return array();
		}

		$clean_norm = ZipNormalizer::normalize( $query_str );
		$prefix     = ZipNormalizer::get_prefix( $query_str );
		$table_name = Schema::get_locations_table();
		$limit      = absint( $limit );
		if ( $limit <= 0 ) {
			$limit = 50;
		}

		// Exact matches or prefix/wildcard matches.
		$like_query = '%' . $wpdb->esc_like( $query_str ) . '%';
		$like_clean = ( '' !== $clean_norm ) ? ( $wpdb->esc_like( $clean_norm ) . '%' ) : $like_query;
		$like_pfx   = ( '' !== $prefix ) ? ( $wpdb->esc_like( $prefix ) . '%' ) : $like_clean;

		$sql = $wpdb->prepare(
			"SELECT loc.post_id, loc.postal_code, loc.postal_code_clean, loc.city, loc.country_code, loc.latitude, loc.longitude, 0.0 AS distance
			FROM {$wpdb->prefix}mb_locations loc
			INNER JOIN {$wpdb->posts} p ON (loc.post_id = p.ID AND p.post_status = 'publish')
			WHERE loc.postal_code = %s
			   OR loc.postal_code_clean = %s
			   OR loc.postal_code LIKE %s
			   OR loc.postal_code_clean LIKE %s
			   OR loc.postal_code LIKE %s
			   OR loc.city LIKE %s
			ORDER BY 
				CASE 
					WHEN loc.postal_code = %s OR loc.postal_code_clean = %s THEN 1
					WHEN loc.postal_code LIKE %s THEN 2
					WHEN loc.city LIKE %s THEN 3
					ELSE 4
				END ASC
			LIMIT %d",
			$query_str,
			$clean_norm,
			$like_query,
			$like_clean,
			$like_pfx,
			$like_query,
			$query_str,
			$clean_norm,
			$like_pfx,
			$like_query,
			$limit
		);

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return is_array( $results ) ? $results : array();
	}

	/**
	 * Comprehensive multi-tier location search.
	 * 1. Tier 1: Local database exact/clean/wildcard postal code and city match.
	 * 2. Tier 2: Spatial coordinate expansion via Geocoder or matched entity anchors.
	 * 3. Tier 3: Merge and deduplicate, prioritizing exact matches.
	 *
	 * @param string $postal_code Postal code.
	 * @param string $city City.
	 * @param string $country Country code/name.
	 * @param float  $radius Radius in distance units.
	 * @param string $unit 'km' or 'miles'.
	 * @param int    $limit Max results.
	 * @return array Array with 'results' => list of location objects, 'center' => lat/lng array or null.
	 */
	public static function search_locations( $postal_code = '', $city = '', $country = '', $radius = 25.0, $unit = 'km', $limit = 50 ) {
		$postal_code = trim( (string) $postal_code );
		$city        = trim( (string) $city );
		$country     = trim( (string) $country );
		$radius      = floatval( $radius );
		if ( $radius <= 0 ) {
			$radius = 25.0;
		}

		$direct_matches = array();
		$seen_post_ids  = array();
		$center_coords  = null;

		// 1. Direct local database search if postal_code or city provided.
		$search_terms = array_filter( array( $postal_code, $city ) );
		foreach ( $search_terms as $term ) {
			$matches = self::search_by_postal_or_city( $term, $limit );
			foreach ( $matches as $m ) {
				$pid = (int) $m->post_id;
				if ( ! isset( $seen_post_ids[ $pid ] ) ) {
					$seen_post_ids[ $pid ] = true;
					$direct_matches[]      = $m;

					// If this match has valid coordinates, use it as anchor if none yet.
					if ( null === $center_coords && ( 0.0 !== floatval( $m->latitude ) || 0.0 !== floatval( $m->longitude ) ) ) {
						$center_coords = array(
							'lat' => floatval( $m->latitude ),
							'lng' => floatval( $m->longitude ),
						);
					}
				}
			}
		}

		// 2. Resolve geocoding if possible to get geographic center.
		if ( null === $center_coords && ( ! empty( $postal_code ) || ! empty( $city ) ) ) {
			$geo = Geocoder::geocode( $postal_code, $city, $country );
			if ( $geo ) {
				$center_coords = $geo;
			}
		}

		// 3. If center coordinates exist, perform spatial radius expansion.
		$radius_matches = array();
		if ( $center_coords ) {
			$spatial_rows = self::find_entities_within_radius(
				$center_coords['lat'],
				$center_coords['lng'],
				$radius,
				$unit,
				$limit
			);

			foreach ( $spatial_rows as $row ) {
				$pid = (int) $row->post_id;
				// If not already in direct matches, add it.
				if ( ! isset( $seen_post_ids[ $pid ] ) ) {
					$seen_post_ids[ $pid ] = true;
					$radius_matches[]      = $row;
				} else {
					// Update distance for direct matches with real computed distance.
					foreach ( $direct_matches as &$dm ) {
						if ( (int) $dm->post_id === $pid ) {
							$dm->distance = $row->distance;
							break;
						}
					}
					unset( $dm );
				}
			}
		}

		// Merge results: direct matches first, followed by radius expansion matches.
		$all_results = array_merge( $direct_matches, $radius_matches );

		return array(
			'results' => $all_results,
			'center'  => $center_coords,
		);
	}

	/**
	 * Calculate direct distance between two coordinate pairs.
	 *
	 * @param float  $lat1 First latitude.
	 * @param float  $lng1 First longitude.
	 * @param float  $lat2 Second latitude.
	 * @param float  $lng2 Second longitude.
	 * @param string $unit 'km' or 'miles'.
	 * @return float
	 */
	public static function calculate_distance( $lat1, $lng1, $lat2, $lng2, $unit = 'km' ) {
		$earth_radius = ( 'miles' === $unit ) ? self::EARTH_RADIUS_MILES : self::EARTH_RADIUS_KM;

		$d_lat = deg2rad( $lat2 - $lat1 );
		$d_lng = deg2rad( $lng2 - $lng1 );

		$a = sin( $d_lat / 2 ) * sin( $d_lat / 2 ) +
			cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) *
			sin( $d_lng / 2 ) * sin( $d_lng / 2 );

		$c = 2 * asin( min( 1.0, sqrt( $a ) ) );

		return round( $earth_radius * $c, 2 );
	}
}

