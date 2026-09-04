/**
 * Booking Engine Admin JavaScript.
 *
 * @package MyBookingEngine
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		initMetaBoxTabs();
		initModelSwitcher();
		initAdminGeocoding();
		initSettingsPage();
		initLayoutSelector();
		initGalleryPicker();
	});

	/**
	 * Tabbed navigation in meta box.
	 */
	function initMetaBoxTabs() {
		$('.mb-tab-link').on('click', function(e) {
			e.preventDefault();
			const targetId = $(this).attr('href');

			$('.mb-tab-link').removeClass('active');
			$(this).addClass('active');

			$('.mb-tab-content').removeClass('active');
			$(targetId).addClass('active');
		});
	}

	/**
	 * Visual layout card selector interaction.
	 */
	function initLayoutSelector() {
		$('.mb-layout-card').on('click', function() {
			$('.mb-layout-card').removeClass('selected');
			$(this).addClass('selected');
			$(this).find('input[type="radio"]').prop('checked', true);
		});
	}

	/**
	 * WordPress Media Library integration for gallery images.
	 */
	function initGalleryPicker() {
		let mediaFrame;

		$('#mb_btn_select_gallery').on('click', function(e) {
			e.preventDefault();

			if (mediaFrame) {
				mediaFrame.open();
				return;
			}

			if (typeof wp === 'undefined' || !wp.media) {
				alert('WordPress Media Library is not available.');
				return;
			}

			mediaFrame = wp.media({
				title: 'Select Listing Gallery Images',
				button: { text: 'Use Selected Images' },
				multiple: true
			});

			mediaFrame.on('select', function() {
				const selection = mediaFrame.state().get('selection');
				const currentVal = $('#mb_gallery_images').val().trim();
				let currentIds = currentVal ? currentVal.split(',').map(function(id) { return id.trim(); }) : [];
				const $preview = $('#mb_gallery_preview');

				selection.each(function(attachment) {
					const item = attachment.toJSON();
					const idStr = String(item.id);
					if (currentIds.indexOf(idStr) === -1) {
						currentIds.push(idStr);

						const thumbUrl = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
						const $thumb = $('<div class="mb-gallery-thumb-item" style="width:72px; height:72px; border-radius:6px; overflow:hidden; border:1px solid #cbd5e1;"><img src="' + thumbUrl + '" style="width:100%; height:100%; object-fit:cover;"></div>');
						$preview.append($thumb);
					}
				});

				$('#mb_gallery_images').val(currentIds.join(', '));
			});

			mediaFrame.open();
		});

		$('#mb_btn_clear_gallery').on('click', function(e) {
			e.preventDefault();
			$('#mb_gallery_images').val('');
			$('#mb_gallery_preview').empty();
		});
	}

	/**
	 * Dynamic fields visibility toggle based on selected booking model.
	 */
	function initModelSwitcher() {
		const $switcher = $('#mb_model_type');
		if (!$switcher.length) return;

		function updateVisibleFields() {
			const selectedModel = $switcher.val();

			// Hide all model-specific rows.
			$('.mb-field-row').hide();

			// Show rows that match this model.
			if (selectedModel === 'night_stay' || selectedModel === 'hotel_room') {
				$('.mb-field-night_stay').show();
			} else if (selectedModel === 'day_rental' || selectedModel === 'daily_booking') {
				$('.mb-field-day_rental').show();
			} else if (selectedModel === 'capacity_roster') {
				$('.mb-field-capacity_roster').show();
			} else {
				// Default hourly/slot models.
				$('.mb-field-hourly_slot').show();
			}
		}

		$switcher.on('change', updateVisibleFields);
		updateVisibleFields();
	}

	/**
	 * One-click Auto-Geocoding AJAX Handler.
	 */
	function initAdminGeocoding() {
		$('#mb_btn_geocode').on('click', function(e) {
			e.preventDefault();

			const $btn = $(this);
			const $spinner = $('#mb_geocode_spinner');
			const $status = $('#mb_geocode_status');

			const postal = $('#mb_postal_code').val().trim();
			const city = $('#mb_city').val().trim();
			const country = $('#mb_country_code').val().trim();

			if (!postal && !city) {
				$status.css('color', '#d63638').text('Please enter a Postal Code or City first.');
				return;
			}

			$btn.prop('disabled', true);
			$spinner.addClass('is-active');
			$status.css('color', '#646970').text(window.mbAdminData.i18n.geocoding || 'Looking up...');

			$.ajax({
				url: window.mbAdminData.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'mb_admin_geocode',
					nonce: window.mbAdminData.nonce,
					postal_code: postal,
					city: city,
					country: country
				},
				success: function(response) {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');

					if (response.success && response.data) {
						$('#mb_latitude').val(response.data.lat);
						$('#mb_longitude').val(response.data.lng);
						$status.css('color', '#007017').text(window.mbAdminData.i18n.geocodeSuccess || 'Coordinates found!');
					} else {
						$status.css('color', '#d63638').text(response.data?.message || window.mbAdminData.i18n.geocodeNotFound || 'Not found.');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
					$status.css('color', '#d63638').text(window.mbAdminData.i18n.geocodeError || 'Geocoding request failed.');
				}
			});
		});
	}

	/**
	 * Settings page dynamic provider toggles.
	 */
	function initSettingsPage() {
		const $providerSelect = $('#mb_geocoder_provider');
		if (!$providerSelect.length) return;

		$providerSelect.on('change', function() {
			if ($(this).val() === 'google') {
				$('#mb_google_key_row').show();
			} else {
				$('#mb_google_key_row').hide();
			}
		});
	}

})(jQuery);
