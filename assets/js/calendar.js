/**
 * Unified Range Calendar Component
 * Lightweight, dependency-free date & range selector with ribbon highlight.
 *
 * @package MyBookingEngine
 */

(function() {
	'use strict';

	class MbUnifiedCalendar {
		constructor(container, options = {}) {
			this.container = typeof container === 'string' ? document.querySelector(container) : container;
			if (!this.container) return;

			this.mode = options.mode || (this.container.dataset.mode === 'range' ? 'range' : 'single');
			this.entityId = options.entityId || this.container.dataset.entityId || 0;
			this.model = options.model || this.container.dataset.model || 'hotel_room';

			this.minDate = options.minDate ? new Date(options.minDate) : new Date();
			this.minDate.setHours(0, 0, 0, 0);

			this.startDate = options.startDate ? new Date(options.startDate) : null;
			this.endDate = options.endDate ? new Date(options.endDate) : null;
			this.hoverDate = null;

			this.viewDate = this.startDate ? new Date(this.startDate) : new Date();
			this.viewDate.setDate(1);

			this.onSelect = options.onSelect || null;

			this.init();
		}

		init() {
			this.container.classList.add('mb-unified-calendar');
			this.render();
			this.bindEvents();
		}

		formatDateYMD(d) {
			if (!d) return '';
			const year = d.getFullYear();
			const month = String(d.getMonth() + 1).padStart(2, '0');
			const day = String(d.getDate()).padStart(2, '0');
			return `${year}-${month}-${day}`;
		}

		// Parse a 'YYYY-MM-DD' string as a LOCAL date. new Date('YYYY-MM-DD')
		// parses as UTC midnight, which normalizes to the previous local day in
		// negative UTC-offset timezones — use this everywhere instead.
		parseLocalDate(dateStr) {
			const [y, m, d] = dateStr.split('-').map(Number);
			return new Date(y, m - 1, d);
		}

		render() {
			const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
			const weekDays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

			const currentYear = this.viewDate.getFullYear();
			const currentMonth = this.viewDate.getMonth();

			const firstDayIndex = new Date(currentYear, currentMonth, 1).getDay();
			const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

			const today = new Date();
			today.setHours(0, 0, 0, 0);

			let html = `
				<div class="mb-cal-header">
					<button type="button" class="mb-cal-nav-btn mb-cal-prev" aria-label="Previous Month">‹</button>
					<div class="mb-cal-title">${monthNames[currentMonth]} ${currentYear}</div>
					<button type="button" class="mb-cal-nav-btn mb-cal-next" aria-label="Next Month">›</button>
				</div>
				<div class="mb-cal-weekdays">
					${weekDays.map(d => `<div>${d}</div>`).join('')}
				</div>
				<div class="mb-cal-days">
			`;

			// Leading empty cells
			for (let i = 0; i < firstDayIndex; i++) {
				html += `<div class="mb-cal-day-cell is-empty"></div>`;
			}

			// Actual days of the month
			for (let day = 1; day <= daysInMonth; day++) {
				const cellDate = new Date(currentYear, currentMonth, day);
				cellDate.setHours(0, 0, 0, 0);
				const dateStr = this.formatDateYMD(cellDate);

				let classes = ['mb-cal-day-cell'];

				if (cellDate < this.minDate) {
					classes.push('is-past');
				}
				if (cellDate.getTime() === today.getTime()) {
					classes.push('is-today');
				}

				// Check range highlighting. In 'single' mode there is no range —
				// the picked day gets a full round marker instead of the
				// half-pill range-start/range-end shapes.
				if (this.mode === 'single') {
					if (this.startDate && cellDate.getTime() === this.startDate.getTime()) {
						classes.push('mb-single-selected');
					}
				} else {
					if (this.startDate && cellDate.getTime() === this.startDate.getTime()) {
						classes.push('mb-range-start');
					}
					if (this.endDate && cellDate.getTime() === this.endDate.getTime()) {
						classes.push('mb-range-end');
					}
					if (this.startDate && this.endDate && cellDate > this.startDate && cellDate < this.endDate) {
						classes.push('mb-in-range');
					}
				}

				// Hover range preview (when start is selected but not end)
				if (this.startDate && !this.endDate && this.hoverDate && cellDate > this.startDate && cellDate <= this.hoverDate) {
					classes.push('mb-range-hover');
				}

				html += `<div class="${classes.join(' ')}" data-date="${dateStr}">${day}</div>`;
			}

			html += `</div>`; // .mb-cal-days

			// Summary footer
			let summaryText = 'Select dates';
			if (this.mode === 'range') {
				if (this.startDate && this.endDate) {
					const nights = Math.round((this.endDate - this.startDate) / (1000 * 60 * 60 * 24));
					summaryText = `${this.formatDateYMD(this.startDate)} → ${this.formatDateYMD(this.endDate)} (${nights} night${nights !== 1 ? 's' : ''})`;
				} else if (this.startDate) {
					summaryText = `Check-in: ${this.formatDateYMD(this.startDate)} (Select check-out)`;
				}
			} else if (this.startDate) {
				summaryText = `Selected: ${this.formatDateYMD(this.startDate)}`;
			}

			html += `
				<div class="mb-cal-footer">
					<span class="mb-cal-selected-summary">${summaryText}</span>
					${(this.startDate || this.endDate) ? '<button type="button" class="mb-btn-link mb-cal-clear">Clear</button>' : ''}
				</div>
			`;

			this.container.innerHTML = html;
		}

		bindEvents() {
			this.container.addEventListener('click', (e) => {
				const prevBtn = e.target.closest('.mb-cal-prev');
				const nextBtn = e.target.closest('.mb-cal-next');
				const clearBtn = e.target.closest('.mb-cal-clear');
				const dayCell = e.target.closest('.mb-cal-day-cell:not(.is-past):not(.is-empty)');

				if (prevBtn) {
					this.viewDate.setMonth(this.viewDate.getMonth() - 1);
					this.render();
				} else if (nextBtn) {
					this.viewDate.setMonth(this.viewDate.getMonth() + 1);
					this.render();
				} else if (clearBtn) {
					this.startDate = null;
					this.endDate = null;
					this.hoverDate = null;
					this.render();
					this.emitSelection();
				} else if (dayCell) {
					const dateStr = dayCell.dataset.date;
					this.handleDayClick(dateStr);
				}
			});

			this.container.addEventListener('mouseover', (e) => {
				if (this.mode !== 'range' || !this.startDate || this.endDate) return;
				const dayCell = e.target.closest('.mb-cal-day-cell:not(.is-past):not(.is-empty)');
				if (dayCell && dayCell.dataset.date) {
					const hovered = this.parseLocalDate(dayCell.dataset.date);
					hovered.setHours(0, 0, 0, 0);
					if (hovered >= this.startDate) {
						this.hoverDate = hovered;
						this.updateHoverClasses();
					}
				}
			});

			this.container.addEventListener('mouseleave', () => {
				if (this.mode === 'range' && this.startDate && !this.endDate) {
					this.hoverDate = null;
					this.updateHoverClasses();
				}
			});
		}

		updateHoverClasses() {
			const cells = this.container.querySelectorAll('.mb-cal-day-cell[data-date]');
			cells.forEach(cell => {
				const d = this.parseLocalDate(cell.dataset.date);
				d.setHours(0, 0, 0, 0);
				if (this.startDate && !this.endDate && this.hoverDate && d > this.startDate && d <= this.hoverDate) {
					cell.classList.add('mb-range-hover');
				} else {
					cell.classList.remove('mb-range-hover');
				}
			});

			const summaryEl = this.container.querySelector('.mb-cal-selected-summary');
			if (summaryEl && this.startDate && !this.endDate && this.hoverDate) {
				const nights = Math.round((this.hoverDate - this.startDate) / 86400000);
				summaryEl.textContent = `${this.formatDateYMD(this.startDate)} → ${this.formatDateYMD(this.hoverDate)} (${nights} night${nights !== 1 ? 's' : ''})`;
			}
		}

		handleDayClick(dateStr) {
			const picked = this.parseLocalDate(dateStr);
			picked.setHours(0, 0, 0, 0);

			if (this.mode === 'single') {
				this.startDate = picked;
				this.endDate = null;
				this.render();
				this.emitSelection();
				return;
			}

			// Range mode logic
			if (!this.startDate || (this.startDate && this.endDate)) {
				// Starting fresh range
				this.startDate = picked;
				this.endDate = null;
				this.hoverDate = null;
			} else if (this.startDate && !this.endDate) {
				if (picked < this.startDate) {
					// User clicked earlier date -> make it new start date
					this.startDate = picked;
					this.endDate = null;
					this.hoverDate = null;
				} else if (picked.getTime() === this.startDate.getTime()) {
					// Same date clicked twice -> 1 night stay
					this.endDate = new Date(picked.getTime() + 86400000);
					this.hoverDate = null;
				} else {
					// Later date clicked -> confirm end date!
					this.endDate = picked;
					this.hoverDate = null;
				}
			}

			this.render();
			this.emitSelection();
		}

		emitSelection() {
			const detail = {
				startDate: this.formatDateYMD(this.startDate),
				endDate: this.formatDateYMD(this.endDate),
				nights: (this.startDate && this.endDate) ? Math.round((this.endDate - this.startDate) / 86400000) : 0,
			};

			this.container.dataset.startDate = detail.startDate || '';
			this.container.dataset.endDate = detail.endDate || '';
			this.container.dataset.nights = String(detail.nights);

			// Custom DOM event
			this.container.dispatchEvent(new CustomEvent('mb:date-selected', {
				bubbles: true,
				detail: detail
			}));

			if (typeof this.onSelect === 'function') {
				this.onSelect(detail);
			}
		}
	}

	// Expose globally
	window.MbUnifiedCalendar = MbUnifiedCalendar;

	// Auto-mount widgets on DOM ready
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.mb-calendar-widget').forEach(el => {
			new MbUnifiedCalendar(el);
		});
	});

})();
