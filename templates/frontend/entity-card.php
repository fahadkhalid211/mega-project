<?php
/**
 * Frontend Entity Card Template.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$entity_id = isset( $entity_id ) ? absint( $entity_id ) : get_the_ID();
$entity    = new \MyBookingEngine\Models\BookingEntity( $entity_id );
$model     = $entity->get_model_type();
$layout    = $entity->get_visual_layout();
$price     = $entity->get_base_price();
$loc       = $entity->get_location();
$thumb     = $entity->get_thumbnail_url( 'medium' );
$gallery   = $entity->get_gallery_images( 'medium' );
$rating    = $entity->get_rating_average();
$rev_count = $entity->get_review_count();

if ( empty( $gallery ) ) {
	$gallery = array( $thumb );
}
$has_multiple = count( $gallery ) > 1;

$settings     = get_option( 'mb_engine_settings', array() );
$currency_sym = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';

$model_labels = array(
	'hotel_room'          => __( 'Hotel / Stay', 'my-booking-engine' ),
	'night_stay'          => __( 'Hotel / Stay', 'my-booking-engine' ),
	'daily_booking'       => __( 'Daily Rental', 'my-booking-engine' ),
	'day_rental'          => __( 'Daily Rental', 'my-booking-engine' ),
	'hourly_booking'      => __( 'Hourly Session', 'my-booking-engine' ),
	'hourly_slot'         => __( 'Hourly Slot', 'my-booking-engine' ),
	'doctor_professional' => __( 'Medical / Clinic', 'my-booking-engine' ),
	'salon_spa'           => __( 'Salon & Spa', 'my-booking-engine' ),
	'shop_business'       => __( 'Local Venue', 'my-booking-engine' ),
	'capacity_roster'     => __( 'Event / Tour', 'my-booking-engine' ),
);

$price_unit_labels = array(
	'hotel_room'          => __( '/ night', 'my-booking-engine' ),
	'night_stay'          => __( '/ night', 'my-booking-engine' ),
	'daily_booking'       => __( '/ day', 'my-booking-engine' ),
	'day_rental'          => __( '/ day', 'my-booking-engine' ),
	'hourly_booking'      => __( '/ hour', 'my-booking-engine' ),
	'hourly_slot'         => __( '/ slot', 'my-booking-engine' ),
	'doctor_professional' => __( '/ visit', 'my-booking-engine' ),
	'salon_spa'           => __( '/ service', 'my-booking-engine' ),
	'shop_business'       => __( '/ booking', 'my-booking-engine' ),
	'capacity_roster'     => __( '/ person', 'my-booking-engine' ),
);
?>

<div class="mb-card-item mb-card-<?php echo esc_attr( $layout ); ?>" data-entity-id="<?php echo esc_attr( $entity_id ); ?>" data-model="<?php echo esc_attr( $model ); ?>" data-layout="<?php echo esc_attr( $layout ); ?>" data-lat="<?php echo ( $loc && 0.0 !== floatval( $loc->latitude ) ) ? esc_attr( $loc->latitude ) : ''; ?>" data-lng="<?php echo ( $loc && 0.0 !== floatval( $loc->longitude ) ) ? esc_attr( $loc->longitude ) : ''; ?>">
	<div class="mb-card-thumb-wrap">
		<div class="mb-card-slider" data-current="0" data-total="<?php echo esc_attr( count( $gallery ) ); ?>">
			<div class="mb-card-slider-track">
				<?php foreach ( $gallery as $img_url ) : ?>
					<div class="mb-card-slide">
						<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( get_the_title( $entity_id ) ); ?>" class="mb-card-thumb" loading="lazy">
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $has_multiple ) : ?>
				<button type="button" class="mb-card-arrow mb-card-arrow-prev" aria-label="<?php esc_attr_e( 'Previous photo', 'my-booking-engine' ); ?>">
					<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
				</button>
				<button type="button" class="mb-card-arrow mb-card-arrow-next" aria-label="<?php esc_attr_e( 'Next photo', 'my-booking-engine' ); ?>">
					<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
				</button>
				<div class="mb-card-slider-dots">
					<?php foreach ( $gallery as $idx => $img_url ) : ?>
						<span class="mb-slider-dot<?php echo 0 === $idx ? ' is-active' : ''; ?>" data-slide="<?php echo esc_attr( $idx ); ?>"></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<a href="<?php echo esc_url( get_permalink( $entity_id ) ); ?>" class="mb-card-link-overlay" aria-label="<?php echo esc_attr( get_the_title( $entity_id ) ); ?>"></a>

		<span class="mb-badge mb-badge-model mb-badge-<?php echo esc_attr( $layout ); ?>">
			<?php echo esc_html( isset( $model_labels[ $model ] ) ? $model_labels[ $model ] : ucfirst( $model ) ); ?>
		</span>
		<?php if ( isset( $distance_text ) && ! empty( $distance_text ) ) : ?>
			<span class="mb-badge mb-badge-distance">
				<svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
				<?php echo esc_html( $distance_text . ' ' . __( 'away', 'my-booking-engine' ) ); ?>
			</span>
		<?php endif; ?>
	</div>

	<div class="mb-card-body">
		<div class="mb-card-rating-row">
			<span class="mb-card-rating">
				<span class="mb-star">★</span> <?php echo esc_html( number_format( $rating, 1 ) ); ?>
				<span class="mb-card-rev-count">(<?php echo esc_html( $rev_count ); ?>)</span>
			</span>
			<?php if ( $loc && ( ! empty( $loc->city ) || ! empty( $loc->postal_code ) ) ) : ?>
				<span class="mb-card-location">
					📍 <?php echo esc_html( trim( "{$loc->city}, {$loc->postal_code}" ) ); ?>
				</span>
			<?php endif; ?>
		</div>

		<h3 class="mb-card-title">
			<a href="<?php echo esc_url( get_permalink( $entity_id ) ); ?>"><?php echo esc_html( get_the_title( $entity_id ) ); ?></a>
		</h3>

		<?php
		$excerpt_clean = wp_trim_words( get_the_excerpt( $entity_id ), 8, '...' );
		if ( empty( $excerpt_clean ) ) {
			$excerpt_clean = wp_trim_words( get_post_field( 'post_content', $entity_id ), 8, '...' );
		}
		if ( ! empty( $excerpt_clean ) ) :
			?>
			<div class="mb-card-excerpt">
				<?php echo esc_html( $excerpt_clean ); ?>
			</div>
		<?php endif; ?>

		<div class="mb-card-footer">
			<div class="mb-card-price">
				<span class="mb-price-amount"><?php echo esc_html( $currency_sym . number_format( $price, 2 ) ); ?></span>
				<span class="mb-price-unit"><?php echo esc_html( isset( $price_unit_labels[ $model ] ) ? $price_unit_labels[ $model ] : '' ); ?></span>
			</div>
			<a href="<?php echo esc_url( get_permalink( $entity_id ) ); ?>" class="mb-btn mb-btn-book">
				<?php esc_html_e( 'View Details', 'my-booking-engine' ); ?>
			</a>
		</div>
	</div>
</div>

