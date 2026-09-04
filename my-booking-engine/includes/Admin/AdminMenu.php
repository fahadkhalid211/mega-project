<?php
/**
 * Admin Menu and Dashboard Coordinator.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Admin;

use MyBookingEngine\Models\Booking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdminMenu
 */
class AdminMenu {

	/**
	 * Initialize admin menu hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menus' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_booking_actions' ) );
	}

	/**
	 * Register menus and submenus.
	 *
	 * @return void
	 */
	public static function register_admin_menus() {
		$parent_slug = 'edit.php?post_type=mb_booking_entity';

		// Submenu: Bookings & Reservations.
		add_submenu_page(
			$parent_slug,
			__( 'All Bookings', 'my-booking-engine' ),
			__( 'Bookings', 'my-booking-engine' ),
			'manage_options',
			'mb-bookings',
			array( __CLASS__, 'render_bookings_page' )
		);

		// Submenu: Settings.
		add_submenu_page(
			$parent_slug,
			__( 'Booking Engine Settings', 'my-booking-engine' ),
			__( 'Settings', 'my-booking-engine' ),
			'manage_options',
			'mb-settings',
			array( 'MyBookingEngine\Admin\SettingsPage', 'render' )
		);
	}

	/**
	 * Handle admin booking inline status actions (Confirm / Cancel).
	 *
	 * @return void
	 */
	public static function handle_booking_actions() {
		if ( ! isset( $_GET['page'] ) || 'mb-bookings' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Single action.
		if ( isset( $_GET['action'], $_GET['booking_id'] ) ) {
			check_admin_referer( 'mb_booking_action' );

			$action     = sanitize_key( $_GET['action'] );
			$booking_id = absint( $_GET['booking_id'] );

			if ( 'confirm' === $action ) {
				Booking::update_status( $booking_id, 'confirmed' );
			} elseif ( 'cancel' === $action ) {
				Booking::update_status( $booking_id, 'cancelled' );
			}

			wp_safe_redirect( remove_query_arg( array( 'action', 'booking_id', '_wpnonce' ) ) );
			exit;
		}

		// Bulk actions.
		if ( isset( $_POST['action'] ) && ! empty( $_POST['booking_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$action = sanitize_key( $_POST['action'] );
			$ids    = array_map( 'absint', (array) $_POST['booking_ids'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( 'bulk_confirm' === $action ) {
				foreach ( $ids as $id ) {
					Booking::update_status( $id, 'confirmed' );
				}
			} elseif ( 'bulk_cancel' === $action ) {
				foreach ( $ids as $id ) {
					Booking::update_status( $id, 'cancelled' );
				}
			}

			wp_safe_redirect( remove_query_arg( array( 'action', 'booking_ids' ) ) );
			exit;
		}
	}

	/**
	 * Render Bookings administration page with WP_List_Table.
	 *
	 * @return void
	 */
	public static function render_bookings_page() {
		$table = new BookingsListTable();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Bookings & Reservations', 'my-booking-engine' ); ?></h1>
			<hr class="wp-header-end">

			<form method="post" action="">
				<?php
				$table->search_box( __( 'Search Bookings', 'my-booking-engine' ), 'mb_booking_search' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}
}
