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
		add_action( 'all_admin_notices', array( __CLASS__, 'render_listings_hero_banner' ) );
	}

	/**
	 * Register Custom Post Type.
	 *
	 * @return void
	 */
	private static function register_post_type() {
		$labels = array(
			'name'               => _x( 'Listings', 'post type general name', 'my-booking-engine' ),
			'singular_name'      => _x( 'Listing', 'post type singular name', 'my-booking-engine' ),
			'menu_name'          => _x( 'Booking Engine', 'admin menu', 'my-booking-engine' ),
			'name_admin_bar'     => _x( 'Listing', 'add new on admin bar', 'my-booking-engine' ),
			'add_new'            => _x( 'Add New', 'listing', 'my-booking-engine' ),
			'add_new_item'       => __( 'Add New Listing', 'my-booking-engine' ),
			'new_item'           => __( 'New Listing', 'my-booking-engine' ),
			'edit_item'          => __( 'Edit Listing', 'my-booking-engine' ),
			'view_item'          => __( 'View Listing', 'my-booking-engine' ),
			'all_items'          => __( 'All Listings', 'my-booking-engine' ),
			'search_items'       => __( 'Search Listings', 'my-booking-engine' ),
			'not_found'          => __( 'No listings found.', 'my-booking-engine' ),
			'not_found_in_trash' => __( 'No listings found in Trash.', 'my-booking-engine' ),
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
			'name'              => _x( 'Listing Categories', 'taxonomy general name', 'my-booking-engine' ),
			'singular_name'     => _x( 'Listing Category', 'taxonomy singular name', 'my-booking-engine' ),
			'search_items'      => __( 'Search Categories', 'my-booking-engine' ),
			'all_items'         => __( 'All Categories', 'my-booking-engine' ),
			'parent_item'       => __( 'Parent Category', 'my-booking-engine' ),
			'parent_item_colon' => __( 'Parent Category:', 'my-booking-engine' ),
			'edit_item'         => __( 'Edit Category', 'my-booking-engine' ),
			'update_item'       => __( 'Update Category', 'my-booking-engine' ),
			'add_new_item'      => __( 'Add New Category', 'my-booking-engine' ),
			'new_item_name'     => __( 'New Category Name', 'my-booking-engine' ),
			'menu_name'         => __( 'Listing Categories', 'my-booking-engine' ),
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
				$thumb_url = $entity->get_thumbnail_url( 'thumbnail' );
				echo '<img src="' . esc_url( $thumb_url ) . '" alt="" class="mb-admin-listing-thumb" loading="lazy" />';
				break;

			case 'mb_model':
				$models = array(
					'hourly_slot'     => __( 'Hourly Session', 'my-booking-engine' ),
					'day_rental'      => __( 'Day Rental', 'my-booking-engine' ),
					'night_stay'      => __( 'Night Stay', 'my-booking-engine' ),
					'capacity_roster' => __( 'Capacity Event', 'my-booking-engine' ),
				);
				$type = $entity->get_model_type();
				$pill_classes = array(
					'hourly_slot'     => 'mb-model-pill-hourly',
					'day_rental'      => 'mb-model-pill-rental',
					'night_stay'      => 'mb-model-pill-stay',
					'capacity_roster' => 'mb-model-pill-capacity',
				);
				$cls = isset( $pill_classes[ $type ] ) ? $pill_classes[ $type ] : 'mb-model-pill-default';
				echo '<span class="mb-admin-model-pill ' . esc_attr( $cls ) . '">' . esc_html( $models[ $type ] ?? $type ) . '</span>';
				break;

			case 'mb_price':
				$settings = get_option( 'mb_engine_settings', array() );
				$sym      = $settings['currency_symbol'] ?? '$';
				echo '<span class="mb-admin-price-pill">' . esc_html( $sym . number_format( $entity->get_base_price(), 2 ) ) . '</span>';
				break;

			case 'mb_location':
				$loc = $entity->get_location();
				if ( $loc && ( ! empty( $loc->city ) || ! empty( $loc->postal_code ) ) ) {
					echo '<div class="mb-admin-location-pill"><svg viewBox="0 0 24 24" width="13" height="13"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg> <span>' . esc_html( trim( "{$loc->postal_code} {$loc->city}" ) ) . '</span></div>';
				} else {
					echo '<span class="mb-admin-no-data">' . esc_html__( 'No location', 'my-booking-engine' ) . '</span>';
				}
				break;

			case 'mb_capacity':
				$cap = $entity->get_capacity();
				if ( $cap > 0 ) {
					echo '<span class="mb-admin-cap-pill"><svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> ' . esc_html( $cap ) . '</span>';
				} else {
					echo '<span class="mb-admin-no-data">—</span>';
				}
				break;
		}
	}

	/**
	 * Render listings hero banner and KPI cards above the listings table.
	 *
	 * @return void
	 */
	public static function render_listings_hero_banner() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || self::POST_TYPE !== $screen->post_type || 'edit' !== $screen->base ) {
			return;
		}

		$counts    = wp_count_posts( self::POST_TYPE );
		$published = isset( $counts->publish ) ? (int) $counts->publish : 0;
		$drafts    = isset( $counts->draft ) ? (int) $counts->draft : 0;
		$total     = $published + $drafts + ( isset( $counts->pending ) ? (int) $counts->pending : 0 );
		$terms_cnt = (int) wp_count_terms( array( 'taxonomy' => self::TAXONOMY, 'hide_empty' => false ) );
		?>
		<div class="mb-admin-dashboard-wrap mb-listings-dashboard-hero">
			<div class="mb-admin-header-hero">
				<div class="mb-admin-header-left">
					<div class="mb-header-badge">
						<svg viewBox="0 0 24 24" width="13" height="13"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
						<?php esc_html_e( 'Inventory & Experiences', 'my-booking-engine' ); ?>
					</div>
					<h1 class="mb-admin-title"><?php esc_html_e( 'All Listings', 'my-booking-engine' ); ?></h1>
					<p class="mb-admin-subtitle"><?php esc_html_e( 'Manage your catalog of properties, vehicle rentals, medical clinics, salons, and bookable activities.', 'my-booking-engine' ); ?></p>
				</div>
				<div class="mb-admin-header-right">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mb-add-listing' ) ); ?>" class="mb-btn mb-btn-primary mb-header-action-btn">
						<svg viewBox="0 0 24 24" width="15" height="15"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
						<?php esc_html_e( 'Add New Listing', 'my-booking-engine' ); ?>
					</a>
				</div>
			</div>

			<div class="mb-kpi-row">
				<div class="mb-kpi-card mb-kpi-total">
					<div class="mb-kpi-icon-wrap" style="background:#eff6ff;color:#2563eb;">
						<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
					</div>
					<div class="mb-kpi-content">
						<span class="mb-kpi-label"><?php esc_html_e( 'Total Listings', 'my-booking-engine' ); ?></span>
						<span class="mb-kpi-value"><?php echo esc_html( number_format_i18n( $total ) ); ?></span>
					</div>
				</div>

				<div class="mb-kpi-card mb-kpi-confirmed">
					<div class="mb-kpi-icon-wrap" style="background:#f0fdf4;color:#16a34a;">
						<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
					</div>
					<div class="mb-kpi-content">
						<span class="mb-kpi-label"><?php esc_html_e( 'Published / Active', 'my-booking-engine' ); ?></span>
						<span class="mb-kpi-value"><?php echo esc_html( number_format_i18n( $published ) ); ?></span>
					</div>
				</div>

				<div class="mb-kpi-card mb-kpi-pending">
					<div class="mb-kpi-icon-wrap" style="background:#fffbeb;color:#d97706;">
						<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
					</div>
					<div class="mb-kpi-content">
						<span class="mb-kpi-label"><?php esc_html_e( 'Drafts / Inactive', 'my-booking-engine' ); ?></span>
						<span class="mb-kpi-value"><?php echo esc_html( number_format_i18n( $drafts ) ); ?></span>
					</div>
				</div>

				<div class="mb-kpi-card mb-kpi-revenue">
					<div class="mb-kpi-icon-wrap" style="background:#faf5ff;color:#9333ea;">
						<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
					</div>
					<div class="mb-kpi-content">
						<span class="mb-kpi-label"><?php esc_html_e( 'Categories', 'my-booking-engine' ); ?></span>
						<span class="mb-kpi-value"><?php echo esc_html( number_format_i18n( $terms_cnt ) ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
