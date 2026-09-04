/**
 * Guided Multi-Step Booking Funnel Controller
 *
 * @package MyBookingEngine
 */

(function() {
	'use strict';

	class MbBookingFunnel {
		constructor() {
			this.modal = null;
			this.currentStep = 1;
			this.entityData = null;
			this.model = 'hourly_slot';
			this.currencySymbol = (window.mbEngineData && window.mbEngineData.currencySymbol) || '$';
			this.selectedService = null;
			this.selectedDates = { startDate: null, endDate: null, nights: 1 };
			this.selectedSlot = null;
			this.liveTotal = null;
			this.slotsLoading = false;
			// True only once the server has actually confirmed the current
			// date/range or slot is bookable — separate from selectedDates
			// being set, which just reflects the calendar click and must
			// not by itself unblock the Next button.
			this.availabilityConfirmed = false;
			this.guests = 1;
			this.customer = { name: '', email: '', phone: '', notes: '' };
			this.init();
		}

		init() {
			document.addEventListener('click', (e) => {
				const startBtn = e.target.closest('#mb-start-booking-btn, .mb-trigger-modal, .mb-btn-reserve, #mb-trigger-date-picker, .mb-date-field, .mb-booking-funnel-trigger');
				if (startBtn) {
					e.preventDefault();
					const source = startBtn.closest('[data-entity-id]') || startBtn;
					const entityId = source.dataset.entityId || (window.mbSingleEntityId || 0);
					const knownModel = source.dataset.model || '';
					this.open(entityId, knownModel);
				}
			});
		}

		open(entityId, knownModel) {
			this.entityId = entityId;
			this.currentStep = 1;
			// If the trigger element already told us the booking model (set
			// server-side from the listing's actual data), use it right away
			// instead of waiting on an async fetch — this is what the
			// calendar mode (single date vs. date range) renders from, so
			// getting it synchronously avoids any flash/fallback to the
			// wrong picker type.
			this.model = knownModel || 'hourly_slot';
			this.selectedDates = { startDate: null, endDate: null, nights: 1 };
			this.selectedSlot = null;
			this.liveTotal = null;
			this.availabilityConfirmed = false;

			let overlay = document.getElementById('mb-funnel-modal');
			if (!overlay) {
				overlay = document.createElement('div');
				overlay.id = 'mb-funnel-modal';
				overlay.className = 'mb-funnel-overlay';
				document.body.appendChild(overlay);
			}

			this.modal = overlay;
			this.render();
			overlay.classList.add('is-open');
			document.body.style.overflow = 'hidden';

			// Confirm/refine against the server (also fetches capacity_roster
			// event details). Only triggers a re-render if our synchronous
			// guess above turns out to have been wrong (e.g. no data-model
			// was available on the trigger element).
			this.fetchAvailability(this.todayStr()).then((data) => {
				if (!this.modal || !this.modal.classList.contains('is-open')) return;
				const previousModel = this.model;
				if (data && data.model) {
					this.model = data.model;
				}
				if (this.model === 'capacity_roster' && data) {
					this.applyRosterAvailability(data);
				}
				// The Step 1 layout differs entirely per model (calendar +
				// slots vs. calendar + trip summary vs. no calendar at all),
				// so re-render if we guessed wrong before this resolved.
				if (this.currentStep === 1 && this.model !== previousModel) {
					this.render();
				}
			});
		}

		close() {
			if (this.modal) {
				this.modal.classList.remove('is-open');
				document.body.style.overflow = '';
			}
		}

		todayStr() {
			return new Date().toISOString().slice(0, 10);
		}

		restBase() {
			return (window.mbEngineData && window.mbEngineData.restUrl) ? window.mbEngineData.restUrl : '/wp-json/my-booking-engine/v1/';
		}

		restNonce() {
			return (window.mbEngineData && window.mbEngineData.nonce) ? window.mbEngineData.nonce : '';
		}

		formatMoney(val) {
			const n = parseFloat(val);
			return this.currencySymbol + (isNaN(n) ? '0.00' : n.toFixed(2));
		}

		/**
		 * Fetch availability / slots / pricing for the current entity from
		 * the REST /slots endpoint. Works for every booking model: hourly
		 * slots, day-rental and night-stay ranges, and fixed capacity events.
		 */
		fetchAvailability(startDate, endDate) {
			const params = new URLSearchParams({ entity_id: this.entityId, date: startDate || '' });
			if (endDate) {
				params.append('end_date', endDate);
			}
			return fetch(this.restBase() + 'slots?' + params.toString(), {
				headers: { 'X-WP-Nonce': this.restNonce() }
			})
				.then((res) => res.json())
				.then((res) => (res && res.success) ? res.data : null)
				.catch(() => null);
		}

		/**
		 * Step 1 markup: a modern inline two-column split — interactive
		 * calendar on the left, live availability/slots + a sticky price
		 * card on the right. The right column's contents depend on model:
		 * hourly slots get a clickable grid, ranges get a live trip summary,
		 * fixed events skip the calendar entirely.
		 */
		getDatePickStepHtml() {
			if (this.model === 'capacity_roster') {
				return `
					<div class="mb-step-view">
						<h3 class="mb-step-title">Confirm Your Spot</h3>
						<p class="mb-step-desc">This is a fixed-schedule event. Review the details and reserve your seat below.</p>
						<div id="mb-step1-error" class="mb-funnel-alert mb-funnel-alert-danger" style="display:none;"></div>
						<div class="mb-live-price-card mb-live-price-card-static" id="mb-live-price-card">
							${this.priceCardInnerHtml()}
						</div>
					</div>
				`;
			}

			const isRange = (this.model === 'day_rental' || this.model === 'night_stay');
			const calMode = isRange ? 'range' : 'single';
			const heading = isRange ? 'Select Your Dates' : 'Select Date & Time';
			const desc = isRange
				? 'Pick your start and end dates on the calendar — pricing and availability update instantly.'
				: 'Pick a date, then choose an available time slot — pricing updates instantly.';

			return `
				<div class="mb-step-view">
					<h3 class="mb-step-title">${heading}</h3>
					<p class="mb-step-desc">${desc}</p>
					<div id="mb-step1-error" class="mb-funnel-alert mb-funnel-alert-danger" style="display:none;"></div>
					<div class="mb-funnel-split">
						<div class="mb-funnel-split-left">
							<div class="mb-funnel-calendar-wrap">
								<div id="mb-funnel-calendar" data-entity-id="${this.entityId}" data-mode="${calMode}"></div>
							</div>
						</div>
						<div class="mb-funnel-split-right">
							<div class="mb-funnel-slots-panel" id="mb-funnel-slots-panel">
								<p class="mb-slots-placeholder">${isRange ? 'Select your dates on the calendar to see the price.' : 'Select a date on the calendar to see available times.'}</p>
							</div>
							<div class="mb-live-price-card" id="mb-live-price-card">
								${this.priceCardInnerHtml()}
							</div>
						</div>
					</div>
				</div>
			`;
		}

		priceCardInnerHtml() {
			if (this.model === 'capacity_roster' && this.entityData) {
				const spots = this.entityData.available_spots;
				return `
					<div class="mb-price-card-row"><span>Event Window</span><strong>${this.entityData.start_datetime || '—'} → ${this.entityData.end_datetime || '—'}</strong></div>
					<div class="mb-price-card-row"><span>Seats Remaining</span><strong>${typeof spots === 'number' ? spots : '—'}</strong></div>
					<div class="mb-price-card-row mb-price-card-total"><span>Price / Person</span><strong id="mb-price-card-total">${this.formatMoney(this.entityData.ticket_price || 0)}</strong></div>
					<p class="mb-price-card-hint">Instant confirmation, no waiting.</p>
				`;
			}

			const dateLabel = this.selectedDates.startDate
				? (this.selectedDates.endDate && this.selectedDates.endDate !== this.selectedDates.startDate
					? `${this.selectedDates.startDate} → ${this.selectedDates.endDate}`
					: this.selectedDates.startDate)
				: '—';
			const slotLabel = this.selectedSlot ? ` · ${this.selectedSlot.start_time}–${this.selectedSlot.end_time}` : '';
			const totalLabel = (this.liveTotal !== null && this.liveTotal !== undefined) ? this.formatMoney(this.liveTotal) : '—';

			return `
				<div class="mb-price-card-row"><span>Selected</span><strong id="mb-price-card-dates">${dateLabel}${slotLabel}</strong></div>
				<div class="mb-price-card-row mb-price-card-total"><span>Total</span><strong id="mb-price-card-total">${totalLabel}</strong></div>
				<p class="mb-price-card-hint">Prices update instantly as you choose.</p>
			`;
		}

		refreshPriceCard() {
			const card = this.modal.querySelector('#mb-live-price-card');
			if (card) {
				card.innerHTML = this.priceCardInnerHtml();
				const totalEl = card.querySelector('.mb-price-card-total strong');
				if (totalEl) {
					totalEl.classList.add('is-updated');
					setTimeout(() => totalEl.classList.remove('is-updated'), 220);
				}
			}
		}

		applyRosterAvailability(data) {
			this.entityData = data;
			this.selectedDates = {
				startDate: data.start_datetime ? data.start_datetime.slice(0, 10) : this.todayStr(),
				endDate: data.end_datetime ? data.end_datetime.slice(0, 10) : this.todayStr(),
				nights: 1
			};
			this.liveTotal = data.ticket_price || 0;
			if (this.modal && this.currentStep === 1) {
				this.refreshPriceCard();
			}
		}

		/**
		 * Handle a calendar date (or range) selection: fetch fresh
		 * availability and either populate the time-slot grid (hourly
		 * model) or update the live price card directly (range models).
		 */
		handleDateSelection(detail) {
			this.selectedDates = detail;
			this.selectedSlot = null;
			this.availabilityConfirmed = false;
			const panel = this.modal.querySelector('#mb-funnel-slots-panel');
			this.clearStep1Error();

			if (!detail.startDate) {
				this.liveTotal = null;
				this.refreshPriceCard();
				return;
			}

			if (this.model === 'hourly_slot') {
				if (panel) {
					panel.innerHTML = '<p class="mb-slots-loading">Loading available times…</p>';
				}
				this.liveTotal = null;
				this.refreshPriceCard();

				this.fetchAvailability(detail.startDate).then((data) => {
					if (!panel || !this.modal) return;
					if (!data) {
						panel.innerHTML = this.availabilityErrorHtml('Could not check availability. Please try again.');
						return;
					}
					if (!data.available || !data.slots || !data.slots.length) {
						panel.innerHTML = `<p class="mb-slots-empty">${data.reason || 'No available time slots for this date.'}</p>`;
						return;
					}
					panel.innerHTML = `<div class="mb-slots-grid-inline">${data.slots.map((slot, i) => `
						<button type="button" class="mb-slot-chip ${slot.is_available ? '' : 'is-disabled'}" data-slot-index="${i}" ${slot.is_available ? '' : 'disabled'}>
							${slot.start_time} – ${slot.end_time}
						</button>
					`).join('')}</div>`;
					panel.dataset.slots = JSON.stringify(data.slots);
				});
				return;
			}

			// Range models (day_rental / night_stay): only price once both
			// ends of the range are chosen.
			if (!detail.endDate) {
				if (panel) {
					panel.innerHTML = '<p class="mb-slots-placeholder">Pick an end date to see the total price.</p>';
				}
				this.liveTotal = null;
				this.refreshPriceCard();
				return;
			}

			if (panel) {
				panel.innerHTML = '<p class="mb-slots-loading">Checking availability…</p>';
			}

			this.fetchAvailability(detail.startDate, detail.endDate).then((data) => {
				if (!panel || !this.modal) return;
				if (!data) {
					panel.innerHTML = this.availabilityErrorHtml('Could not check availability. Please try again.');
					return;
				}
				if (!data.is_available) {
					panel.innerHTML = `<p class="mb-slots-empty">${data.error || 'This date range is not available. Please choose different dates.'}</p>`;
					this.liveTotal = null;
					this.refreshPriceCard();
					return;
				}
				const unit = this.model === 'night_stay' ? 'night' : 'day';
				const count = data.nights_count || data.days_count || 1;
				panel.innerHTML = `
					<div class="mb-trip-summary">
						<div class="mb-trip-summary-row"><span>${count} ${unit}${count !== 1 ? 's' : ''}</span><strong>${data.available_spots} spot${data.available_spots !== 1 ? 's' : ''} left</strong></div>
						<div class="mb-trip-summary-badge is-available">✓ Available for your dates</div>
					</div>
				`;
				this.availabilityConfirmed = true;
				this.liveTotal = data.total_price;
				this.selectedDates.nights = count;
				this.refreshPriceCard();
			});
		}

		/**
		 * Shared markup for a failed availability check, with a retry
		 * action instead of a dead end — re-runs the same check for
		 * whatever dates are currently selected.
		 */
		availabilityErrorHtml(message) {
			return `<p class="mb-slots-empty">${message} <button type="button" class="mb-btn-link mb-retry-availability">Try again</button></p>`;
		}

		clearStep1Error() {
			const err1 = this.modal && this.modal.querySelector('#mb-step1-error');
			if (err1) {
				err1.style.display = 'none';
			}
		}

		selectSlot(index) {
			const panel = this.modal.querySelector('#mb-funnel-slots-panel');
			if (!panel || !panel.dataset.slots) return;
			const slots = JSON.parse(panel.dataset.slots);
			const slot = slots[index];
			if (!slot || !slot.is_available) return;

			this.selectedSlot = slot;
			this.selectedDates.endDate = this.selectedDates.startDate;
			this.liveTotal = slot.price;
			this.availabilityConfirmed = true;

			panel.querySelectorAll('.mb-slot-chip').forEach((chip, i) => {
				chip.classList.toggle('is-selected', i === index);
			});

			this.refreshPriceCard();
		}

		render() {
			if (!this.modal) return;

			this.modal.innerHTML = `
				<div class="mb-funnel-container">
					<div class="mb-funnel-header">
						<div class="mb-funnel-steps-indicator">
							<span class="mb-step-dot ${this.currentStep >= 1 ? 'is-active' : ''}">1</span>
							<span class="mb-step-line ${this.currentStep >= 2 ? 'is-active' : ''}"></span>
							<span class="mb-step-dot ${this.currentStep >= 2 ? 'is-active' : ''}">2</span>
							<span class="mb-step-line ${this.currentStep >= 3 ? 'is-active' : ''}"></span>
							<span class="mb-step-dot ${this.currentStep >= 3 ? 'is-active' : ''}">3</span>
							<span class="mb-step-line ${this.currentStep >= 4 ? 'is-active' : ''}"></span>
							<span class="mb-step-dot ${this.currentStep >= 4 ? 'is-active' : ''}">4</span>
						</div>
						<button type="button" class="mb-funnel-close" aria-label="Close">&times;</button>
					</div>
					<div class="mb-funnel-body" id="mb-funnel-step-content">
						${this.getStepHtml()}
					</div>
					<div class="mb-funnel-footer">
						${this.currentStep > 1 ? '<button type="button" class="mb-btn mb-btn-outline" id="mb-funnel-prev">Back</button>' : '<div></div>'}
						${this.currentStep < 4 ? '<button type="button" class="mb-btn mb-btn-primary" id="mb-funnel-next">Continue</button>' : '<button type="button" class="mb-btn mb-btn-primary mb-btn-confirm" id="mb-funnel-submit">Confirm Reservation</button>'}
					</div>
				</div>
			`;

			this.bindStepEvents();
		}

		getStepHtml() {
			switch (this.currentStep) {
				case 1:
					return this.getDatePickStepHtml();
				case 2:
					return `
						<div class="mb-step-view">
							<h3 class="mb-step-title">Guests & Trip Details</h3>
							<p class="mb-step-desc">Specify the number of attendees and any special arrangements.</p>
							<div id="mb-step2-error" class="mb-funnel-alert mb-funnel-alert-danger" style="display:none;"></div>
							<div class="mb-funnel-form-card">
								<div class="mb-funnel-field">
									<label class="mb-funnel-label" for="mb-funnel-guests">Party Size / Spots</label>
									<div class="mb-input-icon-wrap">
										<span class="mb-input-icon">👥</span>
										<select id="mb-funnel-guests" class="mb-funnel-select">
											<option value="1" ${this.guests === 1 ? 'selected' : ''}>1 Guest</option>
											<option value="2" ${this.guests === 2 ? 'selected' : ''}>2 Guests</option>
											<option value="3" ${this.guests === 3 ? 'selected' : ''}>3 Guests</option>
											<option value="4" ${this.guests === 4 ? 'selected' : ''}>4 Guests</option>
											<option value="5" ${this.guests === 5 ? 'selected' : ''}>5+ Guests</option>
										</select>
									</div>
								</div>
								<div class="mb-funnel-field" style="margin-top:16px;">
									<label class="mb-funnel-label" for="mb-funnel-notes">Special Requests & Notes (Optional)</label>
									<div class="mb-input-icon-wrap">
										<textarea id="mb-funnel-notes" class="mb-funnel-textarea" rows="3" placeholder="Estimated arrival time, dietary preferences, or accessibility needs...">${this.customer.notes || ''}</textarea>
									</div>
								</div>
							</div>
						</div>
					`;
				case 3:
					return `
						<div class="mb-step-view">
							<h3 class="mb-step-title">Your Contact Information</h3>
							<p class="mb-step-desc">We will send your instant booking confirmation and voucher to this address.</p>
							<div id="mb-step3-error" class="mb-funnel-alert mb-funnel-alert-danger" style="display:none;"></div>
							<div class="mb-funnel-form-card">
								<div class="mb-funnel-grid-2">
									<div class="mb-funnel-field">
										<label class="mb-funnel-label" for="mb-funnel-name">Full Name <span class="mb-required">*</span></label>
										<div class="mb-input-icon-wrap">
											<span class="mb-input-icon">👤</span>
											<input type="text" id="mb-funnel-name" class="mb-funnel-input" value="${this.customer.name || ''}" placeholder="e.g. Sarah Connor" required autocomplete="name">
										</div>
										<span class="mb-field-error" id="mb-err-name"></span>
									</div>

									<div class="mb-funnel-field">
										<label class="mb-funnel-label" for="mb-funnel-email">Email Address <span class="mb-required">*</span></label>
										<div class="mb-input-icon-wrap">
											<span class="mb-input-icon">✉️</span>
											<input type="email" id="mb-funnel-email" class="mb-funnel-input" value="${this.customer.email || ''}" placeholder="sarah@example.com" required autocomplete="email">
										</div>
										<span class="mb-field-error" id="mb-err-email"></span>
									</div>
								</div>

								<div class="mb-funnel-grid-2" style="margin-top:10px;">
									<div class="mb-funnel-field">
										<label class="mb-funnel-label" for="mb-funnel-phone">Phone Number <span class="mb-required">*</span></label>
										<div class="mb-input-icon-wrap">
											<span class="mb-input-icon">📞</span>
											<input type="tel" id="mb-funnel-phone" class="mb-funnel-input" value="${this.customer.phone || ''}" placeholder="+1 (555) 234-5678" required autocomplete="tel">
										</div>
										<span class="mb-field-error" id="mb-err-phone"></span>
									</div>

									<div class="mb-funnel-field">
										<label class="mb-funnel-label" for="mb-funnel-notes-step3">Special Notes (Optional)</label>
										<div class="mb-input-icon-wrap">
											<span class="mb-input-icon">📝</span>
											<input type="text" id="mb-funnel-notes-step3" class="mb-funnel-input" value="${this.customer.notes || ''}" placeholder="Arrival time, preferences...">
										</div>
									</div>
								</div>
							</div>
						</div>
					`;
				case 4:
					const nights = this.selectedDates.nights || 1;
					const isRangeModel = (this.model === 'day_rental' || this.model === 'night_stay');
					let whenLabel = this.selectedDates.startDate || 'Selected';
					if (this.model === 'hourly_slot' && this.selectedSlot) {
						whenLabel = `${this.selectedDates.startDate} · ${this.selectedSlot.start_time}–${this.selectedSlot.end_time}`;
					} else if (this.model === 'capacity_roster' && this.entityData) {
						whenLabel = `${this.entityData.start_datetime} → ${this.entityData.end_datetime}`;
					} else if (isRangeModel && this.selectedDates.endDate) {
						whenLabel = `${this.selectedDates.startDate} → ${this.selectedDates.endDate} (${nights} ${this.model === 'night_stay' ? 'night' : 'day'}${nights !== 1 ? 's' : ''})`;
					}
					return `
						<div class="mb-step-view">
							<h3 class="mb-step-title">Review & Complete Reservation</h3>
							<p class="mb-step-desc">Please review your reservation details before confirming.</p>
							<div class="mb-review-summary-card">
								<div class="mb-summary-line">
									<span class="mb-summary-label">📅 When</span>
									<strong>${whenLabel}</strong>
								</div>
								<div class="mb-summary-line">
									<span class="mb-summary-label">👥 Party Size</span>
									<strong>${this.guests} ${this.guests === 1 ? 'Person' : 'People'}</strong>
								</div>
								<div class="mb-summary-line">
									<span class="mb-summary-label">👤 Guest Name</span>
									<strong>${this.customer.name}</strong>
								</div>
								<div class="mb-summary-line">
									<span class="mb-summary-label">✉️ Email</span>
									<strong>${this.customer.email}</strong>
								</div>
								<div class="mb-summary-line">
									<span class="mb-summary-label">📞 Phone</span>
									<strong>${this.customer.phone}</strong>
								</div>
								${this.customer.notes ? `
								<div class="mb-summary-line">
									<span class="mb-summary-label">📝 Notes</span>
									<span>${this.customer.notes}</span>
								</div>` : ''}
								<hr class="mb-divider" />
								<div class="mb-summary-line">
									<span class="mb-summary-label">💳 Total</span>
									<strong>${(this.liveTotal !== null && this.liveTotal !== undefined) ? this.formatMoney(this.liveTotal) : 'Calculated at confirmation'}</strong>
								</div>
								<div class="mb-summary-line mb-summary-total">
									<span>Reservation Status</span>
									<strong style="color: #16a34a;">Guaranteed & Ready</strong>
								</div>
							</div>

							<div class="mb-terms-agreement">
								<label class="mb-terms-label">
									<input type="checkbox" id="mb-agree-terms" checked>
									<span>I agree to the cancellation terms, house rules, and verified booking policy.</span>
								</label>
							</div>
						</div>
					`;
			}
		}

		bindStepEvents() {
			this.modal.querySelector('.mb-funnel-close').addEventListener('click', () => this.close());

			const nextBtn = this.modal.querySelector('#mb-funnel-next');
			if (nextBtn) {
				nextBtn.addEventListener('click', () => {
					// Step 1 Validation: Dates (and slot, for hourly listings) must be selected
					if (this.currentStep === 1) {
						const err1 = this.modal.querySelector('#mb-step1-error');

						if (this.model === 'capacity_roster') {
							err1.style.display = 'none';
						} else if (!this.selectedDates.startDate) {
							err1.textContent = 'Please select a date on the calendar before proceeding.';
							err1.style.display = 'block';
							return;
						} else if (this.model === 'hourly_slot' && !this.selectedSlot) {
							err1.textContent = 'Please choose an available time slot before proceeding.';
							err1.style.display = 'block';
							return;
						} else if ((this.model === 'day_rental' || this.model === 'night_stay') && !this.selectedDates.endDate) {
							err1.textContent = 'Please select both check-in and check-out dates on the calendar.';
							err1.style.display = 'block';
							return;
						} else if (!this.availabilityConfirmed) {
							err1.textContent = 'Please wait for availability to be confirmed before continuing — if the check failed, use "Try again".';
							err1.style.display = 'block';
							return;
						} else {
							err1.style.display = 'none';
						}
					}

					// Step 2 Validation: Guests
					if (this.currentStep === 2) {
						const guestsEl = this.modal.querySelector('#mb-funnel-guests');
						this.guests = guestsEl ? parseInt(guestsEl.value, 10) : 1;
						const notesEl = this.modal.querySelector('#mb-funnel-notes');
						this.customer.notes = notesEl ? notesEl.value.trim() : '';
					}

					// Step 3 Validation: Name, Email, Phone
					if (this.currentStep === 3) {
						const nameEl = this.modal.querySelector('#mb-funnel-name');
						const emailEl = this.modal.querySelector('#mb-funnel-email');
						const phoneEl = this.modal.querySelector('#mb-funnel-phone');
						const err3 = this.modal.querySelector('#mb-step3-error');

						const name = nameEl.value.trim();
						const email = emailEl.value.trim();
						const phone = phoneEl.value.trim();

						let hasError = false;

						// Reset field errors
						nameEl.classList.remove('is-invalid');
						emailEl.classList.remove('is-invalid');
						phoneEl.classList.remove('is-invalid');
						this.modal.querySelector('#mb-err-name').textContent = '';
						this.modal.querySelector('#mb-err-email').textContent = '';
						this.modal.querySelector('#mb-err-phone').textContent = '';

						if (!name || name.length < 2) {
							nameEl.classList.add('is-invalid');
							this.modal.querySelector('#mb-err-name').textContent = 'Please enter your full name (minimum 2 characters).';
							hasError = true;
						}

						const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
						if (!email || !emailRegex.test(email)) {
							emailEl.classList.add('is-invalid');
							this.modal.querySelector('#mb-err-email').textContent = 'Please enter a valid email address (e.g. name@domain.com).';
							hasError = true;
						}

						if (!phone || phone.length < 5) {
							phoneEl.classList.add('is-invalid');
							this.modal.querySelector('#mb-err-phone').textContent = 'Please enter a valid contact phone number.';
							hasError = true;
						}

						if (hasError) {
							err3.textContent = 'Please complete all required fields highlighted in red below.';
							err3.style.display = 'block';
							return;
						}

						err3.style.display = 'none';
						this.customer.name = name;
						this.customer.email = email;
						this.customer.phone = phone;
					}

					this.currentStep++;
					this.render();
				});
			}

			const prevBtn = this.modal.querySelector('#mb-funnel-prev');
			if (prevBtn) {
				prevBtn.addEventListener('click', () => {
					this.currentStep--;
					this.render();
				});
			}

			const submitBtn = this.modal.querySelector('#mb-funnel-submit');
			if (submitBtn) {
				submitBtn.addEventListener('click', () => {
					const agreeTerms = this.modal.querySelector('#mb-agree-terms');
					if (agreeTerms && !agreeTerms.checked) {
						alert('Please accept the cancellation policy and booking terms to complete your reservation.');
						return;
					}
					this.submitBooking(submitBtn);
				});
			}

			// Mount calendar if on Step 1 (fixed-schedule events have no calendar)
			if (this.currentStep === 1) {
				const calEl = this.modal.querySelector('#mb-funnel-calendar');
				if (calEl && window.MbUnifiedCalendar) {
					new window.MbUnifiedCalendar(calEl, {
						entityId: this.entityId,
						mode: calEl.dataset.mode || 'single',
						onSelect: (detail) => {
							this.handleDateSelection(detail);
						}
					});
				}

				// Delegate clicks on dynamically-rendered time-slot chips
				// and the "Try again" retry link shown on a failed check.
				const slotsPanel = this.modal.querySelector('#mb-funnel-slots-panel');
				if (slotsPanel) {
					slotsPanel.addEventListener('click', (e) => {
						const chip = e.target.closest('.mb-slot-chip');
						if (chip && !chip.disabled) {
							this.selectSlot(parseInt(chip.dataset.slotIndex, 10));
							return;
						}
						if (e.target.closest('.mb-retry-availability')) {
							this.handleDateSelection(this.selectedDates);
						}
					});
				}
			}
		}

		submitBooking(btn) {
			btn.disabled = true;
			btn.textContent = 'Reserving...';

			const restUrl = (window.mbEngineData && window.mbEngineData.restUrl) ? window.mbEngineData.restUrl : '/wp-json/my-booking-engine/v1/';
			const nonce = (window.mbEngineData && window.mbEngineData.nonce) ? window.mbEngineData.nonce : '';

			let startFull;
			let endFull;

			if (this.model === 'hourly_slot' && this.selectedSlot) {
				startFull = this.selectedSlot.start_datetime;
				endFull = this.selectedSlot.end_datetime;
			} else if (this.model === 'capacity_roster' && this.entityData) {
				startFull = this.entityData.start_datetime;
				endFull = this.entityData.end_datetime;
			} else {
				const startDateVal = (this.selectedDates.startDate || new Date().toISOString().slice(0, 10));
				const endDateVal = (this.selectedDates.endDate || this.selectedDates.startDate || new Date().toISOString().slice(0, 10));
				startFull = startDateVal.length > 10 ? startDateVal : (startDateVal + ' 10:00:00');
				endFull = endDateVal.length > 10 ? endDateVal : (endDateVal + ' 12:00:00');
			}

			const payload = {
				entity_id: this.entityId,
				customer_name: this.customer.name,
				customer_email: this.customer.email,
				customer_phone: this.customer.phone,
				start_time: startFull,
				end_time: endFull,
				booking_start: startFull,
				booking_end: endFull,
				capacity: this.guests
			};

			fetch(restUrl + 'book', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce
				},
				body: JSON.stringify(payload)
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					if (data.redirect_url) {
						window.location.href = data.redirect_url;
					} else {
						this.modal.querySelector('#mb-funnel-step-content').innerHTML = `
							<div class="mb-success-view">
								<div class="mb-success-icon">🎉</div>
								<h3>Reservation Confirmed!</h3>
								<p>Booking #${data.booking_id} has been reserved. A confirmation email was dispatched to <strong>${this.customer.email}</strong>.</p>
								<button type="button" class="mb-btn mb-btn-primary" onclick="window.location.reload();">Done</button>
							</div>
						`;
						this.modal.querySelector('.mb-funnel-footer').style.display = 'none';
					}
				} else {
					alert(data.message || 'Booking could not be confirmed. Please try another date or time slot.');
					btn.disabled = false;
					btn.textContent = 'Confirm Reservation';
				}
			})
			.catch(err => {
				alert('Network error submitting reservation. Please check your connection.');
				btn.disabled = false;
				btn.textContent = 'Confirm Reservation';
			});
		}
	}

	window.MbBookingFunnel = new MbBookingFunnel();

})();
