<?php
/**
 * Hotel / Accommodation Listing Layout (Airbnb & Booking.com style).
 *
 * @package MyBookingEngine
 */

use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var BookingEntity $entity */
$post_id       = $entity->get_id();
$loc           = $entity->get_location();
$gallery       = $entity->get_gallery_images( 'large' );
$amenities     = $entity->get_amenities();
$rating        = $entity->get_rating_average();
$review_count  = $entity->get_review_count();
$reviews       = $entity->get_reviews( 6 );
$faqs          = $entity->get_faqs();
$policy        = $entity->get_policy();
$base_price    = $entity->get_base_price();
$weekend_price = $entity->get_weekend_price();
$capacity      = $entity->get_capacity();
$checkin       = $entity->get_checkin_time();
$checkout      = $entity->get_checkout_time();

$settings       = get_option( 'mb_engine_settings', array() );
$currency_sym   = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
$loc_text       = $loc ? esc_html( trim( $loc->city . ', ' . $loc->postal_code . ' ' . $loc->country_code, ', ' ) ) : esc_html__( 'Location upon booking', 'my-booking-engine' );
?>

<div class="mb-listing-header">
	<div class="mb-header-main">
		<h1 class="mb-listing-title"><?php echo esc_html( $entity->get_title() ); ?></h1>
		<div class="mb-listing-meta-row">
			<span class="mb-rating-badge">
				<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?>
				<a href="#mb-reviews-section" class="mb-review-count-link">(<?php echo esc_html( $review_count ); ?> <?php esc_html_e( 'reviews', 'my-booking-engine' ); ?>)</a>
			</span>
			<span class="mb-meta-divider">•</span>
			<span class="mb-location-badge">
				<svg class="mb-icon-pin" viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
				<?php echo esc_html( $loc_text ); ?>
			</span>
			<span class="mb-meta-divider">•</span>
			<span class="mb-model-tag"><?php esc_html_e( 'Entire Place / Hotel Room', 'my-booking-engine' ); ?></span>
		</div>
	</div>
</div>

<!-- Airbnb-Style Gallery Mosaic -->
<div class="mb-gallery-mosaic">
	<?php if ( ! empty( $gallery ) ) : ?>
		<div class="mb-gallery-featured">
			<img src="<?php echo esc_url( $gallery[0] ); ?>" alt="<?php echo esc_attr( $entity->get_title() ); ?>" loading="lazy" />
		</div>
		<div class="mb-gallery-thumbs">
			<?php for ( $i = 1; $i < 5; $i++ ) : ?>
				<div class="mb-thumb-item">
					<?php if ( isset( $gallery[ $i ] ) ) : ?>
						<img src="<?php echo esc_url( $gallery[ $i ] ); ?>" alt="<?php echo esc_attr( $entity->get_title() ); ?>" loading="lazy" />
					<?php else : ?>
						<img src="<?php echo esc_url( $gallery[0] ); ?>" alt="<?php echo esc_attr( $entity->get_title() ); ?>" class="mb-placeholder-img" loading="lazy" />
					<?php endif; ?>
				</div>
			<?php endfor; ?>
		</div>
	<?php endif; ?>
</div>

<div class="mb-layout-columns">
	<!-- Left Column: Details & Content -->
	<div class="mb-column-main">
		<!-- Host / Quick Specs Bar -->
		<div class="mb-section mb-specs-card">
			<div class="mb-spec-item">
				<span class="mb-spec-icon">👥</span>
				<span class="mb-spec-label"><?php echo esc_html( sprintf( __( 'Up to %d guests', 'my-booking-engine' ), $capacity ) ); ?></span>
			</div>
			<div class="mb-spec-item">
				<span class="mb-spec-icon">🕒</span>
				<span class="mb-spec-label"><?php echo esc_html( sprintf( __( 'Check-in %s', 'my-booking-engine' ), $checkin ) ); ?></span>
			</div>
			<div class="mb-spec-item">
				<span class="mb-spec-icon">🚪</span>
				<span class="mb-spec-label"><?php echo esc_html( sprintf( __( 'Check-out %s', 'my-booking-engine' ), $checkout ) ); ?></span>
			</div>
			<div class="mb-spec-item">
				<span class="mb-spec-icon">⭐</span>
				<span class="mb-spec-label"><?php esc_html_e( 'Self check-in available', 'my-booking-engine' ); ?></span>
			</div>
		</div>

		<!-- About / Content -->
		<div class="mb-section mb-description-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'About this place', 'my-booking-engine' ); ?></h2>
			<div class="mb-rich-text">
				<?php the_content(); ?>
			</div>
		</div>

		<!-- Amenities Grid -->
		<div class="mb-section mb-amenities-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'What this place offers', 'my-booking-engine' ); ?></h2>
			<div class="mb-amenities-grid">
				<?php foreach ( $amenities as $amenity ) : ?>
					<div class="mb-amenity-pill">
						<span class="mb-amenity-check">✓</span>
						<span class="mb-amenity-name"><?php echo esc_html( $amenity ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Interactive Range Calendar -->
		<div class="mb-section mb-calendar-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Select Dates & Check Availability', 'my-booking-engine' ); ?></h2>
			<p class="mb-section-subtitle"><?php esc_html_e( 'Choose your check-in and check-out dates to see exact pricing and availability.', 'my-booking-engine' ); ?></p>
			<div class="mb-calendar-widget" data-entity-id="<?php echo esc_attr( $post_id ); ?>" data-model="hotel_room" data-mode="range"></div>
		</div>

		<!-- Map Section -->
		<?php if ( $loc ) : ?>
			<div class="mb-section mb-map-section">
				<h2 class="mb-section-title"><?php esc_html_e( 'Where you\'ll be', 'my-booking-engine' ); ?></h2>
				<p class="mb-map-address">📍 <?php echo esc_html( $loc_text ); ?></p>
				<div id="mb-single-map" class="mb-map-canvas" data-lat="<?php echo esc_attr( $loc->latitude ); ?>" data-lng="<?php echo esc_attr( $loc->longitude ); ?>" data-title="<?php echo esc_attr( $entity->get_title() ); ?>"></div>
			</div>
		<?php endif; ?>

		<!-- Verified Reviews Section -->
		<div id="mb-reviews-section" class="mb-section mb-reviews-section">
			<div class="mb-reviews-header">
				<h2 class="mb-section-title">
					<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?> • <?php echo esc_html( $review_count ); ?> <?php esc_html_e( 'reviews', 'my-booking-engine' ); ?>
				</h2>
				<button type="button" class="mb-btn mb-btn-secondary mb-btn-sm" id="mb-write-review-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
					<?php esc_html_e( 'Write a Review', 'my-booking-engine' ); ?>
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
							<?php if ( ! empty( $rev->title ) ) : ?>
								<h5 class="mb-review-title"><?php echo esc_html( $rev->title ); ?></h5>
							<?php endif; ?>
							<p class="mb-review-body"><?php echo esc_html( $rev->content ); ?></p>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="mb-no-reviews"><?php esc_html_e( 'No reviews yet. Be the first verified guest to leave a review!', 'my-booking-engine' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- FAQs & Policy -->
		<div class="mb-section mb-policy-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Things to know', 'my-booking-engine' ); ?></h2>
			<div class="mb-policy-box">
				<h4><?php esc_html_e( 'Cancellation policy', 'my-booking-engine' ); ?></h4>
				<p><?php echo esc_html( $policy ); ?></p>
			</div>
			<?php if ( ! empty( $faqs ) ) : ?>
				<div class="mb-faqs-accordion">
					<h4><?php esc_html_e( 'Frequently Asked Questions', 'my-booking-engine' ); ?></h4>
					<?php foreach ( $faqs as $faq ) : ?>
						<details class="mb-faq-item">
							<summary class="mb-faq-q"><?php echo esc_html( $faq['question'] ); ?></summary>
							<div class="mb-faq-a"><?php echo esc_html( $faq['answer'] ); ?></div>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Right Sticky Column: Booking Card -->
	<div class="mb-column-sidebar">
		<div class="mb-sticky-card">
			<div class="mb-card-price-row">
				<div class="mb-price-tag">
					<span class="mb-currency"><?php echo esc_html( $currency_sym ); ?></span>
					<span class="mb-amount" id="mb-card-price"><?php echo esc_html( number_format( $base_price, 2 ) ); ?></span>
					<span class="mb-period">/ <?php esc_html_e( 'night', 'my-booking-engine' ); ?></span>
				</div>
				<div class="mb-card-rating">
					<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?>
				</div>
			</div>

			<div class="mb-booking-funnel-trigger" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
				<div class="mb-dates-input-box" id="mb-trigger-date-picker">
					<div class="mb-date-field">
						<label><?php esc_html_e( 'CHECK-IN', 'my-booking-engine' ); ?></label>
						<span class="mb-val" id="mb-display-checkin"><?php esc_html_e( 'Add date', 'my-booking-engine' ); ?></span>
					</div>
					<div class="mb-date-field">
						<label><?php esc_html_e( 'CHECKOUT', 'my-booking-engine' ); ?></label>
						<span class="mb-val" id="mb-display-checkout"><?php esc_html_e( 'Add date', 'my-booking-engine' ); ?></span>
					</div>
				</div>

				<div class="mb-guests-field">
					<label><?php esc_html_e( 'GUESTS', 'my-booking-engine' ); ?></label>
					<select id="mb-select-guests" class="mb-form-select">
						<?php for ( $g = 1; $g <= $capacity; $g++ ) : ?>
							<option value="<?php echo esc_attr( $g ); ?>"><?php echo esc_html( sprintf( _n( '%d guest', '%d guests', $g, 'my-booking-engine' ), $g ) ); ?></option>
						<?php endfor; ?>
					</select>
				</div>

				<button type="button" class="mb-btn mb-btn-primary mb-btn-block mb-btn-reserve" id="mb-start-booking-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
					<?php esc_html_e( 'Check Availability / Reserve', 'my-booking-engine' ); ?>
				</button>
			</div>

			<p class="mb-no-charge-notice"><?php esc_html_e( 'You won\'t be charged yet', 'my-booking-engine' ); ?></p>

			<div class="mb-price-breakdown" id="mb-price-breakdown" style="display:none;">
				<div class="mb-breakdown-row">
					<span id="mb-calc-rate-label"><?php echo esc_html( $currency_sym . number_format( $base_price, 2 ) ); ?> x <span id="mb-calc-nights">0</span> nights</span>
					<span id="mb-calc-subtotal">$0.00</span>
				</div>
				<div class="mb-breakdown-row">
					<span><?php esc_html_e( 'Service & Platform Fee', 'my-booking-engine' ); ?></span>
					<span id="mb-calc-fee">$0.00</span>
				</div>
				<hr class="mb-divider" />
				<div class="mb-breakdown-row mb-total-row">
					<strong><?php esc_html_e( 'Total before taxes', 'my-booking-engine' ); ?></strong>
					<strong id="mb-calc-total">$0.00</strong>
				</div>
			</div>
		</div>
	</div>
</div>
