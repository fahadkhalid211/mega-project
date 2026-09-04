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
	$entity = new BookingEntity( get_the_ID() );
	?>
	<div class="mb-marketplace-single mb-layout-<?php echo esc_attr( $entity->get_visual_layout() ); ?>">
		<div class="mb-container">
			<?php TemplateLoader::render_entity_layout( $entity ); ?>
		</div>
	</div>
	<?php
endwhile;

get_footer();
