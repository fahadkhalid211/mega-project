<?php
/**
 * Meta Box Manager for Booking Entities.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Admin;

use MyBookingEngine\Models\BookingEntity;
use MyBookingEngine\Geo\Geocoder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MetaBoxes
 */
class MetaBoxes {

	/**
	 * Initialize meta boxes and AJAX actions.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_mb_booking_entity', array( __CLASS__, 'save_meta_box_data' ) );
		add_action( 'wp_ajax_mb_admin_geocode', array( __CLASS__, 'ajax_geocode_location' ) );
		add_action( 'wp_ajax_mb_quick_create_listing', array( __CLASS__, 'ajax_quick_create_listing' ) );
	}

	/**
	 * Register the entity configuration meta box.
	 *
	 * @return void
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'mb_entity_settings',
			__( 'Booking Entity Configuration & Geolocation', 'my-booking-engine' ),
			array( __CLASS__, 'render_meta_box' ),
			'mb_booking_entity',
			'normal',
			'high'
		);
	}

	/**
	 * Render the configuration meta box view.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'mb_save_entity_meta', 'mb_entity_meta_nonce' );

		$entity       = new BookingEntity( $post->ID );
		$location     = $entity->get_location();
		$availabilities = $entity->get_availabilities();

		include MB_ENGINE_PATH . 'templates/admin/metabox-entity-details.php';
	}

	/**
	 * Save meta box inputs and synchronize custom DB tables.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function save_meta_box_data( $post_id ) {
		if ( ! isset( $_POST['mb_entity_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_entity_meta_nonce'] ) ), 'mb_save_entity_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$entity = new BookingEntity( $post_id );

		// 1. Model & General Settings.
		$model_type = isset( $_POST['mb_model_type'] ) ? sanitize_key( wp_unslash( $_POST['mb_model_type'] ) ) : 'hourly_slot';
		update_post_meta( $post_id, '_mb_model_type', $model_type );

		$base_price    = isset( $_POST['mb_base_price'] ) ? floatval( wp_unslash( $_POST['mb_base_price'] ) ) : 0.00;
		$weekend_price = isset( $_POST['mb_weekend_price'] ) ? floatval( wp_unslash( $_POST['mb_weekend_price'] ) ) : $base_price;
		update_post_meta( $post_id, '_mb_base_price', $base_price );
		update_post_meta( $post_id, '_mb_weekend_price', $weekend_price );

		$slot_duration = isset( $_POST['mb_slot_duration'] ) ? absint( wp_unslash( $_POST['mb_slot_duration'] ) ) : 60;
		$buffer_before = isset( $_POST['mb_buffer_before'] ) ? absint( wp_unslash( $_POST['mb_buffer_before'] ) ) : 0;
		$buffer_after  = isset( $_POST['mb_buffer_after'] ) ? absint( wp_unslash( $_POST['mb_buffer_after'] ) ) : 0;
		$capacity      = isset( $_POST['mb_capacity'] ) ? max( 1, absint( wp_unslash( $_POST['mb_capacity'] ) ) ) : 1;

		update_post_meta( $post_id, '_mb_slot_duration', $slot_duration );
		update_post_meta( $post_id, '_mb_buffer_before', $buffer_before );
		update_post_meta( $post_id, '_mb_buffer_after', $buffer_after );
		update_post_meta( $post_id, '_mb_capacity', $capacity );

		$min_duration = isset( $_POST['mb_min_duration'] ) ? absint( wp_unslash( $_POST['mb_min_duration'] ) ) : 1;
		$max_duration = isset( $_POST['mb_max_duration'] ) ? absint( wp_unslash( $_POST['mb_max_duration'] ) ) : 30;
		update_post_meta( $post_id, '_mb_min_duration', $min_duration );
		update_post_meta( $post_id, '_mb_max_duration', $max_duration );

		$checkin_time  = isset( $_POST['mb_checkin_time'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_checkin_time'] ) ) : '15:00';
		$checkout_time = isset( $_POST['mb_checkout_time'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_checkout_time'] ) ) : '11:00';
		update_post_meta( $post_id, '_mb_checkin_time', $checkin_time );
		update_post_meta( $post_id, '_mb_checkout_time', $checkout_time );

		$event_start = isset( $_POST['mb_event_start'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_event_start'] ) ) : '';
		$event_end   = isset( $_POST['mb_event_end'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_event_end'] ) ) : '';
		update_post_meta( $post_id, '_mb_event_start', $event_start );
		update_post_meta( $post_id, '_mb_event_end', $event_end );

		// Visual Layout & Marketplace Fields (Decoupled from booking logic).
		$visual_layout = isset( $_POST['mb_visual_layout'] ) ? sanitize_key( wp_unslash( $_POST['mb_visual_layout'] ) ) : '';
		update_post_meta( $post_id, '_mb_visual_layout', $visual_layout );

		$gallery_images = isset( $_POST['mb_gallery_images'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_gallery_images'] ) ) : '';
		update_post_meta( $post_id, '_mb_gallery_images', $gallery_images );

		$amenities = isset( $_POST['mb_amenities'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mb_amenities'] ) ) : '';
		update_post_meta( $post_id, '_mb_amenities', $amenities );

		$policy = isset( $_POST['mb_policy'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mb_policy'] ) ) : '';
		update_post_meta( $post_id, '_mb_policy', $policy );

		// 2. Custom Location Table Synchronization.
		$postal_code = isset( $_POST['mb_postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_postal_code'] ) ) : '';
		$city        = isset( $_POST['mb_city'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_city'] ) ) : '';
		$country     = isset( $_POST['mb_country_code'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_country_code'] ) ) : '';
		$latitude    = isset( $_POST['mb_latitude'] ) ? floatval( wp_unslash( $_POST['mb_latitude'] ) ) : 0.0;
		$longitude   = isset( $_POST['mb_longitude'] ) ? floatval( wp_unslash( $_POST['mb_longitude'] ) ) : 0.0;

		// If lat/lng empty but postal/city provided, attempt auto-geocode.
		if ( ( 0.0 === $latitude && 0.0 === $longitude ) && ( ! empty( $postal_code ) || ! empty( $city ) ) ) {
			$geo = Geocoder::geocode( $postal_code, $city, $country );
			if ( $geo ) {
				$latitude  = $geo['lat'];
				$longitude = $geo['lng'];
			}
		}

		$entity->save_location( $postal_code, $city, $country, $latitude, $longitude );

		// 3. Weekly Availability Schedule Rules.
		$rules = array();
		if ( isset( $_POST['mb_schedule'] ) && is_array( $_POST['mb_schedule'] ) ) {
			$raw_schedule = (array) wp_unslash( $_POST['mb_schedule'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( $raw_schedule as $day_index => $day_data ) {
				if ( is_array( $day_data ) && ! empty( $day_data['enabled'] ) && ! empty( $day_data['start'] ) && ! empty( $day_data['end'] ) ) {
					$rules[] = array(
						'rule_type'   => 'weekly_recurring',
						'day_of_week' => absint( $day_index ),
						'start_time'  => sanitize_text_field( $day_data['start'] ) . ':00',
						'end_time'    => sanitize_text_field( $day_data['end'] ) . ':00',
						'capacity'    => $capacity,
					);
				}
			}
		}
		$entity->save_availabilities( $rules );
	}

	/**
	 * AJAX handler to auto-geocode an address from admin panel.
	 *
	 * @return void
	 */
	public static function ajax_geocode_location() {
		check_ajax_referer( 'mb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'my-booking-engine' ) ), 403 );
		}

		$postal_code = isset( $_POST['postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['postal_code'] ) ) : '';
		$city        = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
		$country     = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';

		$geo = Geocoder::geocode( $postal_code, $city, $country );
		if ( $geo ) {
			wp_send_json_success( $geo );
		} else {
			wp_send_json_error( array( 'message' => __( 'Could not find coordinates for this location.', 'my-booking-engine' ) ) );
		}
	}

	/**
	 * AJAX handler for the "Add New Listing" quick-create wizard modal.
	 * Creates the listing post and, via the save_post_mb_booking_entity
	 * hook, immediately saves every wizard field from the same request.
	 *
	 * @return void
	 */
	public static function ajax_quick_create_listing() {
		if ( ! isset( $_POST['mb_entity_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_entity_meta_nonce'] ) ), 'mb_save_entity_meta' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'my-booking-engine' ) ), 403 );
		}

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to create listings.', 'my-booking-engine' ) ), 403 );
		}

		$title = isset( $_POST['mb_quick_title'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_quick_title'] ) ) : '';

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a listing title before publishing.', 'my-booking-engine' ) ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'mb_booking_entity',
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not create the listing. Please try again.', 'my-booking-engine' ) ) );
		}

		// wp_insert_post() fires save_post_mb_booking_entity, which runs
		// self::save_meta_box_data( $post_id ) and reads pricing, location,
		// schedule, gallery, etc. straight from this same $_POST payload.
		wp_send_json_success(
			array(
				'redirect' => get_edit_post_link( $post_id, 'raw' ),
				'post_id'  => $post_id,
			)
		);
	}
}
