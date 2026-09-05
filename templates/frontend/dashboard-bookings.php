<?php
/**
 * Frontend Template: Customer Dashboard - My Bookings List.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = get_option( 'mb_engine_settings', array() );
$sym      = $settings['currency_symbol'] ?? '$';
$now_ts   = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

$upcoming = array();
$past     = array();

foreach ( $bookings as $b ) {
	$start_ts = strtotime( $b->booking_start );
	if ( 'cancelled' !== $b->status && $start_ts >= $now_ts ) {
		$upcoming[] = $b;
	} else {
		$past[] = $b;
	}
}

$status_colors = array(
	'confirmed' => array( '#dcfce7', '#166534' ),
	'pending'   => array( '#fef9c3', '#854d0e' ),
	'cancelled' => array( '#fee2e2', '#991b1b' ),
	'completed' => array( '#dbeafe', '#1e40af' ),
);

if ( ! function_exists( 'mb_dashboard_render_card' ) ) {
	/**
	 * Render a single booking card.
	 *
	 * @param object $b Booking row.
	 * @param string $sym Currency symbol.
	 * @param array  $status_colors Status color map.
	 */
	function mb_dashboard_render_card( $b, $sym, $status_colors ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedParameterFound
		$entity_title = get_the_title( $b->entity_id );
		$permalink    = get_permalink( $b->entity_id );
		$colors       = $status_colors[ $b->status ] ?? array( '#f1f5f9', '#475569' );
		?>
		<div class="mb-dash-card">
			<div class="mb-dash-card-main">
				<h3 class="mb-dash-card-title">
					<?php if ( $permalink ) : ?>
						<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $entity_title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $entity_title ); ?>
					<?php endif; ?>
				</h3>
				<div class="mb-dash-card-meta">
					<span>📅 <?php echo esc_html( $b->booking_start ); ?> → <?php echo esc_html( $b->booking_end ); ?></span>
					<span>👥 <?php echo esc_html__( 'Capacity:', 'my-booking-engine' ); ?> <strong><?php echo esc_html( $b->capacity_booked ); ?></strong> <?php echo ( 1 === (int) $b->capacity_booked ) ? esc_html__( 'Spot / Guest', 'my-booking-engine' ) : esc_html__( 'Spots / Guests', 'my-booking-engine' ); ?></span>
					<span>💳 <?php echo esc_html( $sym . number_format( (float) $b->total_price, 2 ) ); ?></span>
				</div>
			</div>
			<span class="mb-dash-status" style="background:<?php echo esc_attr( $colors[0] ); ?>;color:<?php echo esc_attr( $colors[1] ); ?>;">
				<?php echo esc_html( ucfirst( $b->status ) ); ?>
			</span>
		</div>
		<?php
	}
}
?>
<div class="mb-dash-wrap">
	<div class="mb-dash-header">
		<div>
			<h2 class="mb-dash-title"><?php esc_html_e( 'My Bookings', 'my-booking-engine' ); ?></h2>
			<p class="mb-dash-subtitle">
				<?php
				/* translators: %s: current user display name */
				printf( esc_html__( 'Welcome back, %s', 'my-booking-engine' ), esc_html( wp_get_current_user()->display_name ) );
				?>
			</p>
		</div>
		<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="mb-dash-logout"><?php esc_html_e( 'Log Out', 'my-booking-engine' ); ?></a>
	</div>

	<?php if ( empty( $bookings ) ) : ?>
		<div class="mb-dash-empty">
			<span class="mb-dash-empty-icon">🗓️</span>
			<p><?php esc_html_e( "You don't have any bookings yet.", 'my-booking-engine' ); ?></p>
		</div>
	<?php else : ?>

		<?php if ( ! empty( $upcoming ) ) : ?>
			<h3 class="mb-dash-section-title"><?php esc_html_e( 'Upcoming', 'my-booking-engine' ); ?></h3>
			<div class="mb-dash-list">
				<?php foreach ( $upcoming as $b ) : mb_dashboard_render_card( $b, $sym, $status_colors ); endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $past ) ) : ?>
			<h3 class="mb-dash-section-title" style="margin-top:28px;"><?php esc_html_e( 'Past & Cancelled', 'my-booking-engine' ); ?></h3>
			<div class="mb-dash-list">
				<?php foreach ( $past as $b ) : mb_dashboard_render_card( $b, $sym, $status_colors ); endforeach; ?>
			</div>
		<?php endif; ?>

	<?php endif; ?>
</div>
