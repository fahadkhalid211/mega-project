<?php
/**
 * Shop / Local Business & Venue Listing Layout.
 *
 * @package MyBookingEngine
 */

use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var BookingEntity $entity */
$post_id        = $entity->get_id();
$loc            = $entity->get_location();
$gallery        = $entity->get_gallery_images( 'large' );
$amenities      = $entity->get_amenities();
$rating         = $entity->get_rating_average();
$review_count   = $entity->get_review_count();
$reviews        = $entity->get_reviews( 6 );
$faqs           = $entity->get_faqs();
$availabilities = $entity->get_availabilities();
$base_price     = $entity->get_base_price();

$settings       = get_option( 'mb_engine_settings', array() );
$currency_sym   = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
$loc_text       = $loc ? esc_html( trim( $loc->city . ', ' . $loc->postal_code . ' ' . $loc->country_code, ', ' ) ) : esc_html__( 'Location', 'my-booking-engine' );

$day_names = array(
	1 => __( 'Monday', 'my-booking-engine' ),
	2 => __( 'Tuesday', 'my-booking-engine' ),
	3 => __( 'Wednesday', 'my-booking-engine' ),
	4 => __( 'Thursday', 'my-booking-engine' ),
	5 => __( 'Friday', 'my-booking-engine' ),
	6 => __( 'Saturday', 'my-booking-engine' ),
	7 => __( 'Sunday', 'my-booking-engine' ),
);
?>

<div class="mb-listing-header">
	<div class="mb-header-main">
		<span class="mb-tag mb-tag-shop">🏪 <?php esc_html_e( 'Local Business & Venue', 'my-booking-engine' ); ?></span>
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

<div class="mb-shop-mosaic">
	<?php if ( ! empty( $gallery ) ) : ?>
		<div class="mb-gallery-featured">
			<img src="<?php echo esc_url( $gallery[0] ); ?>" alt="<?php echo esc_attr( $entity->get_title() ); ?>" />
		</div>
		<?php if ( count( $gallery ) > 1 ) : ?>
			<div class="mb-gallery-thumbs">
				<?php for ( $i = 1; $i < min( 5, count( $gallery ) ); $i++ ) : ?>
					<div class="mb-thumb-item">
						<img src="<?php echo esc_url( $gallery[ $i ] ); ?>" alt="<?php echo esc_attr( $entity->get_title() ); ?>" />
					</div>
				<?php endfor; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>

<div class="mb-layout-columns">
	<!-- Left Column -->
	<div class="mb-column-main">
		<!-- Business Overview -->
		<div class="mb-section mb-description-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'About Our Venue', 'my-booking-engine' ); ?></h2>
			<div class="mb-rich-text">
				<?php the_content(); ?>
			</div>
		</div>

		<!-- Weekly Schedule Table -->
		<div class="mb-section mb-hours-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Operating Hours', 'my-booking-engine' ); ?></h2>
			<div class="mb-hours-table">
				<?php foreach ( $day_names as $day_num => $day_label ) : ?>
					<?php
					$rule_found = null;
					foreach ( $availabilities as $rule ) {
						if ( (int) $rule->day_of_week === $day_num ) {
							$rule_found = $rule;
							break;
						}
					}
					?>
					<div class="mb-hour-row">
						<span class="mb-day-label"><?php echo esc_html( $day_label ); ?></span>
						<span class="mb-day-time">
							<?php if ( $rule_found ) : ?>
								<?php echo esc_html( substr( $rule_found->start_time, 0, 5 ) . ' - ' . substr( $rule_found->end_time, 0, 5 ) ); ?>
							<?php else : ?>
								<em class="mb-text-muted"><?php esc_html_e( 'Closed / By Appointment', 'my-booking-engine' ); ?></em>
							<?php endif; ?>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Venue Facilities -->
		<div class="mb-section mb-amenities-section">
			<h2 class="mb-section-title"><?php esc_html_e( 'Facilities & Services', 'my-booking-engine' ); ?></h2>
			<div class="mb-amenities-grid">
				<?php foreach ( $amenities as $amenity ) : ?>
					<div class="mb-amenity-pill">
						<span class="mb-amenity-check">✓</span>
						<span class="mb-amenity-name"><?php echo esc_html( $amenity ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Location Map -->
		<?php if ( $loc ) : ?>
			<div class="mb-section mb-map-section">
				<h2 class="mb-section-title"><?php esc_html_e( 'How to find us', 'my-booking-engine' ); ?></h2>
				<p class="mb-map-address">📍 <?php echo esc_html( $loc_text ); ?></p>
				<div id="mb-single-map" class="mb-map-canvas" data-lat="<?php echo esc_attr( $loc->latitude ); ?>" data-lng="<?php echo esc_attr( $loc->longitude ); ?>" data-title="<?php echo esc_attr( $entity->get_title() ); ?>"></div>
			</div>
		<?php endif; ?>

		<!-- Reviews -->
		<div id="mb-reviews-section" class="mb-section mb-reviews-section">
			<div class="mb-reviews-header">
				<h2 class="mb-section-title">
					<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?> • <?php echo esc_html( $review_count ); ?> <?php esc_html_e( 'customer reviews', 'my-booking-engine' ); ?>
				</h2>
				<button type="button" class="mb-btn mb-btn-secondary mb-btn-sm" id="mb-write-review-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
					<?php esc_html_e( 'Review Venue', 'my-booking-engine' ); ?>
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

	<!-- Right Column -->
	<div class="mb-column-sidebar">
		<div class="mb-sticky-card">
			<h3 class="mb-sidebar-title"><?php esc_html_e( 'Reserve Space / Visit', 'my-booking-engine' ); ?></h3>
			<?php if ( $base_price > 0 ) : ?>
				<div class="mb-card-price-row">
					<div class="mb-price-tag">
						<span class="mb-currency"><?php echo esc_html( $currency_sym ); ?></span>
						<span class="mb-amount"><?php echo esc_html( number_format( $base_price, 2 ) ); ?></span>
						<span class="mb-period"><?php esc_html_e( 'starting rate', 'my-booking-engine' ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<div class="mb-venue-action-box">
				<button type="button" class="mb-btn mb-btn-primary mb-btn-block mb-btn-reserve" id="mb-start-booking-btn" data-entity-id="<?php echo esc_attr( $post_id ); ?>">
					<?php esc_html_e( 'Book Visit / Reserve', 'my-booking-engine' ); ?>
				</button>
			</div>

			<div class="mb-sidebar-badges">
				<div class="mb-badge-row"><span>📞</span> <?php esc_html_e( 'Direct inquiry support', 'my-booking-engine' ); ?></div>
				<div class="mb-badge-row"><span>🅿️</span> <?php esc_html_e( 'Customer parking available', 'my-booking-engine' ); ?></div>
				<div class="mb-badge-row"><span>♿</span> <?php esc_html_e( 'Wheelchair accessible entrance', 'my-booking-engine' ); ?></div>
			</div>
		</div>
	</div>
</div>
