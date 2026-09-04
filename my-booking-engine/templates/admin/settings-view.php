<?php
/**
 * Admin Settings Template.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_distance_unit     = isset( $settings['distance_unit'] ) ? $settings['distance_unit'] : 'km';
$mb_geocoder_provider = isset( $settings['geocoder_provider'] ) ? $settings['geocoder_provider'] : 'nominatim';
$mb_google_api_key    = isset( $settings['google_api_key'] ) ? $settings['google_api_key'] : '';
$mb_lock_duration     = isset( $settings['lock_duration'] ) ? $settings['lock_duration'] : 10;
$mb_enable_wc         = isset( $settings['enable_woocommerce'] ) ? $settings['enable_woocommerce'] : 'yes';
$mb_currency_symbol   = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
$mb_default_radius    = isset( $settings['search_default_rad'] ) ? $settings['search_default_rad'] : 25;

$mb_primary_color     = isset( $settings['primary_color'] ) ? $settings['primary_color'] : '#2563eb';
$mb_primary_hover     = isset( $settings['primary_hover'] ) ? $settings['primary_hover'] : '#1d4ed8';
$mb_accent_color      = isset( $settings['accent_color'] ) ? $settings['accent_color'] : '#f59e0b';
$mb_border_radius     = isset( $settings['border_radius'] ) ? $settings['border_radius'] : 8;
?>

<div class="wrap mb-settings-wrap">
	<h1><?php esc_html_e( 'Booking Engine Settings', 'my-booking-engine' ); ?></h1>
	<hr class="wp-header-end">

	<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully.', 'my-booking-engine' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="mb-settings-grid">
		<!-- Left: Main Settings Form -->
		<div class="mb-settings-main">
			<form method="post" action="options.php">
				<?php settings_fields( 'mb_engine_settings_group' ); ?>

				<div class="mb-card">
					<h2><?php esc_html_e( '1. Geolocation & Spatial Search', 'my-booking-engine' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="mb_geocoder_provider"><?php esc_html_e( 'Geocoding Service', 'my-booking-engine' ); ?></label></th>
							<td>
								<select name="mb_engine_settings[geocoder_provider]" id="mb_geocoder_provider" class="regular-text">
									<option value="nominatim" <?php selected( $mb_geocoder_provider, 'nominatim' ); ?>><?php esc_html_e( 'OpenStreetMap (Nominatim) - Free / Zero API Key Required', 'my-booking-engine' ); ?></option>
									<option value="google" <?php selected( $mb_geocoder_provider, 'google' ); ?>><?php esc_html_e( 'Google Maps Geocoding API', 'my-booking-engine' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Nominatim works out of the box with zero setup. Select Google Maps if you possess a Google Cloud API key.', 'my-booking-engine' ); ?></p>
							</td>
						</tr>
						<tr id="mb_google_key_row" style="<?php echo ( 'google' === $mb_geocoder_provider ) ? '' : 'display:none;'; ?>">
							<th scope="row"><label for="mb_google_api_key"><?php esc_html_e( 'Google Maps API Key', 'my-booking-engine' ); ?></label></th>
							<td>
								<input type="password" name="mb_engine_settings[google_api_key]" id="mb_google_api_key" value="<?php echo esc_attr( $mb_google_api_key ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Enter your Google Geocoding API Key enabled with Geocoding API.', 'my-booking-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mb_distance_unit"><?php esc_html_e( 'Distance Unit of Measurement', 'my-booking-engine' ); ?></label></th>
							<td>
								<select name="mb_engine_settings[distance_unit]" id="mb_distance_unit">
									<option value="km" <?php selected( $mb_distance_unit, 'km' ); ?>><?php esc_html_e( 'Kilometers (km)', 'my-booking-engine' ); ?></option>
									<option value="miles" <?php selected( $mb_distance_unit, 'miles' ); ?>><?php esc_html_e( 'Miles (mi)', 'my-booking-engine' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Unit used for Haversine spatial radius calculations.', 'my-booking-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mb_search_default_rad"><?php esc_html_e( 'Default Search Radius', 'my-booking-engine' ); ?></label></th>
							<td>
								<input type="number" name="mb_engine_settings[search_default_rad]" id="mb_search_default_rad" value="<?php echo esc_attr( $mb_default_radius ); ?>" min="1" max="500" class="small-text">
								<p class="description"><?php esc_html_e( 'Default distance radius loaded in search filter sliders.', 'my-booking-engine' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="mb-card" style="margin-top:20px;">
					<h2><?php esc_html_e( '2. Booking Engine & Mutex Concurrency', 'my-booking-engine' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="mb_lock_duration"><?php esc_html_e( 'Checkout Lock Duration (Minutes)', 'my-booking-engine' ); ?></label></th>
							<td>
								<input type="number" name="mb_engine_settings[lock_duration]" id="mb_lock_duration" value="<?php echo esc_attr( $mb_lock_duration ); ?>" min="2" max="60" class="small-text">
								<p class="description"><?php esc_html_e( 'How long a selected time slot is locked while the customer fills out the form or is in the WooCommerce checkout funnel.', 'my-booking-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mb_currency_symbol"><?php esc_html_e( 'Currency Symbol', 'my-booking-engine' ); ?></label></th>
							<td>
								<input type="text" name="mb_engine_settings[currency_symbol]" id="mb_currency_symbol" value="<?php echo esc_attr( $mb_currency_symbol ); ?>" class="small-text">
								<p class="description"><?php esc_html_e( 'Currency symbol displayed on frontend cards and slot modals (e.g. $, €, £, ¥).', 'my-booking-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mb_enable_woocommerce"><?php esc_html_e( 'Connect WooCommerce Checkout', 'my-booking-engine' ); ?></label></th>
							<td>
								<?php if ( class_exists( 'WooCommerce' ) ) : ?>
									<span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:#dcfce7;color:#166534;margin-bottom:8px;">✓ <?php esc_html_e( 'WooCommerce detected', 'my-booking-engine' ); ?></span>
								<?php else : ?>
									<span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:#fef2f2;color:#b91c1c;margin-bottom:8px;">✕ <?php esc_html_e( 'WooCommerce not installed/active', 'my-booking-engine' ); ?></span>
								<?php endif; ?>
								<br>
								<label>
									<input type="checkbox" name="mb_engine_settings[enable_woocommerce]" id="mb_enable_woocommerce" value="yes" <?php checked( $mb_enable_wc, 'yes' ); ?> <?php disabled( ! class_exists( 'WooCommerce' ) ); ?>>
									<?php esc_html_e( 'Redirect bookings into WooCommerce cart and checkout funnel', 'my-booking-engine' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'When active, bookings leverage WooCommerce payment gateways (Stripe, PayPal, Apple Pay, etc.) and auto-confirm upon payment. When off, bookings are confirmed directly by the plugin instead.', 'my-booking-engine' ); ?></p>
								<?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
									<p class="description" style="color:#b91c1c;"><?php esc_html_e( 'Install and activate WooCommerce to enable this option.', 'my-booking-engine' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>

				<div class="mb-card" style="margin-top:20px;">
					<h2><?php esc_html_e( '3. Appearance, Branding & Color Customizer', 'my-booking-engine' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Customize primary brand colors, buttons, calendar highlights, and card styling across all frontend widgets.', 'my-booking-engine' ); ?></p>
					
					<!-- Palette Quick Presets -->
					<div class="mb-palette-presets" style="margin:16px 0; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
						<span style="font-weight:600; font-size:12px; color:#64748b;"><?php esc_html_e( 'One-Click Palette Presets:', 'my-booking-engine' ); ?></span>
						<button type="button" class="button mb-palette-btn" data-primary="#2563eb" data-hover="#1d4ed8" data-accent="#f59e0b" onclick="document.getElementById('mb_primary_color').value='#2563eb';document.getElementById('mb_primary_color_picker').value='#2563eb';document.getElementById('mb_primary_hover').value='#1d4ed8';document.getElementById('mb_primary_hover_picker').value='#1d4ed8';document.getElementById('mb_accent_color').value='#f59e0b';document.getElementById('mb_accent_color_picker').value='#f59e0b';">🔵 <?php esc_html_e( 'Modern Blue', 'my-booking-engine' ); ?></button>
						<button type="button" class="button mb-palette-btn" data-primary="#ff385c" data-hover="#e00b41" data-accent="#00a699" onclick="document.getElementById('mb_primary_color').value='#ff385c';document.getElementById('mb_primary_color_picker').value='#ff385c';document.getElementById('mb_primary_hover').value='#e00b41';document.getElementById('mb_primary_hover_picker').value='#e00b41';document.getElementById('mb_accent_color').value='#00a699';document.getElementById('mb_accent_color_picker').value='#00a699';">🌺 <?php esc_html_e( 'Airbnb Coral', 'my-booking-engine' ); ?></button>
						<button type="button" class="button mb-palette-btn" data-primary="#059669" data-hover="#047857" data-accent="#d97706" onclick="document.getElementById('mb_primary_color').value='#059669';document.getElementById('mb_primary_color_picker').value='#059669';document.getElementById('mb_primary_hover').value='#047857';document.getElementById('mb_primary_hover_picker').value='#047857';document.getElementById('mb_accent_color').value='#d97706';document.getElementById('mb_accent_color_picker').value='#d97706';">🌿 <?php esc_html_e( 'Emerald Spa', 'my-booking-engine' ); ?></button>
						<button type="button" class="button mb-palette-btn" data-primary="#7c3aed" data-hover="#6d28d9" data-accent="#ec4899" onclick="document.getElementById('mb_primary_color').value='#7c3aed';document.getElementById('mb_primary_color_picker').value='#7c3aed';document.getElementById('mb_primary_hover').value='#6d28d9';document.getElementById('mb_primary_hover_picker').value='#6d28d9';document.getElementById('mb_accent_color').value='#ec4899';document.getElementById('mb_accent_color_picker').value='#ec4899';">👑 <?php esc_html_e( 'Royal Purple', 'my-booking-engine' ); ?></button>
						<button type="button" class="button mb-palette-btn" data-primary="#0f172a" data-hover="#1e293b" data-accent="#f59e0b" onclick="document.getElementById('mb_primary_color').value='#0f172a';document.getElementById('mb_primary_color_picker').value='#0f172a';document.getElementById('mb_primary_hover').value='#1e293b';document.getElementById('mb_primary_hover_picker').value='#1e293b';document.getElementById('mb_accent_color').value='#f59e0b';document.getElementById('mb_accent_color_picker').value='#f59e0b';">🕶️ <?php esc_html_e( 'Luxury Obsidian', 'my-booking-engine' ); ?></button>
					</div>

					<table class="form-table">
						<tr>
							<th scope="row"><label for="mb_primary_color"><?php esc_html_e( 'Primary Brand Color', 'my-booking-engine' ); ?></label></th>
							<td>
								<div style="display:flex; align-items:center; gap:10px;">
									<input type="color" id="mb_primary_color_picker" value="<?php echo esc_attr( $mb_primary_color ); ?>" oninput="document.getElementById('mb_primary_color').value = this.value;" onchange="document.getElementById('mb_primary_color').value = this.value;" style="width:40px; height:36px; padding:0; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer;">
									<input type="text" name="mb_engine_settings[primary_color]" id="mb_primary_color" value="<?php echo esc_attr( $mb_primary_color ); ?>" oninput="if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('mb_primary_color_picker').value = this.value;" class="small-text" style="font-family:monospace;">
								</div>
								<p class="description"><?php esc_html_e( 'Used for book buttons, active calendar dates, tabs, and filter accents.', 'my-booking-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mb_primary_hover"><?php esc_html_e( 'Primary Hover Color', 'my-booking-engine' ); ?></label></th>
							<td>
								<div style="display:flex; align-items:center; gap:10px;">
									<input type="color" id="mb_primary_hover_picker" value="<?php echo esc_attr( $mb_primary_hover ); ?>" oninput="document.getElementById('mb_primary_hover').value = this.value;" onchange="document.getElementById('mb_primary_hover').value = this.value;" style="width:40px; height:36px; padding:0; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer;">
									<input type="text" name="mb_engine_settings[primary_hover]" id="mb_primary_hover" value="<?php echo esc_attr( $mb_primary_hover ); ?>" oninput="if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('mb_primary_hover_picker').value = this.value;" class="small-text" style="font-family:monospace;">
								</div>
								<p class="description"><?php esc_html_e( 'Applied when hovering buttons and active clickable components.', 'my-booking-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mb_accent_color"><?php esc_html_e( 'Accent & Star Rating Color', 'my-booking-engine' ); ?></label></th>
							<td>
								<div style="display:flex; align-items:center; gap:10px;">
									<input type="color" id="mb_accent_color_picker" value="<?php echo esc_attr( $mb_accent_color ); ?>" oninput="document.getElementById('mb_accent_color').value = this.value;" onchange="document.getElementById('mb_accent_color').value = this.value;" style="width:40px; height:36px; padding:0; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer;">
									<input type="text" name="mb_engine_settings[accent_color]" id="mb_accent_color" value="<?php echo esc_attr( $mb_accent_color ); ?>" oninput="if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('mb_accent_color_picker').value = this.value;" class="small-text" style="font-family:monospace;">
								</div>
								<p class="description"><?php esc_html_e( 'Used for review stars, verified badges, and discount highlights.', 'my-booking-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mb_border_radius"><?php esc_html_e( 'Card & Button Corner Radius', 'my-booking-engine' ); ?></label></th>
							<td>
								<select name="mb_engine_settings[border_radius]" id="mb_border_radius" class="small-text">
									<option value="4" <?php selected( $mb_border_radius, 4 ); ?>><?php esc_html_e( '4px (Subtle / Sharp)', 'my-booking-engine' ); ?></option>
									<option value="8" <?php selected( $mb_border_radius, 8 ); ?>><?php esc_html_e( '8px (Modern Default)', 'my-booking-engine' ); ?></option>
									<option value="12" <?php selected( $mb_border_radius, 12 ); ?>><?php esc_html_e( '12px (Smooth Rounded)', 'my-booking-engine' ); ?></option>
									<option value="16" <?php selected( $mb_border_radius, 16 ); ?>><?php esc_html_e( '16px (High Curves)', 'my-booking-engine' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button( __( 'Save All Changes', 'my-booking-engine' ) ); ?>
			</form>
		</div>

		<!-- Right: Freemium / Pro Features Spotlight -->
		<div class="mb-settings-sidebar">
			<div class="mb-card mb-pro-box">
				<div class="mb-pro-badge"><?php esc_html_e( 'PRO ADD-ONS READY', 'my-booking-engine' ); ?></div>
				<h3><?php esc_html_e( 'Unlock Premium Features', 'my-booking-engine' ); ?></h3>
				<p><?php esc_html_e( 'Ready for the Pro extension available on ThemeForest & CodeCanyon:', 'my-booking-engine' ); ?></p>
				<ul class="mb-pro-list">
					<li><span class="dashicons dashicons-yes"></span> <strong><?php esc_html_e( '2-Way Google Calendar Sync', 'my-booking-engine' ); ?></strong> - Real-time auto-export & import without delay.</li>
					<li><span class="dashicons dashicons-yes"></span> <strong><?php esc_html_e( '2-Way iCal Airbnb & VRBO Sync', 'my-booking-engine' ); ?></strong> - Never double-book vacation properties.</li>
					<li><span class="dashicons dashicons-yes"></span> <strong><?php esc_html_e( 'Twilio & WhatsApp SMS Reminders', 'my-booking-engine' ); ?></strong> - Instant notification alerts to customers & staff.</li>
					<li><span class="dashicons dashicons-yes"></span> <strong><?php esc_html_e( 'Multi-Vendor & Staff Calendars', 'my-booking-engine' ); ?></strong> - Dedicated dashboards for individual therapists or staff.</li>
					<li><span class="dashicons dashicons-yes"></span> <strong><?php esc_html_e( 'Custom Fields & Form Builder', 'my-booking-engine' ); ?></strong> - Add custom file uploads, questionnaires, and checkboxes.</li>
				</ul>
				<a href="#" class="button button-primary button-hero mb-pro-cta"><?php esc_html_e( 'View Pro Extensions & Licensing', 'my-booking-engine' ); ?></a>
			</div>

			<div class="mb-card" style="margin-top:20px;">
				<h3><?php esc_html_e( 'Shortcodes Quick Reference', 'my-booking-engine' ); ?></h3>
				<p><code>[mb_search_filter]</code><br><small><?php esc_html_e( 'Embeds the postal code radius search and filter grid.', 'my-booking-engine' ); ?></small></p>
				<p><code>[mb_booking_form id="123"]</code><br><small><?php esc_html_e( 'Embeds the direct booking card for a specific entity.', 'my-booking-engine' ); ?></small></p>
				<p><code>[mb_entities type="rental" limit="6"]</code><br><small><?php esc_html_e( 'Displays a responsive grid of published entities.', 'my-booking-engine' ); ?></small></p>
			</div>
		</div>
	</div>
</div>
