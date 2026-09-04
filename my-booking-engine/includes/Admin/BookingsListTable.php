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
			'entity_title'    => __( 'Booking Entity', 'my-booking-engine' ),
			'customer'        => __( 'Customer Details', 'my-booking-engine' ),
			'booking_dates'   => __( 'Dates / Slot', 'my-booking-engine' ),
			'capacity_booked' => __( 'Spots', 'my-booking-engine' ),
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
	 * Column: Entity Title.
	 *
	 * @param object $item Row item.
	 * @return string
	 */
	protected function column_entity_title( $item ) {
		$title = get_the_title( $item->entity_id );
		$edit  = get_edit_post_link( $item->entity_id );

		if ( $edit ) {
			return sprintf( '<a href="%s"><strong>%s</strong></a>', esc_url( $edit ), esc_html( $title ) );
		}
		return esc_html( $title );
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
		return '<span class="dashicons dashicons-calendar" style="font-size:14px;vertical-align:middle;"></span> '
			. esc_html( $item->booking_start ) . '<br>'
			. '<span class="dashicons dashicons-arrow-right-alt" style="font-size:14px;vertical-align:middle;"></span> '
			. esc_html( $item->booking_end );
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
		$colors = array(
			'confirmed' => '#007017',
			'pending'   => '#dba617',
			'cancelled' => '#d63638',
			'completed' => '#2271b1',
		);
		$color  = $colors[ $item->status ] ?? '#8c8f94';

		return sprintf(
			'<span style="display:inline-block;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:bold;color:#fff;background:%s;">%s</span>',
			esc_attr( $color ),
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
		return sprintf( '<a href="%s">#%d</a>', esc_url( $order_url ), absint( $item->order_id ) );
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
			$actions[] = sprintf( '<a href="%s" style="color:#007017;font-weight:600;">%s</a>', esc_url( $confirm_url ), esc_html__( 'Confirm', 'my-booking-engine' ) );
		}
		if ( 'cancelled' !== $item->status ) {
			$actions[] = sprintf( '<a href="%s" style="color:#d63638;" onclick="return confirm(\'%s\');">%s</a>', esc_url( $cancel_url ), esc_js( __( 'Are you sure you want to cancel this booking?', 'my-booking-engine' ) ), esc_html__( 'Cancel', 'my-booking-engine' ) );
		}

		return implode( ' | ', $actions );
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
