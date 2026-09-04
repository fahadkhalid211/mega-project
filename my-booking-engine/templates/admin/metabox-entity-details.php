<?php
/**
 * Admin Meta Box Template: Entity Details & Configuration.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$model_type    = get_post_meta( $post->ID, '_mb_model_type', true ) ?: 'hourly_slot';
$base_price    = get_post_meta( $post->ID, '_mb_base_price', true ) ?: '0.00';
$weekend_price = get_post_meta( $post->ID, '_mb_weekend_price', true ) ?: '';
$slot_duration = get_post_meta( $post->ID, '_mb_slot_duration', true ) ?: '60';
$buffer_before = get_post_meta( $post->ID, '_mb_buffer_before', true ) ?: '0';
$buffer_after  = get_post_meta( $post->ID, '_mb_buffer_after', true ) ?: '0';
$capacity      = get_post_meta( $post->ID, '_mb_capacity', true ) ?: '1';
$min_duration  = get_post_meta( $post->ID, '_mb_min_duration', true ) ?: '1';
$max_duration  = get_post_meta( $post->ID, '_mb_max_duration', true ) ?: '30';
$checkin_time  = get_post_meta( $post->ID, '_mb_checkin_time', true ) ?: '15:00';
$checkout_time = get_post_meta( $post->ID, '_mb_checkout_time', true ) ?: '11:00';
$event_start   = get_post_meta( $post->ID, '_mb_event_start', true ) ?: '';
$event_end     = get_post_meta( $post->ID, '_mb_event_end', true ) ?: '';

$postal_code  = $location ? $location->postal_code : '';
$city         = $location ? $location->city : '';
$country_code = $location ? $location->country_code : '';
$latitude     = $location ? $location->latitude : '';
$longitude    = $location ? $location->longitude : '';

$visual_layout  = get_post_meta( $post->ID, '_mb_visual_layout', true ) ?: '';
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
	1 => __( 'Monday', 'my-booking-engine' ),
	2 => __( 'Tuesday', 'my-booking-engine' ),
	3 => __( 'Wednesday', 'my-booking-engine' ),
	4 => __( 'Thursday', 'my-booking-engine' ),
	5 => __( 'Friday', 'my-booking-engine' ),
	6 => __( 'Saturday', 'my-booking-engine' ),
	0 => __( 'Sunday', 'my-booking-engine' ),
);
?>

<div class="mb-metabox-wrapper">
	<nav class="mb-tabs-nav">
		<a href="#mb-tab-model" class="mb-tab-link active"><?php esc_html_e( '1. Booking Model & Rules', 'my-booking-engine' ); ?></a>
		<a href="#mb-tab-pricing" class="mb-tab-link"><?php esc_html_e( '2. Pricing & Capacity', 'my-booking-engine' ); ?></a>
		<a href="#mb-tab-location" class="mb-tab-link"><?php esc_html_e( '3. Location & Geocoding', 'my-booking-engine' ); ?></a>
		<a href="#mb-tab-schedule" class="mb-tab-link"><?php esc_html_e( '4. Working Hours Schedule', 'my-booking-engine' ); ?></a>
		<a href="#mb-tab-layout" class="mb-tab-link"><?php esc_html_e( '5. Visual Layout & Media', 'my-booking-engine' ); ?></a>
	</nav>

	<!-- TAB 1: Booking Model & Rules -->
	<div id="mb-tab-model" class="mb-tab-content active">
		<table class="form-table">
			<tr>
				<th><label for="mb_model_type"><?php esc_html_e( 'Engine Booking Model', 'my-booking-engine' ); ?></label></th>
				<td>
					<select name="mb_model_type" id="mb_model_type" class="widefat mb-model-switcher">
						<option value="hourly_slot" <?php selected( $model_type, 'hourly_slot' ); ?>><?php esc_html_e( 'Hourly / Slot-based Appointments (Services, Salons, Doctors, Consultations)', 'my-booking-engine' ); ?></option>
						<option value="day_rental" <?php selected( $model_type, 'day_rental' ); ?>><?php esc_html_e( 'Day-based Rentals (Cars, Machinery, Equipment, Gear)', 'my-booking-engine' ); ?></option>
						<option value="night_stay" <?php selected( $model_type, 'night_stay' ); ?>><?php esc_html_e( 'Night-based Stays (Hotels, Villas, Apartments, Resorts)', 'my-booking-engine' ); ?></option>
						<option value="capacity_roster" <?php selected( $model_type, 'capacity_roster' ); ?>><?php esc_html_e( 'Capacity-based Tickets (Events, Workshops, Group Tours)', 'my-booking-engine' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Select which booking algorithm and schedule rules govern this entity.', 'my-booking-engine' ); ?></p>
				</td>
			</tr>

			<!-- Hourly Slot Fields -->
			<tr class="mb-field-row mb-field-hourly_slot">
				<th><label for="mb_slot_duration"><?php esc_html_e( 'Slot Duration (Minutes)', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="number" name="mb_slot_duration" id="mb_slot_duration" value="<?php echo esc_attr( $slot_duration ); ?>" min="5" step="5" class="small-text">
					<span class="description"><?php esc_html_e( 'e.g., 30, 45, 60 minutes per appointment.', 'my-booking-engine' ); ?></span>
				</td>
			</tr>
			<tr class="mb-field-row mb-field-hourly_slot">
				<th><label><?php esc_html_e( 'Buffer / Turnaround Times', 'my-booking-engine' ); ?></label></th>
				<td>
					<label for="mb_buffer_before">
						<?php esc_html_e( 'Prep before:', 'my-booking-engine' ); ?>
						<input type="number" name="mb_buffer_before" id="mb_buffer_before" value="<?php echo esc_attr( $buffer_before ); ?>" min="0" step="5" class="small-text"> <?php esc_html_e( 'mins', 'my-booking-engine' ); ?>
					</label>
					&nbsp;&nbsp;&nbsp;
					<label for="mb_buffer_after">
						<?php esc_html_e( 'Cleaning/Gap after:', 'my-booking-engine' ); ?>
						<input type="number" name="mb_buffer_after" id="mb_buffer_after" value="<?php echo esc_attr( $buffer_after ); ?>" min="0" step="5" class="small-text"> <?php esc_html_e( 'mins', 'my-booking-engine' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Automatic padding added to each booked slot to prevent back-to-back overlaps.', 'my-booking-engine' ); ?></p>
				</td>
			</tr>

			<!-- Day / Night Rental Fields -->
			<tr class="mb-field-row mb-field-day_rental mb-field-night_stay">
				<th><label><?php esc_html_e( 'Duration Limits (Days/Nights)', 'my-booking-engine' ); ?></label></th>
				<td>
					<label for="mb_min_duration">
						<?php esc_html_e( 'Min:', 'my-booking-engine' ); ?>
						<input type="number" name="mb_min_duration" id="mb_min_duration" value="<?php echo esc_attr( $min_duration ); ?>" min="1" class="small-text">
					</label>
					&nbsp;&nbsp;&nbsp;
					<label for="mb_max_duration">
						<?php esc_html_e( 'Max:', 'my-booking-engine' ); ?>
						<input type="number" name="mb_max_duration" id="mb_max_duration" value="<?php echo esc_attr( $max_duration ); ?>" min="1" class="small-text">
					</label>
				</td>
			</tr>
			<tr class="mb-field-row mb-field-night_stay">
				<th><label><?php esc_html_e( 'Check-in & Check-out Hours', 'my-booking-engine' ); ?></label></th>
				<td>
					<label for="mb_checkin_time">
						<?php esc_html_e( 'Check-in:', 'my-booking-engine' ); ?>
						<input type="time" name="mb_checkin_time" id="mb_checkin_time" value="<?php echo esc_attr( $checkin_time ); ?>">
					</label>
					&nbsp;&nbsp;&nbsp;
					<label for="mb_checkout_time">
						<?php esc_html_e( 'Check-out:', 'my-booking-engine' ); ?>
						<input type="time" name="mb_checkout_time" id="mb_checkout_time" value="<?php echo esc_attr( $checkout_time ); ?>">
					</label>
				</td>
			</tr>

			<!-- Capacity Roster Fields -->
			<tr class="mb-field-row mb-field-capacity_roster">
				<th><label><?php esc_html_e( 'Event Schedule Date/Time', 'my-booking-engine' ); ?></label></th>
				<td>
					<label for="mb_event_start">
						<?php esc_html_e( 'Starts:', 'my-booking-engine' ); ?>
						<input type="datetime-local" name="mb_event_start" id="mb_event_start" value="<?php echo esc_attr( $event_start ); ?>">
					</label>
					&nbsp;&nbsp;&nbsp;
					<label for="mb_event_end">
						<?php esc_html_e( 'Ends:', 'my-booking-engine' ); ?>
						<input type="datetime-local" name="mb_event_end" id="mb_event_end" value="<?php echo esc_attr( $event_end ); ?>">
					</label>
				</td>
			</tr>
		</table>
	</div>

	<!-- TAB 2: Pricing & Capacity -->
	<div id="mb-tab-pricing" class="mb-tab-content">
		<table class="form-table">
			<tr>
				<th><label for="mb_base_price"><?php esc_html_e( 'Base Rate / Price', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="number" name="mb_base_price" id="mb_base_price" value="<?php echo esc_attr( $base_price ); ?>" step="0.01" min="0" class="regular-text">
					<p class="description"><?php esc_html_e( 'Price per appointment slot, per day, per night, or per event ticket.', 'my-booking-engine' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="mb_weekend_price"><?php esc_html_e( 'Weekend Price (Optional)', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="number" name="mb_weekend_price" id="mb_weekend_price" value="<?php echo esc_attr( $weekend_price ); ?>" step="0.01" min="0" class="regular-text">
					<p class="description"><?php esc_html_e( 'Special rate applied on Fridays and Saturdays (for night stays and day rentals). Leave empty to use base price.', 'my-booking-engine' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="mb_capacity"><?php esc_html_e( 'Capacity / Spots', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="number" name="mb_capacity" id="mb_capacity" value="<?php echo esc_attr( $capacity ); ?>" min="1" class="small-text">
					<p class="description"><?php esc_html_e( 'Maximum simultaneous attendees/vehicles/rooms available per slot or event.', 'my-booking-engine' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<!-- TAB 3: Location & Geocoding -->
	<div id="mb-tab-location" class="mb-tab-content">
		<p class="description" style="margin-bottom:15px;">
			<?php esc_html_e( 'Coordinates are stored in the custom fast-index table (wp_mb_locations). Enter a postal code, city, or country and click "Auto-Detect Coordinates" to instantly resolve GPS coordinates.', 'my-booking-engine' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th><label for="mb_postal_code"><?php esc_html_e( 'Postal / ZIP Code', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="text" name="mb_postal_code" id="mb_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., 90210, SW1A 1AA, 75001', 'my-booking-engine' ); ?>">
				</td>
			</tr>
			<tr>
				<th><label for="mb_city"><?php esc_html_e( 'City', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="text" name="mb_city" id="mb_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Los Angeles, London, Paris', 'my-booking-engine' ); ?>">
				</td>
			</tr>
			<tr>
				<th><label for="mb_country_code"><?php esc_html_e( 'Country Code', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="text" name="mb_country_code" id="mb_country_code" value="<?php echo esc_attr( $country_code ); ?>" class="small-text" placeholder="US">
					<span class="description"><?php esc_html_e( 'Two-letter ISO country code (e.g., US, UK, DE, FR, AU).', 'my-booking-engine' ); ?></span>
				</td>
			</tr>
			<tr>
				<th></th>
				<td>
					<button type="button" class="button button-secondary mb-btn-geocode" id="mb_btn_geocode">
						<span class="dashicons dashicons-location-alt" style="vertical-align:middle;"></span>
						<?php esc_html_e( 'Auto-Detect Coordinates (Nominatim / Google)', 'my-booking-engine' ); ?>
					</button>
					<span class="spinner" id="mb_geocode_spinner"></span>
					<span id="mb_geocode_status" style="margin-left:8px;font-weight:600;"></span>
				</td>
			</tr>
			<tr>
				<th><label for="mb_latitude"><?php esc_html_e( 'Latitude', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="text" name="mb_latitude" id="mb_latitude" value="<?php echo esc_attr( $latitude ); ?>" class="regular-text" readonly>
				</td>
			</tr>
			<tr>
				<th><label for="mb_longitude"><?php esc_html_e( 'Longitude', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="text" name="mb_longitude" id="mb_longitude" value="<?php echo esc_attr( $longitude ); ?>" class="regular-text" readonly>
				</td>
			</tr>
		</table>
	</div>

	<!-- TAB 4: Working Hours Schedule -->
	<div id="mb-tab-schedule" class="mb-tab-content">
		<p class="description" style="margin-bottom:15px;">
			<?php esc_html_e( 'Configure recurring weekly operating hours. Used by the Slot Engine to calculate available appointment times.', 'my-booking-engine' ); ?>
		</p>
		<table class="widefat striped mb-schedule-table">
			<thead>
				<tr>
					<th style="width:140px;"><?php esc_html_e( 'Day of Week', 'my-booking-engine' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'Status', 'my-booking-engine' ); ?></th>
					<th><?php esc_html_e( 'Opening Time', 'my-booking-engine' ); ?></th>
					<th><?php esc_html_e( 'Closing Time', 'my-booking-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $days_of_week as $day_idx => $day_name ) :
					$saved_rule = isset( $schedule_map[ $day_idx ] ) ? $schedule_map[ $day_idx ] : null;
					$is_open    = ( null !== $saved_rule ) || ( empty( $schedule_map ) && $day_idx >= 1 && $day_idx <= 5 );
					$start_val  = $saved_rule ? substr( $saved_rule->start_time, 0, 5 ) : '09:00';
					$end_val    = $saved_rule ? substr( $saved_rule->end_time, 0, 5 ) : '17:00';
					?>
					<tr>
						<td><strong><?php echo esc_html( $day_name ); ?></strong></td>
						<td>
							<label>
								<input type="checkbox" name="mb_schedule[<?php echo esc_attr( $day_idx ); ?>][enabled]" value="1" <?php checked( $is_open ); ?> class="mb-day-toggle">
								<?php esc_html_e( 'Open', 'my-booking-engine' ); ?>
							</label>
						</td>
						<td>
							<input type="time" name="mb_schedule[<?php echo esc_attr( $day_idx ); ?>][start]" value="<?php echo esc_attr( $start_val ); ?>" class="mb-time-input">
						</td>
						<td>
							<input type="time" name="mb_schedule[<?php echo esc_attr( $day_idx ); ?>][end]" value="<?php echo esc_attr( $end_val ); ?>" class="mb-time-input">
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- TAB 5: Visual Layout & Presentation -->
	<div id="mb-tab-layout" class="mb-tab-content">
		<h3 style="margin-top:0;"><?php esc_html_e( 'Single Listing Layout Architecture', 'my-booking-engine' ); ?></h3>
		<p class="description" style="margin-bottom:20px;">
			<?php esc_html_e( 'Decoupled from booking logic. Select a specialized visual layout designed for this entity type, or use Auto-Detect to inherit from your booking model.', 'my-booking-engine' ); ?>
		</p>

		<div class="mb-layout-cards-grid">
			<label class="mb-layout-card <?php echo empty( $visual_layout ) ? 'selected' : ''; ?>">
				<input type="radio" name="mb_visual_layout" value="" <?php checked( empty( $visual_layout ) ); ?>>
				<div class="mb-layout-card-inner">
					<div class="mb-layout-icon dashicons dashicons-admin-generic"></div>
					<div class="mb-layout-card-title"><?php esc_html_e( 'Auto-Detect', 'my-booking-engine' ); ?></div>
					<p class="mb-layout-card-desc"><?php esc_html_e( 'Automatically matches layout to the selected booking model.', 'my-booking-engine' ); ?></p>
				</div>
			</label>

			<label class="mb-layout-card <?php echo 'hotel' === $visual_layout ? 'selected' : ''; ?>">
				<input type="radio" name="mb_visual_layout" value="hotel" <?php checked( 'hotel' === $visual_layout ); ?>>
				<div class="mb-layout-card-inner">
					<div class="mb-layout-icon dashicons dashicons-building"></div>
					<div class="mb-layout-card-title"><?php esc_html_e( 'Hotel & Stays', 'my-booking-engine' ); ?></div>
					<p class="mb-layout-card-desc"><?php esc_html_e( 'Airbnb style: mosaic photo gallery, continuous date range ribbon, specs grid, sticky reserve card.', 'my-booking-engine' ); ?></p>
				</div>
			</label>

			<label class="mb-layout-card <?php echo 'doctor' === $visual_layout ? 'selected' : ''; ?>">
				<input type="radio" name="mb_visual_layout" value="doctor" <?php checked( 'doctor' === $visual_layout ); ?>>
				<div class="mb-layout-card-inner">
					<div class="mb-layout-icon dashicons dashicons-id-alt"></div>
					<div class="mb-layout-card-title"><?php esc_html_e( 'Doctor & Specialist', 'my-booking-engine' ); ?></div>
					<p class="mb-layout-card-desc"><?php esc_html_e( 'Physician & healthcare: specialist bio, consultation tiers, clinic hours, dynamic slot chips.', 'my-booking-engine' ); ?></p>
				</div>
			</label>

			<label class="mb-layout-card <?php echo 'salon' === $visual_layout ? 'selected' : ''; ?>">
				<input type="radio" name="mb_visual_layout" value="salon" <?php checked( 'salon' === $visual_layout ); ?>>
				<div class="mb-layout-card-inner">
					<div class="mb-layout-icon dashicons dashicons-admin-customizer"></div>
					<div class="mb-layout-card-title"><?php esc_html_e( 'Salon & Spa', 'my-booking-engine' ); ?></div>
					<p class="mb-layout-card-desc"><?php esc_html_e( 'Fresha style: service treatment menu with durations & prices, specialist picker, interactive cart.', 'my-booking-engine' ); ?></p>
				</div>
			</label>

			<label class="mb-layout-card <?php echo 'rental' === $visual_layout ? 'selected' : ''; ?>">
				<input type="radio" name="mb_visual_layout" value="rental" <?php checked( 'rental' === $visual_layout ); ?>>
				<div class="mb-layout-card-inner">
					<div class="mb-layout-icon dashicons dashicons-car"></div>
					<div class="mb-layout-card-title"><?php esc_html_e( 'Car & Equipment', 'my-booking-engine' ); ?></div>
					<p class="mb-layout-card-desc"><?php esc_html_e( 'Turo style: vehicle specs, multi-day calendar ribbon, insurance protection packages, pricing breakdown.', 'my-booking-engine' ); ?></p>
				</div>
			</label>

			<label class="mb-layout-card <?php echo 'hourly' === $visual_layout ? 'selected' : ''; ?>">
				<input type="radio" name="mb_visual_layout" value="hourly" <?php checked( 'hourly' === $visual_layout ); ?>>
				<div class="mb-layout-card-inner">
					<div class="mb-layout-icon dashicons dashicons-clock"></div>
					<div class="mb-layout-card-title"><?php esc_html_e( 'Hourly & Studio', 'my-booking-engine' ); ?></div>
					<p class="mb-layout-card-desc"><?php esc_html_e( 'Workspaces & studios: quick specs banner, live appointment slot loader, instant booking funnel.', 'my-booking-engine' ); ?></p>
				</div>
			</label>

			<label class="mb-layout-card <?php echo 'shop' === $visual_layout ? 'selected' : ''; ?>">
				<input type="radio" name="mb_visual_layout" value="shop" <?php checked( 'shop' === $visual_layout ); ?>>
				<div class="mb-layout-card-inner">
					<div class="mb-layout-icon dashicons dashicons-store"></div>
					<div class="mb-layout-card-title"><?php esc_html_e( 'Shop & Business', 'my-booking-engine' ); ?></div>
					<p class="mb-layout-card-desc"><?php esc_html_e( 'Local commerce: weekly operating hours schedule table, amenities, Leaflet map location, inquiries.', 'my-booking-engine' ); ?></p>
				</div>
			</label>
		</div>

		<hr style="margin:28px 0 20px 0; border:0; border-top:1px solid #e2e8f0;">

		<table class="form-table">
			<tr>
				<th><label for="mb_gallery_images"><?php esc_html_e( 'Gallery Images', 'my-booking-engine' ); ?></label></th>
				<td>
					<input type="text" name="mb_gallery_images" id="mb_gallery_images" value="<?php echo esc_attr( $gallery_images ); ?>" class="regular-text" placeholder="e.g. 101, 102, 103">
					<button type="button" class="button button-secondary" id="mb_btn_select_gallery">
						<span class="dashicons dashicons-format-gallery" style="vertical-align:middle;margin-top:-2px;"></span>
						<?php esc_html_e( 'Select Media', 'my-booking-engine' ); ?>
					</button>
					<button type="button" class="button button-link-delete" id="mb_btn_clear_gallery" style="margin-left:8px;"><?php esc_html_e( 'Clear', 'my-booking-engine' ); ?></button>
					<div id="mb_gallery_preview" style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
						<?php
						if ( ! empty( $gallery_images ) ) :
							$img_ids = explode( ',', $gallery_images );
							foreach ( $img_ids as $gid ) :
								$gid = absint( trim( $gid ) );
								$src = wp_get_attachment_image_url( $gid, 'thumbnail' );
								if ( $src ) :
									?>
									<div class="mb-gallery-thumb-item" style="width:72px; height:72px; border-radius:6px; overflow:hidden; border:1px solid #cbd5e1;">
										<img src="<?php echo esc_url( $src ); ?>" style="width:100%; height:100%; object-fit:cover;">
									</div>
									<?php
								endif;
							endforeach;
						endif;
						?>
					</div>
					<p class="description"><?php esc_html_e( 'Comma-separated Media Attachment IDs for the mosaic gallery or photo slider.', 'my-booking-engine' ); ?></p>
				</td>
			</tr>

			<tr>
				<th><label for="mb_amenities"><?php esc_html_e( 'Amenities & Features', 'my-booking-engine' ); ?></label></th>
				<td>
					<textarea name="mb_amenities" id="mb_amenities" rows="5" class="large-text" placeholder="<?php esc_attr_e( "High-Speed Wi-Fi\nFree Parking\nAir Conditioning\nDedicated Support\nWheelchair Accessible", 'my-booking-engine' ); ?>"><?php echo esc_textarea( $amenities ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter one amenity or feature per line. Displayed as verified checkmark badges on the listing page.', 'my-booking-engine' ); ?></p>
				</td>
			</tr>

			<tr>
				<th><label for="mb_policy"><?php esc_html_e( 'Cancellation & House Policy', 'my-booking-engine' ); ?></label></th>
				<td>
					<textarea name="mb_policy" id="mb_policy" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'Full refund up to 24 hours before check-in/start time. No smoking, no pets.', 'my-booking-engine' ); ?>"><?php echo esc_textarea( $policy ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Displayed in the Policy & Rules card on the single listing page.', 'my-booking-engine' ); ?></p>
				</td>
			</tr>
		</table>
	</div>
</div>
