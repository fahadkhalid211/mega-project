<?php
/**
 * Admin Meta Box Template: Elegant Guided Listing Wizard.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$model_type     = get_post_meta( $post->ID, '_mb_model_type', true ) ?: 'hourly_slot';
$visual_layout  = get_post_meta( $post->ID, '_mb_visual_layout', true ) ?: '';
$base_price     = get_post_meta( $post->ID, '_mb_base_price', true ) ?: '0.00';
$weekend_price  = get_post_meta( $post->ID, '_mb_weekend_price', true ) ?: '';
$slot_duration  = get_post_meta( $post->ID, '_mb_slot_duration', true ) ?: '60';
$buffer_before  = get_post_meta( $post->ID, '_mb_buffer_before', true ) ?: '0';
$buffer_after   = get_post_meta( $post->ID, '_mb_buffer_after', true ) ?: '0';
$capacity       = get_post_meta( $post->ID, '_mb_capacity', true ) ?: '1';
$min_duration   = get_post_meta( $post->ID, '_mb_min_duration', true ) ?: '1';
$max_duration   = get_post_meta( $post->ID, '_mb_max_duration', true ) ?: '30';
$checkin_time   = get_post_meta( $post->ID, '_mb_checkin_time', true ) ?: '15:00';
$checkout_time  = get_post_meta( $post->ID, '_mb_checkout_time', true ) ?: '11:00';
$event_start    = get_post_meta( $post->ID, '_mb_event_start', true ) ?: '';
$event_end      = get_post_meta( $post->ID, '_mb_event_end', true ) ?: '';

$postal_code    = $location ? $location->postal_code : '';
$city           = $location ? $location->city : '';
$country_code   = $location ? $location->country_code : '';
$latitude       = $location ? $location->latitude : '';
$longitude      = $location ? $location->longitude : '';

$gallery_images = get_post_meta( $post->ID, '_mb_gallery_images', true ) ?: '';
$amenities      = get_post_meta( $post->ID, '_mb_amenities', true ) ?: '';
$policy         = get_post_meta( $post->ID, '_mb_policy', true ) ?: '';

// Convert availabilities array to associative map by day_of_week for easy rendering.
$schedule_map = array();
if ( ! empty( $availabilities ) ) {
	foreach ( $availabilities as $avail ) {
		if ( 'weekly_recurring' === $avail->rule_type && null !== $avail->day_of_week ) {
			$schedule_map[ (int) $avail->day_of_week ] = $avail;
		}
	}
}

$days_of_week = array(
	1 => array( 'name' => __( 'Monday', 'my-booking-engine' ), 'short' => 'Mon' ),
	2 => array( 'name' => __( 'Tuesday', 'my-booking-engine' ), 'short' => 'Tue' ),
	3 => array( 'name' => __( 'Wednesday', 'my-booking-engine' ), 'short' => 'Wed' ),
	4 => array( 'name' => __( 'Thursday', 'my-booking-engine' ), 'short' => 'Thu' ),
	5 => array( 'name' => __( 'Friday', 'my-booking-engine' ), 'short' => 'Fri' ),
	6 => array( 'name' => __( 'Saturday', 'my-booking-engine' ), 'short' => 'Sat' ),
	0 => array( 'name' => __( 'Sunday', 'my-booking-engine' ), 'short' => 'Sun' ),
);
?>

<!-- Launch Wizard Banner in metabox -->
<div class="mb-wizard-launch-bar" id="mb-wizard-launch-bar">
	<div class="mb-wizard-launch-info">
		<span class="mb-wizard-badge">⚡ GUIDED STEPPER WIZARD</span>
		<h4><?php esc_html_e( 'Guided Listing Creation & Setup Wizard', 'my-booking-engine' ); ?></h4>
		<p><?php esc_html_e( 'Configure models, pricing rules, geocoded coordinates, hours, and media in a focused full-screen popup modal.', 'my-booking-engine' ); ?></p>
	</div>
	<button type="button" class="button button-primary button-hero" id="mb-open-modal-wizard-btn">
		✨ <?php esc_html_e( 'Launch Wizard Popup', 'my-booking-engine' ); ?>
	</button>
</div>

<div class="mb-wizard-wrapper" id="mb-admin-wizard">
	<div class="mb-wizard-inner-modal">
		<!-- Modal Header (active in modal mode) -->
		<div class="mb-wizard-modal-header" style="display:none;">
			<div class="mb-wizard-modal-title">
				<span class="mb-modal-icon">✨</span>
				<div>
					<strong><?php esc_html_e( 'Listing Setup Wizard', 'my-booking-engine' ); ?></strong>
					<span class="mb-modal-sub"><?php esc_html_e( 'Step-by-step listing configurator', 'my-booking-engine' ); ?></span>
				</div>
			</div>
			<div class="mb-wizard-modal-actions">
				<button type="button" class="button button-secondary" id="mb-minimize-wizard-btn">
					🗗 <?php esc_html_e( 'Minimize to Page', 'my-booking-engine' ); ?>
				</button>
				<button type="button" class="mb-wizard-modal-close" id="mb-close-wizard-x" title="<?php esc_attr_e( 'Close', 'my-booking-engine' ); ?>">&times;</button>
			</div>
		</div>

		<!-- Scrollable Body for all 5 Steps -->
		<div class="mb-wizard-scroll-body">
			<!-- Quick Title row inside modal -->
			<div class="mb-modal-quick-title-row" style="display:none; margin-bottom: 20px; padding: 14px 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
				<label for="mb_quick_title" style="display:block; font-weight:700; font-size:13px; color:#1e293b; margin-bottom:6px;">
					<?php esc_html_e( 'Listing Title *', 'my-booking-engine' ); ?>
				</label>
				<input type="text" id="mb_quick_title" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Beverly Hills Luxury Suite, Sunset Dental Clinic, Porsche 911 Rental...', 'my-booking-engine' ); ?>" value="<?php echo esc_attr( $post->ID ? get_the_title( $post->ID ) : '' ); ?>" style="font-size:15px; padding:8px 12px;">
			</div>
	<!-- Stepper Progress Header -->
	<div class="mb-wizard-stepper">
		<div class="mb-stepper-progress-track">
			<div class="mb-stepper-progress-bar" id="mb-stepper-bar" style="width: 20%;"></div>
		</div>

		<div class="mb-stepper-items">
			<div class="mb-step-node is-active" data-step="1">
				<div class="mb-node-circle">1</div>
				<div class="mb-node-content">
					<span class="mb-node-step"><?php esc_html_e( 'Step 1', 'my-booking-engine' ); ?></span>
					<strong class="mb-node-title"><?php esc_html_e( 'Type & Design', 'my-booking-engine' ); ?></strong>
				</div>
			</div>

			<div class="mb-step-node" data-step="2">
				<div class="mb-node-circle">2</div>
				<div class="mb-node-content">
					<span class="mb-node-step"><?php esc_html_e( 'Step 2', 'my-booking-engine' ); ?></span>
					<strong class="mb-node-title"><?php esc_html_e( 'Pricing & Rules', 'my-booking-engine' ); ?></strong>
				</div>
			</div>

			<div class="mb-step-node" data-step="3">
				<div class="mb-node-circle">3</div>
				<div class="mb-node-content">
					<span class="mb-node-step"><?php esc_html_e( 'Step 3', 'my-booking-engine' ); ?></span>
					<strong class="mb-node-title"><?php esc_html_e( 'Location & Map', 'my-booking-engine' ); ?></strong>
				</div>
			</div>

			<div class="mb-step-node" data-step="4">
				<div class="mb-node-circle">4</div>
				<div class="mb-node-content">
					<span class="mb-node-step"><?php esc_html_e( 'Step 4', 'my-booking-engine' ); ?></span>
					<strong class="mb-node-title"><?php esc_html_e( 'Operating Hours', 'my-booking-engine' ); ?></strong>
				</div>
			</div>

			<div class="mb-step-node" data-step="5">
				<div class="mb-node-circle">5</div>
				<div class="mb-node-content">
					<span class="mb-node-step"><?php esc_html_e( 'Step 5', 'my-booking-engine' ); ?></span>
					<strong class="mb-node-title"><?php esc_html_e( 'Media & Policy', 'my-booking-engine' ); ?></strong>
				</div>
			</div>
		</div>
	</div>

	<!-- Wizard Content Container -->
	<div class="mb-wizard-body">

		<!-- STEP 1: Type & Layout -->
		<div class="mb-wizard-step-panel is-active" data-panel="1">
			<div class="mb-panel-intro">
				<div class="mb-intro-badge"><?php esc_html_e( 'Listing Architecture', 'my-booking-engine' ); ?></div>
				<h3><?php esc_html_e( 'What kind of listing are you adding?', 'my-booking-engine' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Select the visual layout designed specifically for this business model. Each layout provides an optimized frontend single page experience.', 'my-booking-engine' ); ?>
				</p>
			</div>

			<div class="mb-layout-cards-grid">
				<label class="mb-layout-card <?php echo empty( $visual_layout ) ? 'selected' : ''; ?>">
					<input type="radio" name="mb_visual_layout" value="" <?php checked( empty( $visual_layout ) ); ?>>
					<div class="mb-layout-card-inner">
						<div class="mb-layout-icon">✨</div>
						<div class="mb-layout-card-title"><?php esc_html_e( 'Auto-Detect', 'my-booking-engine' ); ?></div>
						<p class="mb-layout-card-desc"><?php esc_html_e( 'Automatically matches layout to your booking algorithm model.', 'my-booking-engine' ); ?></p>
					</div>
				</label>

				<label class="mb-layout-card <?php echo 'hotel' === $visual_layout ? 'selected' : ''; ?>">
					<input type="radio" name="mb_visual_layout" value="hotel" <?php checked( 'hotel' === $visual_layout ); ?>>
					<div class="mb-layout-card-inner">
						<div class="mb-layout-icon">🏨</div>
						<div class="mb-layout-card-title"><?php esc_html_e( 'Hotel & Vacation Stays', 'my-booking-engine' ); ?></div>
						<p class="mb-layout-card-desc"><?php esc_html_e( 'Airbnb style: 5-photo mosaic gallery, date range ribbon, sticky booking card.', 'my-booking-engine' ); ?></p>
					</div>
				</label>

				<label class="mb-layout-card <?php echo 'rental' === $visual_layout ? 'selected' : ''; ?>">
					<input type="radio" name="mb_visual_layout" value="rental" <?php checked( 'rental' === $visual_layout ); ?>>
					<div class="mb-layout-card-inner">
						<div class="mb-layout-icon">🚗</div>
						<div class="mb-layout-card-title"><?php esc_html_e( 'Car & Equipment Rental', 'my-booking-engine' ); ?></div>
						<p class="mb-layout-card-desc"><?php esc_html_e( 'Turo style: Hero image slider, vehicle specs strip, multi-day calendar ribbon, protection tiers.', 'my-booking-engine' ); ?></p>
					</div>
				</label>

				<label class="mb-layout-card <?php echo 'doctor' === $visual_layout ? 'selected' : ''; ?>">
					<input type="radio" name="mb_visual_layout" value="doctor" <?php checked( 'doctor' === $visual_layout ); ?>>
					<div class="mb-layout-card-inner">
						<div class="mb-layout-icon">🩺</div>
						<div class="mb-layout-card-title"><?php esc_html_e( 'Doctor & Specialist', 'my-booking-engine' ); ?></div>
						<p class="mb-layout-card-desc"><?php esc_html_e( 'Zocdoc style: Specialist doctor bio, consultation types, clinic schedule, dynamic slots.', 'my-booking-engine' ); ?></p>
					</div>
				</label>

				<label class="mb-layout-card <?php echo 'salon' === $visual_layout ? 'selected' : ''; ?>">
					<input type="radio" name="mb_visual_layout" value="salon" <?php checked( 'salon' === $visual_layout ); ?>>
					<div class="mb-layout-card-inner">
						<div class="mb-layout-icon">✂️</div>
						<div class="mb-layout-card-title"><?php esc_html_e( 'Salon & Spa', 'my-booking-engine' ); ?></div>
						<p class="mb-layout-card-desc"><?php esc_html_e( 'Fresha style: Specialist picker, treatment menu with prices & durations, cart checkout.', 'my-booking-engine' ); ?></p>
					</div>
				</label>

				<label class="mb-layout-card <?php echo 'hourly' === $visual_layout ? 'selected' : ''; ?>">
					<input type="radio" name="mb_visual_layout" value="hourly" <?php checked( 'hourly' === $visual_layout ); ?>>
					<div class="mb-layout-card-inner">
						<div class="mb-layout-icon">⏱️</div>
						<div class="mb-layout-card-title"><?php esc_html_e( 'Hourly Studio & Activity', 'my-booking-engine' ); ?></div>
						<p class="mb-layout-card-desc"><?php esc_html_e( 'Peerspace style: Quick studio specs, live hourly slot availability, turnaround buffer info.', 'my-booking-engine' ); ?></p>
					</div>
				</label>

				<label class="mb-layout-card <?php echo 'shop' === $visual_layout ? 'selected' : ''; ?>">
					<input type="radio" name="mb_visual_layout" value="shop" <?php checked( 'shop' === $visual_layout ); ?>>
					<div class="mb-layout-card-inner">
						<div class="mb-layout-icon">🏪</div>
						<div class="mb-layout-card-title"><?php esc_html_e( 'Shop & Local Business', 'my-booking-engine' ); ?></div>
						<p class="mb-layout-card-desc"><?php esc_html_e( 'Storefront hero slider, weekly timetable schedule table, amenities, Leaflet map.', 'my-booking-engine' ); ?></p>
					</div>
				</label>
			</div>

			<div class="mb-wizard-card mb-model-algorithm-card" style="margin-top:24px;">
				<div class="mb-model-algorithm-header">
					<h4 style="margin:0 0 4px 0;"><?php esc_html_e( 'Underlying Booking Engine Algorithm', 'my-booking-engine' ); ?></h4>
					<p class="description" style="margin:0 0 14px 0;"><?php esc_html_e( 'Decoupled from design. Select which scheduling calculation rules calculate pricing and slot availability. You can pair any algorithm with any layout style!', 'my-booking-engine' ); ?></p>
				</div>

				<input type="hidden" name="mb_model_type" id="mb_model_type" value="<?php echo esc_attr( $model_type ); ?>" class="mb-model-switcher">

				<div class="mb-model-pills-group" id="mb-model-pills">
					<button type="button" class="mb-model-pill-btn <?php echo ( 'hourly_slot' === $model_type ) ? 'is-active' : ''; ?>" data-model="hourly_slot">
						<span class="mb-pill-icon">⏱️</span>
						<span class="mb-pill-info">
							<span class="mb-pill-title"><?php esc_html_e( 'Hourly / Appointments', 'my-booking-engine' ); ?></span>
							<span class="mb-pill-desc"><?php esc_html_e( 'Time slots, duration & prep buffers', 'my-booking-engine' ); ?></span>
						</span>
					</button>

					<button type="button" class="mb-model-pill-btn <?php echo ( 'day_rental' === $model_type ) ? 'is-active' : ''; ?>" data-model="day_rental">
						<span class="mb-pill-icon">🚗</span>
						<span class="mb-pill-info">
							<span class="mb-pill-title"><?php esc_html_e( 'Day-based Rentals', 'my-booking-engine' ); ?></span>
							<span class="mb-pill-desc"><?php esc_html_e( 'Full calendar days, pickup & return times', 'my-booking-engine' ); ?></span>
						</span>
					</button>

					<button type="button" class="mb-model-pill-btn <?php echo ( 'night_stay' === $model_type ) ? 'is-active' : ''; ?>" data-model="night_stay">
						<span class="mb-pill-icon">🏨</span>
						<span class="mb-pill-info">
							<span class="mb-pill-title"><?php esc_html_e( 'Night-based Stays', 'my-booking-engine' ); ?></span>
							<span class="mb-pill-desc"><?php esc_html_e( 'Overnight stays, check-in & check-out dates', 'my-booking-engine' ); ?></span>
						</span>
					</button>

					<button type="button" class="mb-model-pill-btn <?php echo ( 'capacity_roster' === $model_type ) ? 'is-active' : ''; ?>" data-model="capacity_roster">
						<span class="mb-pill-icon">👥</span>
						<span class="mb-pill-info">
							<span class="mb-pill-title"><?php esc_html_e( 'Capacity / Event Roster', 'my-booking-engine' ); ?></span>
							<span class="mb-pill-desc"><?php esc_html_e( 'Fixed events, attendee limits & spots', 'my-booking-engine' ); ?></span>
						</span>
					</button>
				</div>
			</div>
		</div>

		<!-- STEP 2: Pricing & Rules -->
		<div class="mb-wizard-step-panel" data-panel="2">
			<div class="mb-panel-intro">
				<div class="mb-intro-badge"><?php esc_html_e( 'Financials & Capacities', 'my-booking-engine' ); ?></div>
				<h3><?php esc_html_e( 'Set your rates, capacity, and scheduling rules', 'my-booking-engine' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Define standard rates, optional weekend surge pricing, slot lengths, and simultaneous attendee capacity.', 'my-booking-engine' ); ?>
				</p>
			</div>

			<div class="mb-wizard-grid-2">
				<div class="mb-wizard-card">
					<h4>💰 <?php esc_html_e( 'Rates & Pricing', 'my-booking-engine' ); ?></h4>
					<div class="mb-w-field">
						<label for="mb_base_price"><?php esc_html_e( 'Base Rate / Price ($)', 'my-booking-engine' ); ?> <span class="mb-req">*</span></label>
						<input type="number" name="mb_base_price" id="mb_base_price" value="<?php echo esc_attr( $base_price ); ?>" step="0.01" min="0" class="widefat" placeholder="0.00">
						<span class="mb-w-hint"><?php esc_html_e( 'Per appointment slot, per night, per day, or per ticket.', 'my-booking-engine' ); ?></span>
					</div>
					<div class="mb-w-field" style="margin-top:14px;">
						<label for="mb_weekend_price"><?php esc_html_e( 'Weekend Price ($) (Optional)', 'my-booking-engine' ); ?></label>
						<input type="number" name="mb_weekend_price" id="mb_weekend_price" value="<?php echo esc_attr( $weekend_price ); ?>" step="0.01" min="0" class="widefat" placeholder="Leave blank to use base rate">
						<span class="mb-w-hint"><?php esc_html_e( 'Applied on Fridays & Saturdays (ideal for hotels, villas & weekend rentals).', 'my-booking-engine' ); ?></span>
					</div>
				</div>

				<div class="mb-wizard-card">
					<h4>👥 <?php esc_html_e( 'Capacity & Attendance', 'my-booking-engine' ); ?></h4>
					<div class="mb-w-field">
						<label for="mb_capacity" id="mb_meta_capacity_label">👥 <?php esc_html_e( 'Maximum Capacity / Guests Allowed', 'my-booking-engine' ); ?></label>
						<input type="number" name="mb_capacity" id="mb_capacity" value="<?php echo esc_attr( $capacity ); ?>" min="1" max="1000" class="widefat mb-capacity-sync-field">
						<span class="mb-w-hint" id="mb_meta_capacity_desc"><?php esc_html_e( 'Maximum simultaneous guests, rooms, vehicles, or available event seats.', 'my-booking-engine' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Dynamic Model Specific Rules -->
			<div class="mb-wizard-card" style="margin-top:20px;">
				<h4>⚙️ <?php esc_html_e( 'Model Rules & Durations', 'my-booking-engine' ); ?></h4>
				
				<!-- Hourly Slot Fields -->
				<div class="mb-field-row mb-field-hourly_slot">
					<div class="mb-wizard-grid-3">
						<div class="mb-w-field">
							<label for="mb_slot_duration"><?php esc_html_e( 'Slot Duration (Minutes)', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_slot_duration" id="mb_slot_duration" value="<?php echo esc_attr( $slot_duration ); ?>" min="5" step="5" class="widefat">
							<span class="mb-w-hint"><?php esc_html_e( 'e.g. 30, 45, 60 minutes per session.', 'my-booking-engine' ); ?></span>
						</div>
						<div class="mb-w-field">
							<label for="mb_buffer_before"><?php esc_html_e( 'Buffer Prep Before (Mins)', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_buffer_before" id="mb_buffer_before" value="<?php echo esc_attr( $buffer_before ); ?>" min="0" step="5" class="widefat">
							<span class="mb-w-hint"><?php esc_html_e( 'Prep time before each booking.', 'my-booking-engine' ); ?></span>
						</div>
						<div class="mb-w-field">
							<label for="mb_buffer_after"><?php esc_html_e( 'Cleaning Gap After (Mins)', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_buffer_after" id="mb_buffer_after" value="<?php echo esc_attr( $buffer_after ); ?>" min="0" step="5" class="widefat">
							<span class="mb-w-hint"><?php esc_html_e( 'Automatic turnaround buffer to block overlap.', 'my-booking-engine' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Day & Night Stays / Rentals -->
				<div class="mb-field-row mb-field-day_rental mb-field-night_stay" style="display:none;">
					<div class="mb-wizard-grid-3">
						<div class="mb-w-field" style="background:#f0f7ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 12px;">
							<label for="mb_capacity_property_meta" style="color:#1d4ed8; font-weight:700;">👥 <?php esc_html_e( 'Maximum Guests Allowed', 'my-booking-engine' ); ?> <span class="mb-req">*</span></label>
							<input type="number" name="mb_capacity_property" id="mb_capacity_property_meta" value="<?php echo esc_attr( $capacity ); ?>" min="1" max="100" class="widefat mb-capacity-sync-field" placeholder="<?php esc_attr_e( 'e.g. 4 guests', 'my-booking-engine' ); ?>" style="margin-top:4px; font-weight:600;">
							<span class="mb-w-hint" style="color:#2563eb; display:block; margin-top:2px;"><?php esc_html_e( 'Max guests permitted for this property rental.', 'my-booking-engine' ); ?></span>
						</div>
						<div class="mb-w-field">
							<label for="mb_min_duration"><?php esc_html_e( 'Minimum Stay / Rental (Days/Nights)', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_min_duration" id="mb_min_duration" value="<?php echo esc_attr( $min_duration ); ?>" min="1" class="widefat" style="margin-top:4px;">
							<span class="mb-w-hint"><?php esc_html_e( 'Shortest allowable stay.', 'my-booking-engine' ); ?></span>
						</div>
						<div class="mb-w-field">
							<label for="mb_max_duration"><?php esc_html_e( 'Maximum Stay / Rental (Days/Nights)', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_max_duration" id="mb_max_duration" value="<?php echo esc_attr( $max_duration ); ?>" min="1" class="widefat" style="margin-top:4px;">
							<span class="mb-w-hint"><?php esc_html_e( 'Longest allowable stay.', 'my-booking-engine' ); ?></span>
						</div>
					</div>
					<div class="mb-wizard-grid-2" style="margin-top:14px;">
						<div class="mb-w-field">
							<label for="mb_checkin_time"><?php esc_html_e( 'Standard Check-in Time', 'my-booking-engine' ); ?></label>
							<input type="time" name="mb_checkin_time" id="mb_checkin_time" value="<?php echo esc_attr( $checkin_time ); ?>" class="widefat">
						</div>
						<div class="mb-w-field">
							<label for="mb_checkout_time"><?php esc_html_e( 'Standard Check-out Time', 'my-booking-engine' ); ?></label>
							<input type="time" name="mb_checkout_time" id="mb_checkout_time" value="<?php echo esc_attr( $checkout_time ); ?>" class="widefat">
						</div>
					</div>
				</div>

				<!-- Capacity Roster Fields -->
				<div class="mb-field-row mb-field-capacity_roster" style="display:none;">
					<div class="mb-wizard-grid-2">
						<div class="mb-w-field">
							<label for="mb_event_start"><?php esc_html_e( 'Event Start Time', 'my-booking-engine' ); ?></label>
							<input type="datetime-local" name="mb_event_start" id="mb_event_start" value="<?php echo esc_attr( $event_start ); ?>" class="widefat">
						</div>
						<div class="mb-w-field">
							<label for="mb_event_end"><?php esc_html_e( 'Event End Time', 'my-booking-engine' ); ?></label>
							<input type="datetime-local" name="mb_event_end" id="mb_event_end" value="<?php echo esc_attr( $event_end ); ?>" class="widefat">
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- STEP 3: Location & Geocoding -->
		<div class="mb-wizard-step-panel" data-panel="3">
			<div class="mb-panel-intro">
				<div class="mb-intro-badge"><?php esc_html_e( 'Worldwide Search & Geocoding', 'my-booking-engine' ); ?></div>
				<h3><?php esc_html_e( 'Where is this listing located?', 'my-booking-engine' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Enter the address or postal code. Click "Auto-Detect Coordinates" to instantly resolve GPS coordinates for radius search and the interactive Leaflet map.', 'my-booking-engine' ); ?>
				</p>
			</div>

			<div class="mb-wizard-card">
				<div class="mb-wizard-grid-3">
					<div class="mb-w-field">
						<label for="mb_postal_code"><?php esc_html_e( 'Postal / ZIP Code', 'my-booking-engine' ); ?> <span class="mb-req">*</span></label>
						<input type="text" name="mb_postal_code" id="mb_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="widefat" placeholder="e.g. 90210, SW1A 1AA, 75001">
					</div>
					<div class="mb-w-field">
						<label for="mb_city"><?php esc_html_e( 'City', 'my-booking-engine' ); ?></label>
						<input type="text" name="mb_city" id="mb_city" value="<?php echo esc_attr( $city ); ?>" class="widefat" placeholder="e.g. Los Angeles, London, Paris">
					</div>
					<div class="mb-w-field">
						<label for="mb_country_code"><?php esc_html_e( 'Country Code (ISO)', 'my-booking-engine' ); ?></label>
						<input type="text" name="mb_country_code" id="mb_country_code" value="<?php echo esc_attr( $country_code ); ?>" class="widefat" placeholder="US, UK, CA, FR">
					</div>
				</div>

				<div style="margin-top:20px; padding:16px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
					<div>
						<strong style="color:#1e40af;"><?php esc_html_e( 'Instant Geocoding Helper', 'my-booking-engine' ); ?></strong>
						<p style="margin:2px 0 0 0; font-size:12px; color:#3b82f6;"><?php esc_html_e( 'Converts postal code into high-speed spatial GPS coordinates.', 'my-booking-engine' ); ?></p>
					</div>
					<div style="display:flex; align-items:center; gap:8px;">
						<button type="button" class="button button-primary mb-btn-geocode" id="mb_btn_geocode">
							<span class="dashicons dashicons-location" style="vertical-align:middle; margin-top:-2px;"></span>
							<?php esc_html_e( 'Auto-Detect Coordinates', 'my-booking-engine' ); ?>
						</button>
						<span class="spinner" id="mb_geocode_spinner"></span>
						<span id="mb_geocode_status" style="font-weight:700; font-size:13px;"></span>
					</div>
				</div>

				<div class="mb-wizard-grid-2" style="margin-top:16px;">
					<div class="mb-w-field">
						<label for="mb_latitude"><?php esc_html_e( 'Latitude', 'my-booking-engine' ); ?></label>
						<input type="text" name="mb_latitude" id="mb_latitude" value="<?php echo esc_attr( $latitude ); ?>" class="widefat" readonly style="background:#f8fafc; font-family:monospace;">
					</div>
					<div class="mb-w-field">
						<label for="mb_longitude"><?php esc_html_e( 'Longitude', 'my-booking-engine' ); ?></label>
						<input type="text" name="mb_longitude" id="mb_longitude" value="<?php echo esc_attr( $longitude ); ?>" class="widefat" readonly style="background:#f8fafc; font-family:monospace;">
					</div>
				</div>
			</div>
		</div>

		<!-- STEP 4: Operating Hours Schedule -->
		<div class="mb-wizard-step-panel" data-panel="4">
			<div class="mb-panel-intro">
				<div class="mb-intro-badge"><?php esc_html_e( 'Weekly Operating Hours', 'my-booking-engine' ); ?></div>
				<h3><?php esc_html_e( 'Set working hours and open days', 'my-booking-engine' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'The appointment engine automatically calculates available bookable slots within these operating windows.', 'my-booking-engine' ); ?>
				</p>
			</div>

			<div class="mb-wizard-card">
				<div class="mb-schedule-list">
					<?php
					foreach ( $days_of_week as $day_idx => $day_info ) :
						$saved_rule = isset( $schedule_map[ $day_idx ] ) ? $schedule_map[ $day_idx ] : null;
						$is_open    = ( null !== $saved_rule ) || ( empty( $schedule_map ) && $day_idx >= 1 && $day_idx <= 5 );
						$start_val  = $saved_rule ? substr( $saved_rule->start_time, 0, 5 ) : '09:00';
						$end_val    = $saved_rule ? substr( $saved_rule->end_time, 0, 5 ) : '17:00';
						?>
						<div class="mb-schedule-row <?php echo $is_open ? 'is-open' : 'is-closed'; ?>">
							<div class="mb-sched-day">
								<span class="mb-day-badge"><?php echo esc_html( $day_info['short'] ); ?></span>
								<strong><?php echo esc_html( $day_info['name'] ); ?></strong>
							</div>

							<div class="mb-sched-toggle">
								<label class="mb-switch">
									<input type="checkbox" name="mb_schedule[<?php echo esc_attr( $day_idx ); ?>][enabled]" value="1" <?php checked( $is_open ); ?> class="mb-day-switch">
									<span class="mb-slider round"></span>
								</label>
								<span class="mb-switch-label"><?php echo $is_open ? esc_html__( 'Open', 'my-booking-engine' ) : esc_html__( 'Closed', 'my-booking-engine' ); ?></span>
							</div>

							<div class="mb-sched-times" style="<?php echo $is_open ? '' : 'opacity:0.4; pointer-events:none;'; ?>">
								<input type="time" name="mb_schedule[<?php echo esc_attr( $day_idx ); ?>][start]" value="<?php echo esc_attr( $start_val ); ?>" class="mb-time-picker">
								<span style="color:#94a3b8;">→</span>
								<input type="time" name="mb_schedule[<?php echo esc_attr( $day_idx ); ?>][end]" value="<?php echo esc_attr( $end_val ); ?>" class="mb-time-picker">
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<!-- STEP 5: Media, Amenities & Policy -->
		<div class="mb-wizard-step-panel" data-panel="5">
			<div class="mb-panel-intro">
				<div class="mb-intro-badge"><?php esc_html_e( 'Showcase & Policies', 'my-booking-engine' ); ?></div>
				<h3><?php esc_html_e( 'Photos, verified amenities, and cancellation terms', 'my-booking-engine' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'High quality photos and transparent house rules boost customer trust and conversion rates.', 'my-booking-engine' ); ?>
				</p>
			</div>

			<div class="mb-wizard-card">
				<h4>📸 <?php esc_html_e( 'Photo Gallery & Sliders', 'my-booking-engine' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Used for the Airbnb mosaic gallery, Car Rental slider, and Shop showcases.', 'my-booking-engine' ); ?></p>
				
				<div style="margin-top:12px;">
					<input type="hidden" name="mb_gallery_images" id="mb_gallery_images" value="<?php echo esc_attr( $gallery_images ); ?>">
					<button type="button" class="button button-primary" id="mb_btn_select_gallery">
						<span class="dashicons dashicons-images-alt2" style="vertical-align:middle; margin-top:-2px;"></span>
						<?php esc_html_e( 'Select Photos from Media Library', 'my-booking-engine' ); ?>
					</button>
					<button type="button" class="button button-link-delete" id="mb_btn_clear_gallery" style="margin-left:10px;">
						<?php esc_html_e( 'Remove All Photos', 'my-booking-engine' ); ?>
					</button>
				</div>

				<div id="mb_gallery_preview" class="mb-gallery-preview-grid" style="margin-top:16px;">
					<?php
					if ( ! empty( $gallery_images ) ) :
						$img_ids = explode( ',', $gallery_images );
						foreach ( $img_ids as $gid ) :
							$gid = absint( trim( $gid ) );
							$src = wp_get_attachment_image_url( $gid, 'thumbnail' );
							if ( $src ) :
								?>
								<div class="mb-preview-thumb">
									<img src="<?php echo esc_url( $src ); ?>" alt="">
								</div>
								<?php
							endif;
						endforeach;
					endif;
					?>
				</div>
			</div>

			<div class="mb-wizard-grid-2" style="margin-top:20px;">
				<div class="mb-wizard-card">
					<h4>✨ <?php esc_html_e( 'Verified Amenities & Features', 'my-booking-engine' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Enter one amenity or feature per line:', 'my-booking-engine' ); ?></p>
					<textarea name="mb_amenities" id="mb_amenities" rows="6" class="widefat" placeholder="<?php esc_attr_e( "High-Speed Wi-Fi
Free Onsite Parking
Air Conditioning
Wheelchair Accessible
Complimentary Coffee", 'my-booking-engine' ); ?>"><?php echo esc_textarea( $amenities ); ?></textarea>
				</div>

				<div class="mb-wizard-card">
					<h4>📜 <?php esc_html_e( 'Cancellation & House Rules Policy', 'my-booking-engine' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Displayed in the Policy card on the listing page and booking funnel:', 'my-booking-engine' ); ?></p>
					<textarea name="mb_policy" id="mb_policy" rows="6" class="widefat" placeholder="<?php esc_attr_e( "Free cancellation up to 24 hours before check-in/start time.
No smoking inside premises.
Please arrive 10 minutes before your scheduled appointment.", 'my-booking-engine' ); ?>"><?php echo esc_textarea( $policy ); ?></textarea>
				</div>
			</div>
		</div>

	</div>

		</div><!-- /.mb-wizard-scroll-body -->

		<!-- Wizard Navigation Footer -->
		<div class="mb-wizard-footer">
			<button type="button" class="button button-large mb-w-btn" id="mb-wiz-prev" style="display:none;">
				← <?php esc_html_e( 'Previous Step', 'my-booking-engine' ); ?>
			</button>

			<div class="mb-wizard-status-indicator">
				<span id="mb-wiz-step-text"><?php esc_html_e( 'Step 1 of 5: Type & Design', 'my-booking-engine' ); ?></span>
			</div>

			<div class="mb-wizard-footer-right" style="display:flex; gap:10px;">
				<button type="button" class="button button-primary button-large mb-w-btn" id="mb-wiz-next">
					<?php esc_html_e( 'Next Step →', 'my-booking-engine' ); ?>
				</button>
				<button type="button" class="button button-primary button-large mb-w-btn" id="mb-wiz-publish-btn" style="display:none; background:#16a34a; border-color:#15803d;">
					💾 <?php esc_html_e( 'Save & Publish Listing', 'my-booking-engine' ); ?>
				</button>
			</div>
		</div>
	</div><!-- /.mb-wizard-inner-modal -->
</div><!-- /.mb-wizard-wrapper -->
