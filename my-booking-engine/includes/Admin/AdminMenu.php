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
		add_action( 'admin_footer-edit.php', array( __CLASS__, 'render_quick_create_modal' ) );
	}

	/**
	 * Output the "Add New Listing" guided wizard as a ready-to-open modal
	 * directly on the Listings list screen, so admins never have to leave
	 * the page to start a new listing. JavaScript (admin.js) intercepts the
	 * native "Add New" link and opens this modal instead of navigating to
	 * post-new.php. The form posts to an AJAX handler that creates the post
	 * and saves every wizard field in one step.
	 *
	 * @return void
	 */
	public static function render_quick_create_modal() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'mb_booking_entity' !== $screen->post_type ) {
			return;
		}

		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}

		// Build a blank stand-in post so the shared wizard template (which
		// expects a real $post/$location/$availabilities in scope) renders
		// with sensible defaults.
		$post           = new \WP_Post( (object) array( 'ID' => 0, 'post_title' => '', 'post_type' => 'mb_booking_entity' ) );
		$location       = null;
		$availabilities = array();
		?>
		<form id="mb-quick-create-form" method="post">
			<?php wp_nonce_field( 'mb_save_entity_meta', 'mb_entity_meta_nonce' ); ?>
			<?php include MB_ENGINE_PATH . 'templates/admin/metabox-entity-details.php'; ?>
		</form>
		<?php
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

		// Submenu: Shortcode Builder.
		add_submenu_page(
			$parent_slug,
			__( 'Shortcode Builder', 'my-booking-engine' ),
			__( 'Shortcode Builder', 'my-booking-engine' ),
			'manage_options',
			'mb-shortcodes',
			array( 'MyBookingEngine\Admin\ShortcodeGenerator', 'render' )
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
