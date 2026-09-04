/**
 * Booking Engine Admin JavaScript.
 *
 * @package MyBookingEngine
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		initAdminWizard();
		initModelSwitcher();
		initAdminGeocoding();
		initSettingsPage();
		initLayoutSelector();
		initGalleryPicker();
		initShortcodeGenerator();
	});

	/**
	 * 5-Step Guided Listing Creation Wizard.
	 */
	function initAdminWizard() {
		const $wizard = $('#mb-admin-wizard');
		if (!$wizard.length) return;

		let currentStep = 1;
		const totalSteps = 5;

		const stepTitles = {
			1: 'Step 1 of 5: Type & Design',
			2: 'Step 2 of 5: Pricing & Rules',
			3: 'Step 3 of 5: Location & Map',
			4: 'Step 4 of 5: Operating Hours',
			5: 'Step 5 of 5: Media & Policy'
		};

		function showStep(step) {
			if (step < 1) step = 1;
			if (step > totalSteps) step = totalSteps;

			currentStep = step;

			// Update Progress Bar
			const pct = (currentStep / totalSteps) * 100;
			$('#mb-stepper-bar').css('width', pct + '%');

			// Update Stepper Nodes
			$('.mb-step-node').each(function() {
				const s = parseInt($(this).data('step'), 10);
				$(this).removeClass('is-active is-completed');
				if (s === currentStep) {
					$(this).addClass('is-active');
				} else if (s < currentStep) {
					$(this).addClass('is-completed');
				}
			});

			// Show Active Panel
			$('.mb-wizard-step-panel').removeClass('is-active');
			$('.mb-wizard-step-panel[data-panel="' + currentStep + '"]').addClass('is-active');

			// Update Navigation Buttons & Status Text
			$('#mb-wiz-step-text').text(stepTitles[currentStep] || ('Step ' + currentStep + ' of ' + totalSteps));

			if (currentStep === 1) {
				$('#mb-wiz-prev').hide();
			} else {
				$('#mb-wiz-prev').show();
			}

			if (currentStep === totalSteps) {
				$('#mb-wiz-next').hide();
				$('#mb-wiz-publish-btn').show();
			} else {
				$('#mb-wiz-next').show().text('Next Step →');
				$('#mb-wiz-publish-btn').hide();
			}
		}

		// Modal Open / Close buttons (editing an existing listing on
		// post.php — the metabox's "Launch Wizard Popup" button)
		$('#mb-open-modal-wizard-btn').on('click', function(e) {
			e.preventDefault();
			$('#mb-admin-wizard').addClass('is-modal');
			$('.mb-wizard-modal-header').show();
			$('.mb-modal-quick-title-row').show();
			$('#mb_quick_title').focus();
		});

		$('#mb-minimize-wizard-btn, #mb-close-wizard-x').on('click', function(e) {
			e.preventDefault();
			$('#mb-admin-wizard').removeClass('is-modal');
			$('.mb-wizard-modal-header').hide();
			$('.mb-modal-quick-title-row').hide();
		});

		// Synchronize Quick Title with WordPress Title input
		$('#mb_quick_title').on('input change', function() {
			const val = $(this).val();
			$('#title').val(val).trigger('change');
			const $gutenbergTitle = $('.editor-post-title__input, textarea.editor-post-title__input');
			if ($gutenbergTitle.length) {
				$gutenbergTitle.val(val).trigger('input');
			}
		});

		$('#title').on('input change', function() {
			$('#mb_quick_title').val($(this).val());
		});

		// Next & Prev Buttons
		$('#mb-wiz-next').on('click', function(e) {
			e.preventDefault();
			if (currentStep < totalSteps) {
				showStep(currentStep + 1);
			}
		});

		$('#mb-wiz-prev').on('click', function(e) {
			e.preventDefault();
			if (currentStep > 1) {
				showStep(currentStep - 1);
			}
		});

		// Save & Publish Button inside Wizard Modal
		$('#mb-wiz-publish-btn').on('click', function(e) {
			e.preventDefault();

			const quickTitle = $('#mb_quick_title').val().trim();
			if (quickTitle && !$('#title').val()) {
				$('#title').val(quickTitle);
			}

			// Classic WordPress editor publish button
			const $publishBtn = $('#publish');
			if ($publishBtn.length) {
				$publishBtn.trigger('click');
				return;
			}

			// Gutenberg Block Editor publish button
			const gBtn = document.querySelector('.editor-post-publish-button, .editor-post-publish-panel__toggle');
			if (gBtn) {
				gBtn.click();
				return;
			}

			// Fallback form submission
			$('form#post').submit();
		});

		// Stepper node click jump
		$('.mb-step-node').on('click', function() {
			const targetStep = parseInt($(this).data('step'), 10);
			if (targetStep >= 1 && targetStep <= totalSteps) {
				showStep(targetStep);
			}
		});

		// Schedule Day Switcher Toggle
		$('.mb-day-switch').on('change', function() {
			const isOpen = $(this).is(':checked');
			const $row = $(this).closest('.mb-schedule-row');
			const $label = $row.find('.mb-switch-label');
			const $times = $row.find('.mb-sched-times');

			if (isOpen) {
				$row.removeClass('is-closed').addClass('is-open');
				$label.text('Open');
				$times.css({ opacity: 1, 'pointer-events': 'auto' });
			} else {
				$row.removeClass('is-open').addClass('is-closed');
				$label.text('Closed');
				$times.css({ opacity: 0.4, 'pointer-events': 'none' });
			}
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
				title: 'Select Listing Gallery Photos',
				button: { text: 'Use Selected Photos' },
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
						const $thumb = $('<div class="mb-preview-thumb"><img src="' + thumbUrl + '"></div>');
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
			$status.css('color', '#646970').text(window.mbAdminData?.i18n?.geocoding || 'Looking up...');

			$.ajax({
				url: window.mbAdminData?.ajaxUrl || ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'mb_admin_geocode',
					nonce: window.mbAdminData?.nonce,
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
						$status.css('color', '#007017').text(window.mbAdminData?.i18n?.geocodeSuccess || 'Coordinates found!');
					} else {
						$status.css('color', '#d63638').text(response.data?.message || window.mbAdminData?.i18n?.geocodeNotFound || 'Not found.');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
					$status.css('color', '#d63638').text(window.mbAdminData?.i18n?.geocodeError || 'Geocoding request failed.');
				}
			});
		});
	}

	/**
	 * Settings page color sync and palette presets.
	 */
	function initSettingsPage() {
		const $providerSelect = $('#mb_geocoder_provider');
		if ($providerSelect.length) {
			$providerSelect.on('change', function() {
				if ($(this).val() === 'google') {
					$('#mb_google_key_row').show();
				} else {
					$('#mb_google_key_row').hide();
				}
			});
		}

		// Two-way color picker input sync
		function bindColorSync(pickerId, textId) {
			$(pickerId).on('input change', function() {
				$(textId).val($(this).val());
			});
			$(textId).on('input change', function() {
				const val = $(this).val().trim();
				if (/^#[0-9A-F]{6}$/i.test(val)) {
					$(pickerId).val(val);
				}
			});
		}

		bindColorSync('#mb_primary_color_picker', '#mb_primary_color');
		bindColorSync('#mb_primary_hover_picker', '#mb_primary_hover');
		bindColorSync('#mb_accent_color_picker', '#mb_accent_color');

		// One-click palette presets
		$('.mb-palette-btn').on('click', function(e) {
			e.preventDefault();
			const p = $(this).data('primary');
			const h = $(this).data('hover');
			const a = $(this).data('accent');

			if (p) {
				$('#mb_primary_color').val(p);
				$('#mb_primary_color_picker').val(p);
			}
			if (h) {
				$('#mb_primary_hover').val(h);
				$('#mb_primary_hover_picker').val(h);
			}
			if (a) {
				$('#mb_accent_color').val(a);
				$('#mb_accent_color_picker').val(a);
			}
		});
	}

	/**
	 * Interactive Shortcode Generator.
	 */
	function initShortcodeGenerator() {
		const $comp = $('#mb_sc_component');
		if (!$comp.length) return;

		function updateShortcode() {
			const comp = $comp.val();
			const limit = $('#mb_sc_limit').val();
			const showMap = $('#mb_sc_show_map').val();
			const cat = $('#mb_sc_category').val();
			const entityId = $('#mb_sc_entity_id').val();

			// Toggle field row visibilities
			$('#mb_sc_category_row').toggle(comp === 'category');
			$('#mb_sc_listing_row').toggle(comp === 'single_form');
			$('#mb_sc_limit_row').toggle(comp !== 'single_form');
			$('#mb_sc_map_row').toggle(comp === 'search');

			let sc = '';
			if (comp === 'search') {
				sc = '[mb_search limit="' + limit + '" show_map="' + showMap + '"]';
			} else if (comp === 'catalog') {
				sc = '[mb_listings limit="' + limit + '"]';
			} else if (comp === 'category') {
				sc = '[mb_listings type="' + cat + '" limit="' + limit + '"]';
			} else if (comp === 'single_form') {
				sc = '[mb_booking_form id="' + entityId + '"]';
			}

			$('#mb_sc_result').val(sc);
		}

		$('#mb_sc_component, #mb_sc_limit, #mb_sc_show_map, #mb_sc_category, #mb_sc_entity_id').on('change input', updateShortcode);
		updateShortcode();

		// Copy Button
		$('#mb_btn_copy_sc').on('click', function(e) {
			e.preventDefault();
			const text = $('#mb_sc_result').val();
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function() {
					$('#mb_copy_status').fadeIn(200).delay(2500).fadeOut(300);
				});
			} else {
				$('#mb_sc_result').select();
				document.execCommand('copy');
				$('#mb_copy_status').fadeIn(200).delay(2500).fadeOut(300);
			}
		});

		// Quick Copy Buttons
		$('.mb-quick-copy').on('click', function(e) {
			e.preventDefault();
			const $btn = $(this);
			const sc = $btn.data('sc');
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(sc).then(function() {
					const orig = $btn.text();
					$btn.text('✓ Copied!').prop('disabled', true);
					setTimeout(function() {
						$btn.text(orig).prop('disabled', false);
					}, 2000);
				});
			}
		});
	}

	/**
	 * Bookings admin page: view-details modal (customer + booking info,
	 * with Approve / Cancel actions) instead of routing to the listing
	 * editor when the entity title is clicked.
	 */
	function initBookingDetailsModal() {
		const $overlay = $('#mb-booking-details-overlay');
		if (!$overlay.length) return;

		const $body = $('#mb-booking-details-body');
		const i18n = (window.mbAdminData && window.mbAdminData.i18n) || {};

		function fieldRow(label, value) {
			return '<div class="mb-detail-row"><span>' + label + '</span><span>' + value + '</span></div>';
		}

		function escapeHtml(str) {
			return $('<div>').text(str == null ? '' : str).html();
		}

		function openDetails(payload) {
			let html = '';
			html += fieldRow('Listing', escapeHtml(payload.entityTitle));
			html += fieldRow('Customer', escapeHtml(payload.customerName));
			html += fieldRow('Email', '<a href="mailto:' + escapeHtml(payload.customerEmail) + '">' + escapeHtml(payload.customerEmail) + '</a>');
			if (payload.customerPhone) {
				html += fieldRow('Phone', escapeHtml(payload.customerPhone));
			}
			html += fieldRow('Start', escapeHtml(payload.bookingStart));
			html += fieldRow('End', escapeHtml(payload.bookingEnd));
			html += fieldRow('Spots', escapeHtml(payload.capacityBooked));
			html += fieldRow('Total', escapeHtml(payload.totalPrice));
			html += fieldRow('Status', '<span class="mb-status-pill mb-status-' + payload.status + '">' + escapeHtml(payload.status.charAt(0).toUpperCase() + payload.status.slice(1)) + '</span>');

			let actions = '<div class="mb-detail-actions">';
			if (payload.status !== 'confirmed') {
				actions += '<a href="' + payload.confirmUrl + '" class="mb-row-action mb-row-action-approve">Approve</a>';
			}
			if (payload.status !== 'cancelled') {
				actions += '<a href="' + payload.cancelUrl + '" class="mb-row-action mb-row-action-cancel" onclick="return confirm(\'Are you sure you want to cancel this booking?\');">Cancel</a>';
			}
			actions += '</div>';

			$body.html(html + actions);
			$overlay.css('display', 'flex');
		}

		function closeDetails() {
			$overlay.css('display', 'none');
			$body.empty();
		}

		$(document).on('click', '.mb-booking-view-link', function () {
			const $row = $(this).closest('tr.mb-booking-row');
			const payload = $row.data('booking-payload');
			if (payload) {
				openDetails(payload);
			}
		});

		$overlay.on('click', function (e) {
			if (e.target === this) closeDetails();
		});
		$(document).on('click', '.mb-booking-details-close', closeDetails);
		$(document).on('keydown', function (e) {
			if (e.key === 'Escape') closeDetails();
		});
	}

	$(function () {
		initBookingDetailsModal();
	});

})(jQuery);
