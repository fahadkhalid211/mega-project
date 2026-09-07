<?php
/**
 * Admin Page Template: "Add New Listing" / "Edit Listing" app builder.
 *
 * A dedicated, fully custom-styled page (no WordPress postbox/metabox
 * chrome) similar in spirit to Amelia / LatePoint's guided setup screens.
 * Features instant smart per-type defaults, live layout preview mockups
 * on hover, soft modern aesthetic, and all settings fully visible.
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
		'icon'     => '⏱️',
		'label'    => __( 'Hourly Studio & Activity', 'my-booking-engine' ),
		'desc'     => __( 'Studio hero & slots. Great for studios, rooms, sports courts.', 'my-booking-engine' ),
		'model'    => 'hourly_slot',
		'price'    => 40,
		'slot'     => 60,
		'before'   => 0,
		'after'    => 15,
		'schedule' => 'all_week',
	),
	'hotel' => array(
		'icon'     => '🏨',
		'label'    => __( 'Hotel & Vacation Stay', 'my-booking-engine' ),
		'desc'     => __( 'Mosaic photo gallery. Great for villas, hotels, venues, cars.', 'my-booking-engine' ),
		'model'    => 'night_stay',
		'price'    => 150,
		'min'      => 1,
		'max'      => 30,
		'checkin'  => '15:00',
		'checkout' => '11:00',
		'capacity' => 4,
		'schedule' => 'all_week',
	),
	'rental' => array(
		'icon'     => '🚗',
		'label'    => __( 'Car & Equipment Rental', 'my-booking-engine' ),
		'desc'     => __( 'Showcase photo slider. Great for vehicles, boats, equipment.', 'my-booking-engine' ),
		'model'    => 'day_rental',
		'price'    => 89,
		'min'      => 1,
		'max'      => 14,
		'checkin'  => '09:00',
		'checkout' => '17:00',
		'capacity' => 5,
		'schedule' => 'all_week',
	),
	'doctor' => array(
		'icon'     => '🩺',
		'label'    => __( 'Doctor & Specialist', 'my-booking-engine' ),
		'desc'     => __( 'Verified profile & timeslots. Great for doctors, consultants.', 'my-booking-engine' ),
		'model'    => 'hourly_slot',
		'price'    => 120,
		'slot'     => 30,
		'before'   => 5,
		'after'    => 10,
		'schedule' => 'weekdays',
	),
	'salon' => array(
		'icon'     => '✂️',
		'label'    => __( 'Salon & Spa', 'my-booking-engine' ),
		'desc'     => __( 'Treatments & services menu. Great for salons, spas, stylists.', 'my-booking-engine' ),
		'model'    => 'hourly_slot',
		'price'    => 55,
		'slot'     => 45,
		'before'   => 0,
		'after'    => 15,
		'schedule' => 'six_day',
	),
	'shop' => array(
		'icon'     => '🏪',
		'label'    => __( 'Shop & Local Business', 'my-booking-engine' ),
		'desc'     => __( 'Storefront & timetable. Great for shops, restaurants, retail.', 'my-booking-engine' ),
		'model'    => 'hourly_slot',
		'price'    => 25,
		'slot'     => 30,
		'before'   => 0,
		'after'    => 5,
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

// Build rich gallery array with ID and thumbnail URL for the media preview
$existing_gallery_items = array();
if ( ! empty( $gallery_ids_val ) ) {
	$raw_gids = explode( ',', $gallery_ids_val );
	foreach ( $raw_gids as $gid ) {
		$gid = absint( trim( $gid ) );
		if ( $gid > 0 ) {
			$thumb_url = wp_get_attachment_image_url( $gid, 'thumbnail' );
			if ( $thumb_url ) {
				$existing_gallery_items[] = array(
					'id'  => $gid,
					'url' => $thumb_url,
				);
			}
		}
	}
}

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

// Weekly schedule prefill
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

// Appearance & Color Customizer — read current global settings for prefill
$mb_appearance    = get_option( 'mb_engine_settings', array() );
$mb_primary_color = ! empty( $mb_appearance['primary_color'] ) ? $mb_appearance['primary_color'] : '#2563eb';
$mb_primary_hover = ! empty( $mb_appearance['primary_hover'] ) ? $mb_appearance['primary_hover'] : '#1d4ed8';
$mb_accent_color  = ! empty( $mb_appearance['accent_color'] ) ? $mb_appearance['accent_color'] : '#f59e0b';
$mb_border_radius = isset( $mb_appearance['border_radius'] ) ? absint( $mb_appearance['border_radius'] ) : 8;
?>
<div class="mb-app-shell" id="mb-app-add-listing" data-mode="<?php echo $is_edit ? 'edit' : 'create'; ?>">

	<div class="mb-app-header">
		<div class="mb-app-header-inner">
			<div class="mb-app-brand">
				<span class="mb-app-brand-icon"><?php echo $is_edit ? '✏️' : '✨'; ?></span>
				<div>
					<strong><?php echo $is_edit ? esc_html__( 'Edit Listing', 'my-booking-engine' ) : esc_html__( 'Add New Listing', 'my-booking-engine' ); ?></strong>
					<span><?php echo $is_edit
						? esc_html__( 'Fine-tune listing details, pricing, layout, and operating hours', 'my-booking-engine' )
						: esc_html__( 'Create and customize a bookable listing with instant layout preview', 'my-booking-engine' ); ?></span>
				</div>
			</div>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mb_booking_entity' ) ); ?>" class="mb-app-close" title="<?php esc_attr_e( 'Back to Listings', 'my-booking-engine' ); ?>">&times;</a>
		</div>
	</div>

	<form id="mb-app-form" class="mb-app-body" method="post">
		<?php wp_nonce_field( 'mb_save_entity_meta', 'mb_entity_meta_nonce' ); ?>
		<input type="hidden" name="mb_post_id" id="mb_post_id" value="<?php echo esc_attr( $edit_entity_id ); ?>">

		<div id="mb-app-alert" class="mb-app-alert" style="display:none;"></div>

		<!-- 1. Layout & Design Picker with Notification Marks & Hover Mockups -->
		<div class="mb-app-card mb-app-card-types" id="mb-app-type-picker">
			<div class="mb-app-card-header">
				<div class="mb-app-card-badge-icon" style="background:#eff6ff; color:#2563eb;">🎨</div>
				<div>
					<h2 class="mb-app-section-title"><?php esc_html_e( 'Choose Layout & Visual Design', 'my-booking-engine' ); ?></h2>
					<p class="mb-app-section-desc"><?php esc_html_e( 'Select the presentation style your visitors will see. Hover over any card or its Preview badge to inspect the exact live layout mockup.', 'my-booking-engine' ); ?></p>
				</div>
			</div>

			<!-- Mix & Match Flexibility Callout Banner -->
			<div class="mb-flexibility-banner">
				<div class="mb-flex-icon">✨</div>
				<div class="mb-flex-text">
					<strong><?php esc_html_e( '100% Flexible — Mix & Match Any Layout with Any Booking Engine!', 'my-booking-engine' ); ?></strong>
					<p><?php esc_html_e( 'You have total freedom to pick ANY layout for ANY business. For example, use the Hotel mosaic gallery for a luxury car rental, or the Car showcase slider for a photography studio. You can independently customize the booking engine model in Section 5 below.', 'my-booking-engine' ); ?></p>
				</div>
			</div>

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
						data-capacity="<?php echo esc_attr( $type['capacity'] ?? '' ); ?>"
						data-schedule="<?php echo esc_attr( $type['schedule'] ); ?>">
						
						<input type="radio" name="mb_visual_layout" value="<?php echo esc_attr( $key ); ?>" <?php checked( $default_type, $key ); ?>>

						<!-- Notification mark / Preview Indicator -->
						<div class="mb-type-notification-mark" title="<?php esc_attr_e( 'Hover to view layout mockup', 'my-booking-engine' ); ?>">
							<span class="mb-notif-ping"></span>
							<span class="mb-notif-dot"></span>
							<span class="mb-notif-label"><?php esc_html_e( 'Preview', 'my-booking-engine' ); ?></span>
						</div>

						<div class="mb-layout-card-inner">
							<div class="mb-layout-icon"><?php echo esc_html( $type['icon'] ); ?></div>
							<div class="mb-layout-card-title"><?php echo esc_html( $type['label'] ); ?></div>
							<p class="mb-layout-card-desc"><?php echo esc_html( $type['desc'] ); ?></p>
						</div>

						<!-- Layout Mockup Popover on Hover -->
						<div class="mb-layout-mockup-popover">
							<div class="mb-mockup-window">
								<div class="mb-mockup-topbar">
									<span class="mb-mockup-dots"><i class="dot-r"></i><i class="dot-y"></i><i class="dot-g"></i></span>
									<span class="mb-mockup-url">yourdomain.com/listings/<?php echo esc_attr( $key ); ?></span>
									<span class="mb-mockup-badge"><?php echo esc_html( $type['icon'] . ' ' . ucfirst( $key ) ); ?></span>
								</div>

								<div class="mb-mockup-canvas">
									<?php if ( 'hourly' === $key ) : ?>
										<!-- Hourly Studio Mockup -->
										<div class="mb-mockup-hero mb-mockup-hero-studio">
											<div class="mb-mockup-hero-overlay">
												<div class="mb-mockup-title">Downtown Creative Dance Studio</div>
												<div class="mb-mockup-meta">⭐ 4.9 (48) · 📍 Arts District</div>
											</div>
											<div class="mb-mockup-spec-pill">⏱️ 60 Min Session · ⚡ Instant</div>
										</div>
										<div class="mb-mockup-split">
											<div class="mb-mockup-main">
												<div class="mb-mockup-p-line"></div>
												<div class="mb-mockup-p-line short"></div>
												<div class="mb-mockup-tags">
													<span class="mb-tag">🔊 Sound System</span>
													<span class="mb-tag">🪞 Wall Mirrors</span>
												</div>
											</div>
											<div class="mb-mockup-sidebar">
												<div class="mb-mockup-sidecard">
													<div class="mb-mockup-side-price"><strong>$40.00</strong> <span>/ hour</span></div>
													<div class="mb-mockup-slots-label">Time Slots:</div>
													<div class="mb-mockup-slots-grid">
														<span class="mb-slot-chip active">09:00 AM</span>
														<span class="mb-slot-chip">10:30 AM</span>
														<span class="mb-slot-chip">01:00 PM</span>
													</div>
													<div class="mb-mockup-cta-btn">Book Studio</div>
												</div>
											</div>
										</div>

									<?php elseif ( 'hotel' === $key ) : ?>
										<!-- Hotel & Vacation Stay Mockup (5-Photo Mosaic) -->
										<div class="mb-mockup-header-sm">
											<div class="mb-mockup-title">Grand Oceanview Luxury Villa</div>
											<div class="mb-mockup-meta">⭐ 4.96 (180 reviews) · 🏅 Superhost</div>
										</div>
										<div class="mb-mockup-mosaic-gallery">
											<div class="mb-mosaic-main"></div>
											<div class="mb-mosaic-sub">
												<div class="mb-mosaic-thumb"></div>
												<div class="mb-mosaic-thumb"></div>
												<div class="mb-mosaic-thumb"></div>
												<div class="mb-mosaic-thumb"></div>
											</div>
										</div>
										<div class="mb-mockup-split">
											<div class="mb-mockup-main">
												<div class="mb-mockup-p-line"></div>
												<div class="mb-mockup-tags">
													<span class="mb-tag">🏊 Infinity Pool</span>
													<span class="mb-tag">📶 WiFi</span>
													<span class="mb-tag">🍳 Breakfast</span>
												</div>
											</div>
											<div class="mb-mockup-sidebar">
												<div class="mb-mockup-sidecard">
													<div class="mb-mockup-side-price"><strong>$150.00</strong> <span>/ night</span></div>
													<div class="mb-mockup-range-box">
														<div class="mb-range-col"><span>CHECK-IN</span><strong>Oct 12</strong></div>
														<div class="mb-range-col"><span>CHECKOUT</span><strong>Oct 15</strong></div>
													</div>
													<div class="mb-mockup-calc-row"><span>3 Nights</span><strong>$450.00</strong></div>
													<div class="mb-mockup-cta-btn">Reserve Stay</div>
												</div>
											</div>
										</div>

									<?php elseif ( 'rental' === $key ) : ?>
										<!-- Car & Rental Mockup (Hero Slider + Specs) -->
										<div class="mb-mockup-hero mb-mockup-hero-car">
											<div class="mb-mockup-hero-overlay">
												<div class="mb-mockup-title">Tesla Model 3 Long Range (2024)</div>
												<div class="mb-mockup-meta">⭐ 5.0 (64 trips) · 📍 Central Hub</div>
											</div>
											<div class="mb-mockup-slider-dots"><span></span><span class="active"></span><span></span></div>
										</div>
										<div class="mb-mockup-specs-strip">
											<span class="mb-spec-item">⚡ Electric</span>
											<span class="mb-spec-item">🚗 Auto</span>
											<span class="mb-spec-item">👥 5 Seats</span>
											<span class="mb-spec-item">🛣️ 330mi</span>
										</div>
										<div class="mb-mockup-split">
											<div class="mb-mockup-main">
												<div class="mb-mockup-p-line"></div>
												<div class="mb-mockup-tags">
													<span class="mb-tag">🛡️ Full Insurance</span>
													<span class="mb-tag">📍 Free Airport Pickup</span>
												</div>
											</div>
											<div class="mb-mockup-sidebar">
												<div class="mb-mockup-sidecard">
													<div class="mb-mockup-side-price"><strong>$89.00</strong> <span>/ day</span></div>
													<div class="mb-mockup-range-box">
														<div class="mb-range-col"><span>PICK-UP</span><strong>10:00 AM</strong></div>
														<div class="mb-range-col"><span>RETURN</span><strong>06:00 PM</strong></div>
													</div>
													<div class="mb-mockup-cta-btn">Rent Vehicle</div>
												</div>
											</div>
										</div>

									<?php elseif ( 'doctor' === $key ) : ?>
										<!-- Doctor Mockup -->
										<div class="mb-mockup-doctor-header">
											<div class="mb-doctor-avatar-circle">👩‍⚕️</div>
											<div class="mb-doctor-info">
												<div class="mb-mockup-title">Dr. Sarah Jenkins, MD <span class="mb-verified-badge">✓</span></div>
												<div class="mb-mockup-meta">Board Certified Cardiologist · 🏥 St. Jude Health</div>
												<div class="mb-doctor-tags">
													<span class="mb-tag-dr">Cardiology</span>
													<span class="mb-tag-dr">Consultations</span>
												</div>
											</div>
										</div>
										<div class="mb-mockup-split">
											<div class="mb-mockup-main">
												<div class="mb-mockup-p-line"></div>
												<div class="mb-mockup-insurances">
													<span>Accepted: BlueCross · Aetna · UnitedHealth</span>
												</div>
											</div>
											<div class="mb-mockup-sidebar">
												<div class="mb-mockup-sidecard">
													<div class="mb-mockup-side-price"><strong>$120.00</strong> <span>/ consult</span></div>
													<div class="mb-mockup-slots-label">Appointment Slots:</div>
													<div class="mb-mockup-slots-grid">
														<span class="mb-slot-chip active">09:30 AM</span>
														<span class="mb-slot-chip">11:00 AM</span>
														<span class="mb-slot-chip">02:30 PM</span>
													</div>
													<div class="mb-mockup-cta-btn">Book Appointment</div>
												</div>
											</div>
										</div>

									<?php elseif ( 'salon' === $key ) : ?>
										<!-- Salon Mockup (Interactive Services Menu) -->
										<div class="mb-mockup-hero mb-mockup-hero-salon">
											<div class="mb-mockup-hero-overlay">
												<div class="mb-mockup-title">Luxe Glow Hair Studio & Spa</div>
												<div class="mb-mockup-meta">⭐ 4.98 (210 reviews) · 📍 Beverly Hills</div>
											</div>
										</div>
										<div class="mb-mockup-services-list">
											<div class="mb-mini-service-row active">
												<div><strong>Signature Haircut & Style</strong> <span>45 mins</span></div>
												<div class="mb-service-price">$55 <span class="mb-check-mark">✓</span></div>
											</div>
											<div class="mb-mini-service-row">
												<div><strong>Balayage, Toner & Gloss</strong> <span>90 mins</span></div>
												<div class="mb-service-price">$140 <span class="mb-add-mark">+</span></div>
											</div>
										</div>
										<div class="mb-mockup-split">
											<div class="mb-mockup-main">
												<div class="mb-mockup-stylists">
													<span>Stylists: 👤 Emma · 👤 Liam</span>
												</div>
											</div>
											<div class="mb-mockup-sidebar">
												<div class="mb-mockup-sidecard">
													<div class="mb-mockup-side-price"><strong>$55.00</strong> <span>(1 item)</span></div>
													<div class="mb-mockup-cta-btn">Book Experience</div>
												</div>
											</div>
										</div>

									<?php elseif ( 'shop' === $key ) : ?>
										<!-- Shop Mockup (Storefront + Products Grid) -->
										<div class="mb-mockup-hero mb-mockup-hero-shop">
											<div class="mb-mockup-hero-overlay">
												<div class="mb-mockup-title">Heritage Artisan Coffee Roasters</div>
												<div class="mb-mockup-meta">🟢 Open Now (Closes 7:00 PM)</div>
											</div>
										</div>
										<div class="mb-mockup-products-grid">
											<div class="mb-product-mini">
												<div class="mb-product-img roast1"></div>
												<strong>House Blend</strong>
												<span>$18.00</span>
											</div>
											<div class="mb-product-mini">
												<div class="mb-product-img roast2"></div>
												<strong>Pour-Over Kit</strong>
												<span>$45.00</span>
											</div>
											<div class="mb-product-mini">
												<div class="mb-product-img roast3"></div>
												<strong>Cold Brew</strong>
												<span>$16.00</span>
											</div>
										</div>
										<div class="mb-mockup-split">
											<div class="mb-mockup-main">
												<div class="mb-mockup-tags">
													<span class="mb-tag">☕ Tasting Room</span>
													<span class="mb-tag">🛍️ Store Pickup</span>
												</div>
											</div>
											<div class="mb-mockup-sidebar">
												<div class="mb-mockup-sidecard">
													<div class="mb-mockup-side-price"><strong>$25.00</strong> <span>/ tasting</span></div>
													<div class="mb-mockup-cta-btn">Book Store Visit</div>
												</div>
											</div>
										</div>
									<?php endif; ?>
								</div>

								<div class="mb-mockup-features">
									<?php if ( 'hourly' === $key ) : ?>
										<span class="mb-feature-pill">⏱️ Dynamic Slot Engine</span>
										<span class="mb-feature-pill">🛡️ Buffer Protection</span>
										<span class="mb-feature-pill">⚡ Live Price Card</span>
									<?php elseif ( 'hotel' === $key ) : ?>
										<span class="mb-feature-pill">🖼️ 5-Photo Mosaic</span>
										<span class="mb-feature-pill">📅 Multi-Night Range</span>
										<span class="mb-feature-pill">👥 Guest Selector</span>
									<?php elseif ( 'rental' === $key ) : ?>
										<span class="mb-feature-pill">🎠 Hero Image Slider</span>
										<span class="mb-feature-pill">🚗 Vehicle Specs Strip</span>
										<span class="mb-feature-pill">⏰ Pick-up & Return</span>
									<?php elseif ( 'doctor' === $key ) : ?>
										<span class="mb-feature-pill">🩺 Doctor Bio & Badges</span>
										<span class="mb-feature-pill">🏥 Clinic Schedules</span>
										<span class="mb-feature-pill">📋 Visit Reasons</span>
									<?php elseif ( 'salon' === $key ) : ?>
										<span class="mb-feature-pill">💇 Interactive Services</span>
										<span class="mb-feature-pill">🛒 Multi-Service Cart</span>
										<span class="mb-feature-pill">👤 Stylist Selection</span>
									<?php elseif ( 'shop' === $key ) : ?>
										<span class="mb-feature-pill">🏪 Storefront Header</span>
										<span class="mb-feature-pill">📦 Product Showcase</span>
										<span class="mb-feature-pill">🟢 Live Open Ribbon</span>
									<?php endif; ?>
								</div>

								<div class="mb-mockup-footer-note">
									👁️ <?php esc_html_e( 'Single Listing Page Preview — how visitors experience this layout', 'my-booking-engine' ); ?>
								</div>
							</div>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Smart Defaults Banner -->
		<div class="mb-app-smart-banner" id="mb-app-smart-banner">
			<span class="mb-app-smart-icon">✨</span>
			<div>
				<strong><?php esc_html_e( "We've pre-configured the recommended settings for", 'my-booking-engine' ); ?> <span id="mb-app-smart-type-label"><?php echo esc_html( $listing_types[ $default_type ]['label'] ); ?></span>.</strong>
				<p><?php esc_html_e( 'All pricing, rules, hours, and policies are fully visible below — edit anything you wish anytime.', 'my-booking-engine' ); ?></p>
				<p class="mb-app-defaults-summary" id="mb-app-defaults-summary"></p>
			</div>
		</div>

		<!-- 2. The Basics & Location -->
		<div class="mb-app-card" id="mb-app-basics-card">
			<div class="mb-app-card-header">
				<div class="mb-app-card-badge-icon" style="background:#f0fdf4; color:#16a34a;">📝</div>
				<div>
					<h2 class="mb-app-section-title"><?php esc_html_e( 'The Basics & Location', 'my-booking-engine' ); ?></h2>
					<p class="mb-app-section-desc"><?php esc_html_e( 'Enter your listing title, base rate, description, and address for search indexing.', 'my-booking-engine' ); ?></p>
				</div>
			</div>

			<div class="mb-app-grid-2">
				<div class="mb-w-field">
					<label for="mb_quick_title"><?php esc_html_e( 'Listing Title', 'my-booking-engine' ); ?> <span class="mb-req">*</span></label>
					<input type="text" id="mb_quick_title" name="mb_quick_title" class="widefat" required placeholder="<?php esc_attr_e( 'e.g. Downtown Creative Studio', 'my-booking-engine' ); ?>" value="<?php echo esc_attr( $title_val ); ?>">
				</div>
				<div class="mb-w-field">
					<label for="mb_base_price"><?php esc_html_e( 'Base Price', 'my-booking-engine' ); ?> <span class="mb-req">*</span></label>
					<input type="number" id="mb_base_price" name="mb_base_price" class="widefat" step="0.01" min="0" required value="<?php echo esc_attr( $price_val ); ?>" <?php echo $is_edit ? 'data-user-edited="true"' : ''; ?>>
				</div>
			</div>

			<div class="mb-w-field" style="margin-top:18px;">
				<label for="mb_description"><?php esc_html_e( 'Description', 'my-booking-engine' ); ?></label>
				<p class="description" style="margin:0 0 6px;"><?php esc_html_e( 'A compelling overview shown on the listing page — what it is, who it is for, and key highlights.', 'my-booking-engine' ); ?></p>
				<textarea name="mb_description" id="mb_description" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'Tell customers what makes this experience special…', 'my-booking-engine' ); ?>"><?php echo esc_textarea( $description_val ); ?></textarea>
			</div>

			<div class="mb-app-grid-3" style="margin-top:18px;">
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

		<!-- 3. Photo Gallery -->
		<div class="mb-app-card" id="mb-app-gallery-card">
			<div class="mb-app-card-header">
				<div class="mb-app-card-badge-icon" style="background:#faf5ff; color:#9333ea;">📸</div>
				<div>
					<h2 class="mb-app-section-title"><?php esc_html_e( 'Photo Gallery', 'my-booking-engine' ); ?></h2>
					<p class="mb-app-section-desc"><?php esc_html_e( 'Listings with high-resolution photos convert dramatically higher. Upload multiple photos to populate hero sliders and mosaic grids.', 'my-booking-engine' ); ?></p>
				</div>
			</div>

			<input type="hidden" name="mb_gallery_images" id="mb_gallery_images" value="<?php echo esc_attr( $gallery_ids_val ); ?>">
			
			<div class="mb-gallery-dropzone" id="mb_gallery_dropzone">
				<div class="mb-dropzone-icon">🖼️</div>
				<div class="mb-dropzone-content">
					<div class="mb-dropzone-title"><?php esc_html_e( 'Upload & Select Multiple Photos', 'my-booking-engine' ); ?></div>
					<div class="mb-dropzone-desc"><?php esc_html_e( 'Click any photo in the library to toggle select/deselect instantly — no Ctrl or Command key required!', 'my-booking-engine' ); ?></div>
				</div>
				<div class="mb-gallery-actions">
					<button type="button" class="button mb-btn-media-picker" id="mb_btn_select_gallery">
						<span class="dashicons dashicons-images-alt2"></span>
						<span class="mb-btn-text"><?php esc_html_e( 'Select Photos from Media Library', 'my-booking-engine' ); ?></span>
					</button>
					<button type="button" class="button mb-btn-clear-gallery" id="mb_btn_clear_gallery">
						<span class="dashicons dashicons-trash"></span>
						<span class="mb-btn-text"><?php esc_html_e( 'Remove All Photos', 'my-booking-engine' ); ?></span>
					</button>
				</div>
			</div>

			<div class="mb-gallery-hint">
				<span class="dashicons dashicons-info" style="font-size:16px; width:16px; height:16px; margin-right:4px; vertical-align:text-bottom;"></span>
				<span><?php esc_html_e( 'Tip: Hover over any photo below and click the ✕ button to delete individual images from this listing.', 'my-booking-engine' ); ?></span>
			</div>

			<div id="mb_gallery_preview" class="mb-gallery-preview-grid" data-existing="<?php echo esc_attr( wp_json_encode( $existing_gallery_items ) ); ?>"></div>
		</div>

		<!-- 4. Services & Add-ons (Menu-driven: Salon & Spa) -->
		<div class="mb-app-card" id="mb-app-services-card" style="display:none;">
			<div class="mb-app-card-header">
				<div class="mb-app-card-badge-icon" style="background:#fdf2f8; color:#db2777;">💇</div>
				<div>
					<h2 class="mb-app-section-title"><?php esc_html_e( 'Services & Treatments Menu', 'my-booking-engine' ); ?></h2>
					<p class="mb-app-section-desc"><?php esc_html_e( 'List individual services customers can select from. Each service can have its own duration and pricing.', 'my-booking-engine' ); ?></p>
				</div>
			</div>

			<input type="hidden" name="mb_services" id="mb_services_json" value="<?php echo esc_attr( wp_json_encode( $services_val ) ); ?>">
			<div id="mb-services-rows" class="mb-services-rows"></div>
			
			<button type="button" class="button button-secondary" id="mb_btn_add_service" style="margin-top:10px;">
				<span class="dashicons dashicons-plus-alt2" style="vertical-align:middle; margin-top:-2px;"></span>
				<?php esc_html_e( 'Add a Service', 'my-booking-engine' ); ?>
			</button>
		</div>

		<!-- 5. Booking Rules & Duration (Always Visible, no dropdown) -->
		<div class="mb-app-card" id="mb-app-rules-card">
			<div class="mb-app-card-header">
				<div class="mb-app-card-badge-icon" style="background:#fff7ed; color:#ea580c;">⚙️</div>
				<div>
					<h2 class="mb-app-section-title"><?php esc_html_e( 'Booking Engine Rules & Duration', 'my-booking-engine' ); ?></h2>
					<p class="mb-app-section-desc">
						<?php esc_html_e( 'Pre-filled automatically based on your listing type. You can adjust duration, buffers, stay limits, and weekend pricing right here.', 'my-booking-engine' ); ?>
						<a href="#mb-app-type-picker" id="mb-app-change-type-link" style="margin-left:6px;"><?php esc_html_e( 'Change listing type ↑', 'my-booking-engine' ); ?></a>
					</p>
				</div>
			</div>

			<div class="mb-model-algorithm-wrap">
				<div class="mb-model-algorithm-header">
					<label class="mb-model-algorithm-label">
						<span><?php esc_html_e( 'Booking Engine Algorithm', 'my-booking-engine' ); ?></span>
						<span class="mb-model-decoupled-badge">💡 <?php esc_html_e( 'Decoupled from Visual Layout', 'my-booking-engine' ); ?></span>
					</label>
					<p class="mb-model-algorithm-desc">
						<?php esc_html_e( 'Select which scheduling calculation rules calculate pricing and slot availability. You can freely pair any algorithm with any layout chosen above!', 'my-booking-engine' ); ?>
					</p>
				</div>

				<input type="hidden" name="mb_model_type" id="mb_model_type" value="<?php echo esc_attr( $model_val ); ?>" class="mb-model-switcher">

				<div class="mb-model-pills-group" id="mb-model-pills">
					<button type="button" class="mb-model-pill-btn <?php echo ( 'hourly_slot' === $model_val ) ? 'is-active' : ''; ?>" data-model="hourly_slot">
						<span class="mb-pill-icon">⏱️</span>
						<span class="mb-pill-info">
							<span class="mb-pill-title"><?php esc_html_e( 'Hourly / Appointments', 'my-booking-engine' ); ?></span>
							<span class="mb-pill-desc"><?php esc_html_e( 'Time slots, duration & prep buffers', 'my-booking-engine' ); ?></span>
						</span>
					</button>

					<button type="button" class="mb-model-pill-btn <?php echo ( 'day_rental' === $model_val ) ? 'is-active' : ''; ?>" data-model="day_rental">
						<span class="mb-pill-icon">🚗</span>
						<span class="mb-pill-info">
							<span class="mb-pill-title"><?php esc_html_e( 'Day-based Rentals', 'my-booking-engine' ); ?></span>
							<span class="mb-pill-desc"><?php esc_html_e( 'Full calendar days, pickup & return times', 'my-booking-engine' ); ?></span>
						</span>
					</button>

					<button type="button" class="mb-model-pill-btn <?php echo ( 'night_stay' === $model_val ) ? 'is-active' : ''; ?>" data-model="night_stay">
						<span class="mb-pill-icon">🏨</span>
						<span class="mb-pill-info">
							<span class="mb-pill-title"><?php esc_html_e( 'Night-based Stays', 'my-booking-engine' ); ?></span>
							<span class="mb-pill-desc"><?php esc_html_e( 'Overnight stays, check-in & check-out dates', 'my-booking-engine' ); ?></span>
						</span>
					</button>

					<button type="button" class="mb-model-pill-btn <?php echo ( 'capacity_roster' === $model_val ) ? 'is-active' : ''; ?>" data-model="capacity_roster">
						<span class="mb-pill-icon">👥</span>
						<span class="mb-pill-info">
							<span class="mb-pill-title"><?php esc_html_e( 'Capacity / Event Roster', 'my-booking-engine' ); ?></span>
							<span class="mb-pill-desc"><?php esc_html_e( 'Fixed events, attendee limits & spots', 'my-booking-engine' ); ?></span>
						</span>
					</button>
				</div>
			</div>

			<!-- Hourly slot settings -->
			<div class="mb-field-row mb-field-hourly_slot" style="margin-top:20px;">
				<p class="description" style="margin:0 0 10px;"><?php esc_html_e( 'Configure session duration and any prep/cleanup buffers around each appointment.', 'my-booking-engine' ); ?></p>
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

			<!-- Day rental & Night stay settings -->
			<div class="mb-field-row mb-field-day_rental mb-field-night_stay" style="display:none; margin-top:20px;">
				<p class="description" style="margin:0 0 12px;"><?php esc_html_e( 'Configure guest limits, minimum and maximum duration, plus check-in and check-out times for property rentals.', 'my-booking-engine' ); ?></p>
				
				<div class="mb-wizard-grid-3" style="margin-bottom:16px;">
					<div class="mb-w-field" style="background:#f0f7ff; border:1.5px solid #bfdbfe; border-radius:10px; padding:12px 14px;">
						<label for="mb_capacity_property" style="color:#1d4ed8; font-weight:700;">👥 <?php esc_html_e( 'Maximum Guests Allowed', 'my-booking-engine' ); ?> <span class="mb-req">*</span></label>
						<input type="number" name="mb_capacity_property" id="mb_capacity_property" value="<?php echo esc_attr( $capacity_val ); ?>" min="1" max="100" class="widefat mb-capacity-sync-field" placeholder="<?php esc_attr_e( 'e.g. 4', 'my-booking-engine' ); ?>" style="margin-top:4px; font-weight:700; font-size:15px; color:#1e293b;">
						<span class="description" style="color:#2563eb; display:block; margin-top:4px;"><?php esc_html_e( 'Max guests permitted. Controls the guest selector dropdown on the single property page.', 'my-booking-engine' ); ?></span>
					</div>
					<div class="mb-w-field">
						<label for="mb_min_duration"><?php esc_html_e( 'Minimum Stay / Rental (Nights/Days)', 'my-booking-engine' ); ?></label>
						<input type="number" name="mb_min_duration" id="mb_min_duration" value="<?php echo esc_attr( $min_val ); ?>" min="1" class="widefat" style="margin-top:4px;">
						<span class="description"><?php esc_html_e( 'Shortest allowable booking duration.', 'my-booking-engine' ); ?></span>
					</div>
					<div class="mb-w-field">
						<label for="mb_max_duration"><?php esc_html_e( 'Maximum Stay / Rental (Nights/Days)', 'my-booking-engine' ); ?></label>
						<input type="number" name="mb_max_duration" id="mb_max_duration" value="<?php echo esc_attr( $max_val ); ?>" min="1" class="widefat" style="margin-top:4px;">
						<span class="description"><?php esc_html_e( 'Longest allowable booking duration.', 'my-booking-engine' ); ?></span>
					</div>
				</div>

				<div class="mb-wizard-grid-2">
					<div class="mb-w-field">
						<label for="mb_checkin_time"><?php esc_html_e( 'Check-in Time', 'my-booking-engine' ); ?></label>
						<input type="time" name="mb_checkin_time" id="mb_checkin_time" value="<?php echo esc_attr( $checkin_val ); ?>" class="widefat">
						<span class="description"><?php esc_html_e( 'Standard check-in time for arriving guests.', 'my-booking-engine' ); ?></span>
					</div>
					<div class="mb-w-field">
						<label for="mb_checkout_time"><?php esc_html_e( 'Check-out Time', 'my-booking-engine' ); ?></label>
						<input type="time" name="mb_checkout_time" id="mb_checkout_time" value="<?php echo esc_attr( $checkout_val ); ?>" class="widefat">
						<span class="description"><?php esc_html_e( 'Standard check-out time on departure date.', 'my-booking-engine' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Capacity roster settings -->
			<div class="mb-field-row mb-field-capacity_roster" style="display:none; margin-top:20px;">
				<p class="description" style="margin:0 0 10px;"><?php esc_html_e( 'For a fixed scheduled event with specific start and end timestamps.', 'my-booking-engine' ); ?></p>
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

			<!-- Pricing & Capacity extras -->
			<div class="mb-app-grid-2" style="margin-top:20px;">
				<div class="mb-w-field">
					<label for="mb_weekend_price"><?php esc_html_e( 'Weekend Price (Optional)', 'my-booking-engine' ); ?></label>
					<input type="number" name="mb_weekend_price" id="mb_weekend_price" class="widefat" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Leave blank to use base rate', 'my-booking-engine' ); ?>" value="<?php echo esc_attr( $weekend_val ); ?>">
					<span class="description"><?php esc_html_e( 'Applies automatically for Saturday and Sunday bookings.', 'my-booking-engine' ); ?></span>
				</div>
				<div class="mb-w-field" id="mb_capacity_general_wrapper">
					<label for="mb_capacity" id="mb_capacity_general_label">👥 <?php esc_html_e( 'Maximum Capacity / Spots', 'my-booking-engine' ); ?> <span class="mb-req">*</span></label>
					<input type="number" name="mb_capacity" id="mb_capacity" value="<?php echo esc_attr( $capacity_val ); ?>" min="1" max="1000" class="widefat mb-capacity-sync-field">
					<span class="description" id="mb_capacity_general_desc"><?php esc_html_e( 'Max customers, attendees, or tickets allowed per slot/booking.', 'my-booking-engine' ); ?></span>
				</div>
			</div>
		</div>

		<!-- 6. Weekly Operating Hours (Always Visible, no dropdown) -->
		<div class="mb-app-card" id="mb-app-schedule-card">
			<div class="mb-app-card-header">
				<div class="mb-app-card-badge-icon" style="background:#ecfeff; color:#0891b2;">🕒</div>
				<div>
					<h2 class="mb-app-section-title"><?php esc_html_e( 'Weekly Operating Hours', 'my-booking-engine' ); ?></h2>
					<p class="mb-app-section-desc"><?php esc_html_e( 'Define your open days and hours. Toggle off any day to block it completely from the booking calendar.', 'my-booking-engine' ); ?></p>
				</div>
			</div>

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
							<span style="color:#94a3b8; font-weight:700;">→</span>
							<input type="time" name="mb_schedule[<?php echo esc_attr( $day_idx ); ?>][end]" value="<?php echo esc_attr( $end_time ); ?>" class="mb-time-picker">
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- 7. Amenities, Policies & House Rules (Always Visible, no dropdown) -->
		<div class="mb-app-card" id="mb-app-policies-card">
			<div class="mb-app-card-header">
				<div class="mb-app-card-badge-icon" style="background:#f1f5f9; color:#475569;">📋</div>
				<div>
					<h2 class="mb-app-section-title"><?php esc_html_e( 'Amenities & Policies', 'my-booking-engine' ); ?></h2>
					<p class="mb-app-section-desc"><?php esc_html_e( 'Shown clearly on your listing page to give customers all the details before they book.', 'my-booking-engine' ); ?></p>
				</div>
			</div>

			<div class="mb-app-grid-2">
				<div class="mb-w-field">
					<label for="mb_amenities"><?php esc_html_e( 'Amenities & Inclusions (one per line)', 'my-booking-engine' ); ?></label>
					<textarea name="mb_amenities" id="mb_amenities" rows="4" class="widefat" placeholder="<?php esc_attr_e( "High-Speed WiFi\nAir Conditioning\nFree Parking", 'my-booking-engine' ); ?>"><?php echo esc_textarea( $amenities_val ); ?></textarea>
					<span class="description"><?php esc_html_e( 'Renders as neat feature pills on the frontend.', 'my-booking-engine' ); ?></span>
				</div>
				<div class="mb-w-field">
					<label for="mb_policy"><?php esc_html_e( 'Cancellation Policy & House Rules', 'my-booking-engine' ); ?></label>
					<textarea name="mb_policy" id="mb_policy" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'Free cancellation up to 48 hours before check-in. No smoking.', 'my-booking-engine' ); ?>"><?php echo esc_textarea( $policy_val ); ?></textarea>
					<span class="description"><?php esc_html_e( 'Displayed in the policies accordion on the single listing page.', 'my-booking-engine' ); ?></span>
				</div>
			</div>
		</div>

		<!-- 8. Appearance & Color Customizer -->
		<div class="mb-app-card" id="mb-app-appearance-card">
			<div class="mb-app-card-header">
				<div class="mb-app-card-badge-icon" style="background:#fef3c7; color:#92400e;">🎨</div>
				<div>
					<h2 class="mb-app-section-title"><?php esc_html_e( 'Appearance & Colors', 'my-booking-engine' ); ?></h2>
					<p class="mb-app-section-desc"><?php esc_html_e( 'Global brand colors applied across all frontend widgets, buttons, and cards. Changes here update site-wide styling instantly.', 'my-booking-engine' ); ?></p>
				</div>
			</div>

			<!-- One-Click Palette Presets -->
			<div class="mb-palette-presets" style="margin:0 0 20px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
				<span style="font-weight:500; font-size:12px; color:#64748b; white-space:nowrap;"><?php esc_html_e( 'Quick Presets:', 'my-booking-engine' ); ?></span>
				<button type="button" class="button mb-palette-btn mb-al-palette" data-primary="#2563eb" data-hover="#1d4ed8" data-accent="#f59e0b">🔵 <?php esc_html_e( 'Modern Blue', 'my-booking-engine' ); ?></button>
				<button type="button" class="button mb-palette-btn mb-al-palette" data-primary="#ff385c" data-hover="#e00b41" data-accent="#00a699">🌺 <?php esc_html_e( 'Airbnb Coral', 'my-booking-engine' ); ?></button>
				<button type="button" class="button mb-palette-btn mb-al-palette" data-primary="#059669" data-hover="#047857" data-accent="#d97706">🌿 <?php esc_html_e( 'Emerald Spa', 'my-booking-engine' ); ?></button>
				<button type="button" class="button mb-palette-btn mb-al-palette" data-primary="#7c3aed" data-hover="#6d28d9" data-accent="#ec4899">👑 <?php esc_html_e( 'Royal Purple', 'my-booking-engine' ); ?></button>
				<button type="button" class="button mb-palette-btn mb-al-palette" data-primary="#0f172a" data-hover="#1e293b" data-accent="#f59e0b">🕶️ <?php esc_html_e( 'Luxury Obsidian', 'my-booking-engine' ); ?></button>
			</div>

			<!-- Color Pickers Grid -->
			<div class="mb-app-grid-3" style="gap:20px;">
				<!-- Primary Brand Color -->
				<div class="mb-w-field">
					<label for="mb_al_primary_color"><?php esc_html_e( 'Primary Brand Color', 'my-booking-engine' ); ?></label>
					<div style="display:flex; align-items:center; gap:10px; margin-top:6px;">
						<input type="color" id="mb_al_primary_color_picker"
							value="<?php echo esc_attr( $mb_primary_color ); ?>"
							oninput="document.getElementById('mb_al_primary_color').value=this.value;"
							onchange="document.getElementById('mb_al_primary_color').value=this.value;"
							style="width:42px;height:38px;padding:2px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;">
						<input type="text" name="mb_engine_settings[primary_color]" id="mb_al_primary_color"
							value="<?php echo esc_attr( $mb_primary_color ); ?>"
							oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value))document.getElementById('mb_al_primary_color_picker').value=this.value;"
							class="small-text" style="font-family:monospace; border-radius:6px;">
					</div>
					<span class="description"><?php esc_html_e( 'Buttons, calendar, tabs, filters.', 'my-booking-engine' ); ?></span>
				</div>

				<!-- Primary Hover Color -->
				<div class="mb-w-field">
					<label for="mb_al_primary_hover"><?php esc_html_e( 'Hover Color', 'my-booking-engine' ); ?></label>
					<div style="display:flex; align-items:center; gap:10px; margin-top:6px;">
						<input type="color" id="mb_al_primary_hover_picker"
							value="<?php echo esc_attr( $mb_primary_hover ); ?>"
							oninput="document.getElementById('mb_al_primary_hover').value=this.value;"
							onchange="document.getElementById('mb_al_primary_hover').value=this.value;"
							style="width:42px;height:38px;padding:2px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;">
						<input type="text" name="mb_engine_settings[primary_hover]" id="mb_al_primary_hover"
							value="<?php echo esc_attr( $mb_primary_hover ); ?>"
							oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value))document.getElementById('mb_al_primary_hover_picker').value=this.value;"
							class="small-text" style="font-family:monospace; border-radius:6px;">
					</div>
					<span class="description"><?php esc_html_e( 'Applied on button/link hover states.', 'my-booking-engine' ); ?></span>
				</div>

				<!-- Accent / Star Color -->
				<div class="mb-w-field">
					<label for="mb_al_accent_color"><?php esc_html_e( 'Accent & Star Color', 'my-booking-engine' ); ?></label>
					<div style="display:flex; align-items:center; gap:10px; margin-top:6px;">
						<input type="color" id="mb_al_accent_color_picker"
							value="<?php echo esc_attr( $mb_accent_color ); ?>"
							oninput="document.getElementById('mb_al_accent_color').value=this.value;"
							onchange="document.getElementById('mb_al_accent_color').value=this.value;"
							style="width:42px;height:38px;padding:2px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;">
						<input type="text" name="mb_engine_settings[accent_color]" id="mb_al_accent_color"
							value="<?php echo esc_attr( $mb_accent_color ); ?>"
							oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value))document.getElementById('mb_al_accent_color_picker').value=this.value;"
							class="small-text" style="font-family:monospace; border-radius:6px;">
					</div>
					<span class="description"><?php esc_html_e( 'Review stars, badges, highlights.', 'my-booking-engine' ); ?></span>
				</div>
			</div>

			<!-- Border Radius -->
			<div class="mb-w-field" style="margin-top:20px; max-width:320px;">
				<label for="mb_al_border_radius"><?php esc_html_e( 'Card & Button Corner Radius', 'my-booking-engine' ); ?></label>
				<select name="mb_engine_settings[border_radius]" id="mb_al_border_radius" class="regular-text" style="border-radius:8px; margin-top:6px;">
					<option value="4"  <?php selected( $mb_border_radius, 4 ); ?>><?php esc_html_e( '4px — Subtle / Sharp', 'my-booking-engine' ); ?></option>
					<option value="8"  <?php selected( $mb_border_radius, 8 ); ?>><?php esc_html_e( '8px — Modern Default', 'my-booking-engine' ); ?></option>
					<option value="12" <?php selected( $mb_border_radius, 12 ); ?>><?php esc_html_e( '12px — Smooth Rounded', 'my-booking-engine' ); ?></option>
					<option value="16" <?php selected( $mb_border_radius, 16 ); ?>><?php esc_html_e( '16px — High Curves', 'my-booking-engine' ); ?></option>
				</select>
			</div>
		</div>

		<!-- Sticky Footer Publish Bar -->
		<div class="mb-app-sticky-footer">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mb_booking_entity' ) ); ?>" class="button mb-app-cancel-btn"><?php esc_html_e( 'Cancel', 'my-booking-engine' ); ?></a>
			<button type="submit" class="button mb-app-publish-btn" id="mb-app-publish-btn">
				<?php echo $is_edit ? '💾 ' . esc_html__( 'Update Listing', 'my-booking-engine' ) : '🚀 ' . esc_html__( 'Publish Listing', 'my-booking-engine' ); ?>
			</button>
		</div>
	</form>
</div>
