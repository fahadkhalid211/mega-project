<?php
/**
 * Admin Page Template: "Add New Listing" / "Edit Listing" app builder.
 *
 * A dedicated, fully custom-styled page (no WordPress postbox/metabox
 * chrome) similar in spirit to Amelia / LatePoint's guided setup screens.
 * Picking a listing type instantly applies smart defaults for pricing,
 * duration, and hours; everything else stays available to fine-tune under
 * "Advanced Settings" for admins who want more control.
 *
 * Shared between Add New Listing and Edit Listing: when $edit_entity_id
 * is set (by AdminMenu::render_edit_listing_page()) to an existing post
 * ID, every field below is prefilled from that listing's saved data and
 * the form submits as an update instead of a create.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_entity_id = isset( $edit_entity_id ) ? absint( $edit_entity_id ) : 0;
$is_edit        = $edit_entity_id > 0 && 'mb_booking_entity' === get_post_type( $edit_entity_id );
$entity         = $is_edit ? new \MyBookingEngine\Models\BookingEntity( $edit_entity_id ) : null;

$days_of_week = array(
	1 => array( 'name' => __( 'Monday', 'my-booking-engine' ), 'short' => 'Mon' ),
	2 => array( 'name' => __( 'Tuesday', 'my-booking-engine' ), 'short' => 'Tue' ),
	3 => array( 'name' => __( 'Wednesday', 'my-booking-engine' ), 'short' => 'Wed' ),
	4 => array( 'name' => __( 'Thursday', 'my-booking-engine' ), 'short' => 'Thu' ),
	5 => array( 'name' => __( 'Friday', 'my-booking-engine' ), 'short' => 'Fri' ),
	6 => array( 'name' => __( 'Saturday', 'my-booking-engine' ), 'short' => 'Sat' ),
	0 => array( 'name' => __( 'Sunday', 'my-booking-engine' ), 'short' => 'Sun' ),
);

$listing_types = array(
	'hourly' => array(
		'icon'  => '⏱️',
		'label' => __( 'Hourly Studio & Activity', 'my-booking-engine' ),
		'desc'  => __( 'Studios, activities, quick sessions.', 'my-booking-engine' ),
		'model' => 'hourly_slot',
		'price' => 40,
		'slot'  => 60,
		'before' => 0,
		'after' => 15,
		'schedule' => 'all_week',
	),
	'hotel' => array(
		'icon'  => '🏨',
		'label' => __( 'Hotel & Vacation Stay', 'my-booking-engine' ),
		'desc'  => __( 'Rooms, villas, apartments.', 'my-booking-engine' ),
		'model' => 'night_stay',
		'price' => 150,
		'min'   => 1,
		'max'   => 30,
		'checkin'  => '15:00',
		'checkout' => '11:00',
		'schedule' => 'all_week',
	),
	'rental' => array(
		'icon'  => '🚗',
		'label' => __( 'Car & Equipment Rental', 'my-booking-engine' ),
		'desc'  => __( 'Vehicles, gear, machinery.', 'my-booking-engine' ),
		'model' => 'day_rental',
		'price' => 89,
		'min'   => 1,
		'max'   => 14,
		'checkin'  => '09:00',
		'checkout' => '17:00',
		'schedule' => 'all_week',
	),
	'doctor' => array(
		'icon'  => '🩺',
		'label' => __( 'Doctor & Specialist', 'my-booking-engine' ),
		'desc'  => __( 'Clinics, consultations.', 'my-booking-engine' ),
		'model' => 'hourly_slot',
		'price' => 120,
		'slot'  => 30,
		'before' => 5,
		'after' => 10,
		'schedule' => 'weekdays',
	),
	'salon' => array(
		'icon'  => '✂️',
		'label' => __( 'Salon & Spa', 'my-booking-engine' ),
		'desc'  => __( 'Stylists, treatments.', 'my-booking-engine' ),
		'model' => 'hourly_slot',
		'price' => 55,
		'slot'  => 45,
		'before' => 0,
		'after' => 15,
		'schedule' => 'six_day',
	),
	'shop' => array(
		'icon'  => '🏪',
		'label' => __( 'Shop & Local Business', 'my-booking-engine' ),
		'desc'  => __( 'Storefronts, local venues.', 'my-booking-engine' ),
		'model' => 'hourly_slot',
		'price' => 25,
		'slot'  => 30,
		'before' => 0,
		'after' => 5,
		'schedule' => 'six_day',
	),
);

$default_type = $is_edit ? $entity->get_visual_layout() : 'hourly';
if ( ! isset( $listing_types[ $default_type ] ) ) {
	$default_type = 'hourly';
}

// Prefill values — blank/default for Add New, existing data for Edit.
$title_val       = $is_edit ? $entity->get_title() : '';
$description_val = $is_edit ? get_post_field( 'post_content', $edit_entity_id ) : '';
$price_val       = $is_edit ? $entity->get_base_price() : $listing_types[ $default_type ]['price'];
$weekend_val     = $is_edit ? $entity->get_weekend_price() : '';
$model_val       = $is_edit ? $entity->get_model_type() : $listing_types[ $default_type ]['model'];
$slot_val        = $is_edit ? $entity->get_slot_duration() : 60;
$before_val      = $is_edit ? $entity->get_buffer_before() : 0;
$after_val       = $is_edit ? $entity->get_buffer_after() : 15;
$min_val         = $is_edit ? $entity->get_min_duration() : 1;
$max_val         = $is_edit ? $entity->get_max_duration() : 30;
$checkin_val     = $is_edit ? $entity->get_checkin_time() : '15:00';
$checkout_val    = $is_edit ? $entity->get_checkout_time() : '11:00';
$capacity_val    = $is_edit ? $entity->get_capacity() : 1;
$event_start_val = $is_edit ? $entity->get_event_start() : '';
$event_end_val   = $is_edit ? $entity->get_event_end() : '';

$location_val    = $is_edit ? $entity->get_location() : false;
$postal_val      = $location_val ? $location_val->postal_code : '';
$city_val        = $location_val ? $location_val->city : '';
$country_val     = $location_val ? $location_val->country_code : '';
$lat_val         = $location_val ? $location_val->latitude : '';
$lng_val         = $location_val ? $location_val->longitude : '';

$gallery_ids_val = $is_edit ? get_post_meta( $edit_entity_id, '_mb_gallery_images', true ) : '';
$gallery_ids_val = is_array( $gallery_ids_val ) ? implode( ',', $gallery_ids_val ) : (string) $gallery_ids_val;

$amenities_val = $is_edit ? get_post_meta( $edit_entity_id, '_mb_amenities', true ) : '';
if ( is_array( $amenities_val ) ) {
	$amenities_val = implode( "\n", $amenities_val );
}
$policy_val = $is_edit ? get_post_meta( $edit_entity_id, '_mb_policy', true ) : '';

$services_val = array();
if ( $is_edit ) {
	$raw_services = get_post_meta( $edit_entity_id, '_mb_services', true );
	if ( is_array( $raw_services ) ) {
		$services_val = $raw_services;
	}
}

// Weekly schedule prefill: turn saved availability rows into a per-day
// lookup so the day toggles/times below reflect what's actually saved.
$schedule_lookup = array();
if ( $is_edit ) {
	foreach ( $entity->get_availabilities() as $rule ) {
		if ( isset( $rule->day_of_week ) && '' !== $rule->day_of_week && null !== $rule->day_of_week ) {
			$schedule_lookup[ (int) $rule->day_of_week ] = array(
				'start' => substr( $rule->start_time, 0, 5 ),
				'end'   => substr( $rule->end_time, 0, 5 ),
			);
		}
	}
}
?>
<div class="mb-app-shell" id="mb-app-add-listing" data-mode="<?php echo $is_edit ? 'edit' : 'create'; ?>">

	<div class="mb-app-header">
		<div class="mb-app-header-inner">
			<div class="mb-app-brand">
				<span class="mb-app-brand-icon"><?php echo $is_edit ? '✏️' : '🗓️'; ?></span>
				<div>
					<strong><?php echo $is_edit ? esc_html__( 'Edit Listing', 'my-booking-engine' ) : esc_html__( 'Add New Listing', 'my-booking-engine' ); ?></strong>
					<span><?php echo $is_edit
						? esc_html__( 'Update this listing\'s details, pricing, and availability', 'my-booking-engine' )
						: esc_html__( 'Set up a new bookable listing in a couple of minutes', 'my-booking-engine' ); ?></span>
				</div>
			</div>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mb_booking_entity' ) ); ?>" class="mb-app-close" title="<?php esc_attr_e( 'Back to Listings', 'my-booking-engine' ); ?>">&times;</a>
		</div>
	</div>

	<form id="mb-app-form" class="mb-app-body" method="post">
		<?php wp_nonce_field( 'mb_save_entity_meta', 'mb_entity_meta_nonce' ); ?>
		<input type="hidden" name="mb_post_id" id="mb_post_id" value="<?php echo esc_attr( $edit_entity_id ); ?>">

		<div id="mb-app-alert" class="mb-app-alert" style="display:none;"></div>

		<!-- Type Picker -->
		<div class="mb-app-card mb-app-card-types" id="mb-app-type-picker">
			<h2 class="mb-app-section-title"><?php esc_html_e( 'What are you listing?', 'my-booking-engine' ); ?></h2>
			<p class="mb-app-section-desc"><?php esc_html_e( "Pick the closest match. We'll set smart defaults for pricing, duration, and hours automatically — you can fine-tune anything afterward.", 'my-booking-engine' ); ?></p>

			<div class="mb-layout-cards-grid">
				<?php foreach ( $listing_types as $key => $type ) : ?>
					<label class="mb-layout-card mb-app-type-card <?php echo ( $default_type === $key ) ? 'selected' : ''; ?>"
						data-type="<?php echo esc_attr( $key ); ?>"
						data-label="<?php echo esc_attr( $type['label'] ); ?>"
						data-model="<?php echo esc_attr( $type['model'] ); ?>"
						data-price="<?php echo esc_attr( $type['price'] ); ?>"
						data-slot="<?php echo esc_attr( $type['slot'] ?? '' ); ?>"
						data-before="<?php echo esc_attr( $type['before'] ?? '' ); ?>"
						data-after="<?php echo esc_attr( $type['after'] ?? '' ); ?>"
						data-min="<?php echo esc_attr( $type['min'] ?? '' ); ?>"
						data-max="<?php echo esc_attr( $type['max'] ?? '' ); ?>"
						data-checkin="<?php echo esc_attr( $type['checkin'] ?? '' ); ?>"
						data-checkout="<?php echo esc_attr( $type['checkout'] ?? '' ); ?>"
						data-schedule="<?php echo esc_attr( $type['schedule'] ); ?>">
						<input type="radio" name="mb_visual_layout" value="<?php echo esc_attr( $key ); ?>" <?php checked( $default_type, $key ); ?>>
						<div class="mb-layout-card-inner">
							<div class="mb-layout-icon"><?php echo esc_html( $type['icon'] ); ?></div>
							<div class="mb-layout-card-title"><?php echo esc_html( $type['label'] ); ?></div>
							<p class="mb-layout-card-desc"><?php echo esc_html( $type['desc'] ); ?></p>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Smart defaults banner -->
		<div class="mb-app-smart-banner" id="mb-app-smart-banner">
			<span class="mb-app-smart-icon">✨</span>
			<div>
				<strong><?php esc_html_e( "We've pre-filled the best settings for", 'my-booking-engine' ); ?> <span id="mb-app-smart-type-label"><?php echo esc_html( $listing_types[ $default_type ]['label'] ); ?></span>.</strong>
				<p><?php esc_html_e( 'Just add a title and price below — open Advanced Settings only if you want to change anything.', 'my-booking-engine' ); ?></p>
				<p class="mb-app-defaults-summary" id="mb-app-defaults-summary"></p>
			</div>
		</div>

		<!-- Basics -->
		<div class="mb-app-card">
			<h2 class="mb-app-section-title"><?php esc_html_e( 'The Basics', 'my-booking-engine' ); ?></h2>
			<div class="mb-app-grid-2">
				<div class="mb-w-field">
					<label for="mb_quick_title"><?php esc_html_e( 'Listing Title', 'my-booking-engine' ); ?> <span class="mb-req">*</span></label>
					<input type="text" id="mb_quick_title" name="mb_quick_title" class="widefat" required placeholder="<?php esc_attr_e( 'e.g. Downtown Hair Studio', 'my-booking-engine' ); ?>" value="<?php echo esc_attr( $title_val ); ?>">
				</div>
				<div class="mb-w-field">
					<label for="mb_base_price"><?php esc_html_e( 'Base Price', 'my-booking-engine' ); ?> <span class="mb-req">*</span></label>
					<input type="number" id="mb_base_price" name="mb_base_price" class="widefat" step="0.01" min="0" required value="<?php echo esc_attr( $price_val ); ?>" <?php echo $is_edit ? 'data-user-edited="true"' : ''; ?>>
				</div>
			</div>
			<div class="mb-w-field" style="margin-top:16px;">
				<label for="mb_description"><?php esc_html_e( 'Description', 'my-booking-engine' ); ?></label>
				<p class="description" style="margin:0 0 6px;"><?php esc_html_e( 'A short overview shown on the listing page — what it is, who it\'s for, what makes it worth booking.', 'my-booking-engine' ); ?></p>
				<textarea name="mb_description" id="mb_description" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'Tell customers what to expect…', 'my-booking-engine' ); ?>"><?php echo esc_textarea( $description_val ); ?></textarea>
			</div>
			<div class="mb-app-grid-3" style="margin-top:16px;">
				<div class="mb-w-field">
					<label for="mb_postal_code"><?php esc_html_e( 'Postal / ZIP Code', 'my-booking-engine' ); ?></label>
					<input type="text" id="mb_postal_code" name="mb_postal_code" class="widefat" placeholder="90210" value="<?php echo esc_attr( $postal_val ); ?>">
				</div>
				<div class="mb-w-field">
					<label for="mb_city"><?php esc_html_e( 'City', 'my-booking-engine' ); ?></label>
					<input type="text" id="mb_city" name="mb_city" class="widefat" placeholder="Los Angeles" value="<?php echo esc_attr( $city_val ); ?>">
				</div>
				<div class="mb-w-field">
					<label for="mb_country_code"><?php esc_html_e( 'Country Code', 'my-booking-engine' ); ?></label>
					<input type="text" id="mb_country_code" name="mb_country_code" class="widefat" placeholder="US" value="<?php echo esc_attr( $country_val ); ?>">
				</div>
			</div>
			<div class="mb-app-geocode-row">
				<button type="button" class="button button-primary mb-btn-geocode" id="mb_btn_geocode">
					<span class="dashicons dashicons-location" style="vertical-align:middle; margin-top:-2px;"></span>
					<?php esc_html_e( 'Auto-Detect Coordinates', 'my-booking-engine' ); ?>
				</button>
				<span class="spinner" id="mb_geocode_spinner"></span>
				<span id="mb_geocode_status"></span>
				<input type="hidden" name="mb_latitude" id="mb_latitude" value="<?php echo esc_attr( $lat_val ); ?>">
				<input type="hidden" name="mb_longitude" id="mb_longitude" value="<?php echo esc_attr( $lng_val ); ?>">
			</div>
		</div>

		<!-- Photo Gallery — moved out of Advanced Settings: photos are one
		     of the first things a listing needs, not an edge-case setting. -->
		<div class="mb-app-card">
			<h3>📸 <?php esc_html_e( 'Photo Gallery', 'my-booking-engine' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Listings with real photos get booked far more often than ones without — add a few before publishing.', 'my-booking-engine' ); ?></p>
			<input type="hidden" name="mb_gallery_images" id="mb_gallery_images" value="<?php echo esc_attr( $gallery_ids_val ); ?>">
			<button type="button" class="button button-primary" id="mb_btn_select_gallery">
				<span class="dashicons dashicons-images-alt2" style="vertical-align:middle; margin-top:-2px;"></span>
				<?php esc_html_e( 'Select Photos from Media Library', 'my-booking-engine' ); ?>
			</button>
			<button type="button" class="button button-link-delete" id="mb_btn_clear_gallery" style="margin-left:10px;">
				<?php esc_html_e( 'Remove All Photos', 'my-booking-engine' ); ?>
			</button>
			<div id="mb_gallery_preview" class="mb-gallery-preview-grid" style="margin-top:16px;" data-existing='<?php echo esc_attr( wp_json_encode( $entity ? $entity->get_gallery_images( 'thumbnail' ) : array() ) ); ?>'></div>
		</div>

		<!-- Services & Add-ons — only relevant for menu-driven listings
		     (salon, spa) where customers pick from specific services. -->
		<div class="mb-app-card" id="mb-app-services-card" style="display:none;">
			<h3>💇 <?php esc_html_e( 'Services & Add-ons', 'my-booking-engine' ); ?></h3>
			<p class="description"><?php esc_html_e( 'List the individual services customers can book — a haircut, a color treatment, an add-on. Each can have its own duration and price.', 'my-booking-engine' ); ?></p>
			<input type="hidden" name="mb_services" id="mb_services_json" value="<?php echo esc_attr( wp_json_encode( $services_val ) ); ?>">
			<div id="mb-services-rows" class="mb-services-rows"></div>
			<button type="button" class="button" id="mb_btn_add_service">
				<span class="dashicons dashicons-plus-alt2" style="vertical-align:middle; margin-top:-2px;"></span>
				<?php esc_html_e( 'Add a Service', 'my-booking-engine' ); ?>
			</button>
		</div>

		<!-- Advanced toggle -->
		<button type="button" class="mb-app-advanced-toggle" id="mb-app-advanced-toggle" aria-expanded="false">
			<span>⚙️ <?php esc_html_e( 'Customize Advanced Settings', 'my-booking-engine' ); ?></span>
			<span class="mb-app-toggle-caret">⌄</span>
		</button>

		<div class="mb-app-advanced-panel" id="mb-app-advanced-panel">

			<div class="mb-app-card">
				<h3><?php esc_html_e( 'Booking Engine Algorithm & Rules', 'my-booking-engine' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Already set automatically based on the type you picked above.', 'my-booking-engine' ); ?>
					<a href="#mb-app-type-picker" id="mb-app-change-type-link"><?php esc_html_e( 'Change listing type/design ↑', 'my-booking-engine' ); ?></a>
				</p>

				<select name="mb_model_type" id="mb_model_type" class="widefat mb-model-switcher" style="margin-top:10px; padding:8px 12px; font-weight:600;">
					<option value="hourly_slot" <?php selected( $model_val, 'hourly_slot' ); ?>><?php esc_html_e( 'Hourly / Slot Appointments', 'my-booking-engine' ); ?></option>
					<option value="day_rental" <?php selected( $model_val, 'day_rental' ); ?>><?php esc_html_e( 'Day-based Rentals', 'my-booking-engine' ); ?></option>
					<option value="night_stay" <?php selected( $model_val, 'night_stay' ); ?>><?php esc_html_e( 'Night-based Stays', 'my-booking-engine' ); ?></option>
					<option value="capacity_roster" <?php selected( $model_val, 'capacity_roster' ); ?>><?php esc_html_e( 'Capacity Roster / Tickets', 'my-booking-engine' ); ?></option>
				</select>

				<div class="mb-field-row mb-field-hourly_slot" style="margin-top:18px;">
					<p class="description" style="margin-top:0;"><?php esc_html_e( 'How long each appointment slot lasts, and any prep/cleanup buffer around it.', 'my-booking-engine' ); ?></p>
					<div class="mb-wizard-grid-3">
						<div class="mb-w-field">
							<label for="mb_slot_duration"><?php esc_html_e( 'Slot Duration (Minutes)', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_slot_duration" id="mb_slot_duration" value="<?php echo esc_attr( $slot_val ); ?>" min="5" step="5" class="widefat">
						</div>
						<div class="mb-w-field">
							<label for="mb_buffer_before"><?php esc_html_e( 'Buffer Before (Mins)', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_buffer_before" id="mb_buffer_before" value="<?php echo esc_attr( $before_val ); ?>" min="0" step="5" class="widefat">
						</div>
						<div class="mb-w-field">
							<label for="mb_buffer_after"><?php esc_html_e( 'Buffer After (Mins)', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_buffer_after" id="mb_buffer_after" value="<?php echo esc_attr( $after_val ); ?>" min="0" step="5" class="widefat">
						</div>
					</div>
				</div>

				<div class="mb-field-row mb-field-day_rental mb-field-night_stay" style="display:none; margin-top:18px;">
					<p class="description" style="margin-top:0;"><?php esc_html_e( 'How many days/nights a booking must span, and the standard check-in/check-out times.', 'my-booking-engine' ); ?></p>
					<div class="mb-wizard-grid-2">
						<div class="mb-w-field">
							<label for="mb_min_duration"><?php esc_html_e( 'Minimum Stay / Rental', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_min_duration" id="mb_min_duration" value="<?php echo esc_attr( $min_val ); ?>" min="1" class="widefat">
						</div>
						<div class="mb-w-field">
							<label for="mb_max_duration"><?php esc_html_e( 'Maximum Stay / Rental', 'my-booking-engine' ); ?></label>
							<input type="number" name="mb_max_duration" id="mb_max_duration" value="<?php echo esc_attr( $max_val ); ?>" min="1" class="widefat">
						</div>
					</div>
					<div class="mb-wizard-grid-2" style="margin-top:14px;">
						<div class="mb-w-field">
							<label for="mb_checkin_time"><?php esc_html_e( 'Check-in Time', 'my-booking-engine' ); ?></label>
							<input type="time" name="mb_checkin_time" id="mb_checkin_time" value="<?php echo esc_attr( $checkin_val ); ?>" class="widefat">
						</div>
						<div class="mb-w-field">
							<label for="mb_checkout_time"><?php esc_html_e( 'Check-out Time', 'my-booking-engine' ); ?></label>
							<input type="time" name="mb_checkout_time" id="mb_checkout_time" value="<?php echo esc_attr( $checkout_val ); ?>" class="widefat">
						</div>
					</div>
				</div>

				<div class="mb-field-row mb-field-capacity_roster" style="display:none; margin-top:18px;">
					<p class="description" style="margin-top:0;"><?php esc_html_e( 'For a fixed-schedule event with one start and end time, rather than a range of bookable dates.', 'my-booking-engine' ); ?></p>
					<div class="mb-wizard-grid-2">
						<div class="mb-w-field">
							<label for="mb_event_start"><?php esc_html_e( 'Event Start Time', 'my-booking-engine' ); ?></label>
							<input type="datetime-local" name="mb_event_start" id="mb_event_start" class="widefat" value="<?php echo esc_attr( $event_start_val ); ?>">
						</div>
						<div class="mb-w-field">
							<label for="mb_event_end"><?php esc_html_e( 'Event End Time', 'my-booking-engine' ); ?></label>
							<input type="datetime-local" name="mb_event_end" id="mb_event_end" class="widefat" value="<?php echo esc_attr( $event_end_val ); ?>">
						</div>
					</div>
				</div>

				<div class="mb-app-grid-2" style="margin-top:18px;">
					<div class="mb-w-field">
						<label for="mb_weekend_price"><?php esc_html_e( 'Weekend Price (Optional)', 'my-booking-engine' ); ?></label>
						<input type="number" name="mb_weekend_price" id="mb_weekend_price" class="widefat" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Leave blank to use base rate', 'my-booking-engine' ); ?>" value="<?php echo esc_attr( $weekend_val ); ?>">
					</div>
					<div class="mb-w-field">
						<label for="mb_capacity"><?php esc_html_e( 'Maximum Capacity / Spots', 'my-booking-engine' ); ?></label>
						<input type="number" name="mb_capacity" id="mb_capacity" value="<?php echo esc_attr( $capacity_val ); ?>" min="1" class="widefat">
					</div>
				</div>
			</div>

			<div class="mb-app-card">
				<h3><?php esc_html_e( 'Weekly Operating Hours', 'my-booking-engine' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Which days you\'re open for bookings, and your hours on each one. Toggle a day off to block it entirely.', 'my-booking-engine' ); ?></p>
				<div class="mb-schedule-list">
					<?php foreach ( $days_of_week as $day_idx => $day_info ) :
						$is_open    = $is_edit ? isset( $schedule_lookup[ $day_idx ] ) : ( $day_idx >= 1 && $day_idx <= 5 );
						$start_time = isset( $schedule_lookup[ $day_idx ]['start'] ) ? $schedule_lookup[ $day_idx ]['start'] : '09:00';
						$end_time   = isset( $schedule_lookup[ $day_idx ]['end'] ) ? $schedule_lookup[ $day_idx ]['end'] : '17:00';
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
								<input type="time" name="mb_schedule[<?php echo esc_attr( $day_idx ); ?>][start]" value="<?php echo esc_attr( $start_time ); ?>" class="mb-time-picker">
								<span style="color:#94a3b8;">→</span>
								<input type="time" name="mb_schedule[<?php echo esc_attr( $day_idx ); ?>][end]" value="<?php echo esc_attr( $end_time ); ?>" class="mb-time-picker">
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="mb-app-card">
				<h3><?php esc_html_e( 'More Details', 'my-booking-engine' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Optional extras shown further down the listing page — a bullet list of what\'s included, and your cancellation terms.', 'my-booking-engine' ); ?></p>
				<div class="mb-app-grid-2" style="margin-top:12px;">
					<div class="mb-w-field">
						<label for="mb_amenities"><?php esc_html_e( 'Amenities (one per line)', 'my-booking-engine' ); ?></label>
						<textarea name="mb_amenities" id="mb_amenities" rows="4" class="widefat"><?php echo esc_textarea( $amenities_val ); ?></textarea>
					</div>
					<div class="mb-w-field">
						<label for="mb_policy"><?php esc_html_e( 'Cancellation & House Rules', 'my-booking-engine' ); ?></label>
						<textarea name="mb_policy" id="mb_policy" rows="4" class="widefat"><?php echo esc_textarea( $policy_val ); ?></textarea>
					</div>
				</div>
			</div>
		</div>

		<div class="mb-app-sticky-footer">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mb_booking_entity' ) ); ?>" class="button button-large"><?php esc_html_e( 'Cancel', 'my-booking-engine' ); ?></a>
			<button type="submit" class="button button-primary button-hero mb-app-publish-btn" id="mb-app-publish-btn">
				<?php echo $is_edit ? '💾 ' . esc_html__( 'Update Listing', 'my-booking-engine' ) : '🚀 ' . esc_html__( 'Publish Listing', 'my-booking-engine' ); ?>
			</button>
		</div>
	</form>
</div>
