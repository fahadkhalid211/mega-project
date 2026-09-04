<?php
/**
 * Doctor / Medical & Professional Listing Layout.
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
$services     = $entity->get_services();
$staff        = $entity->get_staff();
$doctor       = ! empty( $staff ) ? $staff[0] : null;

$settings     = get_option( 'mb_engine_settings', array() );
$currency_sym = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
$loc_text     = $loc ? esc_html( trim( $loc->city . ', ' . $loc->postal_code . ' ' . $loc->country_code, ', ' ) ) : esc_html__( 'Clinic address provided upon booking', 'my-booking-engine' );
?>

<div class="mb-doctor-hero-card">
	<div class="mb-doctor-avatar-wrap">
		<img src="<?php echo esc_url( $entity->get_thumbnail_url( 'medium' ) ); ?>" alt="<?php echo esc_attr( $entity->get_title() ); ?>" class="mb-doctor-avatar" />
		<span class="mb-verified-badge" title="<?php esc_attr_e( 'Verified Professional', 'my-booking-engine' ); ?>">✓</span>
	</div>
	<div class="mb-doctor-info">
		<div class="mb-doctor-title-row">
			<h1 class="mb-doctor-name"><?php echo esc_html( $entity->get_title() ); ?></h1>
			<span class="mb-rating-badge">
				<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?>
				<a href="#mb-reviews-section" class="mb-review-count-link">(<?php echo esc_html( $review_count ); ?> <?php esc_html_e( 'patient reviews', 'my-booking-engine' ); ?>)</a>
			</span>
		</div>
		<p class="mb-doctor-specialty"><?php echo esc_html( ! empty( $doctor['role'] ) ? $doctor['role'] : __( 'Certified Specialist', 'my-booking-engine' ) ); ?></p>
		<p class="mb-doctor-clinic">🏥 <?php echo esc_html( $loc_text ); ?></p>
		<div class="mb-doctor-tags">
			<span class="mb-tag">🩺 <?php esc_html_e( 'In-Person & Telehealth', 'my-booking-engine' ); ?></span>
			<span class="mb-tag">⚡ <?php esc_html_e( 'Instant Confirmation', 'my-booking-engine' ); ?></span>
			<span class="mb-tag">🛡️ <?php esc_html_e( 'HIPAA & Privacy Compliant', 'my-booking-engine' ); ?></span>
		</div>
	</div>
</div>

<div class="mb-layout-columns">
	<!-- Left Column -->
	<div class="mb-column-main">
		<!-- Consultation Types / Services -->
		<div class="mb-section mb-consultation-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Consultation Options', 'my-booking-engine' ); ?></h2>
			<div class="mb-services-list">
				<?php foreach ( $services as $idx => $svc ) : ?>
					<div class="mb-service-item <?php echo ( 0 === $idx ) ? 'mb-service-selected' : ''; ?>" data-service-id="<?php echo esc_attr( isset( $svc['id'] ) ? $svc['id'] : $idx ); ?>" data-price="<?php echo esc_attr( isset( $svc['price'] ) ? $svc['price'] : $base_price ); ?>">
						<div class="mb-service-info">
							<h3 class="mb-service-title"><?php echo esc_html( isset( $svc['name'] ) ? $svc['name'] : $entity->get_title() ); ?></h3>
							<p class="mb-service-desc"><?php echo esc_html( isset( $svc['description'] ) ? $svc['description'] : __( 'Comprehensive consultation and assessment.', 'my-booking-engine' ) ); ?></p>
							<span class="mb-service-meta">⏱️ <?php echo esc_html( isset( $svc['duration'] ) ? $svc['duration'] : $entity->get_slot_duration() ); ?> <?php esc_html_e( 'mins', 'my-booking-engine' ); ?></span>
						</div>
						<div class="mb-service-action">
							<span class="mb-service-cost"><?php echo esc_html( $currency_sym . number_format( isset( $svc['price'] ) ? $svc['price'] : $base_price, 2 ) ); ?></span>
							<button type="button" class="mb-btn mb-btn-sm mb-btn-outline mb-select-service-btn"><?php esc_html_e( 'Select', 'my-booking-engine' ); ?></button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- About Practitioner -->
		<div class="mb-section mb-bio-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'About the Practitioner', 'my-booking-engine' ); ?></h2>
			<div class="mb-rich-text">
				<?php the_content(); ?>
			</div>
		</div>

		<!-- Qualifications & Features -->
		<div class="mb-section mb-amenities-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Credentials & Clinic Facilities', 'my-booking-engine' ); ?></h2>
			<div class="mb-amenities-grid">
				<?php foreach ( $amenities as $amenity ) : ?>
					<div class="mb-amenity-pill">
						<span class="mb-amenity-check">✓</span>
						<span class="mb-amenity-name"><?php echo esc_html( $amenity ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Clinic Location Map -->
		<?php if ( $loc ) : ?>
			<div class="mb-section mb-map-section">
				<h2 class="mb-section-title"><?php esc_html_e( 'Clinic Location', 'my-booking-engine' ); ?></h2>
				<p class="mb-map-address">📍 <?php echo esc_html( $loc_text ); ?></p>
				<div id="mb-single-map" class="mb-map-canvas" data-lat="<?php echo esc_attr( $loc->latitude ); ?>" data-lng="<?php echo esc_attr( $loc->longitude ); ?>" data-title="<?php echo esc_attr( $entity->get_title() ); ?>"></div>
			</div>
		<?php endif; ?>

		<!-- Patient Reviews -->
		<div id="mb-reviews-section" class="mb-section mb-reviews-section">
			<div class="mb-reviews-header">
				<h2 class="mb-section-title">
					<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?> • <?php echo esc_html( $review_count ); ?> <?php esc_html_e( 'verified patient reviews', 'my-booking-engine' ); ?>
				</h2>
				<button type="button" class="mb-btn mb-btn-secondary mb-btn-sm" id="mb-write-review-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
					<?php esc_html_e( 'Leave Review', 'my-booking-engine' ); ?>
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
					<p class="mb-no-reviews"><?php esc_html_e( 'No patient reviews yet.', 'my-booking-engine' ); ?></p>
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
					<span class="mb-period">/ <?php esc_html_e( 'consultation', 'my-booking-engine' ); ?></span>
				</div>
			</div>

			<div class="mb-doctor-summary-box">
				<p>👨‍⚕️ <strong><?php echo esc_html( $entity->get_title() ); ?></strong></p>
				<p class="mb-text-muted">⏱️ <?php echo esc_html( $entity->get_slot_duration() ); ?> <?php esc_html_e( 'min appointment', 'my-booking-engine' ); ?></p>
			</div>

			<button type="button" class="mb-btn mb-btn-primary mb-btn-block mb-btn-reserve" id="mb-start-booking-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
				<?php esc_html_e( 'Book Appointment', 'my-booking-engine' ); ?>
			</button>

			<div class="mb-sidebar-badges">
				<div class="mb-badge-row"><span>✓</span> <?php esc_html_e( 'No cancellation fee up to 24h prior', 'my-booking-engine' ); ?></div>
				<div class="mb-badge-row"><span>✓</span> <?php esc_html_e( 'Direct confirmation with clinic', 'my-booking-engine' ); ?></div>
				<div class="mb-badge-row"><span>✓</span> <?php esc_html_e( 'Electronic medical intake form', 'my-booking-engine' ); ?></div>
			</div>
		</div>
	</div>
</div>
