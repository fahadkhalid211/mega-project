<?php
/**
 * Salon, Spa & Beauty Listing Layout (Fresha style).
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
$services     = $entity->get_services();
$staff        = $entity->get_staff();
$base_price   = $entity->get_base_price();

$settings     = get_option( 'mb_engine_settings', array() );
$currency_sym = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
$loc_text     = $loc ? esc_html( trim( $loc->city . ', ' . $loc->postal_code . ' ' . $loc->country_code, ', ' ) ) : esc_html__( 'Salon address', 'my-booking-engine' );
?>

<div class="mb-salon-hero" style="background-image: url('<?php echo esc_url( ! empty( $gallery ) ? $gallery[0] : $entity->get_thumbnail_url() ); ?>');">
	<div class="mb-salon-hero-overlay">
		<div class="mb-salon-hero-content">
			<span class="mb-tag mb-tag-glass">✨ <?php esc_html_e( 'Salon & Wellness Studio', 'my-booking-engine' ); ?></span>
			<h1 class="mb-salon-title"><?php echo esc_html( $entity->get_title() ); ?></h1>
			<div class="mb-salon-meta">
				<span class="mb-rating-badge mb-badge-light">
					<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?> (<?php echo esc_html( $review_count ); ?>)
				</span>
				<span class="mb-meta-divider">•</span>
				<span>📍 <?php echo esc_html( $loc_text ); ?></span>
			</div>
		</div>
	</div>
</div>

<div class="mb-layout-columns">
	<!-- Left Column: Services & Stylists -->
	<div class="mb-column-main">
		<!-- Team / Specialists Carousel -->
		<?php if ( ! empty( $staff ) ) : ?>
			<div class="mb-section mb-staff-section">
				<h2 class="mb-section-title"><?php esc_html_e( 'Choose a Specialist', 'my-booking-engine' ); ?></h2>
				<div class="mb-staff-grid">
					<div class="mb-staff-card mb-staff-active" data-staff-id="any">
						<div class="mb-avatar-placeholder">👥</div>
						<h4><?php esc_html_e( 'Any Specialist', 'my-booking-engine' ); ?></h4>
						<p class="mb-text-muted"><?php esc_html_e( 'Maximum availability', 'my-booking-engine' ); ?></p>
					</div>
					<?php foreach ( $staff as $person ) : ?>
						<div class="mb-staff-card" data-staff-id="<?php echo esc_attr( isset( $person['id'] ) ? $person['id'] : sanitize_title( $person['name'] ) ); ?>">
							<?php if ( ! empty( $person['image'] ) ) : ?>
								<img src="<?php echo esc_url( $person['image'] ); ?>" alt="<?php echo esc_attr( $person['name'] ); ?>" class="mb-staff-img" />
							<?php else : ?>
								<div class="mb-avatar-placeholder"><?php echo esc_html( strtoupper( substr( $person['name'], 0, 1 ) ) ); ?></div>
							<?php endif; ?>
							<h4><?php echo esc_html( $person['name'] ); ?></h4>
							<p class="mb-text-muted"><?php echo esc_html( isset( $person['role'] ) ? $person['role'] : __( 'Stylist', 'my-booking-engine' ) ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Services Menu -->
		<div class="mb-section mb-menu-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Service Menu', 'my-booking-engine' ); ?></h2>
			<div class="mb-services-list">
				<?php foreach ( $services as $idx => $svc ) : ?>
					<div class="mb-salon-service-row" data-service-id="<?php echo esc_attr( isset( $svc['id'] ) ? $svc['id'] : $idx ); ?>" data-price="<?php echo esc_attr( isset( $svc['price'] ) ? $svc['price'] : $base_price ); ?>" data-name="<?php echo esc_attr( isset( $svc['name'] ) ? $svc['name'] : $entity->get_title() ); ?>">
						<div class="mb-service-details">
							<h3 class="mb-service-title"><?php echo esc_html( isset( $svc['name'] ) ? $svc['name'] : $entity->get_title() ); ?></h3>
							<p class="mb-service-desc"><?php echo esc_html( isset( $svc['description'] ) ? $svc['description'] : __( 'Premium beauty treatment with certified products.', 'my-booking-engine' ) ); ?></p>
							<span class="mb-service-meta">⏱️ <?php echo esc_html( isset( $svc['duration'] ) ? $svc['duration'] : $entity->get_slot_duration() ); ?> <?php esc_html_e( 'mins', 'my-booking-engine' ); ?></span>
						</div>
						<div class="mb-service-price-box">
							<span class="mb-price-val"><?php echo esc_html( $currency_sym . number_format( isset( $svc['price'] ) ? $svc['price'] : $base_price, 2 ) ); ?></span>
							<button type="button" class="mb-btn mb-btn-sm mb-btn-outline mb-add-service-btn"><?php esc_html_e( '+ Book', 'my-booking-engine' ); ?></button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- About / Gallery -->
		<div class="mb-section mb-description-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'About the Salon', 'my-booking-engine' ); ?></h2>
			<div class="mb-rich-text">
				<?php the_content(); ?>
			</div>
		</div>

		<!-- Map -->
		<?php if ( $loc ) : ?>
			<div class="mb-section mb-map-section">
				<h2 class="mb-section-title"><?php esc_html_e( 'Location & Parking', 'my-booking-engine' ); ?></h2>
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

	<!-- Right Sticky Column: Order Summary -->
	<div class="mb-column-sidebar">
		<div class="mb-sticky-card">
			<h3 class="mb-sidebar-title"><?php esc_html_e( 'Your Appointment', 'my-booking-engine' ); ?></h3>
			<div class="mb-selected-services-tray" id="mb-selected-services-tray">
				<p class="mb-text-muted" id="mb-no-services-prompt"><?php esc_html_e( 'Select a service from the menu to get started.', 'my-booking-engine' ); ?></p>
				<ul class="mb-selected-services-list" id="mb-selected-list" style="display:none;"></ul>
			</div>

			<div class="mb-salon-total-row" id="mb-salon-total-row" style="display:none;">
				<span><?php esc_html_e( 'Total', 'my-booking-engine' ); ?></span>
				<strong id="mb-salon-total-price"><?php echo esc_html( $currency_sym ); ?>0.00</strong>
			</div>

			<button type="button" class="mb-btn mb-btn-primary mb-btn-block mb-btn-reserve" id="mb-start-booking-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>" data-model="<?php echo esc_attr( $entity->get_model_type() ); ?>">
				<?php esc_html_e( 'Select Date & Time', 'my-booking-engine' ); ?>
			</button>
		</div>
	</div>
</div>
