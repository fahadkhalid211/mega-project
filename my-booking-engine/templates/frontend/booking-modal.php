<?php
/**
 * Frontend Booking Drawer / Modal Template.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$entity_id = isset( $entity_id ) ? absint( $entity_id ) : 0;
$entity    = $entity_id ? new \MyBookingEngine\Models\BookingEntity( $entity_id ) : null;
$model     = $entity ? $entity->get_model_type() : 'hourly_slot';
$price     = $entity ? $entity->get_base_price() : 0.00;

$settings     = get_option( 'mb_engine_settings', array() );
$currency_sym = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
$today        = current_time( 'Y-m-d' );
$tomorrow     = gmdate( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );
?>

<div class="mb-booking-card" id="mb-booking-card-<?php echo esc_attr( $entity_id ); ?>" data-entity-id="<?php echo esc_attr( $entity_id ); ?>" data-model="<?php echo esc_attr( $model ); ?>">
	<div class="mb-booking-header">
		<div class="mb-booking-header-info">
			<h3><?php echo esc_html( $entity ? $entity->get_title() : __( 'Book Reservation', 'my-booking-engine' ) ); ?></h3>
			<span class="mb-rate-badge">
				<?php echo esc_html( $currency_sym . number_format( $price, 2 ) ); ?>
			</span>
		</div>
		<button type="button" class="mb-drawer-close" aria-label="<?php esc_attr_e( 'Close', 'my-booking-engine' ); ?>">&times;</button>
	</div>

	<form class="mb-booking-form" id="mb-form-<?php echo esc_attr( $entity_id ); ?>" onsubmit="return false;">
		<input type="hidden" name="entity_id" value="<?php echo esc_attr( $entity_id ); ?>">
		<input type="hidden" name="model" value="<?php echo esc_attr( $model ); ?>">
		<input type="hidden" name="selected_start" id="mb_selected_start" value="">
		<input type="hidden" name="selected_end" id="mb_selected_end" value="">

		<!-- 1. DATE SELECTION STEP -->
		<div class="mb-step mb-step-dates">
			<?php if ( 'hourly_slot' === $model ) : ?>
				<label for="mb_book_date"><?php esc_html_e( 'Select Appointment Date:', 'my-booking-engine' ); ?></label>
				<input type="date" id="mb_book_date" name="book_date" min="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( $today ); ?>" class="mb-input-date">

			<?php elseif ( 'day_rental' === $model ) : ?>
				<div class="mb-date-range-grid">
					<div>
						<label for="mb_rental_start"><?php esc_html_e( 'Pickup Date:', 'my-booking-engine' ); ?></label>
						<input type="date" id="mb_rental_start" name="rental_start" min="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( $today ); ?>" class="mb-input-date">
					</div>
					<div>
						<label for="mb_rental_end"><?php esc_html_e( 'Return Date:', 'my-booking-engine' ); ?></label>
						<input type="date" id="mb_rental_end" name="rental_end" min="<?php echo esc_attr( $tomorrow ); ?>" value="<?php echo esc_attr( $tomorrow ); ?>" class="mb-input-date">
					</div>
				</div>

			<?php elseif ( 'night_stay' === $model ) : ?>
				<div class="mb-date-range-grid">
					<div>
						<label for="mb_checkin_date"><?php esc_html_e( 'Check-in:', 'my-booking-engine' ); ?></label>
						<input type="date" id="mb_checkin_date" name="checkin_date" min="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( $today ); ?>" class="mb-input-date">
					</div>
					<div>
						<label for="mb_checkout_date"><?php esc_html_e( 'Check-out:', 'my-booking-engine' ); ?></label>
						<input type="date" id="mb_checkout_date" name="checkout_date" min="<?php echo esc_attr( $tomorrow ); ?>" value="<?php echo esc_attr( $tomorrow ); ?>" class="mb-input-date">
					</div>
				</div>

			<?php elseif ( 'capacity_roster' === $model ) : ?>
				<div class="mb-event-info-box">
					<p><strong><?php esc_html_e( 'Event Start:', 'my-booking-engine' ); ?></strong> <?php echo esc_html( $entity ? $entity->get_event_start() : '' ); ?></p>
					<p><strong><?php esc_html_e( 'Event End:', 'my-booking-engine' ); ?></strong> <?php echo esc_html( $entity ? $entity->get_event_end() : '' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<!-- 2. TIME SLOTS CONTAINER (For Hourly Slot Model) -->
		<?php if ( 'hourly_slot' === $model ) : ?>
			<div class="mb-step mb-step-slots">
				<label><?php esc_html_e( 'Available Time Slots:', 'my-booking-engine' ); ?></label>
				<div class="mb-slots-grid" id="mb-slots-container">
					<div class="mb-slots-loading"><?php esc_html_e( 'Loading slots for selected date...', 'my-booking-engine' ); ?></div>
				</div>
			</div>
		<?php endif; ?>

		<!-- 3. CAPACITY / GUESTS -->
		<div class="mb-step mb-step-capacity">
			<label for="mb_book_capacity"><?php esc_html_e( 'Number of Guests / Spots:', 'my-booking-engine' ); ?></label>
			<input type="number" id="mb_book_capacity" name="capacity" min="1" max="<?php echo esc_attr( $entity ? $entity->get_capacity() : 10 ); ?>" value="1" class="mb-input-number">
		</div>

		<!-- 4. CUSTOMER CONTACT DETAILS -->
		<div class="mb-step mb-step-customer">
			<h4><?php esc_html_e( 'Contact Information', 'my-booking-engine' ); ?></h4>
			<div class="mb-funnel-field">
				<div class="mb-input-icon-wrap">
					<span class="mb-input-icon">👤</span>
					<input type="text" name="customer_name" id="mb_cust_name" class="mb-funnel-input" placeholder="<?php esc_attr_e( 'Full Name *', 'my-booking-engine' ); ?>" required autocomplete="name">
				</div>
			</div>
			<div class="mb-funnel-field" style="margin-top:12px;">
				<div class="mb-input-icon-wrap">
					<span class="mb-input-icon">✉️</span>
					<input type="email" name="customer_email" id="mb_cust_email" class="mb-funnel-input" placeholder="<?php esc_attr_e( 'Email Address *', 'my-booking-engine' ); ?>" required autocomplete="email">
				</div>
			</div>
			<div class="mb-funnel-field" style="margin-top:12px;">
				<div class="mb-input-icon-wrap">
					<span class="mb-input-icon">📞</span>
					<input type="tel" name="customer_phone" id="mb_cust_phone" class="mb-funnel-input" placeholder="<?php esc_attr_e( 'Phone Number *', 'my-booking-engine' ); ?>" required autocomplete="tel">
				</div>
			</div>
		</div>

		<!-- 5. SUMMARY & SUBMIT -->
		<div class="mb-step mb-step-summary">
			<div class="mb-summary-row">
				<span><?php esc_html_e( 'Estimated Total:', 'my-booking-engine' ); ?></span>
				<strong class="mb-total-price" id="mb-total-display"><?php echo esc_html( $currency_sym . number_format( $price, 2 ) ); ?></strong>
			</div>

			<div class="mb-booking-alerts" id="mb-booking-alerts"></div>

			<button type="button" class="mb-btn mb-btn-submit-booking" id="mb-btn-confirm">
				<span class="mb-btn-text"><?php esc_html_e( 'Confirm & Reserve', 'my-booking-engine' ); ?></span>
				<span class="mb-btn-spinner" style="display:none;"></span>
			</button>
		</div>
	</form>
</div>
