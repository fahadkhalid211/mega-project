<?php
/**
 * Bookings List Table.
 * Admin WP_List_Table implementation for managing reservations.
 *
 * @package MyBookingEngine
 */

namespace MyBookingEngine\Admin;

use MyBookingEngine\Models\Booking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class BookingsListTable
 */
class BookingsListTable extends \WP_List_Table {

	/**
	 * Last database error encountered while querying bookings, if any.
	 *
	 * @var string
	 */
	public $db_error = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'booking',
				'plural'   => 'bookings',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'              => '<input type="checkbox" />',
			'id'              => __( 'ID', 'my-booking-engine' ),
			'thumb'           => __( 'Photo', 'my-booking-engine' ),
			'entity_title'    => __( 'Booking Entity', 'my-booking-engine' ),
			'customer'        => __( 'Customer Details', 'my-booking-engine' ),
			'booking_dates'   => __( 'Dates / Slot', 'my-booking-engine' ),
			'capacity_booked' => __( 'Capacity', 'my-booking-engine' ),
			'total_price'     => __( 'Total Price', 'my-booking-engine' ),
			'status'          => __( 'Status', 'my-booking-engine' ),
			'order_id'        => __( 'WC Order', 'my-booking-engine' ),
			'actions'         => __( 'Actions', 'my-booking-engine' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'id'            => array( 'id', false ),
			'booking_dates' => array( 'booking_start', false ),
			'total_price'   => array( 'total_price', false ),
			'status'        => array( 'status', false ),
		);
	}

	/**
	 * Checkbox column for bulk actions.
	 *
	 * @param object $item Current row item.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="booking_ids[]" value="%d" />', $item->id );
	}

	/**
	 * Default column rendering fallback.
	 *
	 * @param object $item Current row item.
	 * @param string $column_name Column key.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}

	/**
	 * Column: ID.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_id( $item ) {
		return '<strong>#' . absint( $item->id ) . '</strong>';
	}

	/**
	 * Column: Listing Thumbnail / Photo.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_thumb( $item ) {
		$entity = new \MyBookingEngine\Models\BookingEntity( $item->entity_id );
		$url    = $entity->get_thumbnail_url( 'thumbnail' );

		if ( $url ) {
			return sprintf(
				'<img src="%s" class="mb-admin-listing-thumb" alt="%s" />',
				esc_url( $url ),
				esc_attr( get_the_title( $item->entity_id ) )
			);
		}

		return '<div class="mb-admin-listing-thumb mb-thumb-placeholder"><svg viewBox="0 0 24 24" width="20" height="20"><path fill="#94a3b8" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg></div>';
	}

	/**
	 * Column: Entity Title.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_entity_title( $item ) {
		$title = get_the_title( $item->entity_id );
		$edit  = get_edit_post_link( $item->entity_id );

		$out = sprintf(
			'<button type="button" class="mb-booking-view-link" data-booking-id="%d">%s</button>',
			absint( $item->id ),
			esc_html( $title )
		);

		if ( $edit ) {
			$out .= sprintf(
				' <a href="%s" class="mb-booking-edit-listing" title="%s"><span class="dashicons dashicons-edit"></span></a>',
				esc_url( $edit ),
				esc_attr__( 'Edit listing', 'my-booking-engine' )
			);
		}

		return $out;
	}

	/**
	 * Column: Customer.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_customer( $item ) {
		$out = '<strong>' . esc_html( $item->customer_name ) . '</strong><br>';
		$out .= '<a href="mailto:' . esc_attr( $item->customer_email ) . '">' . esc_html( $item->customer_email ) . '</a>';
		if ( ! empty( $item->customer_phone ) ) {
			$out .= '<br><small>' . esc_html( $item->customer_phone ) . '</small>';
		}
		return $out;
	}

	/**
	 * Column: Booking Dates.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_booking_dates( $item ) {
		$start_ts = ! empty( $item->booking_start ) ? strtotime( $item->booking_start ) : 0;
		$end_ts   = ! empty( $item->booking_end ) ? strtotime( $item->booking_end ) : 0;

		$start_str = $start_ts ? date_i18n( 'M j, Y — g:i A', $start_ts ) : $item->booking_start;
		$end_str   = $end_ts ? date_i18n( 'M j, Y — g:i A', $end_ts ) : $item->booking_end;

		return '<div class="mb-booking-dates-cell">'
			. '<div class="mb-date-row"><span class="mb-date-prefix">' . esc_html__( 'Start', 'my-booking-engine' ) . '</span> <strong>' . esc_html( $start_str ) . '</strong></div>'
			. '<div class="mb-date-row"><span class="mb-date-prefix">' . esc_html__( 'End', 'my-booking-engine' ) . '</span> <span>' . esc_html( $end_str ) . '</span></div>'
			. '</div>';
	}

	/**
	 * Column: Capacity / Spots booked.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_capacity_booked( $item ) {
		$cap   = max( 1, absint( $item->capacity_booked ) );
		$label = ( 1 === $cap ) ? __( 'Spot / Guest', 'my-booking-engine' ) : __( 'Spots / Guests', 'my-booking-engine' );
		return sprintf(
			'<span class="mb-capacity-pill"><strong>%d</strong> <span class="mb-capacity-subtext">%s</span></span>',
			$cap,
			esc_html( $label )
		);
	}

	/**
	 * Column: Total Price.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_total_price( $item ) {
		$settings = get_option( 'mb_engine_settings', array() );
		$sym      = $settings['currency_symbol'] ?? '$';
		return '<strong>' . esc_html( $sym . number_format( $item->total_price, 2 ) ) . '</strong>';
	}

	/**
	 * Column: Status badge.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_status( $item ) {
		return sprintf(
			'<span class="mb-status-pill mb-status-%s">%s</span>',
			esc_attr( sanitize_html_class( $item->status ) ),
			esc_html( ucfirst( $item->status ) )
		);
	}

	/**
	 * Column: WooCommerce Order.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_order_id( $item ) {
		if ( empty( $item->order_id ) ) {
			return '<span style="color:#8c8f94;">' . esc_html__( 'Direct Booking', 'my-booking-engine' ) . '</span>';
		}

		$order_url = admin_url( 'post.php?post=' . absint( $item->order_id ) . '&action=edit' );
		return sprintf( '<a href="%s" style="font-weight:600;">#%d ↗</a>', esc_url( $order_url ), absint( $item->order_id ) );
	}

	/**
	 * Column: Inline Actions.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_actions( $item ) {
		$confirm_url = wp_nonce_url(
			admin_url( 'admin.php?page=mb-bookings&action=confirm&booking_id=' . absint( $item->id ) ),
			'mb_booking_action'
		);
		$cancel_url  = wp_nonce_url(
			admin_url( 'admin.php?page=mb-bookings&action=cancel&booking_id=' . absint( $item->id ) ),
			'mb_booking_action'
		);

		$actions = array();
		if ( 'confirmed' !== $item->status ) {
			$actions[] = sprintf( '<a href="%s" class="mb-row-action mb-row-action-approve">%s</a>', esc_url( $confirm_url ), esc_html__( 'Approve', 'my-booking-engine' ) );
		}
		if ( 'cancelled' !== $item->status ) {
			$actions[] = sprintf( '<a href="%s" class="mb-row-action mb-row-action-cancel" onclick="return confirm(\'%s\');">%s</a>', esc_url( $cancel_url ), esc_js( __( 'Are you sure you want to cancel this booking?', 'my-booking-engine' ) ), esc_html__( 'Cancel', 'my-booking-engine' ) );
		}

		return '<div class="mb-row-actions">' . implode( '', $actions ) . '</div>';
	}

	/**
	 * Render a single row, attaching the full booking payload as a data
	 * attribute so the details panel can render without another request.
	 *
	 * @param object $item Row item.
	 * @return void
	 */
	public function single_row( $item ) {
		$settings = get_option( 'mb_engine_settings', array() );
		$sym      = $settings['currency_symbol'] ?? '$';

		$start_ts = ! empty( $item->booking_start ) ? strtotime( $item->booking_start ) : 0;
		$end_ts   = ! empty( $item->booking_end ) ? strtotime( $item->booking_end ) : 0;

		$start_formatted = $start_ts ? date_i18n( 'M j, Y — g:i A', $start_ts ) : $item->booking_start;
		$end_formatted   = $end_ts ? date_i18n( 'M j, Y — g:i A', $end_ts ) : $item->booking_end;
		$cap             = max( 1, absint( $item->capacity_booked ) );

		$payload = array(
			'id'                    => absint( $item->id ),
			'entityTitle'           => get_the_title( $item->entity_id ),
			'customerName'          => ! empty( $item->customer_name ) ? $item->customer_name : __( 'Guest', 'my-booking-engine' ),
			'customerEmail'         => $item->customer_email,
			'customerPhone'         => $item->customer_phone,
			'bookingStart'          => $item->booking_start,
			'bookingStartFormatted' => $start_formatted,
			'bookingEnd'            => $item->booking_end,
			'bookingEndFormatted'   => $end_formatted,
			'capacityBooked'        => $cap,
			'capacityLabel'         => ( 1 === $cap ) ? __( 'Spot / Guest', 'my-booking-engine' ) : __( 'Spots / Guests', 'my-booking-engine' ),
			'totalPrice'            => $sym . number_format( (float) $item->total_price, 2 ),
			'status'                => $item->status,
			'orderId'               => absint( $item->order_id ),
			'orderUrl'              => $item->order_id ? admin_url( 'post.php?post=' . absint( $item->order_id ) . '&action=edit' ) : '',
			'confirmUrl'            => wp_nonce_url( admin_url( 'admin.php?page=mb-bookings&action=confirm&booking_id=' . absint( $item->id ) ), 'mb_booking_action' ),
			'cancelUrl'             => wp_nonce_url( admin_url( 'admin.php?page=mb-bookings&action=cancel&booking_id=' . absint( $item->id ) ), 'mb_booking_action' ),
		);

		printf(
			'<tr class="mb-booking-row" data-booking-id="%d" data-booking-payload="%s">',
			absint( $item->id ),
			esc_attr( wp_json_encode( $payload ) )
		);
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'bulk_confirm' => __( 'Mark Confirmed', 'my-booking-engine' ),
			'bulk_cancel'  => __( 'Mark Cancelled', 'my-booking-engine' ),
		);
	}

	/**
	 * Prepare items for rendering.
	 *
	 * @return void
	 */
	public function prepare_items() {
		// WP_List_Table normally derives this lazily, but on some admin
		// page setups (a custom top-level/submenu page rendered outside
		// the native edit.php screen) it never gets initialized, so the
		// table renders its shell (count, bulk actions) with zero columns.
		// Set it explicitly so rows always have something to draw into.
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page     = 20;
		$current_page = $this->get_pagenum();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search  = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'id';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC';

		$query_result = Booking::query(
			array(
				'status'   => $status,
				'search'   => $search,
				'orderby'  => $orderby,
				'order'    => $order,
				'per_page' => $per_page,
				'page'     => $current_page,
			)
		);

		$this->items   = $query_result['items'];
		$this->db_error = ! empty( $query_result['db_error'] ) ? $query_result['db_error'] : '';

		$this->set_pagination_args(
			array(
				'total_items' => $query_result['total'],
				'per_page'    => $per_page,
				'total_pages' => ceil( $query_result['total'] / $per_page ),
			)
		);
	}
}
