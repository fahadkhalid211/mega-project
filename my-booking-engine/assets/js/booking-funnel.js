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
							<p class="mb-step-desc">Pick your check-in and check-out or appointment date.</p>
							<div class="mb-funnel-calendar-wrap">
								<div id="mb-funnel-calendar" data-entity-id="${this.entityId}" data-mode="range"></div>
							</div>
						</div>
					`;
				case 2:
					return `
						<div class="mb-step-view">
							<h3 class="mb-step-title">Party Size & Special Requests</h3>
							<div class="mb-form-group">
								<label for="mb-funnel-guests">Number of Guests / Spots</label>
								<select id="mb-funnel-guests" class="mb-form-select">
									<option value="1" ${this.guests === 1 ? 'selected' : ''}>1 person</option>
									<option value="2" ${this.guests === 2 ? 'selected' : ''}>2 people</option>
									<option value="3" ${this.guests === 3 ? 'selected' : ''}>3 people</option>
									<option value="4" ${this.guests === 4 ? 'selected' : ''}>4+ people</option>
								</select>
							</div>
							<div class="mb-form-group">
								<label for="mb-funnel-notes">Special Requests / Notes</label>
								<textarea id="mb-funnel-notes" class="mb-form-textarea" placeholder="Any specific requirements, dietary preferences, or arrival info...">${this.customer.notes || ''}</textarea>
							</div>
						</div>
					`;
				case 3:
					return `
						<div class="mb-step-view">
							<h3 class="mb-step-title">Contact & Guest Details</h3>
							<p class="mb-step-desc">Enter your contact information for instant confirmation and booking tickets.</p>
							<div class="mb-form-group">
								<label for="mb-funnel-name">Full Name *</label>
								<input type="text" id="mb-funnel-name" class="mb-form-input" value="${this.customer.name || ''}" placeholder="Jane Doe" required>
							</div>
							<div class="mb-form-group">
								<label for="mb-funnel-email">Email Address *</label>
								<input type="email" id="mb-funnel-email" class="mb-form-input" value="${this.customer.email || ''}" placeholder="jane@example.com" required>
							</div>
							<div class="mb-form-group">
								<label for="mb-funnel-phone">Phone Number</label>
								<input type="tel" id="mb-funnel-phone" class="mb-form-input" value="${this.customer.phone || ''}" placeholder="+1 (555) 000-0000">
							</div>
						</div>
					`;
				case 4:
					return `
						<div class="mb-step-view">
							<h3 class="mb-step-title">Review & Confirm</h3>
							<div class="mb-review-summary-card">
								<div class="mb-summary-line">
									<span>Dates</span>
									<strong>${this.selectedDates.startDate || 'Selected on arrival'} ${this.selectedDates.endDate ? '→ ' + this.selectedDates.endDate : ''}</strong>
								</div>
								<div class="mb-summary-line">
									<span>Guests</span>
									<strong>${this.guests}</strong>
								</div>
								<div class="mb-summary-line">
									<span>Guest Name</span>
									<strong>${this.customer.name}</strong>
								</div>
								<div class="mb-summary-line">
									<span>Email</span>
									<strong>${this.customer.email}</strong>
								</div>
								<hr class="mb-divider" />
								<div class="mb-summary-line mb-summary-total">
									<span>Status</span>
									<strong style="color: #16a34a;">Ready for Reservation</strong>
								</div>
							</div>
							<p class="mb-confirm-terms">By confirming, you agree to the booking cancellation terms and policies.</p>
						</div>
					`;
			}
		}

		bindStepEvents() {
			this.modal.querySelector('.mb-funnel-close').addEventListener('click', () => this.close());

			const nextBtn = this.modal.querySelector('#mb-funnel-next');
			if (nextBtn) {
				nextBtn.addEventListener('click', () => {
					if (this.currentStep === 3) {
						const name = this.modal.querySelector('#mb-funnel-name').value.trim();
						const email = this.modal.querySelector('#mb-funnel-email').value.trim();
						if (!name || !email) {
							alert('Please enter your full name and email address.');
							return;
						}
						this.customer.name = name;
						this.customer.email = email;
						this.customer.phone = this.modal.querySelector('#mb-funnel-phone').value.trim();
					} else if (this.currentStep === 2) {
						this.guests = parseInt(this.modal.querySelector('#mb-funnel-guests').value, 10);
						this.customer.notes = this.modal.querySelector('#mb-funnel-notes').value.trim();
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
				submitBtn.addEventListener('click', () => this.submitBooking(submitBtn));
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
