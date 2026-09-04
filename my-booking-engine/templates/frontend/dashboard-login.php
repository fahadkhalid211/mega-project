<?php
/**
 * Frontend Template: Customer Dashboard Login.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$redirect_to = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : home_url( '/' );
$login_error = isset( $_GET['mb_login_error'] ) ? sanitize_text_field( wp_unslash( $_GET['mb_login_error'] ) ) : '';
?>
<div class="mb-dash-wrap mb-dash-login-wrap">
	<div class="mb-dash-login-card">
		<div class="mb-dash-login-icon">🔐</div>
		<h2 class="mb-dash-title"><?php esc_html_e( 'My Bookings', 'my-booking-engine' ); ?></h2>
		<p class="mb-dash-subtitle"><?php esc_html_e( 'Log in to view your current and past bookings.', 'my-booking-engine' ); ?></p>

		<?php if ( $login_error ) : ?>
			<div class="mb-dash-alert"><?php echo esc_html( $login_error ); ?></div>
		<?php endif; ?>

		<?php
		wp_login_form(
			array(
				'redirect'       => $redirect_to,
				'label_username' => __( 'Email or Username', 'my-booking-engine' ),
				'label_password' => __( 'Password', 'my-booking-engine' ),
				'label_log_in'   => __( 'Log In', 'my-booking-engine' ),
				'remember'       => true,
			)
		);
		?>

		<p class="mb-dash-lost-password">
			<a href="<?php echo esc_url( wp_lostpassword_url( $redirect_to ) ); ?>"><?php esc_html_e( 'Forgot your password?', 'my-booking-engine' ); ?></a>
		</p>

		<p class="mb-dash-hint">
			<?php esc_html_e( "Don't have an account yet? Simply make a booking and we'll set one up for you automatically — you'll get an email to create your password.", 'my-booking-engine' ); ?>
		</p>
	</div>
</div>
