<?php
/**
 * Admin Settings Page Manager.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsPage
 */
class SettingsPage {

	/**
	 * Initialize settings.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Register settings and fields.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'mb_engine_settings_group',
			'mb_engine_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize plugin options.
	 *
	 * @param array $input Raw input values.
	 * @return array Sanitized values.
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['distance_unit'] = ( isset( $input['distance_unit'] ) && 'miles' === $input['distance_unit'] ) ? 'miles' : 'km';
		$sanitized['geocoder_provider'] = ( isset( $input['geocoder_provider'] ) && 'google' === $input['geocoder_provider'] ) ? 'google' : 'nominatim';
		$sanitized['google_api_key']    = isset( $input['google_api_key'] ) ? sanitize_text_field( $input['google_api_key'] ) : '';

		$lock_duration = isset( $input['lock_duration'] ) ? absint( $input['lock_duration'] ) : 10;
		$sanitized['lock_duration'] = max( 2, min( 60, $lock_duration ) );

		$sanitized['enable_woocommerce'] = ( isset( $input['enable_woocommerce'] ) && 'yes' === $input['enable_woocommerce'] ) ? 'yes' : 'no';
		$sanitized['currency_symbol']    = isset( $input['currency_symbol'] ) ? sanitize_text_field( $input['currency_symbol'] ) : '$';

		$radius = isset( $input['search_default_rad'] ) ? absint( $input['search_default_rad'] ) : 25;
		$sanitized['search_default_rad'] = max( 1, min( 500, $radius ) );

		// Brand Appearance & Color Customizer
		$primary = isset( $input['primary_color'] ) ? sanitize_hex_color( $input['primary_color'] ) : '#2563eb';
		$sanitized['primary_color'] = $primary ? $primary : '#2563eb';

		$hover = isset( $input['primary_hover'] ) ? sanitize_hex_color( $input['primary_hover'] ) : '#1d4ed8';
		$sanitized['primary_hover'] = $hover ? $hover : '#1d4ed8';

		$accent = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : '#f59e0b';
		$sanitized['accent_color'] = $accent ? $accent : '#f59e0b';

		$radius_val = isset( $input['border_radius'] ) ? absint( $input['border_radius'] ) : 8;
		$sanitized['border_radius'] = max( 0, min( 30, $radius_val ) );

		return $sanitized;
	}

	/**
	 * Render settings view.
	 *
	 * @return void
	 */
	public static function render() {
		$settings = get_option( 'mb_engine_settings', array() );
		include MB_ENGINE_PATH . 'templates/admin/settings-view.php';
	}
}
