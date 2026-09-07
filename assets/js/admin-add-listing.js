/**
 * "Add New Listing" app page: smart per-type defaults, Advanced Settings
 * reveal, and AJAX publish. Loaded only on admin.php?page=mb-add-listing.
 *
 * @package MyBookingEngine
 */

(function($) {
	'use strict';

	const SCHEDULE_PRESETS = {
		weekdays: { days: [1, 2, 3, 4, 5], start: '09:00', end: '17:00' },
		six_day:  { days: [1, 2, 3, 4, 5, 6], start: '09:00', end: '18:00' },
		all_week: { days: [0, 1, 2, 3, 4, 5, 6], start: '09:00', end: '17:00' }
	};

	$(document).ready(function() {
		const $root = $('#mb-app-add-listing');
		if (!$root.length) return;

		const isEditMode = $root.data('mode') === 'edit';
		const publishBtnHtml = isEditMode ? '💾 Update Listing' : '🚀 Publish Listing';

		applyTypeDefaults($root.find('.mb-app-type-card.selected'), isEditMode);
		renderExistingGallery();
		initServicesCard(isEditMode);

		// Type card selection -> smart defaults
		$('.mb-app-type-card').on('click', function() {
			applyTypeDefaults($(this), false);
		});

		$('#mb_model_type').on('change', function() {
			updateCapacityLabels($(this).val());
		});

		function applyTypeDefaults($card, isInitialLoad) {
			if (!$card.length) return;

			const type     = $card.data('type');
			const model    = $card.data('model');
			const price    = $card.data('price');
			const slot     = $card.data('slot');
			const before   = $card.data('before');
			const after    = $card.data('after');
			const min      = $card.data('min');
			const max      = $card.data('max');
			const checkin  = $card.data('checkin');
			const checkout = $card.data('checkout');
			const capacity = $card.data('capacity');
			const schedule = $card.data('schedule');
			const label    = $card.data('label');

			$('#mb-app-smart-type-label').text(label);
			$('#mb-app-smart-banner').addClass('is-pulsing');
			setTimeout(() => $('#mb-app-smart-banner').removeClass('is-pulsing'), 500);

			// On an existing listing (edit mode, first load) don't clobber
			// its saved settings just because its type card is "selected" —
			// only apply defaults when the admin actively picks a new type.
			if (!isInitialLoad || !isEditMode) {
				if (model) {
					$('#mb_model_type').val(model).trigger('change');
				}
				if (price !== undefined && price !== '' && !$('#mb_base_price').data('user-edited')) {
					$('#mb_base_price').val(price);
				}
				if (slot) $('#mb_slot_duration').val(slot);
				if (before !== undefined && before !== '') $('#mb_buffer_before').val(before);
				if (after !== undefined && after !== '') $('#mb_buffer_after').val(after);
				if (min) $('#mb_min_duration').val(min);
				if (max) $('#mb_max_duration').val(max);
				if (checkin) $('#mb_checkin_time').val(checkin);
				if (checkout) $('#mb_checkout_time').val(checkout);
				if (capacity !== undefined && capacity !== '' && !$('#mb_capacity').data('user-edited')) {
					$('.mb-capacity-sync-field').val(capacity);
				}

				if (schedule && SCHEDULE_PRESETS[schedule]) {
					applySchedulePreset(SCHEDULE_PRESETS[schedule]);
				}
			}

			updateCapacityLabels(model);
			updateDefaultsSummary($card);
			toggleServicesCard(type);
		}

		function updateCapacityLabels(model) {
			if (model === 'night_stay' || model === 'day_rental') {
				$('#mb_capacity_general_label').html('👥 Maximum Guests Allowed <span class="mb-req">*</span>');
				$('#mb_capacity_general_desc').text('Maximum number of guests / occupants permitted for this property rental.');
			} else if (model === 'capacity_roster') {
				$('#mb_capacity_general_label').html('👥 Event Capacity / Tickets <span class="mb-req">*</span>');
				$('#mb_capacity_general_desc').text('Total number of tickets or attendee spots available for this event.');
			} else {
				$('#mb_capacity_general_label').html('👥 Maximum Capacity / Spots <span class="mb-req">*</span>');
				$('#mb_capacity_general_desc').text('Max customers or tickets allowed per slot/booking.');
			}
		}

		// Two-way sync for property rental guests & capacity field
		$(document).on('input change', '.mb-capacity-sync-field', function() {
			const val = $(this).val();
			$('.mb-capacity-sync-field').not(this).val(val);
			$('#mb_capacity').data('user-edited', true);
			updateDefaultsSummary($('.mb-app-type-card.selected'));
		});

		// Live one-line recap of what the current type/settings mean, so
		// admins can see at a glance what "smart defaults" actually set.
		function updateDefaultsSummary($card) {
			const $summary = $('#mb-app-defaults-summary');
			if (!$summary.length) return;

			const model = $('#mb_model_type').val();
			const price = $('#mb_base_price').val();
			const currency = (window.mbEngineData && window.mbEngineData.currencySymbol) || '$';
			let bits = [];

			if (price) bits.push(currency + parseFloat(price).toFixed(2) + ' base price');

			if (model === 'hourly_slot') {
				const slot = $('#mb_slot_duration').val();
				if (slot) bits.push(slot + '-minute sessions');
			} else if (model === 'day_rental' || model === 'night_stay') {
				const min = $('#mb_min_duration').val();
				const max = $('#mb_max_duration').val();
				const cap = $('#mb_capacity_property').val() || $('#mb_capacity').val();
				if (min && max) bits.push(min + '–' + max + (model === 'night_stay' ? ' night stay' : ' day rental'));
				if (cap) bits.push('up to ' + cap + ' guests');
			} else if (model === 'capacity_roster') {
				bits.push('fixed-schedule event');
			}

			const openDays = $('.mb-schedule-row.is-open').length;
			if (openDays) bits.push(openDays + ' day' + (openDays !== 1 ? 's' : '') + ' open per week');

			$summary.text(bits.length ? bits.join(' · ') : '');
		}

		// Services & Add-ons card is only relevant for menu-driven listing
		// types (currently: Salon & Spa).
		function toggleServicesCard(type) {
			$('#mb-app-services-card').toggle(type === 'salon');
		}

		// Track manual price edits so re-selecting a type doesn't clobber a
		// price the admin has already customized.
		$('#mb_base_price').on('input', function() {
			$(this).data('user-edited', true);
		});

		function applySchedulePreset(preset) {
			$('.mb-schedule-row').each(function(i) {
				// Rows are rendered Mon..Sun with day index 1..6,0 — read the
				// checkbox's own name to know its actual day index.
				const $row = $(this);
				const $checkbox = $row.find('.mb-day-switch');
				const nameAttr = $checkbox.attr('name') || '';
				const match = nameAttr.match(/mb_schedule\[(\d+)\]/);
				const dayIdx = match ? parseInt(match[1], 10) : i;
				const isOpen = preset.days.indexOf(dayIdx) !== -1;

				$checkbox.prop('checked', isOpen);
				$row.find('.mb-time-picker').eq(0).val(preset.start);
				$row.find('.mb-time-picker').eq(1).val(preset.end);
				toggleScheduleRow($row, isOpen);
			});
		}

		function toggleScheduleRow($row, isOpen) {
			const $label = $row.find('.mb-switch-label');
			const $times = $row.find('.mb-sched-times');
			$row.toggleClass('is-open', isOpen).toggleClass('is-closed', !isOpen);
			$label.text(isOpen ? 'Open' : 'Closed');
			$times.css({ opacity: isOpen ? 1 : 0.4, 'pointer-events': isOpen ? 'auto' : 'none' });
			updateDefaultsSummary();
		}

		// Manual day-switch toggling
		$(document).on('change', '.mb-day-switch', function() {
			toggleScheduleRow($(this).closest('.mb-schedule-row'), $(this).is(':checked'));
		});

		// Keep the summary line in sync with manual edits too, not just
		// type-card clicks and schedule toggles.
		$('#mb_base_price, #mb_slot_duration, #mb_min_duration, #mb_max_duration').on('input change', function() {
			updateDefaultsSummary();
		});
		$('#mb_model_type').on('change', function() {
			updateDefaultsSummary();
		});

		// Notification mark preview toggle (click / tap handler for mobile & accessible navigation)
		$(document).on('click', '.mb-type-notification-mark', function(e) {
			e.preventDefault();
			e.stopPropagation();
			const $card = $(this).closest('.mb-app-type-card');
			const wasActive = $card.hasClass('preview-active');
			$('.mb-app-type-card').removeClass('preview-active');
			if (!wasActive) {
				$card.addClass('preview-active');
			}
		});

		// Close preview popover when clicking outside
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.mb-app-type-card').length) {
				$('.mb-app-type-card').removeClass('preview-active');
			}
		});

		// Prevent clicks inside the mockup popover from inadvertently triggering type switch
		$(document).on('click', '.mb-layout-mockup-popover', function(e) {
			e.stopPropagation();
		});

		// AJAX publish
		$('#mb-app-form').on('submit', function(e) {
			e.preventDefault();

			const $form = $(this);
			const $btn = $('#mb-app-publish-btn');
			const $alert = $('#mb-app-alert');
			const title = $('#mb_quick_title').val().trim();
			const price = $('#mb_base_price').val();

			$alert.hide();

			if (!title) {
				showAlert('Please enter a listing title.');
				$('#mb_quick_title').trigger('focus');
				return;
			}
			if (price === '' || parseFloat(price) < 0) {
				showAlert('Please enter a valid base price.');
				$('#mb_base_price').trigger('focus');
				return;
			}

			syncServicesToHiddenField();

			$btn.prop('disabled', true).addClass('is-loading').html(isEditMode ? 'Saving…' : 'Publishing…');

			const payload = $form.serialize() +
				'&action=mb_quick_create_listing' +
				'&mb_quick_title=' + encodeURIComponent(title);

			$.post(window.mbAdminData?.ajaxUrl || ajaxurl, payload)
				.done(function(response) {
					if (response && response.success && response.data && response.data.redirect) {
						$btn.removeClass('is-loading').html(isEditMode ? '✓ Updated!' : '✓ Published!');
						window.location.href = response.data.redirect;
						return;
					}
					showAlert((response && response.data && response.data.message) || 'Could not save the listing. Please try again.');
					$btn.prop('disabled', false).removeClass('is-loading').html(publishBtnHtml);
				})
				.fail(function() {
					showAlert('Network error while saving. Please try again.');
					$btn.prop('disabled', false).removeClass('is-loading').html(publishBtnHtml);
				});
		});

		function showAlert(message) {
			$('#mb-app-alert').text(message).slideDown(150);
		}

		/**
		 * Existing gallery photos (edit mode): render thumbnail previews
		 * from the URLs the server embedded on #mb_gallery_preview, so an
		 * admin editing a listing sees its current photos immediately
		 * instead of an empty picker.
		 */
		function renderExistingGallery() {
			const $preview = $('#mb_gallery_preview');
			if (!$preview.length) return;

			let items = [];
			try {
				items = JSON.parse($preview.attr('data-existing') || '[]');
			} catch (err) {
				items = [];
			}

			items.forEach(function(item) {
				const url = typeof item === 'object' && item.url ? item.url : item;
				const id  = typeof item === 'object' && item.id ? String(item.id) : '';
				const dataAttr = id ? ' data-id="' + id + '"' : '';
				$preview.append(
					'<div class="mb-preview-thumb"' + dataAttr + '>' +
						'<img src="' + url + '" alt="">' +
						'<button type="button" class="mb-remove-thumb-btn" title="Remove image" aria-label="Remove image">&times;</button>' +
					'</div>'
				);
			});
		}

		/**
		 * Services & Add-ons card: renders existing rows (edit mode),
		 * lets the admin add/remove rows, and keeps the hidden JSON field
		 * in sync so it submits with the rest of the form.
		 */
		function initServicesCard() {
			const $rows = $('#mb-services-rows');
			const $hidden = $('#mb_services_json');
			if (!$rows.length || !$hidden.length) return;

			let services = [];
			try {
				services = JSON.parse($hidden.val() || '[]');
			} catch (err) {
				services = [];
			}

			if (services.length) {
				services.forEach(function(svc) {
					addServiceRow(svc);
				});
			} else {
				addServiceRow();
			}

			$('#mb_btn_add_service').on('click', function(e) {
				e.preventDefault();
				addServiceRow();
			});

			$rows.on('click', '.mb-service-remove', function(e) {
				e.preventDefault();
				$(this).closest('.mb-service-row').remove();
				syncServicesToHiddenField();
			});

			$rows.on('input change', 'input, textarea', function() {
				syncServicesToHiddenField();
			});

			function addServiceRow(svc) {
				svc = svc || { name: '', duration: '', price: '', description: '' };
				const $row = $(
					'<div class="mb-service-row" style="display:flex; gap:10px; align-items:flex-start; margin-bottom:12px; padding:12px; border:1px solid #e5e7eb; border-radius:6px;">' +
						'<div style="flex:2;"><label>Service Name</label><input type="text" class="widefat mb-svc-name" placeholder="e.g. Haircut" value="' + escapeAttr(svc.name) + '"></div>' +
						'<div style="flex:1;"><label>Duration (min)</label><input type="number" class="widefat mb-svc-duration" min="0" step="5" value="' + escapeAttr(svc.duration) + '"></div>' +
						'<div style="flex:1;"><label>Price</label><input type="number" class="widefat mb-svc-price" min="0" step="0.01" value="' + escapeAttr(svc.price) + '"></div>' +
						'<div style="flex:2;"><label>Description</label><input type="text" class="widefat mb-svc-desc" placeholder="Optional" value="' + escapeAttr(svc.description) + '"></div>' +
						'<button type="button" class="button button-link-delete mb-service-remove" style="margin-top:22px;" title="Remove">&times;</button>' +
					'</div>'
				);
				$rows.append($row);
				syncServicesToHiddenField();
			}
		}

		function syncServicesToHiddenField() {
			const $hidden = $('#mb_services_json');
			if (!$hidden.length) return;

			const services = [];
			$('#mb-services-rows .mb-service-row').each(function() {
				const $row = $(this);
				const name = $row.find('.mb-svc-name').val().trim();
				if (!name) return;
				services.push({
					name: name,
					duration: $row.find('.mb-svc-duration').val(),
					price: $row.find('.mb-svc-price').val(),
					description: $row.find('.mb-svc-desc').val()
				});
			});

			$hidden.val(JSON.stringify(services));
		}

		function escapeAttr(val) {
			return String(val === undefined || val === null ? '' : val).replace(/"/g, '&quot;');
		}

		// ── Palette Presets (Appearance & Colors card) ──────────────────────
		$(document).on('click', '.mb-al-palette', function () {
			var $btn    = $(this);
			var primary = $btn.data('primary');
			var hover   = $btn.data('hover');
			var accent  = $btn.data('accent');

			$('#mb_al_primary_color_picker').val(primary);
			$('#mb_al_primary_color').val(primary);
			$('#mb_al_primary_hover_picker').val(hover);
			$('#mb_al_primary_hover').val(hover);
			$('#mb_al_accent_color_picker').val(accent);
			$('#mb_al_accent_color').val(accent);
		});
	});

})(jQuery);
