<?php
/**
 * Archive Listing Template.
 *
 * @package MyBookingEngine
 */

use MyBookingEngine\Models\BookingEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="mb-marketplace-archive">
	<div class="mb-container">
		<div class="mb-archive-header">
			<h1 class="mb-archive-title"><?php esc_html_e( 'Explore Bookings & Experiences', 'my-booking-engine' ); ?></h1>
			<p class="mb-archive-subtitle"><?php esc_html_e( 'Find and reserve top-rated stays, rentals, appointments, and services worldwide.', 'my-booking-engine' ); ?></p>
		</div>

		<?php echo do_shortcode( '[mb_search_filter show_map="yes"]' ); ?>
	</div>
</div>

<?php
get_footer();
