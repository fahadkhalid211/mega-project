<?php
/**
 * Customer Dashboard: [mb_my_bookings] shortcode.
 *
 * Logged-out visitors see a login form; logged-in customers see their
 * current and past bookings.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Frontend;

use MyBookingEngine\Models\Booking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CustomerDashboard
 */
class CustomerDashboard {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'mb_my_bookings', array( __CLASS__, 'render' ) );
		add_shortcode( 'mb_dashboard', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @return string
	 */
	public static function render() {
		ob_start();

		if ( ! is_user_logged_in() ) {
			include MB_ENGINE_PATH . 'templates/frontend/dashboard-login.php';
			return ob_get_clean();
		}

		$user  = wp_get_current_user();
		$items = Booking::query(
			array(
				'customer_email' => $user->user_email,
				'orderby'        => 'booking_start',
				'order'          => 'DESC',
				'per_page'       => 50,
			)
		);

		$bookings = $items['items'];

		include MB_ENGINE_PATH . 'templates/frontend/dashboard-bookings.php';
		return ob_get_clean();
	}
}
