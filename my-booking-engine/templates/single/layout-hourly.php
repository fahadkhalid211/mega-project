<?php
/**
 * Hourly Appointment & Consultation Listing Layout.
 *
 * @package MyBookingEngine
 */

use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var BookingEntity $entity */
$post_id      = $entity->get_id();
$loc          = $entity->get_location();
$gallery      = $entity->get_gallery_images( 'large' );
$amenities    = $entity->get_amenities();
$rating       = $entity->get_rating_average();
$review_count = $entity->get_review_count();
$reviews      = $entity->get_reviews( 6 );
$faqs         = $entity->get_faqs();
$policy       = $entity->get_policy();
$base_price   = $entity->get_base_price();
$duration     = $entity->get_slot_duration();

$settings     = get_option( 'mb_engine_settings', array() );
$currency_sym = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
$loc_text     = $loc ? esc_html( trim( $loc->city . ', ' . $loc->postal_code . ' ' . $loc->country_code, ', ' ) ) : esc_html__( 'Location upon booking', 'my-booking-engine' );
?>

<div class="mb-listing-header">
	<div class="mb-header-main">
		<span class="mb-tag mb-tag-hourly">⏱️ <?php esc_html_e( 'Hourly Booking', 'my-booking-engine' ); ?></span>
		<h1 class="mb-listing-title"><?php echo esc_html( $entity->get_title() ); ?></h1>
		<div class="mb-listing-meta-row">
			<span class="mb-rating-badge">
				<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?> (<?php echo esc_html( $review_count ); ?>)
			</span>
			<span class="mb-meta-divider">•</span>
			<span class="mb-location-badge">📍 <?php echo esc_html( $loc_text ); ?></span>
		</div>
	</div>
</div>

<div class="mb-hourly-banner">
	<img src="<?php echo esc_url( ! empty( $gallery ) ? $gallery[0] : $entity->get_thumbnail_url() ); ?>" alt="<?php echo esc_attr( $entity->get_title() ); ?>" class="mb-banner-img" />
</div>

<div class="mb-layout-columns">
	<!-- Left Column -->
	<div class="mb-column-main">
		<!-- Quick Details Bar -->
		<div class="mb-section mb-specs-card">
			<div class="mb-spec-item">
				<span class="mb-spec-icon">⏱️</span>
				<span class="mb-spec-label"><?php
				/* translators: %d: session duration in minutes */
				echo esc_html( sprintf( __( '%d-minute sessions', 'my-booking-engine' ), $duration ) );
				?></span>
			</div>
			<div class="mb-spec-item">
				<span class="mb-spec-icon">⚡</span>
				<span class="mb-spec-label"><?php esc_html_e( 'Instant confirmation', 'my-booking-engine' ); ?></span>
			</div>
			<div class="mb-spec-item">
				<span class="mb-spec-icon">🔒</span>
				<span class="mb-spec-label"><?php esc_html_e( 'Secure reservation', 'my-booking-engine' ); ?></span>
			</div>
		</div>

		<!-- About -->
		<div class="mb-section mb-description-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'About this service', 'my-booking-engine' ); ?></h2>
			<div class="mb-rich-text">
				<?php the_content(); ?>
			</div>
		</div>

		<!-- Included Amenities / Perks -->
		<div class="mb-section mb-amenities-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Included with your session', 'my-booking-engine' ); ?></h2>
			<div class="mb-amenities-grid">
				<?php foreach ( $amenities as $amenity ) : ?>
					<div class="mb-amenity-pill">
						<span class="mb-amenity-check">✓</span>
						<span class="mb-amenity-name"><?php echo esc_html( $amenity ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Time Slot Calendar Picker -->
		<div class="mb-section mb-calendar-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Select Date & Available Time Slot', 'my-booking-engine' ); ?></h2>
			<p class="mb-section-subtitle"><?php esc_html_e( 'Pick a date to browse real-time open time slots.', 'my-booking-engine' ); ?></p>
			<div class="mb-calendar-widget" data-entity-id="<?php echo esc_attr( $post_id ); ?>" data-model="hourly_slot" data-mode="single"></div>
			<div class="mb-slots-container" id="mb-hourly-slots" style="display:none;">
				<h4 class="mb-slots-title"><?php esc_html_e( 'Open slots:', 'my-booking-engine' ); ?></h4>
				<div class="mb-slots-grid"></div>
			</div>
		</div>

		<!-- Map -->
		<?php if ( $loc ) : ?>
			<div class="mb-section mb-map-section">
				<h2 class="mb-section-title"><?php esc_html_e( 'Location', 'my-booking-engine' ); ?></h2>
				<p class="mb-map-address">📍 <?php echo esc_html( $loc_text ); ?></p>
				<div id="mb-single-map" class="mb-map-canvas" data-lat="<?php echo esc_attr( $loc->latitude ); ?>" data-lng="<?php echo esc_attr( $loc->longitude ); ?>" data-title="<?php echo esc_attr( $entity->get_title() ); ?>"></div>
			</div>
		<?php endif; ?>

		<!-- Reviews -->
		<div id="mb-reviews-section" class="mb-section mb-reviews-section">
			<div class="mb-reviews-header">
				<h2 class="mb-section-title">
					<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?> • <?php echo esc_html( $review_count ); ?> <?php esc_html_e( 'reviews', 'my-booking-engine' ); ?>
				</h2>
				<button type="button" class="mb-btn mb-btn-secondary mb-btn-sm" id="mb-write-review-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
					<?php esc_html_e( 'Write Review', 'my-booking-engine' ); ?>
				</button>
			</div>
			<div class="mb-reviews-grid">
				<?php if ( ! empty( $reviews ) ) : ?>
					<?php foreach ( $reviews as $rev ) : ?>
						<div class="mb-review-card">
							<div class="mb-review-author">
								<div class="mb-avatar-placeholder"><?php echo esc_html( strtoupper( substr( $rev->customer_name, 0, 1 ) ) ); ?></div>
								<div>
									<h4 class="mb-author-name"><?php echo esc_html( $rev->customer_name ); ?></h4>
									<span class="mb-review-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $rev->created_at ) ) ); ?></span>
								</div>
							</div>
							<div class="mb-review-stars">
								<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
									<span class="<?php echo ( $s <= (int) $rev->rating ) ? 'mb-star-filled' : 'mb-star-empty'; ?>">★</span>
								<?php endfor; ?>
							</div>
							<p class="mb-review-body"><?php echo esc_html( $rev->content ); ?></p>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="mb-no-reviews"><?php esc_html_e( 'No reviews yet.', 'my-booking-engine' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Right Sticky Column -->
	<div class="mb-column-sidebar">
		<div class="mb-sticky-card">
			<div class="mb-card-price-row">
				<div class="mb-price-tag">
					<span class="mb-currency"><?php echo esc_html( $currency_sym ); ?></span>
					<span class="mb-amount" id="mb-card-price"><?php echo esc_html( number_format( $base_price, 2 ) ); ?></span>
					<span class="mb-period">/ <?php echo esc_html( $duration ); ?> <?php esc_html_e( 'min', 'my-booking-engine' ); ?></span>
				</div>
			</div>

			<div class="mb-booking-funnel-trigger" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
				<div class="mb-date-field mb-single-date-box" id="mb-trigger-date-picker">
					<label><?php esc_html_e( 'SESSION DATE & TIME', 'my-booking-engine' ); ?></label>
					<span class="mb-val" id="mb-display-checkin"><?php esc_html_e( 'Select slot', 'my-booking-engine' ); ?></span>
				</div>

				<button type="button" class="mb-btn mb-btn-primary mb-btn-block mb-btn-reserve" id="mb-start-booking-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
					<?php esc_html_e( 'Book Now', 'my-booking-engine' ); ?>
				</button>
			</div>

			<div class="mb-sidebar-badges">
				<div class="mb-badge-row"><span>✓</span> <?php esc_html_e( 'Calendar invite sent automatically', 'my-booking-engine' ); ?></div>
				<div class="mb-badge-row"><span>✓</span> <?php esc_html_e( 'Free cancellation up to 24h before', 'my-booking-engine' ); ?></div>
			</div>
		</div>
	</div>
</div>
