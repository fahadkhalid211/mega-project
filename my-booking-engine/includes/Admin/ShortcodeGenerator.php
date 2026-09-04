<?php
/**
 * Admin Shortcode Generator Page.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ShortcodeGenerator
 */
class ShortcodeGenerator {

	/**
	 * Render the visual shortcode builder page.
	 *
	 * @return void
	 */
	public static function render() {
		 = get_posts(
			array(
				'post_type'      => 'mb_booking_entity',
				'post_status'    => 'publish',
				'posts_per_page' => 150,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		include MB_ENGINE_PATH . 'templates/admin/shortcode-generator.php';
	}
}
