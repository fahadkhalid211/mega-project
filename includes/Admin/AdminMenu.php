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
		add_action( 'admin_head', array( __CLASS__, 'hide_add_listing_menu_item' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_booking_actions' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'redirect_legacy_add_new' ) );
		add_action( 'load-post.php', array( __CLASS__, 'redirect_legacy_edit' ) );
	}

	/**
	 * WordPress always generates a default "Add New" link pointing at
	 * post-new.php for any public post type. Rather than fight that link
	 * (which proved unreliable to intercept client-side), redirect it
	 * server-side — every path to "Add New Listing" ends up on our custom,
	 * fully app-styled listing builder instead of the native post editor.
	 *
	 * @return void
	 */
	public static function redirect_legacy_add_new() {
		if ( ! isset( $_GET['post_type'] ) || 'mb_booking_entity' !== sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mb-add-listing' ) );
		exit;
	}

	/**
	 * Same idea as redirect_legacy_add_new(), but for editing an existing
	 * listing: WordPress always links row actions / "Edit" at post.php,
	 * which normally opens the native metabox screen. Send that to our
	 * custom Edit Listing app page instead, so admins never have to touch
	 * WordPress meta boxes to manage a listing.
	 *
	 * @return void
	 */
	public static function redirect_legacy_edit() {
		if ( ! isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$post_id = absint( wp_unslash( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $post_id || 'mb_booking_entity' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mb-edit-listing&post_id=' . $post_id ) );
		exit;
	}

	/**
	 * Keep the "Add New Listing" builder reachable by URL without showing a
	 * second, redundant "Add New" entry next to WordPress's own submenu.
	 *
	 * IMPORTANT: this hides the menu item with CSS rather than calling
	 * remove_submenu_page(). Removing the submenu entry also strips the
	 * record WordPress uses to look up which capability guards the page,
	 * which makes the page itself inaccessible ("Sorry, you are not
	 * allowed to access this page") even for admins. CSS-hiding keeps the
	 * page fully registered and accessible while just not showing the
	 * duplicate link.
	 *
	 * @return void
	 */
	public static function hide_add_listing_menu_item() {
		echo '<style>#adminmenu a[href*="page=mb-add-listing"], #adminmenu a[href*="page=mb-edit-listing"] { display: none; }</style>';
	}

	/**
	 * Register menus and submenus.
	 *
	 * @return void
	 */
	public static function register_admin_menus() {
		$parent_slug = 'edit.php?post_type=mb_booking_entity';

		// Hidden page: the app-styled "Add New Listing" builder. Registered
		// as a submenu (so it inherits capability checks + the WP admin
		// frame) but removed from the visible menu by hide_add_listing_submenu().
		add_submenu_page(
			$parent_slug,
			__( 'Add New Listing', 'my-booking-engine' ),
			__( 'Add New Listing', 'my-booking-engine' ),
			'publish_posts',
			'mb-add-listing',
			array( __CLASS__, 'render_add_listing_page' )
		);

		// Hidden page: the same app-styled builder in edit mode, reached
		// via redirect_legacy_edit() whenever someone opens an existing
		// listing — replaces the native WordPress meta-box edit screen.
		add_submenu_page(
			$parent_slug,
			__( 'Edit Listing', 'my-booking-engine' ),
			__( 'Edit Listing', 'my-booking-engine' ),
			'edit_posts',
			'mb-edit-listing',
			array( __CLASS__, 'render_edit_listing_page' )
		);

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
	 * Render the app-styled "Add New Listing" builder — a dedicated page
	 * with none of the standard WordPress postbox/metabox chrome, similar
	 * in spirit to the guided setup screens in Amelia/LatePoint.
	 *
	 * @return void
	 */
	public static function render_add_listing_page() {
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to create listings.', 'my-booking-engine' ) );
		}
		include MB_ENGINE_PATH . 'templates/admin/add-listing-app.php';
	}

	/**
	 * Render the same app-styled builder in edit mode for an existing
	 * listing, identified by ?post_id= in the URL.
	 *
	 * @return void
	 */
	public static function render_edit_listing_page() {
		$edit_entity_id = isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $edit_entity_id || 'mb_booking_entity' !== get_post_type( $edit_entity_id ) ) {
			wp_die( esc_html__( 'Listing not found.', 'my-booking-engine' ) );
		}

		if ( ! current_user_can( 'edit_post', $edit_entity_id ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this listing.', 'my-booking-engine' ) );
		}

		include MB_ENGINE_PATH . 'templates/admin/add-listing-app.php';
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
		try {
			$table = new BookingsListTable();
			$table->prepare_items();
		} catch ( \Throwable $e ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Bookings & Reservations', 'my-booking-engine' ) . '</h1>';
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The bookings table could not be loaded:', 'my-booking-engine' ) . ' ' . esc_html( $e->getMessage() ) . '</p></div></div>';
			return;
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Bookings & Reservations', 'my-booking-engine' ); ?></h1>
			<hr class="wp-header-end">

			<?php if ( ! empty( $table->db_error ) ) : ?>
				<div class="notice notice-error"><p>
					<strong><?php esc_html_e( 'Database error while loading bookings:', 'my-booking-engine' ); ?></strong>
					<?php echo esc_html( $table->db_error ); ?>
					— <?php esc_html_e( 'try deactivating and reactivating the plugin to repair the bookings table.', 'my-booking-engine' ); ?>
				</p></div>
			<?php endif; ?>

			<form method="post" action="">
				<?php
				$table->search_box( __( 'Search Bookings', 'my-booking-engine' ), 'mb_booking_search' );
				$table->display();
				?>
			</form>
		</div>

		<div id="mb-booking-details-overlay" class="mb-booking-details-overlay" style="display:none;">
			<div class="mb-booking-details-panel" role="dialog" aria-modal="true">
				<div class="mb-booking-details-header">
					<h2><?php esc_html_e( 'Booking Details', 'my-booking-engine' ); ?></h2>
					<button type="button" class="mb-booking-details-close" aria-label="<?php esc_attr_e( 'Close', 'my-booking-engine' ); ?>">&times;</button>
				</div>
				<div class="mb-booking-details-body" id="mb-booking-details-body"></div>
			</div>
		</div>
		<?php
	}
}
