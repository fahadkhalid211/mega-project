<?php
/**
 * REST Endpoint for Searching and Geolocation Radius Filtering.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Api;

use MyBookingEngine\Geo\Geocoder;
use MyBookingEngine\Geo\SpatialQuery;
use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SearchEndpoint
 */
class SearchEndpoint extends RestController {

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/search',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_entities' ),
					'permission_callback' => array( $this, 'public_permission_check' ),
					'args'                => array(
						'postal_code' => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'city'        => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'country'     => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'radius'      => array(
							'sanitize_callback' => 'floatval',
							'default'           => 25.0,
						),
						'type'        => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'model'       => array(
							'sanitize_callback' => 'sanitize_key',
							'default'           => '',
						),
						'min_price'   => array(
							'sanitize_callback' => 'floatval',
							'default'           => 0.0,
						),
						'max_price'   => array(
							'sanitize_callback' => 'floatval',
							'default'           => 0.0,
						),
						'page'        => array(
							'sanitize_callback' => 'absint',
							'default'           => 1,
						),
						'per_page'    => array(
							'sanitize_callback' => 'absint',
							'default'           => 12,
						),
					),
				),
			)
		);
	}

	/**
	 * Handle search request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function search_entities( $request ) {
		$postal_code = $request->get_param( 'postal_code' );
		$city        = $request->get_param( 'city' );
		$country     = $request->get_param( 'country' );
		$radius      = floatval( $request->get_param( 'radius' ) );
		$type_slug   = $request->get_param( 'type' );
		$model_type  = $request->get_param( 'model' );
		$min_price   = floatval( $request->get_param( 'min_price' ) );
		$max_price   = floatval( $request->get_param( 'max_price' ) );

		$settings = get_option( 'mb_engine_settings', array() );
		$unit     = isset( $settings['distance_unit'] ) ? $settings['distance_unit'] : 'km';

		$entity_distances = array();
		$geocoded_center  = null;

		// 1. Perform Multi-Tier Location Search if location criteria provided.
		$has_location_filter = ( ! empty( $postal_code ) || ! empty( $city ) );
		if ( $has_location_filter ) {
			$spatial_data = SpatialQuery::search_locations(
				$postal_code,
				$city,
				$country,
				$radius,
				$unit,
				100
			);

			$geocoded_center = $spatial_data['center'];

			foreach ( $spatial_data['results'] as $row ) {
				$entity_distances[ (int) $row->post_id ] = round( floatval( $row->distance ), 1 );
			}

			// If search location was specified but NO entities matched locally or within radius, return empty.
			if ( empty( $entity_distances ) ) {
				return rest_ensure_response(
					array(
						'success'         => true,
						'geocoded_center' => $geocoded_center,
						'total'           => 0,
						'items'           => array(),
					)
				);
			}
		}

		// 2. Query WP Posts for matching entities.
		$args = array(
			'post_type'      => 'mb_booking_entity',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ),
			'paged'          => $request->get_param( 'page' ),
		);

		// If spatial filter was applied, restrict post__in.
		if ( ! empty( $entity_distances ) ) {
			$args['post__in'] = array_keys( $entity_distances );
			$args['orderby']  = 'post__in';
		}

		// Taxonomy filter.
		if ( ! empty( $type_slug ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'mb_entity_type',
					'field'    => 'slug',
					'terms'    => $type_slug,
				),
			);
		}

		// Meta filters (Model type and Price).
		$meta_query = array( 'relation' => 'AND' );

		if ( ! empty( $model_type ) ) {
			$meta_query[] = array(
				'key'   => '_mb_model_type',
				'value' => $model_type,
			);
		}

		if ( $min_price > 0 ) {
			$meta_query[] = array(
				'key'     => '_mb_base_price',
				'value'   => $min_price,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		}

		if ( $max_price > 0 ) {
			$meta_query[] = array(
				'key'     => '_mb_base_price',
				'value'   => $max_price,
				'type'    => 'NUMERIC',
				'compare' => '<=',
			);
		}

		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $args );
		$items = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id     = get_the_ID();
				$entity = new BookingEntity( $id );
				$loc    = $entity->get_location();

				$distance_text = '';
				if ( isset( $entity_distances[ $id ] ) ) {
					$distance_text = $entity_distances[ $id ] . ' ' . $unit;
				}

				$items[] = array(
					'id'            => $id,
					'title'         => get_the_title(),
					'permalink'     => get_permalink(),
					'excerpt'       => wp_strip_all_tags( get_the_excerpt() ),
					'thumbnail'     => $entity->get_thumbnail_url(),
					'gallery'       => $entity->get_gallery_images( 'medium' ),
					'model_type'    => $entity->get_model_type(),
					'visual_layout' => $entity->get_visual_layout(),
					'base_price'    => $entity->get_base_price(),
					'weekend_price' => $entity->get_weekend_price(),
					'capacity'      => $entity->get_capacity(),
					'rating'        => $entity->get_rating_average(),
					'review_count'  => $entity->get_review_count(),
					'amenities'     => array_slice( $entity->get_amenities(), 0, 4 ),
					'distance'      => isset( $entity_distances[ $id ] ) ? $entity_distances[ $id ] : null,
					'distance_text' => $distance_text,
					'location'      => $loc ? array(
						'city'        => $loc->city,
						'postal_code' => $loc->postal_code,
						'country'     => $loc->country_code,
						'lat'         => ( 0.0 !== floatval( $loc->latitude ) ) ? floatval( $loc->latitude ) : null,
						'lng'         => ( 0.0 !== floatval( $loc->longitude ) ) ? floatval( $loc->longitude ) : null,
					) : null,
				);
			}
			wp_reset_postdata();
		}

		// Sort items by distance if distance filter is active.
		if ( ! empty( $entity_distances ) ) {
			usort(
				$items,
				function( $a, $b ) {
					if ( null === $a['distance'] || null === $b['distance'] ) {
						return 0;
					}
					return $a['distance'] <=> $b['distance'];
				}
			);
		}

		return rest_ensure_response(
			array(
				'success'         => true,
				'geocoded_center' => $geocoded_center,
				'total'           => $query->found_posts,
				'items'           => $items,
			)
		);
	}
}
