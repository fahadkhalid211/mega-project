<?php
/**
 * Custom Post Type and Taxonomy Registration.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Admin;

use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PostType
 */
class PostType {

	const POST_TYPE = 'mb_booking_entity';
	const TAXONOMY  = 'mb_entity_type';

	/**
	 * Register CPT and Taxonomies.
	 *
	 * @return void
	 */
	public static function register() {
		self::register_post_type();
		self::register_taxonomy();

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'manage_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
	}

	/**
	 * Register Custom Post Type.
	 *
	 * @return void
	 */
	private static function register_post_type() {
		$labels = array(
			'name'               => _x( 'Booking Entities', 'post type general name', 'my-booking-engine' ),
			'singular_name'      => _x( 'Booking Entity', 'post type singular name', 'my-booking-engine' ),
			'menu_name'          => _x( 'Booking Engine', 'admin menu', 'my-booking-engine' ),
			'name_admin_bar'     => _x( 'Booking Entity', 'add new on admin bar', 'my-booking-engine' ),
			'add_new'            => _x( 'Add New', 'entity', 'my-booking-engine' ),
			'add_new_item'       => __( 'Add New Booking Entity (Service, Rental, Property, Event)', 'my-booking-engine' ),
			'new_item'           => __( 'New Booking Entity', 'my-booking-engine' ),
			'edit_item'          => __( 'Edit Booking Entity', 'my-booking-engine' ),
			'view_item'          => __( 'View Booking Entity', 'my-booking-engine' ),
			'all_items'          => __( 'All Entities', 'my-booking-engine' ),
			'search_items'       => __( 'Search Booking Entities', 'my-booking-engine' ),
			'not_found'          => __( 'No booking entities found.', 'my-booking-engine' ),
			'not_found_in_trash' => __( 'No booking entities found in Trash.', 'my-booking-engine' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'booking' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 26,
			'menu_icon'          => 'dashicons-calendar-alt',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'show_in_rest'       => true,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register Custom Taxonomy for Entity Types.
	 *
	 * @return void
	 */
	private static function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Entity Types', 'taxonomy general name', 'my-booking-engine' ),
			'singular_name'     => _x( 'Entity Type', 'taxonomy singular name', 'my-booking-engine' ),
			'search_items'      => __( 'Search Entity Types', 'my-booking-engine' ),
			'all_items'         => __( 'All Entity Types', 'my-booking-engine' ),
			'parent_item'       => __( 'Parent Entity Type', 'my-booking-engine' ),
			'parent_item_colon' => __( 'Parent Entity Type:', 'my-booking-engine' ),
			'edit_item'         => __( 'Edit Entity Type', 'my-booking-engine' ),
			'update_item'       => __( 'Update Entity Type', 'my-booking-engine' ),
			'add_new_item'      => __( 'Add New Entity Type', 'my-booking-engine' ),
			'new_item_name'     => __( 'New Entity Type Name', 'my-booking-engine' ),
			'menu_name'         => __( 'Categories / Types', 'my-booking-engine' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'entity-type' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( self::TAXONOMY, array( self::POST_TYPE ), $args );
	}

	/**
	 * Custom columns for admin list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function manage_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			if ( 'title' === $key ) {
				$new_columns['mb_thumb'] = __( 'Thumb', 'my-booking-engine' );
			}
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['mb_model']    = __( 'Booking Model', 'my-booking-engine' );
				$new_columns['mb_price']    = __( 'Base Rate', 'my-booking-engine' );
				$new_columns['mb_location'] = __( 'Location (Postal / City)', 'my-booking-engine' );
				$new_columns['mb_capacity'] = __( 'Capacity', 'my-booking-engine' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_columns( $column, $post_id ) {
		$entity = new BookingEntity( $post_id );

		switch ( $column ) {
			case 'mb_thumb':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'style' => 'border-radius:4px;object-fit:cover;' ) );
				} else {
					echo '<span style="display:inline-block;width:48px;height:48px;background:#f0f0f1;border-radius:4px;text-align:center;line-height:48px;color:#8c8f94;"><span class="dashicons dashicons-calendar-alt"></span></span>';
				}
				break;

			case 'mb_model':
				$models = array(
					'hourly_slot'     => __( 'Hourly Appointment', 'my-booking-engine' ),
					'day_rental'      => __( 'Day Rental (Car/Gear)', 'my-booking-engine' ),
					'night_stay'      => __( 'Night Stay (Property/Hotel)', 'my-booking-engine' ),
					'capacity_roster' => __( 'Capacity Event / Tour', 'my-booking-engine' ),
				);
				$type   = $entity->get_model_type();
				$badge_color = ( 'hourly_slot' === $type ) ? '#2271b1' : ( ( 'day_rental' === $type ) ? '#007017' : ( ( 'night_stay' === $type ) ? '#8a2487' : '#d63638' ) );
				echo '<span style="display:inline-block;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:600;color:#fff;background:' . esc_attr( $badge_color ) . ';">' . esc_html( $models[ $type ] ?? $type ) . '</span>';
				break;

			case 'mb_price':
				$settings = get_option( 'mb_engine_settings', array() );
				$sym      = $settings['currency_symbol'] ?? '$';
				echo esc_html( $sym . number_format( $entity->get_base_price(), 2 ) );
				break;

			case 'mb_location':
				$loc = $entity->get_location();
				if ( $loc && ( ! empty( $loc->city ) || ! empty( $loc->postal_code ) ) ) {
					echo '<strong>' . esc_html( $loc->postal_code ) . '</strong> ' . esc_html( $loc->city );
					if ( ! empty( $loc->country_code ) ) {
						echo ' (' . esc_html( $loc->country_code ) . ')';
					}
				} else {
					echo '<em style="color:#8c8f94;">' . esc_html__( 'No location saved', 'my-booking-engine' ) . '</em>';
				}
				break;

			case 'mb_capacity':
				echo esc_html( $entity->get_capacity() );
				break;
		}
	}
}
