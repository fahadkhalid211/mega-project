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
			this.selectedService = null;
			this.selectedDates = { startDate: null, endDate: null, nights: 1 };
			this.selectedSlot = null;
			this.guests = 1;
			this.customer = { name: '', email: '', phone: '', notes: '' };
			this.init();
		}

		init() {
			document.addEventListener('click', (e) => {
				const startBtn = e.target.closest('#mb-start-booking-btn, .mb-trigger-modal, .mb-btn-reserve');
				if (startBtn) {
					e.preventDefault();
					const entityId = startBtn.dataset.entityId || (window.mbSingleEntityId || 0);
					this.open(entityId);
				}
			});
		}

		open(entityId) {
			this.entityId = entityId;
			this.currentStep = 1;

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
		}

		close() {
			if (this.modal) {
				this.modal.classList.remove('is-open');
				document.body.style.overflow = '';
			}
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
					return `
						<div class="mb-step-view">
							<h3 class="mb-step-title">Select Reservation Dates</h3>
							<p class="mb-step-desc">Pick your check-in and check-out dates on the interactive calendar below.</p>
							<div id="mb-step1-error" class="mb-funnel-alert mb-funnel-alert-danger" style="display:none;"></div>
							<div class="mb-funnel-calendar-wrap">
								<div id="mb-funnel-calendar" data-entity-id="${this.entityId}" data-mode="range"></div>
							</div>
						</div>
					`;
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
								<div class="mb-funnel-field">
									<label class="mb-funnel-label" for="mb-funnel-name">Full Name <span class="mb-required">*</span></label>
									<div class="mb-input-icon-wrap">
										<span class="mb-input-icon">👤</span>
										<input type="text" id="mb-funnel-name" class="mb-funnel-input" value="${this.customer.name || ''}" placeholder="e.g. Sarah Connor" required autocomplete="name">
									</div>
									<span class="mb-field-error" id="mb-err-name"></span>
								</div>

								<div class="mb-funnel-field" style="margin-top:14px;">
									<label class="mb-funnel-label" for="mb-funnel-email">Email Address <span class="mb-required">*</span></label>
									<div class="mb-input-icon-wrap">
										<span class="mb-input-icon">✉️</span>
										<input type="email" id="mb-funnel-email" class="mb-funnel-input" value="${this.customer.email || ''}" placeholder="sarah@example.com" required autocomplete="email">
									</div>
									<span class="mb-field-error" id="mb-err-email"></span>
								</div>

								<div class="mb-funnel-field" style="margin-top:14px;">
									<label class="mb-funnel-label" for="mb-funnel-phone">Phone Number <span class="mb-required">*</span></label>
									<div class="mb-input-icon-wrap">
										<span class="mb-input-icon">📞</span>
										<input type="tel" id="mb-funnel-phone" class="mb-funnel-input" value="${this.customer.phone || ''}" placeholder="+1 (555) 234-5678" required autocomplete="tel">
									</div>
									<span class="mb-field-error" id="mb-err-phone"></span>
								</div>
							</div>
						</div>
					`;
				case 4:
					const nights = this.selectedDates.nights || 1;
					return `
						<div class="mb-step-view">
							<h3 class="mb-step-title">Review & Complete Reservation</h3>
							<p class="mb-step-desc">Please review your reservation details before confirming.</p>
							<div class="mb-review-summary-card">
								<div class="mb-summary-line">
									<span class="mb-summary-label">📅 Dates</span>
									<strong>${this.selectedDates.startDate || 'Selected'} ${this.selectedDates.endDate ? '→ ' + this.selectedDates.endDate : ''} (${nights} night${nights !== 1 ? 's' : ''})</strong>
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
					// Step 1 Validation: Dates must be selected
					if (this.currentStep === 1) {
						const err1 = this.modal.querySelector('#mb-step1-error');
						if (!this.selectedDates.startDate) {
							err1.textContent = 'Please select a date on the calendar before proceeding.';
							err1.style.display = 'block';
							return;
						}
						// If in range mode and end date is missing
						const calEl = this.modal.querySelector('#mb-funnel-calendar');
						const mode = calEl ? calEl.dataset.mode : 'range';
						if (mode === 'range' && !this.selectedDates.endDate) {
							err1.textContent = 'Please select both check-in and check-out dates on the calendar.';
							err1.style.display = 'block';
							return;
						}
						err1.style.display = 'none';
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

			// Mount calendar if on Step 1
			if (this.currentStep === 1) {
				const calEl = this.modal.querySelector('#mb-funnel-calendar');
				if (calEl && window.MbUnifiedCalendar) {
					new window.MbUnifiedCalendar(calEl, {
						entityId: this.entityId,
						mode: 'range',
						onSelect: (detail) => {
							this.selectedDates = detail;
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

			const payload = {
				entity_id: this.entityId,
				customer_name: this.customer.name,
				customer_email: this.customer.email,
				customer_phone: this.customer.phone,
				booking_start: (this.selectedDates.startDate || new Date().toISOString().slice(0, 10)) + ' 10:00:00',
				booking_end: (this.selectedDates.endDate || this.selectedDates.startDate || new Date().toISOString().slice(0, 10)) + ' 12:00:00',
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
					if (data.checkout_url) {
						window.location.href = data.checkout_url;
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
