<?php
/**
 * Rental / Vehicle & Equipment Listing Layout (Turo style).
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
$capacity     = $entity->get_capacity();
$min_days     = $entity->get_min_duration();
$max_days     = $entity->get_max_duration();

$settings     = get_option( 'mb_engine_settings', array() );
$currency_sym = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
$loc_text     = $loc ? esc_html( trim( $loc->city . ', ' . $loc->postal_code . ' ' . $loc->country_code, ', ' ) ) : esc_html__( 'Pickup location provided upon booking', 'my-booking-engine' );
?>

<div class="mb-listing-header">
	<div class="mb-header-main">
		<span class="mb-tag mb-tag-rental">🚗 <?php esc_html_e( 'Daily Rental', 'my-booking-engine' ); ?></span>
		<h1 class="mb-listing-title"><?php echo esc_html( $entity->get_title() ); ?></h1>
		<div class="mb-listing-meta-row">
			<span class="mb-rating-badge">
				<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?>
				<a href="#mb-reviews-section" class="mb-review-count-link">(<?php echo esc_html( $review_count ); ?> <?php esc_html_e( 'trips / reviews', 'my-booking-engine' ); ?>)</a>
			</span>
			<span class="mb-meta-divider">•</span>
			<span class="mb-location-badge">📍 <?php echo esc_html( $loc_text ); ?></span>
		</div>
	</div>
</div>

<!-- Vehicle Photo Showcase Slider -->
<div class="mb-hero-slider-wrap mb-rental-slider-wrap" id="mb-rental-slider">
	<?php if ( ! empty( $gallery ) ) : ?>
		<div class="mb-hero-slider">
			<div class="mb-slider-track">
				<?php foreach ( $gallery as $idx => $img_url ) : ?>
					<div class="mb-slider-slide <?php echo 0 === $idx ? 'is-active' : ''; ?>" data-slide="<?php echo esc_attr( $idx ); ?>">
						<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $entity->get_title() . ' - Photo ' . ( $idx + 1 ) ); ?>" loading="<?php echo 0 === $idx ? 'eager' : 'lazy'; ?>" />
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( count( $gallery ) > 1 ) : ?>
				<button type="button" class="mb-slider-nav mb-slider-prev" aria-label="<?php esc_attr_e( 'Previous Image', 'my-booking-engine' ); ?>">‹</button>
				<button type="button" class="mb-slider-nav mb-slider-next" aria-label="<?php esc_attr_e( 'Next Image', 'my-booking-engine' ); ?>">›</button>
				<div class="mb-slider-counter">
					<span class="mb-curr-slide">1</span> / <span class="mb-total-slides"><?php echo count( $gallery ); ?></span>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( count( $gallery ) > 1 ) : ?>
			<div class="mb-slider-thumbs">
				<?php foreach ( $gallery as $idx => $img_url ) : ?>
					<button type="button" class="mb-slider-thumb <?php echo 0 === $idx ? 'is-active' : ''; ?>" data-thumb="<?php echo esc_attr( $idx ); ?>">
						<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( 'Thumb ' . ( $idx + 1 ) ); ?>" />
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>

<div class="mb-layout-columns">
	<!-- Left Column -->
	<div class="mb-column-main">
		<!-- Vehicle Specs Chips -->
		<div class="mb-section mb-rental-specs">
			<div class="mb-spec-pill">
				<span class="mb-spec-icon">💺</span>
				<span class="mb-spec-text"><?php echo esc_html( sprintf( __( '%d Seats / Capacity', 'my-booking-engine' ), $capacity ) ); ?></span>
			</div>
			<div class="mb-spec-pill">
				<span class="mb-spec-icon">⚙️</span>
				<span class="mb-spec-text"><?php esc_html_e( 'Automatic / Manual', 'my-booking-engine' ); ?></span>
			</div>
			<div class="mb-spec-pill">
				<span class="mb-spec-icon">🛡️</span>
				<span class="mb-spec-text"><?php esc_html_e( 'Insurance & Roadside Included', 'my-booking-engine' ); ?></span>
			</div>
			<div class="mb-spec-pill">
				<span class="mb-spec-icon">⚡</span>
				<span class="mb-spec-text"><?php esc_html_e( 'Instant Pickup', 'my-booking-engine' ); ?></span>
			</div>
		</div>

		<!-- Overview -->
		<div class="mb-section mb-description-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Vehicle & Rental Features', 'my-booking-engine' ); ?></h2>
			<div class="mb-rich-text">
				<?php the_content(); ?>
			</div>
		</div>

		<!-- Included Features -->
		<div class="mb-section mb-amenities-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Features & Guidelines', 'my-booking-engine' ); ?></h2>
			<div class="mb-amenities-grid">
				<?php foreach ( $amenities as $amenity ) : ?>
					<div class="mb-amenity-pill">
						<span class="mb-amenity-check">✓</span>
						<span class="mb-amenity-name"><?php echo esc_html( $amenity ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Range Highlight Calendar -->
		<div class="mb-section mb-calendar-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Rental Availability', 'my-booking-engine' ); ?></h2>
			<p class="mb-section-subtitle"><?php esc_html_e( 'Select your trip start and return dates.', 'my-booking-engine' ); ?></p>
			<div class="mb-calendar-widget" data-entity-id="<?php echo esc_attr( $post_id ); ?>" data-model="daily_booking" data-mode="range"></div>
		</div>

		<!-- Pickup Location -->
		<?php if ( $loc ) : ?>
			<div class="mb-section mb-map-section">
				<h2 class="mb-section-title"><?php esc_html_e( 'Pickup & Return Location', 'my-booking-engine' ); ?></h2>
				<p class="mb-map-address">📍 <?php echo esc_html( $loc_text ); ?></p>
				<div id="mb-single-map" class="mb-map-canvas" data-lat="<?php echo esc_attr( $loc->latitude ); ?>" data-lng="<?php echo esc_attr( $loc->longitude ); ?>" data-title="<?php echo esc_attr( $entity->get_title() ); ?>"></div>
			</div>
		<?php endif; ?>

		<!-- Reviews -->
		<div id="mb-reviews-section" class="mb-section mb-reviews-section">
			<div class="mb-reviews-header">
				<h2 class="mb-section-title">
					<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?> • <?php echo esc_html( $review_count ); ?> <?php esc_html_e( 'ratings', 'my-booking-engine' ); ?>
				</h2>
				<button type="button" class="mb-btn mb-btn-secondary mb-btn-sm" id="mb-write-review-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
					<?php esc_html_e( 'Rate This Rental', 'my-booking-engine' ); ?>
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
					<p class="mb-no-reviews"><?php esc_html_e( 'No rental reviews yet.', 'my-booking-engine' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Right Sticky Card -->
	<div class="mb-column-sidebar">
		<div class="mb-sticky-card">
			<div class="mb-card-price-row">
				<div class="mb-price-tag">
					<span class="mb-currency"><?php echo esc_html( $currency_sym ); ?></span>
					<span class="mb-amount" id="mb-card-price"><?php echo esc_html( number_format( $base_price, 2 ) ); ?></span>
					<span class="mb-period">/ <?php esc_html_e( 'day', 'my-booking-engine' ); ?></span>
				</div>
			</div>

			<div class="mb-dates-input-box" id="mb-trigger-date-picker">
				<div class="mb-date-field">
					<label><?php esc_html_e( 'TRIP START', 'my-booking-engine' ); ?></label>
					<span class="mb-val" id="mb-display-checkin"><?php esc_html_e( 'Pick date', 'my-booking-engine' ); ?></span>
				</div>
				<div class="mb-date-field">
					<label><?php esc_html_e( 'TRIP END', 'my-booking-engine' ); ?></label>
					<span class="mb-val" id="mb-display-checkout"><?php esc_html_e( 'Pick date', 'my-booking-engine' ); ?></span>
				</div>
			</div>

			<div class="mb-rental-protection-box">
				<label><?php esc_html_e( 'Protection Plan', 'my-booking-engine' ); ?></label>
				<select class="mb-form-select" id="mb-protection-plan">
					<option value="standard"><?php esc_html_e( 'Standard Protection (Included)', 'my-booking-engine' ); ?></option>
					<option value="premium"><?php esc_html_e( 'Premium Full Coverage (+$15/day)', 'my-booking-engine' ); ?></option>
				</select>
			</div>

			<button type="button" class="mb-btn mb-btn-primary mb-btn-block mb-btn-reserve" id="mb-start-booking-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
				<?php esc_html_e( 'Continue to Book', 'my-booking-engine' ); ?>
			</button>

			<div class="mb-sidebar-badges">
				<div class="mb-badge-row"><span>✓</span> <?php esc_html_e( 'Free cancellation up to 24h prior', 'my-booking-engine' ); ?></div>
				<div class="mb-badge-row"><span>✓</span> <?php esc_html_e( 'Valid driver license required at pickup', 'my-booking-engine' ); ?></div>
			</div>
		</div>
	</div>
</div>
