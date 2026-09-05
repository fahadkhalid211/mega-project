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
		add_filter( 'admin_body_class', array( __CLASS__, 'add_admin_body_classes' ) );
	}

	/**
	 * Add body classes for custom sleek white theme on All Listings and All Bookings pages.
	 *
	 * @param string $classes Existing body classes.
	 * @return string
	 */
	public static function add_admin_body_classes( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$param_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $screen ) {
			if ( 'mb_booking_entity' === $screen->post_type && 'edit' === $screen->base ) {
				$classes .= ' mb-admin-white-theme mb-admin-listings-page ';
			}
			if ( 'mb-bookings' === $param_page || ( false !== strpos( $screen->id, 'mb-bookings' ) ) ) {
				$classes .= ' mb-admin-white-theme mb-admin-bookings-page ';
			}
		} elseif ( 'mb-bookings' === $param_page ) {
			$classes .= ' mb-admin-white-theme mb-admin-bookings-page ';
		}

		return $classes;
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

		$stats        = Booking::get_summary_stats();
		$settings     = get_option( 'mb_engine_settings', array() );
		$currency_sym = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
		$curr_status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap mb-admin-dashboard-wrap mb-bookings-dashboard">
			<!-- Header Banner -->
			<div class="mb-admin-header-hero">
				<div class="mb-admin-header-left">
					<div class="mb-header-badge">
						<svg viewBox="0 0 24 24" width="13" height="13"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
						<?php esc_html_e( 'Reservations Console', 'my-booking-engine' ); ?>
					</div>
					<h1 class="mb-admin-title"><?php esc_html_e( 'Bookings & Reservations', 'my-booking-engine' ); ?></h1>
					<p class="mb-admin-subtitle"><?php esc_html_e( 'Manage real-time customer bookings, appointment schedules, and confirmed transaction revenues.', 'my-booking-engine' ); ?></p>
				</div>
				<div class="mb-admin-header-right">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mb-add-listing' ) ); ?>" class="mb-btn mb-btn-primary mb-header-action-btn">
						<svg viewBox="0 0 24 24" width="15" height="15"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
						<?php esc_html_e( 'Add New Listing', 'my-booking-engine' ); ?>
					</a>
				</div>
			</div>

			<!-- KPI Summary Row -->
			<div class="mb-kpi-row">
				<div class="mb-kpi-card mb-kpi-total">
					<div class="mb-kpi-icon-wrap" style="background:#eff6ff;color:#2563eb;">
						<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
					</div>
					<div class="mb-kpi-content">
						<span class="mb-kpi-label"><?php esc_html_e( 'Total Bookings', 'my-booking-engine' ); ?></span>
						<span class="mb-kpi-value"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
					</div>
				</div>

				<div class="mb-kpi-card mb-kpi-confirmed">
					<div class="mb-kpi-icon-wrap" style="background:#f0fdf4;color:#16a34a;">
						<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
					</div>
					<div class="mb-kpi-content">
						<span class="mb-kpi-label"><?php esc_html_e( 'Confirmed', 'my-booking-engine' ); ?></span>
						<span class="mb-kpi-value"><?php echo esc_html( number_format_i18n( $stats['confirmed'] ) ); ?></span>
					</div>
				</div>

				<div class="mb-kpi-card mb-kpi-pending">
					<div class="mb-kpi-icon-wrap" style="background:#fffbeb;color:#d97706;">
						<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
					</div>
					<div class="mb-kpi-content">
						<span class="mb-kpi-label"><?php esc_html_e( 'Pending Approval', 'my-booking-engine' ); ?></span>
						<span class="mb-kpi-value"><?php echo esc_html( number_format_i18n( $stats['pending'] ) ); ?></span>
					</div>
				</div>

				<div class="mb-kpi-card mb-kpi-revenue">
					<div class="mb-kpi-icon-wrap" style="background:#faf5ff;color:#9333ea;">
						<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
					</div>
					<div class="mb-kpi-content">
						<span class="mb-kpi-label"><?php esc_html_e( 'Confirmed Revenue', 'my-booking-engine' ); ?></span>
						<span class="mb-kpi-value"><?php echo esc_html( $currency_sym . number_format( $stats['revenue'], 2 ) ); ?></span>
					</div>
				</div>
			</div>

			<!-- Quick Filter Pills -->
			<div class="mb-admin-filter-bar">
				<div class="mb-filter-pills-list">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mb-bookings' ) ); ?>" class="mb-filter-pill<?php echo empty( $curr_status ) ? ' is-active' : ''; ?>">
						<?php esc_html_e( 'All Bookings', 'my-booking-engine' ); ?> <span class="mb-pill-count"><?php echo esc_html( $stats['total'] ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mb-bookings&status=confirmed' ) ); ?>" class="mb-filter-pill<?php echo ( 'confirmed' === $curr_status ) ? ' is-active' : ''; ?>">
						<?php esc_html_e( 'Confirmed', 'my-booking-engine' ); ?> <span class="mb-pill-count"><?php echo esc_html( $stats['confirmed'] ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mb-bookings&status=pending' ) ); ?>" class="mb-filter-pill<?php echo ( 'pending' === $curr_status ) ? ' is-active' : ''; ?>">
						<?php esc_html_e( 'Pending', 'my-booking-engine' ); ?> <span class="mb-pill-count"><?php echo esc_html( $stats['pending'] ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mb-bookings&status=cancelled' ) ); ?>" class="mb-filter-pill<?php echo ( 'cancelled' === $curr_status ) ? ' is-active' : ''; ?>">
						<?php esc_html_e( 'Cancelled', 'my-booking-engine' ); ?> <span class="mb-pill-count"><?php echo esc_html( $stats['cancelled'] ); ?></span>
					</a>
				</div>
			</div>

			<?php if ( ! empty( $table->db_error ) ) : ?>
				<div class="notice notice-error"><p>
					<strong><?php esc_html_e( 'Database error while loading bookings:', 'my-booking-engine' ); ?></strong>
					<?php echo esc_html( $table->db_error ); ?>
					— <?php esc_html_e( 'try deactivating and reactivating the plugin to repair the bookings table.', 'my-booking-engine' ); ?>
				</p></div>
			<?php endif; ?>

			<!-- Main White Table Card -->
			<div class="mb-admin-table-card">
				<form method="post" action="">
					<?php
					$table->search_box( __( 'Search Bookings', 'my-booking-engine' ), 'mb_booking_search' );
					$table->display();
					?>
				</form>
			</div>
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
