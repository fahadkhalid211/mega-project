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

		applyTypeDefaults($root.find('.mb-app-type-card.selected'));

		// Type card selection -> smart defaults
		$('.mb-app-type-card').on('click', function() {
			applyTypeDefaults($(this));
		});

		function applyTypeDefaults($card) {
			if (!$card.length) return;

			const model    = $card.data('model');
			const price    = $card.data('price');
			const slot     = $card.data('slot');
			const before   = $card.data('before');
			const after    = $card.data('after');
			const min      = $card.data('min');
			const max      = $card.data('max');
			const checkin  = $card.data('checkin');
			const checkout = $card.data('checkout');
			const schedule = $card.data('schedule');
			const label    = $card.data('label');

			$('#mb-app-smart-type-label').text(label);
			$('#mb-app-smart-banner').addClass('is-pulsing');
			setTimeout(() => $('#mb-app-smart-banner').removeClass('is-pulsing'), 500);

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

			if (schedule && SCHEDULE_PRESETS[schedule]) {
				applySchedulePreset(SCHEDULE_PRESETS[schedule]);
			}
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
		}

		// Manual day-switch toggling
		$(document).on('change', '.mb-day-switch', function() {
			toggleScheduleRow($(this).closest('.mb-schedule-row'), $(this).is(':checked'));
		});

		// Advanced Settings reveal, animated
		const $advToggle = $('#mb-app-advanced-toggle');
		const $advPanel = $('#mb-app-advanced-panel');
		$advToggle.on('click', function() {
			const isOpen = $advPanel.hasClass('is-open');
			$advPanel.toggleClass('is-open', !isOpen);
			$advToggle.attr('aria-expanded', String(!isOpen)).toggleClass('is-open', !isOpen);
			if (!isOpen) {
				$advPanel.css('max-height', $advPanel.prop('scrollHeight') + 'px');
			} else {
				$advPanel.css('max-height', '0px');
			}
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

			$btn.prop('disabled', true).addClass('is-loading').html('Publishing…');

			const payload = $form.serialize() +
				'&action=mb_quick_create_listing' +
				'&mb_quick_title=' + encodeURIComponent(title);

			$.post(window.mbAdminData?.ajaxUrl || ajaxurl, payload)
				.done(function(response) {
					if (response && response.success && response.data && response.data.redirect) {
						$btn.removeClass('is-loading').html('✓ Published!');
						window.location.href = response.data.redirect;
						return;
					}
					showAlert((response && response.data && response.data.message) || 'Could not publish the listing. Please try again.');
					$btn.prop('disabled', false).removeClass('is-loading').html('🚀 Publish Listing');
				})
				.fail(function() {
					showAlert('Network error while publishing. Please try again.');
					$btn.prop('disabled', false).removeClass('is-loading').html('🚀 Publish Listing');
				});
		});

		function showAlert(message) {
			$('#mb-app-alert').text(message).slideDown(150);
		}
	});

})(jQuery);
