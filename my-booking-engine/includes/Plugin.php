<?php
/**
 * Main Plugin coordinator class.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine;

use MyBookingEngine\Database\Schema;
use MyBookingEngine\Admin\PostType;
use MyBookingEngine\Admin\MetaBoxes;
use MyBookingEngine\Admin\AdminMenu;
use MyBookingEngine\Admin\SettingsPage;
use MyBookingEngine\Api\SearchEndpoint;
use MyBookingEngine\Api\SlotsEndpoint;
use MyBookingEngine\Api\BookingEndpoint;
use MyBookingEngine\Api\ReviewsEndpoint;
use MyBookingEngine\Presentation\TemplateLoader;
use MyBookingEngine\Integrations\WooCommerce\ProductType as WcProductType;
use MyBookingEngine\Integrations\WooCommerce\CartManager as WcCartManager;
use MyBookingEngine\Integrations\WooCommerce\OrderSync as WcOrderSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Plugin activation routine.
	 *
	 * @return void
	 */
	public static function activate() {
		Schema::install();

		// Ensure $wp_rewrite is available if flushing rewrites.
		global $wp_rewrite;
		if ( ! is_object( $wp_rewrite ) ) {
			require_once ABSPATH . WPINC . '/class-wp-rewrite.php';
			$wp_rewrite = new \WP_Rewrite();
		}

		PostType::register();

		// Flush rewrites.
		flush_rewrite_rules();

		// Set default settings if not already set.
		if ( false === get_option( 'mb_engine_settings' ) ) {
			update_option(
				'mb_engine_settings',
				array(
					'distance_unit'       => 'km',
					'geocoder_provider'   => 'nominatim',
					'google_api_key'      => '',
					'lock_duration'       => 10, // minutes
					'enable_woocommerce'  => 'yes',
					'currency_symbol'     => '$',
					'search_default_rad'  => 25,
				)
			);
		}
	}

	/**
	 * Plugin deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Initialize plugin components and hooks.
	 *
	 * @return void
	 */
	public function init() {
		// Register Post Types, Taxonomies, and Translations on 'init' (required by WP 6.7+).
		add_action( 'init', array( $this, 'on_init' ), 0 );

		// Admin hooks.
		if ( is_admin() ) {
			AdminMenu::init();
			MetaBoxes::init();
			SettingsPage::init();
		}

		// Single listing & archive template router.
		TemplateLoader::init();

		// REST API Endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// WooCommerce Integration (if WooCommerce active).
		$this->init_woocommerce();

		// Frontend asset enqueue and shortcodes.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		$this->register_shortcodes();
	}

	/**
	 * Hooked to WordPress 'init' action.
	 *
	 * @return void
	 */
	public function on_init() {
		// Load textdomain safely on 'init' as required by WP 6.7+.
		load_plugin_textdomain( 'my-booking-engine', false, dirname( MB_ENGINE_BASENAME ) . '/languages' );

		// Register Post Types & Taxonomies when $wp_rewrite is ready.
		PostType::register();

		// Auto-migrate schema if updated without reactivation.
		if ( get_option( 'mb_engine_db_version' ) !== Schema::DB_VERSION ) {
			Schema::install();
		}
	}

	/**
	 * Register REST API Endpoints.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		$search_endpoint  = new SearchEndpoint();
		$slots_endpoint   = new SlotsEndpoint();
		$booking_endpoint = new BookingEndpoint();
		$reviews_endpoint = new ReviewsEndpoint();

		$search_endpoint->register_routes();
		$slots_endpoint->register_routes();
		$booking_endpoint->register_routes();
		$reviews_endpoint->register_routes();
	}

	/**
	 * Initialize WooCommerce bridge if WC is present.
	 *
	 * @return void
	 */
	private function init_woocommerce() {
		$settings = get_option( 'mb_engine_settings', array() );
		$enabled  = isset( $settings['enable_woocommerce'] ) ? $settings['enable_woocommerce'] : 'yes';

		if ( 'yes' === $enabled && class_exists( 'WooCommerce' ) ) {
			WcProductType::init();
			WcCartManager::init();
			WcOrderSync::init();
		}
	}

	/**
	 * Register frontend shortcodes.
	 *
	 * @return void
	 */
	private function register_shortcodes() {
		add_shortcode( 'mb_search_filter', array( $this, 'render_search_filter_shortcode' ) );
		add_shortcode( 'mb_booking_form', array( $this, 'render_booking_form_shortcode' ) );
		add_shortcode( 'mb_entities', array( $this, 'render_entities_shortcode' ) );
	}

	/**
	 * Render [mb_search_filter] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function render_search_filter_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'layout'     => 'grid',
				'limit'      => 9,
				'show_map'   => 'no',
			),
			$atts,
			'mb_search_filter'
		);

		ob_start();
		include MB_ENGINE_PATH . 'templates/frontend/search-filters.php';
		return ob_get_clean();
	}

	/**
	 * Render [mb_booking_form id="123"] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function render_booking_form_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'mb_booking_form'
		);

		$entity_id = absint( $atts['id'] );
		if ( ! $entity_id || 'mb_booking_entity' !== get_post_type( $entity_id ) ) {
			return '<p class="mb-error">' . esc_html__( 'Invalid booking entity specified.', 'my-booking-engine' ) . '</p>';
		}

		ob_start();
		include MB_ENGINE_PATH . 'templates/frontend/booking-modal.php';
		return ob_get_clean();
	}

	/**
	 * Render [mb_entities] grid shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function render_entities_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'  => '',
				'limit' => 6,
			),
			$atts,
			'mb_entities'
		);

		$args = array(
			'post_type'      => 'mb_booking_entity',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
		);

		if ( ! empty( $atts['type'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'mb_entity_type',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['type'] ),
				),
			);
		}

		$query = new \WP_Query( $args );

		ob_start();
		if ( $query->have_posts() ) {
			echo '<div class="mb-entities-grid">';
			while ( $query->have_posts() ) {
				$query->the_post();
				$entity_id = get_the_ID();
				include MB_ENGINE_PATH . 'templates/frontend/entity-card.php';
			}
			echo '</div>';
			wp_reset_postdata();
		} else {
			echo '<p>' . esc_html__( 'No booking entities found.', 'my-booking-engine' ) . '</p>';
		}
		return ob_get_clean();
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// 1. Unified Calendar CSS & JS.
		wp_enqueue_style(
			'mb-engine-calendar',
			MB_ENGINE_URL . 'assets/css/calendar.css',
			array(),
			MB_ENGINE_VERSION
		);

		// 2. Leaflet Map CSS & JS (Free, OpenStreetMap).
		wp_enqueue_style(
			'mb-engine-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);

		// 3. Main Marketplace Stylesheet.
		wp_enqueue_style(
			'mb-engine-frontend',
			MB_ENGINE_URL . 'assets/css/frontend.css',
			array( 'mb-engine-calendar', 'mb-engine-leaflet' ),
			MB_ENGINE_VERSION
		);

		wp_enqueue_script(
			'mb-engine-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);

		wp_enqueue_script(
			'mb-engine-calendar',
			MB_ENGINE_URL . 'assets/js/calendar.js',
			array(),
			MB_ENGINE_VERSION,
			true
		);

		wp_enqueue_script(
			'mb-engine-map',
			MB_ENGINE_URL . 'assets/js/map.js',
			array( 'mb-engine-leaflet' ),
			MB_ENGINE_VERSION,
			true
		);

		wp_enqueue_script(
			'mb-engine-funnel',
			MB_ENGINE_URL . 'assets/js/booking-funnel.js',
			array( 'mb-engine-calendar' ),
			MB_ENGINE_VERSION,
			true
		);

		wp_enqueue_script(
			'mb-engine-frontend',
			MB_ENGINE_URL . 'assets/js/frontend.js',
			array( 'mb-engine-calendar', 'mb-engine-map', 'mb-engine-funnel' ),
			MB_ENGINE_VERSION,
			true
		);

		$settings = get_option( 'mb_engine_settings', array() );

		wp_localize_script(
			'mb-engine-frontend',
			'mbEngineData',
			array(
				'restUrl'         => esc_url_raw( rest_url( 'my-booking-engine/v1/' ) ),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'currencySymbol'  => isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$',
				'distanceUnit'    => isset( $settings['distance_unit'] ) ? $settings['distance_unit'] : 'km',
				'singleEntityId'  => is_singular( 'mb_booking_entity' ) ? get_the_ID() : 0,
				'i18n'            => array(
					'searching'       => esc_html__( 'Searching verified listings...', 'my-booking-engine' ),
					'noResults'       => esc_html__( 'No listings found matching your location and filters.', 'my-booking-engine' ),
					'bookNow'         => esc_html__( 'Book Now', 'my-booking-engine' ),
					'showMap'         => esc_html__( 'Show Map', 'my-booking-engine' ),
					'hideMap'         => esc_html__( 'Hide Map', 'my-booking-engine' ),
					'checkSlots'      => esc_html__( 'Check Availability', 'my-booking-engine' ),
					'selectDate'      => esc_html__( 'Select a date to view available time slots.', 'my-booking-engine' ),
					'noSlots'         => esc_html__( 'No available slots for this date.', 'my-booking-engine' ),
					'confirmBooking'  => esc_html__( 'Confirming reservation...', 'my-booking-engine' ),
					'bookingSuccess'  => esc_html__( 'Booking reserved successfully! Redirecting...', 'my-booking-engine' ),
					'bookingError'    => esc_html__( 'Error processing reservation. Please try another slot.', 'my-booking-engine' ),
					'distanceAway'    => esc_html__( 'away', 'my-booking-engine' ),
					'capacityLeft'    => esc_html__( 'spots remaining', 'my-booking-engine' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		global $post;

		$is_mb_post_type = ( isset( $post->post_type ) && 'mb_booking_entity' === $post->post_type );
		$is_mb_page      = ( false !== strpos( $hook, 'mb-engine' ) );

		if ( ! $is_mb_post_type && ! $is_mb_page ) {
			return;
		}

		if ( $is_mb_post_type ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'mb-engine-admin',
			MB_ENGINE_URL . 'assets/css/admin.css',
			array(),
			MB_ENGINE_VERSION
		);

		wp_enqueue_script(
			'mb-engine-admin',
			MB_ENGINE_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			MB_ENGINE_VERSION,
			true
		);

		wp_localize_script(
			'mb-engine-admin',
			'mbAdminData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mb_admin_nonce' ),
				'i18n'    => array(
					'geocoding'        => esc_html__( 'Looking up coordinates...', 'my-booking-engine' ),
					'geocodeSuccess'   => esc_html__( 'Coordinates found and updated!', 'my-booking-engine' ),
					'geocodeNotFound'  => esc_html__( 'Location coordinates not found. Please refine Postal Code and Country.', 'my-booking-engine' ),
					'geocodeError'     => esc_html__( 'Geocoding request failed. Check your network or API keys.', 'my-booking-engine' ),
				),
			)
		);
	}
}
