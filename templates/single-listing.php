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
	$mb_entity = new BookingEntity( get_the_ID() );
	?>
	<div class="mb-marketplace-single mb-layout-<?php echo esc_attr( $mb_entity->get_visual_layout() ); ?>">
		<div class="mb-container">
			<?php TemplateLoader::render_entity_layout( $mb_entity ); ?>
		</div>
	</div>
	<?php
endwhile;

get_footer();
