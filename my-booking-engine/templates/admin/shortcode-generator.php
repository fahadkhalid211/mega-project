<?php
/**
 * Admin Shortcode Builder View.
 *
 * @package MyBookingEngine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap mb-shortcode-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Visual Shortcode Builder', 'my-booking-engine' ); ?></h1>
	<p class="description" style="font-size: 14px; margin-top: 6px; margin-bottom: 20px;">
		<?php esc_html_e( 'Generate and copy shortcodes to embed booking forms, search directories, and listing catalogs on any page or post without writing code.', 'my-booking-engine' ); ?>
	</p>
	<hr class="wp-header-end">

	<div class="mb-settings-grid" style="margin-top: 20px;">
		<!-- Main Generator Card -->
		<div class="mb-settings-main">
			<div class="mb-card">
				<h2 style="margin-top:0; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
					<?php esc_html_e( 'Interactive Shortcode Generator', 'my-booking-engine' ); ?>
				</h2>

				<table class="form-table">
					<tr>
						<th scope="row"><label for="mb_sc_component"><?php esc_html_e( 'What would you like to display?', 'my-booking-engine' ); ?></label></th>
						<td>
							<select id="mb_sc_component" class="regular-text" style="font-weight:600;">
								<option value="search"><?php esc_html_e( 'Global Search Bar & Directory (with Interactive Map)', 'my-booking-engine' ); ?></option>
								<option value="catalog"><?php esc_html_e( 'All Listings Catalog (with Category Filter Tabs)', 'my-booking-engine' ); ?></option>
								<option value="category"><?php esc_html_e( 'Specific Category Showcase (Hotels, Cars, Doctors...)', 'my-booking-engine' ); ?></option>
								<option value="single_form"><?php esc_html_e( 'Direct Booking Card for a Specific Listing', 'my-booking-engine' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Choose which component you want to embed into your page.', 'my-booking-engine' ); ?></p>
						</td>
					</tr>

					<!-- Category Select Row (Hidden by default unless category chosen) -->
					<tr id="mb_sc_category_row" style="display:none;">
						<th scope="row"><label for="mb_sc_category"><?php esc_html_e( 'Select Category', 'my-booking-engine' ); ?></label></th>
						<td>
							<select id="mb_sc_category" class="regular-text">
								<option value="hotel"><?php esc_html_e( 'Hotels & Vacation Stays', 'my-booking-engine' ); ?></option>
								<option value="rental"><?php esc_html_e( 'Car & Equipment Rentals', 'my-booking-engine' ); ?></option>
								<option value="doctor"><?php esc_html_e( 'Doctors & Healthcare Specialists', 'my-booking-engine' ); ?></option>
								<option value="salon"><?php esc_html_e( 'Salons, Spas & Beauty', 'my-booking-engine' ); ?></option>
								<option value="hourly"><?php esc_html_e( 'Hourly Studios & Workspaces', 'my-booking-engine' ); ?></option>
								<option value="shop"><?php esc_html_e( 'Local Shops & Businesses', 'my-booking-engine' ); ?></option>
							</select>
						</td>
					</tr>

					<!-- Specific Listing Select Row (Hidden unless single_form chosen) -->
					<tr id="mb_sc_listing_row" style="display:none;">
						<th scope="row"><label for="mb_sc_entity_id"><?php esc_html_e( 'Select Specific Listing', 'my-booking-engine' ); ?></label></th>
						<td>
							<select id="mb_sc_entity_id" class="regular-text">
								<?php if ( ! empty( $entities ) ) : ?>
									<?php foreach ( $entities as $ent ) : ?>
										<option value="<?php echo esc_attr( $ent->ID ); ?>"><?php echo esc_html( $ent->post_title ); ?> (ID: <?php echo esc_html( $ent->ID ); ?>)</option>
									<?php endforeach; ?>
								<?php else : ?>
									<option value="0"><?php esc_html_e( 'No listings created yet', 'my-booking-engine' ); ?></option>
								<?php endif; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select by listing title. You never have to manually remember or lookup numeric IDs.', 'my-booking-engine' ); ?></p>
						</td>
					</tr>

					<!-- Limit / Items Count Row -->
					<tr id="mb_sc_limit_row">
						<th scope="row"><label for="mb_sc_limit"><?php esc_html_e( 'Number of Listings to Display', 'my-booking-engine' ); ?></label></th>
						<td>
							<select id="mb_sc_limit" class="small-text">
								<option value="3">3</option>
								<option value="6">6</option>
								<option value="9" selected>9</option>
								<option value="12">12</option>
								<option value="24">24</option>
							</select>
						</td>
					</tr>

					<!-- Show Map Row -->
					<tr id="mb_sc_map_row">
						<th scope="row"><label for="mb_sc_show_map"><?php esc_html_e( 'Interactive Map', 'my-booking-engine' ); ?></label></th>
						<td>
							<select id="mb_sc_show_map" class="small-text">
								<option value="no"><?php esc_html_e( 'No (Listings Grid Only)', 'my-booking-engine' ); ?></option>
								<option value="yes"><?php esc_html_e( 'Yes (Split Map & Directory View)', 'my-booking-engine' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<!-- Generated Output Box -->
				<div style="margin-top:24px; padding:20px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px;">
					<label style="display:block; font-weight:700; margin-bottom:8px; font-size:14px; color:#0f172a;">
						<?php esc_html_e( 'Your Generated Shortcode:', 'my-booking-engine' ); ?>
					</label>
					<div style="display:flex; gap:12px; align-items:center;">
						<input type="text" id="mb_sc_result" value="[mb_search]" readonly style="flex:1; font-family:monospace; font-size:16px; font-weight:700; padding:10px 14px; background:#ffffff; border:2px solid #2563eb; color:#2563eb; border-radius:6px;">
						<button type="button" class="button button-primary button-hero" id="mb_btn_copy_sc" style="white-space:nowrap;">
							📋 <?php esc_html_e( 'Copy Shortcode', 'my-booking-engine' ); ?>
						</button>
					</div>
					<div id="mb_copy_status" style="display:none; color:#16a34a; font-weight:700; font-size:13px; margin-top:8px;">✓ <?php esc_html_e( 'Copied to clipboard! Paste into any WordPress page or widget.', 'my-booking-engine' ); ?></div>
				</div>
			</div>
		</div>

		<!-- Right: Quick Reference Cheat-Sheet -->
		<div class="mb-settings-sidebar">
			<div class="mb-card">
				<h3 style="margin-top:0; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
					⚡ <?php esc_html_e( 'Instant Shortcodes Cheat-Sheet', 'my-booking-engine' ); ?>
				</h3>
				<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Zero-config shortcodes ready to copy and paste immediately:', 'my-booking-engine' ); ?></p>

				<div class="mb-cheat-item" style="margin-bottom:16px; padding:12px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
					<strong><?php esc_html_e( 'Global Search & Directory', 'my-booking-engine' ); ?></strong>
					<div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
						<code>[mb_search]</code>
						<button type="button" class="button button-small mb-quick-copy" data-sc="[mb_search]"><?php esc_html_e( 'Copy', 'my-booking-engine' ); ?></button>
					</div>
				</div>

				<div class="mb-cheat-item" style="margin-bottom:16px; padding:12px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
					<strong><?php esc_html_e( 'All Listings Catalog (Tabs)', 'my-booking-engine' ); ?></strong>
					<div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
						<code>[mb_listings]</code>
						<button type="button" class="button button-small mb-quick-copy" data-sc="[mb_listings]"><?php esc_html_e( 'Copy', 'my-booking-engine' ); ?></button>
					</div>
				</div>

				<div class="mb-cheat-item" style="margin-bottom:16px; padding:12px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
					<strong><?php esc_html_e( 'Hotels & Vacation Stays', 'my-booking-engine' ); ?></strong>
					<div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
						<code>[mb_listings type="hotel"]</code>
						<button type="button" class="button button-small mb-quick-copy" data-sc='[mb_listings type="hotel"]'><?php esc_html_e( 'Copy', 'my-booking-engine' ); ?></button>
					</div>
				</div>

				<div class="mb-cheat-item" style="margin-bottom:16px; padding:12px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
					<strong><?php esc_html_e( 'Car & Equipment Rentals', 'my-booking-engine' ); ?></strong>
					<div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
						<code>[mb_listings type="rental"]</code>
						<button type="button" class="button button-small mb-quick-copy" data-sc='[mb_listings type="rental"]'><?php esc_html_e( 'Copy', 'my-booking-engine' ); ?></button>
					</div>
				</div>

				<div class="mb-cheat-item" style="margin-bottom:16px; padding:12px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
					<strong><?php esc_html_e( 'Doctors & Healthcare', 'my-booking-engine' ); ?></strong>
					<div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
						<code>[mb_listings type="doctor"]</code>
						<button type="button" class="button button-small mb-quick-copy" data-sc='[mb_listings type="doctor"]'><?php esc_html_e( 'Copy', 'my-booking-engine' ); ?></button>
					</div>
				</div>

				<div class="mb-cheat-item" style="padding:12px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
					<strong><?php esc_html_e( 'Auto-Detect Booking Card', 'my-booking-engine' ); ?></strong>
					<div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px;">
						<code>[mb_booking_form]</code>
						<button type="button" class="button button-small mb-quick-copy" data-sc="[mb_booking_form]"><?php esc_html_e( 'Copy', 'my-booking-engine' ); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
