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
	 * Handle REST search request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function search_entities( $request ) {
		ob_start();
		try {
			$params = array(
				'postal_code' => $request->get_param( 'postal_code' ),
				'city'        => $request->get_param( 'city' ),
				'country'     => $request->get_param( 'country' ),
				'radius'      => $request->get_param( 'radius' ),
				'type'        => $request->get_param( 'type' ),
				'model'       => $request->get_param( 'model' ),
				'min_price'   => $request->get_param( 'min_price' ),
				'max_price'   => $request->get_param( 'max_price' ),
				'page'        => $request->get_param( 'page' ),
				'per_page'    => $request->get_param( 'per_page' ),
			);
			$data = $this->execute_search( $params );
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			return rest_ensure_response( $data );
		} catch ( \Throwable $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
					'items'   => array(),
				),
				500
			);
		}
	}

	/**
	 * AJAX fallback handler for search queries (admin-ajax.php?action=mb_search_entities).
	 *
	 * @return void
	 */
	public static function ajax_search_entities_static() {
		$instance = new self();
		$instance->ajax_search_entities();
	}

	/**
	 * Instance AJAX handler.
	 *
	 * @return void
	 */
	public function ajax_search_entities() {
		ob_start();
		try {
			$params = array(
				'postal_code' => isset( $_GET['postal_code'] ) ? sanitize_text_field( wp_unslash( $_GET['postal_code'] ) ) : '',
				'city'        => isset( $_GET['city'] ) ? sanitize_text_field( wp_unslash( $_GET['city'] ) ) : '',
				'country'     => isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : '',
				'radius'      => isset( $_GET['radius'] ) ? floatval( $_GET['radius'] ) : 25.0,
				'type'        => isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '',
				'model'       => isset( $_GET['model'] ) ? sanitize_key( wp_unslash( $_GET['model'] ) ) : '',
				'min_price'   => isset( $_GET['min_price'] ) ? floatval( $_GET['min_price'] ) : 0.0,
				'max_price'   => isset( $_GET['max_price'] ) ? floatval( $_GET['max_price'] ) : 0.0,
				'page'        => isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1,
				'per_page'    => isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 12,
			);
			$data = $this->execute_search( $params );
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			wp_send_json( $data );
		} catch ( \Throwable $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			wp_send_json_error( array( 'message' => $e->getMessage(), 'items' => array() ), 500 );
		}
	}

	/**
	 * Shared search execution logic for both REST and AJAX calls.
	 *
	 * @param array $params Query parameters.
	 * @return array
	 */
	public function execute_search( array $params ) {
		$postal_code = isset( $params['postal_code'] ) ? sanitize_text_field( $params['postal_code'] ) : '';
		$city        = isset( $params['city'] ) ? sanitize_text_field( $params['city'] ) : '';
		$country     = isset( $params['country'] ) ? sanitize_text_field( $params['country'] ) : '';
		$radius      = isset( $params['radius'] ) ? floatval( $params['radius'] ) : 25.0;
		$type_slug   = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : '';
		$model_type  = isset( $params['model'] ) ? sanitize_key( $params['model'] ) : '';
		$min_price   = isset( $params['min_price'] ) ? floatval( $params['min_price'] ) : 0.0;
		$max_price   = isset( $params['max_price'] ) ? floatval( $params['max_price'] ) : 0.0;
		$per_page    = isset( $params['per_page'] ) ? absint( $params['per_page'] ) : 12;
		$page        = isset( $params['page'] ) ? max( 1, absint( $params['page'] ) ) : 1;

		if ( $per_page <= 0 ) {
			$per_page = 12;
		}

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
				return array(
					'success'         => true,
					'geocoded_center' => $geocoded_center,
					'total'           => 0,
					'items'           => array(),
				);
			}
		}

		// 2. Query WP Posts for matching entities.
		$args = array(
			'post_type'      => 'mb_booking_entity',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
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

		// Meta filters (Model type, Layout and Price).
		$meta_query = array( 'relation' => 'AND' );

		if ( ! empty( $model_type ) ) {
			$model_map = array(
				'hotel_room'          => array( 'night_stay', 'hotel' ),
				'daily_booking'       => array( 'day_rental', 'rental' ),
				'hourly_booking'      => array( 'hourly_slot', 'hourly' ),
				'doctor_professional' => array( 'hourly_slot', 'doctor' ),
				'salon_spa'           => array( 'hourly_slot', 'salon' ),
				'shop_business'       => array( 'hourly_slot', 'shop' ),
			);

			$targets = isset( $model_map[ $model_type ] ) ? $model_map[ $model_type ] : array( $model_type );

			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_mb_model_type',
					'value'   => $targets,
					'compare' => 'IN',
				),
				array(
					'key'     => '_mb_visual_layout',
					'value'   => $targets,
					'compare' => 'IN',
				),
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

				$raw_excerpt   = wp_strip_all_tags( get_the_excerpt() );
				if ( empty( $raw_excerpt ) ) {
					$raw_excerpt = wp_strip_all_tags( get_post_field( 'post_content', $id ) );
				}
				$short_excerpt = wp_trim_words( $raw_excerpt, 8, '...' );

				$items[] = array(
					'id'            => $id,
					'title'         => get_the_title(),
					'permalink'     => get_permalink(),
					'excerpt'       => $short_excerpt,
					'thumbnail'     => $entity->get_thumbnail_url( 'medium' ),
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

		return array(
			'success'         => true,
			'geocoded_center' => $geocoded_center,
			'total'           => $query->found_posts,
			'items'           => $items,
		);
	}
}
