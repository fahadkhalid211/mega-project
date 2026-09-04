<?php
/**
 * Booking Entity Model.
 * Represents a bookable service, rental car, property, or event.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Models;

use MyBookingEngine\Database\Schema;
use MyBookingEngine\Geo\Geocoder;
use MyBookingEngine\Geo\ZipNormalizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BookingEntity
 */
class BookingEntity {

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Constructor.
	 *
	 * @param int $post_id Entity post ID.
	 */
	public function __construct( $post_id ) {
		$this->id = absint( $post_id );
	}

	/**
	 * Get entity ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get entity title.
	 *
	 * @return string
	 */
	public function get_title() {
		return get_the_title( $this->id );
	}

	/**
	 * Get entity permalink.
	 *
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->id );
	}

	/**
	 * Get entity thumbnail URL.
	 *
	 * @param string $size Image size.
	 * @return string
	 */
	public function get_thumbnail_url( $size = 'medium' ) {
		$thumb = get_the_post_thumbnail_url( $this->id, $size );
		return $thumb ? $thumb : MB_ENGINE_URL . 'assets/images/placeholder.svg';
	}

	/**
	 * Get booking model type.
	 *
	 * @return string 'hourly_slot'|'day_rental'|'night_stay'|'capacity_roster'
	 */
	public function get_model_type() {
		$type = get_post_meta( $this->id, '_mb_model_type', true );
		return ! empty( $type ) ? $type : 'hourly_slot';
	}

	/**
	 * Get base price.
	 *
	 * @return float
	 */
	public function get_base_price() {
		return floatval( get_post_meta( $this->id, '_mb_base_price', true ) );
	}

	/**
	 * Get weekend price (if applicable).
	 *
	 * @return float
	 */
	public function get_weekend_price() {
		$price = get_post_meta( $this->id, '_mb_weekend_price', true );
		return '' !== $price ? floatval( $price ) : $this->get_base_price();
	}

	/**
	 * Get slot duration in minutes (for hourly_slot).
	 *
	 * @return int
	 */
	public function get_slot_duration() {
		$duration = absint( get_post_meta( $this->id, '_mb_slot_duration', true ) );
		return $duration > 0 ? $duration : 60;
	}

	/**
	 * Get buffer time before in minutes.
	 *
	 * @return int
	 */
	public function get_buffer_before() {
		return absint( get_post_meta( $this->id, '_mb_buffer_before', true ) );
	}

	/**
	 * Get buffer time after in minutes (prep/cleaning gap).
	 *
	 * @return int
	 */
	public function get_buffer_after() {
		return absint( get_post_meta( $this->id, '_mb_buffer_after', true ) );
	}

	/**
	 * Get maximum simultaneous capacity or seats.
	 *
	 * @return int
	 */
	public function get_capacity() {
		$capacity = absint( get_post_meta( $this->id, '_mb_capacity', true ) );
		return $capacity > 0 ? $capacity : 1;
	}

	/**
	 * Get minimum duration (days/nights).
	 *
	 * @return int
	 */
	public function get_min_duration() {
		$min = absint( get_post_meta( $this->id, '_mb_min_duration', true ) );
		return $min > 0 ? $min : 1;
	}

	/**
	 * Get maximum duration (days/nights).
	 *
	 * @return int
	 */
	public function get_max_duration() {
		$max = absint( get_post_meta( $this->id, '_mb_max_duration', true ) );
		return $max > 0 ? $max : 30;
	}

	/**
	 * Get check-in time for night_stay (e.g. '14:00').
	 *
	 * @return string
	 */
	public function get_checkin_time() {
		$time = get_post_meta( $this->id, '_mb_checkin_time', true );
		return ! empty( $time ) ? $time : '15:00';
	}

	/**
	 * Get check-out time for night_stay (e.g. '11:00').
	 *
	 * @return string
	 */
	public function get_checkout_time() {
		$time = get_post_meta( $this->id, '_mb_checkout_time', true );
		return ! empty( $time ) ? $time : '11:00';
	}

	/**
	 * Get event start date/time (for capacity_roster).
	 *
	 * @return string
	 */
	public function get_event_start() {
		return get_post_meta( $this->id, '_mb_event_start', true );
	}

	/**
	 * Get event end date/time (for capacity_roster).
	 *
	 * @return string
	 */
	public function get_event_end() {
		return get_post_meta( $this->id, '_mb_event_end', true );
	}

	/**
	 * Get location record from wp_mb_locations.
	 *
	 * @return object|false
	 */
	public function get_location() {
		global $wpdb;
		$table = Schema::get_locations_table();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mb_locations WHERE post_id = %d LIMIT 1",
				$this->id
			)
		);

		return $row ? $row : false;
	}

	/**
	 * Save or update location in custom database table.
	 *
	 * @param string $postal_code Postal code.
	 * @param string $city City name.
	 * @param string $country_code Country code.
	 * @param float  $latitude Latitude.
	 * @param float  $longitude Longitude.
	 * @return bool
	 */
	public function save_location( $postal_code, $city, $country_code, $latitude, $longitude ) {
		global $wpdb;
		$table = Schema::get_locations_table();

		$existing = $this->get_location();

		$postal_code_raw   = sanitize_text_field( $postal_code );
		$postal_code_clean = ZipNormalizer::normalize( $postal_code_raw );

		$data = array(
			'post_id'           => $this->id,
			'postal_code'       => $postal_code_raw,
			'postal_code_clean' => $postal_code_clean,
			'city'              => sanitize_text_field( $city ),
			'country_code'      => sanitize_text_field( $country_code ),
			'latitude'          => floatval( $latitude ),
			'longitude'         => floatval( $longitude ),
		);

		$format = array( '%d', '%s', '%s', '%s', '%s', '%f', '%f' );

		if ( $existing ) {
			return false !== $wpdb->update( $table, $data, array( 'id' => $existing->id ), $format, array( '%d' ) );
		} else {
			return false !== $wpdb->insert( $table, $data, $format );
		}
	}

	/**
	 * Retrieve weekly availability rules from wp_mb_availabilities.
	 *
	 * @return array
	 */
	public function get_availabilities() {
		global $wpdb;
		$table = Schema::get_availabilities_table();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mb_availabilities WHERE entity_id = %d ORDER BY day_of_week ASC, start_time ASC",
				$this->id
			)
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Save availability schedule rules.
	 *
	 * @param array $rules Array of schedule rules.
	 * @return void
	 */
	public function save_availabilities( array $rules ) {
		global $wpdb;
		$table = Schema::get_availabilities_table();

		// Delete existing rules for this entity.
		$wpdb->delete( $table, array( 'entity_id' => $this->id ), array( '%d' ) );

		foreach ( $rules as $rule ) {
			if ( empty( $rule['start_time'] ) || empty( $rule['end_time'] ) ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'entity_id'   => $this->id,
					'rule_type'   => isset( $rule['rule_type'] ) ? sanitize_key( $rule['rule_type'] ) : 'weekly_recurring',
					'day_of_week' => isset( $rule['day_of_week'] ) ? absint( $rule['day_of_week'] ) : null,
					'start_date'  => ! empty( $rule['start_date'] ) ? sanitize_text_field( $rule['start_date'] ) : null,
					'end_date'    => ! empty( $rule['end_date'] ) ? sanitize_text_field( $rule['end_date'] ) : null,
					'start_time'  => sanitize_text_field( $rule['start_time'] ),
					'end_time'    => sanitize_text_field( $rule['end_time'] ),
					'capacity'    => isset( $rule['capacity'] ) ? absint( $rule['capacity'] ) : $this->get_capacity(),
				),
				array( '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d' )
			);
		}
	}

	/**
	 * Get visual layout template slug.
	 * Decoupled from booking logic. Defaults according to booking model type.
	 *
	 * @return string 'hotel'|'rental'|'hourly'|'doctor'|'salon'|'shop'
	 */
	public function get_visual_layout() {
		$layout = get_post_meta( $this->id, '_mb_visual_layout', true );
		if ( ! empty( $layout ) ) {
			return sanitize_key( $layout );
		}

		// Fallback default mapping based on model type.
		$model = $this->get_model_type();
		switch ( $model ) {
			case 'hotel_room':
			case 'night_stay':
				return 'hotel';
			case 'daily_booking':
			case 'day_rental':
				return 'rental';
			case 'doctor_professional':
				return 'doctor';
			case 'salon_spa':
				return 'salon';
			case 'shop_business':
			case 'capacity_roster':
				return 'shop';
			case 'hourly_booking':
			case 'hourly_slot':
			default:
				return 'hourly';
		}
	}

	/**
	 * Get gallery image URLs.
	 *
	 * @param string $size Image size.
	 * @return array Array of image URLs.
	 */
	public function get_gallery_images( $size = 'large' ) {
		$gallery_meta = get_post_meta( $this->id, '_mb_gallery_images', true );
		$urls         = array();

		if ( ! empty( $gallery_meta ) ) {
			$ids = is_array( $gallery_meta ) ? $gallery_meta : explode( ',', $gallery_meta );
			foreach ( $ids as $img_id ) {
				$img_id = absint( trim( $img_id ) );
				if ( $img_id > 0 ) {
					$url = wp_get_attachment_image_url( $img_id, $size );
					if ( $url ) {
						$urls[] = $url;
					}
				}
			}
		}

		// If no gallery images uploaded, include featured thumbnail.
		if ( empty( $urls ) ) {
			$thumb = $this->get_thumbnail_url( $size );
			if ( $thumb ) {
				$urls[] = $thumb;
			}
		}

		return $urls;
	}

	/**
	 * Get amenities/features list.
	 *
	 * @return array Array of amenity strings.
	 */
	public function get_amenities() {
		$amenities = get_post_meta( $this->id, '_mb_amenities', true );
		if ( is_array( $amenities ) ) {
			return array_filter( array_map( 'sanitize_text_field', $amenities ) );
		}
		if ( is_string( $amenities ) && ! empty( $amenities ) ) {
			return array_filter( array_map( 'trim', explode( "\n", $amenities ) ) );
		}
		return array(
			__( 'High-Speed Wi-Fi', 'my-booking-engine' ),
			__( 'Dedicated Support', 'my-booking-engine' ),
			__( 'Free Parking / Access', 'my-booking-engine' ),
			__( 'Air Conditioned / Climate Control', 'my-booking-engine' ),
		);
	}

	/**
	 * Get services menu (for salon, doctor, hourly).
	 *
	 * @return array Array of service items.
	 */
	public function get_services() {
		$services = get_post_meta( $this->id, '_mb_services', true );
		if ( is_array( $services ) && ! empty( $services ) ) {
			return $services;
		}

		// Default primary service based on entity.
		return array(
			array(
				'id'          => 'primary_service',
				'name'        => $this->get_title(),
				'duration'    => $this->get_slot_duration(),
				'price'       => $this->get_base_price(),
				'description' => __( 'Standard full session with our dedicated professional.', 'my-booking-engine' ),
			),
		);
	}

	/**
	 * Get staff/specialists list.
	 *
	 * @return array Array of staff items.
	 */
	public function get_staff() {
		$staff = get_post_meta( $this->id, '_mb_staff', true );
		if ( is_array( $staff ) && ! empty( $staff ) ) {
			return $staff;
		}

		return array(
			array(
				'id'    => 'default_specialist',
				'name'  => get_the_author_meta( 'display_name', get_post_field( 'post_author', $this->id ) ),
				'role'  => __( 'Lead Specialist', 'my-booking-engine' ),
				'bio'   => __( 'Certified specialist with over 8 years of industry experience.', 'my-booking-engine' ),
				'image' => get_avatar_url( get_post_field( 'post_author', $this->id ), array( 'size' => 120 ) ),
			),
		);
	}

	/**
	 * Get frequently asked questions.
	 *
	 * @return array Array of Q&A pairs.
	 */
	public function get_faqs() {
		$faqs = get_post_meta( $this->id, '_mb_faqs', true );
		if ( is_array( $faqs ) && ! empty( $faqs ) ) {
			return $faqs;
		}

		return array(
			array(
				'question' => __( 'What is your cancellation policy?', 'my-booking-engine' ),
				'answer'   => __( 'Free cancellation is permitted up to 24 hours prior to scheduled start time.', 'my-booking-engine' ),
			),
			array(
				'question' => __( 'Are taxes and fees included in the price?', 'my-booking-engine' ),
				'answer'   => __( 'All standard fees and local taxes are transparently calculated at checkout.', 'my-booking-engine' ),
			),
		);
	}

	/**
	 * Get policy terms.
	 *
	 * @return string Policy text.
	 */
	public function get_policy() {
		$policy = get_post_meta( $this->id, '_mb_policy', true );
		if ( ! empty( $policy ) ) {
			return $policy;
		}
		return __( 'Cancellations made 24 hours or more before the reservation will receive a 100% refund. Please arrive 10 minutes prior to your booking start time.', 'my-booking-engine' );
	}

	/**
	 * Get average rating for this entity from approved reviews.
	 *
	 * @return float
	 */
	public function get_rating_average() {
		global $wpdb;
		$table = Schema::get_reviews_table();

		$avg = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(rating) FROM {$wpdb->prefix}mb_reviews WHERE entity_id = %d AND status = 'approved'",
				$this->id
			)
		);

		return ( null !== $avg ) ? round( floatval( $avg ), 1 ) : 5.0;
	}

	/**
	 * Get total count of approved reviews.
	 *
	 * @return int
	 */
	public function get_review_count() {
		global $wpdb;
		$table = Schema::get_reviews_table();

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mb_reviews WHERE entity_id = %d AND status = 'approved'",
				$this->id
			)
		);

		return absint( $count );
	}

	/**
	 * Get approved reviews for this entity.
	 *
	 * @param int $limit Max reviews to return.
	 * @return array
	 */
	public function get_reviews( $limit = 10 ) {
		global $wpdb;
		$table = Schema::get_reviews_table();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mb_reviews WHERE entity_id = %d AND status = 'approved' ORDER BY created_at DESC LIMIT %d",
				$this->id,
				absint( $limit )
			)
		);

		return is_array( $results ) ? $results : array();
	}
}
