<?php
/**
 * Template Loader for Booking Entities.
 * Intercepts template loading to render single listing pages and archives.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Presentation;

use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TemplateLoader
 */
class TemplateLoader {

	/**
	 * Initialize template hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_loader' ), 99 );
	}

	/**
	 * Filter template_include to load plugin templates when appropriate.
	 *
	 * @param string $template Current template file path.
	 * @return string Filtered template file path.
	 */
	public static function template_loader( $template ) {
		// 1. Single booking entity post.
		if ( is_singular( 'mb_booking_entity' ) ) {
			// Allow theme override first:
			$theme_file = locate_template( array( 'single-mb_booking_entity.php', 'booking-engine/single-listing.php' ) );
			if ( ! empty( $theme_file ) ) {
				return $theme_file;
			}

			$plugin_template = MB_ENGINE_PATH . 'templates/single-listing.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		// 2. Post type archive or taxonomy archive.
		if ( is_post_type_archive( 'mb_booking_entity' ) || is_tax( 'mb_entity_type' ) ) {
			$theme_file = locate_template( array( 'archive-mb_booking_entity.php', 'booking-engine/archive.php' ) );
			if ( ! empty( $theme_file ) ) {
				return $theme_file;
			}

			$plugin_template = MB_ENGINE_PATH . 'templates/archive-listing.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	/**
	 * Load layout template for a single entity based on visual layout.
	 *
	 * @param BookingEntity $entity Entity object.
	 * @return void
	 */
	public static function render_entity_layout( BookingEntity $entity ) {
		$layout = $entity->get_visual_layout();
		$allowed_layouts = array( 'hotel', 'rental', 'hourly', 'doctor', 'salon', 'shop' );

		if ( ! in_array( $layout, $allowed_layouts, true ) ) {
			$layout = 'hotel';
		}

		$file = MB_ENGINE_PATH . "templates/single/layout-{$layout}.php";

		// Allow theme override.
		$theme_override = locate_template( array( "booking-engine/single/layout-{$layout}.php" ) );
		if ( ! empty( $theme_override ) ) {
			$file = $theme_override;
		}

		if ( file_exists( $file ) ) {
			include $file;
		} else {
			// Fallback to hotel layout if specialized file not found.
			include MB_ENGINE_PATH . 'templates/single/layout-hotel.php';
		}
	}
}
