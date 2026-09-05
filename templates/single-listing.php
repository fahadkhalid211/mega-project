<?php
/**
 * Single Listing Template Controller.
 *
 * @package MyBookingEngine
 */

use MyBookingEngine\Models\BookingEntity;
use MyBookingEngine\Presentation\TemplateLoader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$mb_entity      = new BookingEntity( get_the_ID() );
	$settings       = get_option( 'mb_engine_settings', array() );
	$currency_sym   = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
	$model_type     = $mb_entity->get_model_type();
	$price_period   = ( 'hourly_slot' === $model_type )
		? __( 'session', 'my-booking-engine' )
		: ( ( 'day_rental' === $model_type )
			? __( 'day', 'my-booking-engine' )
			: ( ( 'night_stay' === $model_type ) ? __( 'night', 'my-booking-engine' ) : __( 'ticket', 'my-booking-engine' ) ) );
	$rating_val     = $mb_entity->get_rating_average();
	$review_count   = $mb_entity->get_review_count();
	?>
	<div class="mb-marketplace-single mb-layout-<?php echo esc_attr( $mb_entity->get_visual_layout() ); ?>">
		<div class="mb-container">
			<?php TemplateLoader::render_entity_layout( $mb_entity ); ?>
		</div>

		<!-- Mobile Sticky Reservation Bar (Appears when scrolled past hero/gallery) -->
		<div class="mb-mobile-sticky-bar" id="mb-mobile-sticky-bar" data-entity-id="<?php echo esc_attr( get_the_ID() ); ?>" data-model="<?php echo esc_attr( $model_type ); ?>">
			<div class="mb-mobile-bar-inner">
				<div class="mb-mobile-bar-price-wrap">
					<div class="mb-mobile-price-line">
						<span class="mb-mobile-price-amount"><?php echo esc_html( $currency_sym . number_format( $mb_entity->get_base_price(), 2 ) ); ?></span>
						<span class="mb-mobile-price-period">/ <?php echo esc_html( $price_period ); ?></span>
					</div>
					<div class="mb-mobile-rating-snippet">
						<span class="mb-star" style="color:#f59e0b;">★</span> <?php echo esc_html( number_format( $rating_val, 1 ) ); ?>
						<span class="mb-mobile-revs">(<?php echo esc_html( $review_count ); ?>)</span>
					</div>
				</div>

				<button type="button" class="mb-btn mb-btn-primary mb-mobile-book-btn" id="mb-mobile-book-btn">
					<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
					<span><?php esc_html_e( 'Book Now', 'my-booking-engine' ); ?></span>
				</button>
			</div>
		</div>
	</div>
	<?php
endwhile;

get_footer();
