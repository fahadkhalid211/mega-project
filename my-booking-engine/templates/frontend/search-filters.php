<?php
/**
 * Frontend Search & Filter Bar Template.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings       = get_option( 'mb_engine_settings', array() );
$unit           = isset( $settings['distance_unit'] ) ? $settings['distance_unit'] : 'km';
$default_radius = isset( $settings['search_default_rad'] ) ? $settings['search_default_rad'] : 25;
$currency_sym   = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';

$terms = get_terms(
	array(
		'taxonomy'   => 'mb_entity_type',
		'hide_empty' => false,
	)
);
?>

<div class="mb-search-container" id="mb-search-app">
	<form class="mb-filter-form" id="mb-filter-form" onsubmit="return false;">
		<div class="mb-filter-row mb-filter-primary">
			<!-- Postal Code / Location Input -->
			<div class="mb-filter-col mb-col-location">
				<label for="mb_search_postal"><?php esc_html_e( 'Enter Postal / ZIP Code or City', 'my-booking-engine' ); ?></label>
				<div class="mb-input-with-icon">
					<svg class="mb-icon" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
					<input type="text" id="mb_search_postal" name="postal_code" placeholder="<?php esc_attr_e( 'e.g. 90210, SW1A 1AA, Paris...', 'my-booking-engine' ); ?>" autocomplete="postal-code">
				</div>
			</div>

			<!-- Category Filter -->
			<div class="mb-filter-col mb-col-type">
				<label for="mb_search_type"><?php esc_html_e( 'Category / Type', 'my-booking-engine' ); ?></label>
				<select id="mb_search_type" name="type">
					<option value=""><?php esc_html_e( 'All Categories', 'my-booking-engine' ); ?></option>
					<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
						<?php foreach ( $terms as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</div>

			<!-- Booking Engine Model -->
			<div class="mb-filter-col mb-col-model">
				<label for="mb_search_model"><?php esc_html_e( 'Booking Model', 'my-booking-engine' ); ?></label>
				<select id="mb_search_model" name="model">
					<option value=""><?php esc_html_e( 'All Booking Types', 'my-booking-engine' ); ?></option>
					<option value="hotel_room"><?php esc_html_e( 'Hotels & Accommodations', 'my-booking-engine' ); ?></option>
					<option value="daily_booking"><?php esc_html_e( 'Daily Rentals (Cars/Gear)', 'my-booking-engine' ); ?></option>
					<option value="hourly_booking"><?php esc_html_e( 'Hourly Appointments', 'my-booking-engine' ); ?></option>
					<option value="doctor_professional"><?php esc_html_e( 'Medical & Professional', 'my-booking-engine' ); ?></option>
					<option value="salon_spa"><?php esc_html_e( 'Salon & Wellness', 'my-booking-engine' ); ?></option>
					<option value="shop_business"><?php esc_html_e( 'Local Businesses & Venues', 'my-booking-engine' ); ?></option>
				</select>
			</div>

			<!-- Search Button -->
			<div class="mb-filter-col mb-col-btn">
				<button type="submit" class="mb-btn mb-btn-primary" id="mb-btn-submit-search">
					<svg class="mb-icon" viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
					<?php esc_html_e( 'Find Available', 'my-booking-engine' ); ?>
				</button>
			</div>
		</div>

		<!-- Secondary Expandable Filters (Radius Slider & Price Range) -->
		<div class="mb-filter-row mb-filter-secondary">
			<!-- Radius Slider -->
			<div class="mb-filter-col mb-col-radius">
				<div class="mb-radius-header">
					<label for="mb_search_radius"><?php esc_html_e( 'Distance Radius:', 'my-booking-engine' ); ?></label>
					<span class="mb-radius-val" id="mb_radius_val"><?php echo esc_html( $default_radius . ' ' . $unit ); ?></span>
				</div>
				<input type="range" id="mb_search_radius" name="radius" min="2" max="150" value="<?php echo esc_attr( $default_radius ); ?>" class="mb-slider">
			</div>

			<!-- Price Filter -->
			<div class="mb-filter-col mb-col-price">
				<label><?php esc_html_e( 'Price Range', 'my-booking-engine' ); ?></label>
				<div class="mb-price-inputs">
					<input type="number" id="mb_search_min_price" name="min_price" placeholder="<?php echo esc_attr( $currency_sym ); ?> Min" min="0" step="1">
					<span>-</span>
					<input type="number" id="mb_search_max_price" name="max_price" placeholder="<?php echo esc_attr( $currency_sym ); ?> Max" min="0" step="1">
				</div>
			</div>

			<!-- View Switcher (Grid / Map) -->
			<div class="mb-filter-col mb-col-view-switch">
				<label><?php esc_html_e( 'Display View', 'my-booking-engine' ); ?></label>
				<button type="button" class="mb-btn mb-btn-outline mb-btn-sm" id="mb-toggle-map-btn" data-mode="grid">
					🗺️ <?php esc_html_e( 'Show Map', 'my-booking-engine' ); ?>
				</button>
			</div>

			<!-- Reset Action -->
			<div class="mb-filter-col mb-col-reset">
				<button type="button" class="mb-btn-link" id="mb-btn-reset-filters"><?php esc_html_e( 'Reset Filters', 'my-booking-engine' ); ?></button>
			</div>
		</div>
	</form>

	<!-- Live Results Status Banner -->
	<div class="mb-results-status" id="mb-results-status"></div>

	<!-- Marketplace Split View Container -->
	<div class="mb-results-wrapper" id="mb-results-wrapper">
		<!-- Results Grid -->
		<div class="mb-entities-grid" id="mb-entities-results">
			<div class="mb-loading-state" id="mb-loading-state" style="display:none;">
				<div class="mb-spinner"></div>
				<p><?php esc_html_e( 'Searching available booking options...', 'my-booking-engine' ); ?></p>
			</div>
		</div>

		<!-- Interactive Leaflet Map -->
		<div class="mb-directory-map-col" id="mb-directory-map-col" style="display:none;">
			<div id="mb-directory-map" class="mb-directory-map"></div>
		</div>
	</div>

	<!-- Modal Container -->
	<div id="mb-booking-drawer" class="mb-drawer-overlay" style="display:none;">
		<div class="mb-drawer-content" id="mb-drawer-inner">
			<!-- Populated via AJAX / Frontend JS -->
		</div>
	</div>
</div>
