/**
 * Booking Engine Frontend Controller.
 * Vanilla JavaScript implementation for Search, Map Sync, Dynamic Slot Booking, and Reviews.
 *
 * @package MyBookingEngine
 */

(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		initSearchFilters();
		initSingleListingPage();
		initReviewModal();
		initHeroSliders();
		initCategoryFilterPills();
		initCardSliders();
		initMobileStickyBar();
	});

	/**
	 * Search & Distance Filter Module with Map Integration.
	 */
	function initSearchFilters() {
		const form = document.getElementById('mb-filter-form');
		const radiusSlider = document.getElementById('mb_search_radius');
		const radiusVal = document.getElementById('mb_radius_val');
		const resultsContainer = document.getElementById('mb-entities-results');
		const statusBanner = document.getElementById('mb-results-status');
		const loadingState = document.getElementById('mb-loading-state');
		const resetBtn = document.getElementById('mb-btn-reset-filters');
		const toggleMapBtn = document.getElementById('mb-toggle-map-btn');
		const mapCol = document.getElementById('mb-directory-map-col');
		const resultsWrapper = document.getElementById('mb-results-wrapper');

		if (!form || !resultsContainer) {
			return;
		}

		let isMapVisible = false;
		let lastItems = [];
		let lastCenter = null;

		// Map Toggle
		if (toggleMapBtn && mapCol) {
			toggleMapBtn.addEventListener('click', function() {
				isMapVisible = !isMapVisible;
				if (isMapVisible) {
					mapCol.style.display = 'block';
					resultsWrapper?.classList.add('is-split-view');
					toggleMapBtn.innerHTML = '📋 ' + (window.mbEngineData?.i18n?.hideMap || 'Hide Map');
					if (window.MbMapController) {
						window.MbMapController.initDirectoryMap('mb-directory-map', lastItems, lastCenter);
					}
				} else {
					mapCol.style.display = 'none';
					resultsWrapper?.classList.remove('is-split-view');
					toggleMapBtn.innerHTML = '🗺️ ' + (window.mbEngineData?.i18n?.showMap || 'Show Map');
				}
			});
		}

		// Update slider label on drag.
		if (radiusSlider && radiusVal) {
			radiusSlider.addEventListener('input', function() {
				const unit = (window.mbEngineData && window.mbEngineData.distanceUnit) ? window.mbEngineData.distanceUnit : 'km';
				radiusVal.textContent = this.value + ' ' + unit;
			});

			radiusSlider.addEventListener('change', function() {
				triggerSearch();
			});
		}

		// Search on submit.
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			triggerSearch();
		});

		const liveSelects = form.querySelectorAll('select');
		liveSelects.forEach(function(el) {
			el.addEventListener('change', function() {
				triggerSearch();
			});
		});

		if (resetBtn) {
			resetBtn.addEventListener('click', function() {
				form.reset();
				if (radiusSlider && radiusVal) {
					const unit = (window.mbEngineData && window.mbEngineData.distanceUnit) ? window.mbEngineData.distanceUnit : 'km';
					radiusVal.textContent = radiusSlider.value + ' ' + unit;
				}
				triggerSearch();
			});
		}

		// Perform initial search.
		triggerSearch();

		function triggerSearch() {
			const postal = document.getElementById('mb_search_postal')?.value.trim() || '';
			const type = document.getElementById('mb_search_type')?.value || '';
			const model = document.getElementById('mb_search_model')?.value || '';
			const radius = radiusSlider ? radiusSlider.value : 25;
			const minPrice = document.getElementById('mb_search_min_price')?.value || '';
			const maxPrice = document.getElementById('mb_search_max_price')?.value || '';

			const params = new URLSearchParams();
			if (postal) params.append('postal_code', postal);
			if (type) params.append('type', type);
			if (model) params.append('model', model);
			if (radius) params.append('radius', radius);
			if (minPrice) params.append('min_price', minPrice);
			if (maxPrice) params.append('max_price', maxPrice);

			if (loadingState) loadingState.style.display = 'block';
			if (statusBanner) statusBanner.textContent = (window.mbEngineData && window.mbEngineData.i18n && window.mbEngineData.i18n.searching) || 'Searching...';

			function handleResults(data) {
				if (loadingState) loadingState.style.display = 'none';

				if (data && data.success && Array.isArray(data.items)) {
					lastItems = data.items;
					lastCenter = data.geocoded_center;
					renderResults(data.items, data.geocoded_center);

					// Sync with directory map if open
					if (isMapVisible && window.MbMapController) {
						window.MbMapController.initDirectoryMap('mb-directory-map', data.items, data.geocoded_center);
					}
				} else {
					resultsContainer.innerHTML = '<p class="mb-no-results">' + ((window.mbEngineData && window.mbEngineData.i18n && window.mbEngineData.i18n.noResults) || 'No results found.') + '</p>';
					if (statusBanner) statusBanner.textContent = '0 results found.';
				}
			}

			function fallbackToAjax(originalErr) {
				const ajaxUrl = (window.mbEngineData && window.mbEngineData.ajaxUrl) ? window.mbEngineData.ajaxUrl : '/wp-admin/admin-ajax.php';
				const ajaxParams = new URLSearchParams(params);
				ajaxParams.append('action', 'mb_search_entities');

				fetch(ajaxUrl + '?' + ajaxParams.toString(), {
					method: 'GET'
				})
				.then(res => res.json())
				.then(data => {
					handleResults(data);
				})
				.catch(ajaxErr => {
					if (loadingState) loadingState.style.display = 'none';
					console.error('Booking Engine Search error (REST & AJAX failed):', originalErr, ajaxErr);
					resultsContainer.innerHTML = '<p class="mb-no-results">' + ((window.mbEngineData && window.mbEngineData.i18n && window.mbEngineData.i18n.noResults) || 'No results found.') + '</p>';
					if (statusBanner) {
						statusBanner.textContent = 'Could not load search results. Please check your connection.';
					}
				});
			}

			// Build REST URL (cleanly handling both pretty and plain permalinks)
			const restBase = (window.mbEngineData && window.mbEngineData.restUrl)
				? window.mbEngineData.restUrl
				: '/wp-json/my-booking-engine/v1/';

			let searchUrl;
			const qs = params.toString();
			if (restBase.includes('rest_route=')) {
				const parts = restBase.split('rest_route=');
				const route = (parts[1] || '').replace(/\/+$/, '') + '/search';
				searchUrl = parts[0] + 'rest_route=' + route + (qs ? '&' + qs : '');
			} else {
				searchUrl = restBase.replace(/\/+$/, '') + '/search' + (qs ? '?' + qs : '');
			}

			fetch(searchUrl, {
				method: 'GET'
			})
			.then(response => {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then(data => {
				handleResults(data);
			})
			.catch(err => {
				// Automatic fallback to admin-ajax if REST 404s, CORS fails, or fails to parse
				fallbackToAjax(err);
			});
		}

		function renderResults(items, geocodedCenter) {
			const currency = (window.mbEngineData && window.mbEngineData.currencySymbol) || '$';
			const unit = (window.mbEngineData && window.mbEngineData.distanceUnit) || 'km';

			if (items.length === 0) {
				resultsContainer.innerHTML = '<p class="mb-no-results">' + ((window.mbEngineData && window.mbEngineData.i18n.noResults) || 'No entities found.') + '</p>';
				if (statusBanner) statusBanner.textContent = '0 results found.';
				return;
			}

			let statusText = `Showing ${items.length} verified listings`;
			if (geocodedCenter && geocodedCenter.display_name) {
				statusText += ` near "${geocodedCenter.display_name}"`;
			}
			if (statusBanner) statusBanner.textContent = statusText;

			const html = items.map(item => {
				const distanceBadge = (item.distance !== null && item.distance !== undefined)
					? `<span class="mb-badge mb-badge-distance">
						<svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
						${item.distance} ${unit} away
					</span>`
					: '';

				const locationText = item.location ? `${item.location.postal_code || ''} ${item.location.city || ''}`.trim() : '';
				const ratingVal = item.rating ? parseFloat(item.rating).toFixed(1) : '5.0';
				const reviewCount = item.review_count || 0;
				const layout = item.visual_layout || 'hotel';

				const galleryImages = (Array.isArray(item.gallery) && item.gallery.length > 0)
					? item.gallery
					: (item.thumbnail ? [item.thumbnail] : []);

				const hasMultiple = galleryImages.length > 1;

				const slidesHtml = galleryImages.map(imgUrl => `
					<div class="mb-card-slide">
						<img src="${escapeHtml(imgUrl)}" alt="${escapeHtml(item.title)}" class="mb-card-thumb" loading="lazy">
					</div>
				`).join('');

				const arrowsHtml = hasMultiple ? `
					<button type="button" class="mb-card-arrow mb-card-arrow-prev" aria-label="Previous photo">
						<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
					</button>
					<button type="button" class="mb-card-arrow mb-card-arrow-next" aria-label="Next photo">
						<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
					</button>
					<div class="mb-card-slider-dots">
						${galleryImages.map((_, sIdx) => `<span class="mb-slider-dot${sIdx === 0 ? ' is-active' : ''}" data-slide="${sIdx}"></span>`).join('')}
					</div>
				` : '';

				let shortExcerpt = (item.excerpt || '').trim();
				const words = shortExcerpt.split(/\s+/);
				if (words.length > 8) {
					shortExcerpt = words.slice(0, 8).join(' ') + '...';
				}

				return `
					<div class="mb-card-item mb-card-${layout}" data-entity-id="${item.id}" data-model="${item.model_type}" data-lat="${item.location?.lat || ''}" data-lng="${item.location?.lng || ''}">
						<div class="mb-card-thumb-wrap">
							<div class="mb-card-slider" data-current="0" data-total="${galleryImages.length}">
								<div class="mb-card-slider-track">
									${slidesHtml}
								</div>
								${arrowsHtml}
							</div>
							<a href="${item.permalink}" class="mb-card-link-overlay" aria-label="${escapeHtml(item.title)}"></a>
							<span class="mb-badge mb-badge-model mb-badge-${layout}">
								${formatModelLabel(item.model_type)}
							</span>
							${distanceBadge}
						</div>
						<div class="mb-card-body">
							<div class="mb-card-rating-row">
								<span class="mb-card-rating">
									<span class="mb-star">★</span> ${ratingVal}
									<span class="mb-card-rev-count">(${reviewCount})</span>
								</span>
								${locationText ? `<span class="mb-card-location">📍 ${escapeHtml(locationText)}</span>` : ''}
							</div>
							<h3 class="mb-card-title"><a href="${item.permalink}">${escapeHtml(item.title)}</a></h3>
							${shortExcerpt ? `<div class="mb-card-excerpt">${escapeHtml(shortExcerpt)}</div>` : ''}
							<div class="mb-card-footer">
								<div class="mb-card-price">
									<span class="mb-price-amount">${currency}${parseFloat(item.base_price).toFixed(2)}</span>
									<span class="mb-price-unit">${formatPriceUnit(item.model_type)}</span>
								</div>
								<a href="${item.permalink}" class="mb-btn mb-btn-book">
									View Details
								</a>
							</div>
						</div>
					</div>
				`;
			}).join('');

			resultsContainer.innerHTML = html;
			initCardSliders();
		}
	}

	/**
	 * Single Listing Page Interactions.
	 */
	function initSingleListingPage() {
		// Listen for range or single calendar date selection.
		document.addEventListener('mb:date-selected', function(e) {
			const detail = e.detail;
			const checkinDisplay = document.getElementById('mb-display-checkin');
			const checkoutDisplay = document.getElementById('mb-display-checkout');
			const breakdown = document.getElementById('mb-price-breakdown');
			const nightsCount = document.getElementById('mb-calc-nights');
			const subtotalDisplay = document.getElementById('mb-calc-subtotal');
			const totalDisplay = document.getElementById('mb-calc-total');
			const feeDisplay = document.getElementById('mb-calc-fee');
			const basePriceEl = document.getElementById('mb-card-price');

			if (checkinDisplay && detail.startDate) {
				checkinDisplay.textContent = detail.startDate;
			}
			if (checkoutDisplay) {
				checkoutDisplay.textContent = detail.endDate || 'Add date';
			}

			// If nights > 0 and price elements exist, calculate live breakdown
			if (detail.nights > 0 && basePriceEl && breakdown) {
				const price = parseFloat(basePriceEl.textContent.replace(/[^0-9.]/g, '')) || 0;
				const subtotal = price * detail.nights;
				const fee = Math.round(subtotal * 0.05 * 100) / 100; // 5% platform fee
				const total = subtotal + fee;
				const currency = (window.mbEngineData && window.mbEngineData.currencySymbol) || '$';

				if (nightsCount) nightsCount.textContent = detail.nights;
				if (subtotalDisplay) subtotalDisplay.textContent = currency + subtotal.toFixed(2);
				if (feeDisplay) feeDisplay.textContent = currency + fee.toFixed(2);
				if (totalDisplay) totalDisplay.textContent = currency + total.toFixed(2);

				breakdown.style.display = 'block';
			}
		});

		// Salon layout: Add service selection
		document.querySelectorAll('.mb-add-service-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const row = this.closest('.mb-salon-service-row');
				if (!row) return;

				const name = row.dataset.name;
				const price = parseFloat(row.dataset.price) || 0;
				const tray = document.getElementById('mb-selected-list');
				const prompt = document.getElementById('mb-no-services-prompt');
				const totalRow = document.getElementById('mb-salon-total-row');
				const totalDisplay = document.getElementById('mb-salon-total-price');
				const currency = (window.mbEngineData && window.mbEngineData.currencySymbol) || '$';

				if (prompt) prompt.style.display = 'none';
				if (tray) {
					tray.style.display = 'block';
					const li = document.createElement('li');
					li.className = 'mb-selected-item';
					li.innerHTML = `<span>${name}</span> <strong>${currency}${price.toFixed(2)}</strong>`;
					tray.appendChild(li);
				}

				if (totalRow && totalDisplay) {
					totalRow.style.display = 'flex';
					totalDisplay.textContent = currency + price.toFixed(2);
				}

				this.textContent = '✓ Added';
				this.disabled = true;
			});
		});

		// Doctor layout: consultation selection
		document.querySelectorAll('.mb-select-service-btn, .mb-service-item').forEach(el => {
			el.addEventListener('click', function() {
				document.querySelectorAll('.mb-service-item').forEach(i => i.classList.remove('mb-service-selected'));
				const item = this.closest('.mb-service-item') || this;
				item.classList.add('mb-service-selected');
				const price = item.dataset.price;
				const cardPrice = document.getElementById('mb-card-price');
				if (cardPrice && price) {
					cardPrice.textContent = parseFloat(price).toFixed(2);
				}
			});
		});

		// Specialist card selection
		document.querySelectorAll('.mb-staff-card').forEach(card => {
			card.addEventListener('click', function() {
				document.querySelectorAll('.mb-staff-card').forEach(c => c.classList.remove('mb-staff-active'));
				this.classList.add('mb-staff-active');
			});
		});
	}

	/**
	 * Verified Reviews Modal Module.
	 */
	function initReviewModal() {
		document.addEventListener('click', function(e) {
			const btn = e.target.closest('#mb-write-review-btn');
			if (!btn) return;

			const entityId = btn.dataset.entityId;
			openReviewModal(entityId);
		});

		function openReviewModal(entityId) {
			let modal = document.getElementById('mb-review-modal');
			if (!modal) {
				modal = document.createElement('div');
				modal.id = 'mb-review-modal';
				modal.className = 'mb-funnel-overlay is-open';
				document.body.appendChild(modal);
			} else {
				modal.classList.add('is-open');
			}

			modal.innerHTML = `
				<div class="mb-funnel-container" style="max-width: 460px;">
					<div class="mb-funnel-header">
						<h3>Write a Verified Review</h3>
						<button type="button" class="mb-funnel-close" onclick="document.getElementById('mb-review-modal').classList.remove('is-open')">&times;</button>
					</div>
					<div class="mb-funnel-body">
						<form id="mb-review-form" onsubmit="return false;">
							<div class="mb-form-group">
								<label>Rating *</label>
								<div class="mb-star-rating-picker" id="mb-star-picker">
									<span data-star="1" class="is-selected">★</span>
									<span data-star="2" class="is-selected">★</span>
									<span data-star="3" class="is-selected">★</span>
									<span data-star="4" class="is-selected">★</span>
									<span data-star="5" class="is-selected">★</span>
								</div>
								<input type="hidden" id="mb_review_rating" value="5">
							</div>
							<div class="mb-form-group">
								<label for="mb_rev_name">Your Name *</label>
								<input type="text" id="mb_rev_name" class="mb-form-input" placeholder="e.g. Alex Morgan" required>
							</div>
							<div class="mb-form-group">
								<label for="mb_rev_email">Booking Email (Verification) *</label>
								<input type="email" id="mb_rev_email" class="mb-form-input" placeholder="Email used when booking" required>
							</div>
							<div class="mb-form-group">
								<label for="mb_rev_title">Review Title</label>
								<input type="text" id="mb_rev_title" class="mb-form-input" placeholder="e.g. Fantastic stay, highly recommended!">
							</div>
							<div class="mb-form-group">
								<label for="mb_rev_content">Comments & Feedback *</label>
								<textarea id="mb_rev_content" class="mb-form-textarea" placeholder="Share your experience..." required></textarea>
							</div>
							<div id="mb-review-msg"></div>
							<button type="submit" class="mb-btn mb-btn-primary mb-btn-block" id="mb-submit-review-btn">Submit Review</button>
						</form>
					</div>
				</div>
			`;

			// Star picker interactions
			const picker = modal.querySelector('#mb-star-picker');
			picker.querySelectorAll('span').forEach(star => {
				star.addEventListener('click', function() {
					const rating = parseInt(this.dataset.star, 10);
					document.getElementById('mb_review_rating').value = rating;
					picker.querySelectorAll('span').forEach(s => {
						const num = parseInt(s.dataset.star, 10);
						s.classList.toggle('is-selected', num <= rating);
					});
				});
			});

			// Form submit
			const form = modal.querySelector('#mb-review-form');
			form.addEventListener('submit', function() {
				const submitBtn = modal.querySelector('#mb-submit-review-btn');
				const msgBox = modal.querySelector('#mb-review-msg');

				submitBtn.disabled = true;
				submitBtn.textContent = 'Submitting...';

				const restUrl = (window.mbEngineData && window.mbEngineData.restUrl) ? window.mbEngineData.restUrl : '/wp-json/my-booking-engine/v1/';
				const nonce = (window.mbEngineData && window.mbEngineData.nonce) ? window.mbEngineData.nonce : '';

				const payload = {
					entity_id: parseInt(entityId, 10),
					rating: parseInt(document.getElementById('mb_review_rating').value, 10),
					customer_name: document.getElementById('mb_rev_name').value.trim(),
					customer_email: document.getElementById('mb_rev_email').value.trim(),
					title: document.getElementById('mb_rev_title').value.trim(),
					content: document.getElementById('mb_rev_content').value.trim(),
				};

				fetch(restUrl + 'reviews', {
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
						msgBox.innerHTML = '<p class="mb-alert mb-alert-success">' + (data.message || 'Review submitted successfully!') + '</p>';
						setTimeout(() => {
							modal.classList.remove('is-open');
							window.location.reload();
						}, 1600);
					} else {
						msgBox.innerHTML = '<p class="mb-alert mb-alert-error">' + (data.message || 'Error submitting review.') + '</p>';
						submitBtn.disabled = false;
						submitBtn.textContent = 'Submit Review';
					}
				})
				.catch(err => {
					msgBox.innerHTML = '<p class="mb-alert mb-alert-error">Network error. Please try again.</p>';
					submitBtn.disabled = false;
					submitBtn.textContent = 'Submit Review';
				});
			});
		}
	}

	function formatModelLabel(model) {
		const map = {
			hotel_room: 'Hotel / Stay',
			night_stay: 'Hotel / Stay',
			daily_booking: 'Daily Rental',
			day_rental: 'Daily Rental',
			hourly_booking: 'Hourly Session',
			hourly_slot: 'Hourly Slot',
			doctor_professional: 'Medical & Clinic',
			salon_spa: 'Salon & Spa',
			shop_business: 'Local Venue',
			capacity_roster: 'Event / Tour'
		};
		return map[model] || model;
	}

	function formatPriceUnit(model) {
		const map = {
			hotel_room: '/ night',
			night_stay: '/ night',
			daily_booking: '/ day',
			day_rental: '/ day',
			hourly_booking: '/ hour',
			hourly_slot: '/ slot',
			doctor_professional: '/ visit',
			salon_spa: '/ service',
			shop_business: '/ booking',
			capacity_roster: '/ person'
		};
		return map[model] || '';
	}

	function escapeHtml(str) {
		if (!str) return '';
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	/**
	 * Interactive Hero Photo Slider Controller.
	 */
	function initHeroSliders() {
		document.querySelectorAll('.mb-hero-slider-wrap').forEach(wrap => {
			const slides = wrap.querySelectorAll('.mb-slider-slide');
			const thumbs = wrap.querySelectorAll('.mb-slider-thumb');
			const prevBtn = wrap.querySelector('.mb-slider-prev');
			const nextBtn = wrap.querySelector('.mb-slider-next');
			const counterEl = wrap.querySelector('.mb-curr-slide');

			if (!slides.length) return;

			let currentIndex = 0;

			function goToSlide(idx) {
				if (idx < 0) idx = slides.length - 1;
				if (idx >= slides.length) idx = 0;

				currentIndex = idx;

				slides.forEach((s, i) => {
					if (i === currentIndex) {
						s.classList.add('is-active');
					} else {
						s.classList.remove('is-active');
					}
				});

				thumbs.forEach((t, i) => {
					if (i === currentIndex) {
						t.classList.add('is-active');
						t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
					} else {
						t.classList.remove('is-active');
					}
				});

				if (counterEl) {
					counterEl.textContent = String(currentIndex + 1);
				}
			}

			if (prevBtn) {
				prevBtn.addEventListener('click', e => {
					e.preventDefault();
					goToSlide(currentIndex - 1);
				});
			}

			if (nextBtn) {
				nextBtn.addEventListener('click', e => {
					e.preventDefault();
					goToSlide(currentIndex + 1);
				});
			}

			thumbs.forEach(thumb => {
				thumb.addEventListener('click', e => {
					e.preventDefault();
					const idx = parseInt(thumb.dataset.thumb, 10);
					goToSlide(idx);
				});
			});

			// Touch swipe gesture support
			let startX = 0;
			const sliderArea = wrap.querySelector('.mb-hero-slider');
			if (sliderArea) {
				sliderArea.addEventListener('touchstart', e => {
					startX = e.touches[0].clientX;
				}, { passive: true });

				sliderArea.addEventListener('touchend', e => {
					const diffX = e.changedTouches[0].clientX - startX;
					if (Math.abs(diffX) > 40) {
						if (diffX > 0) {
							goToSlide(currentIndex - 1);
						} else {
							goToSlide(currentIndex + 1);
						}
					}
				}, { passive: true });
			}
		});
	}

	/**
	 * Interactive Category Pill Filtering for [mb_listings] grid.
	 */
	function initCategoryFilterPills() {
		const pills = document.querySelectorAll('.mb-cat-pill');
		if (!pills.length) return;

		pills.forEach(pill => {
			pill.addEventListener('click', function(e) {
				e.preventDefault();
				pills.forEach(p => p.classList.remove('is-active'));
				this.classList.add('is-active');

				const filter = this.dataset.filter;
				const cards = document.querySelectorAll('#mb-catalog-grid .mb-card-item');

				cards.forEach(card => {
					if (filter === 'all') {
						card.style.display = '';
						return;
					}
					const layout = card.dataset.layout || '';
					const model = card.dataset.model || '';
					const classes = card.className || '';

					if (layout === filter || model.includes(filter) || classes.includes('mb-card-' + filter)) {
						card.style.display = '';
					} else {
						card.style.display = 'none';
					}
				});
			});
		});
	}

	/**
	 * Interactive Slider for Listing Cards (Archive & Directory).
	 * Supports click navigation arrows, indicator dots, and touch swiping.
	 */
	function initCardSliders() {
		if (window._mbCardSlidersInitialized) return;
		window._mbCardSlidersInitialized = true;

		// Delegate click for arrows and dots
		document.addEventListener('click', function(e) {
			const arrow = e.target.closest('.mb-card-arrow');
			const dot = e.target.closest('.mb-slider-dot');

			if (arrow) {
				e.preventDefault();
				e.stopPropagation();
				const slider = arrow.closest('.mb-card-slider');
				if (!slider) return;

				const total = parseInt(slider.getAttribute('data-total') || '1', 10);
				if (total <= 1) return;

				let current = parseInt(slider.getAttribute('data-current') || '0', 10);
				if (arrow.classList.contains('mb-card-arrow-next')) {
					current = (current + 1) % total;
				} else {
					current = (current - 1 + total) % total;
				}

				updateCardSlider(slider, current);
				return;
			}

			if (dot) {
				e.preventDefault();
				e.stopPropagation();
				const slider = dot.closest('.mb-card-slider');
				if (!slider) return;

				const slideIdx = parseInt(dot.getAttribute('data-slide') || '0', 10);
				updateCardSlider(slider, slideIdx);
				return;
			}
		});

		// Touch swipe gesture support for cards
		let cardTouchStartX = 0;
		let cardTouchStartY = 0;
		let activeCardSlider = null;

		document.addEventListener('touchstart', function(e) {
			activeCardSlider = e.target.closest('.mb-card-slider');
			if (activeCardSlider) {
				cardTouchStartX = e.touches[0].clientX;
				cardTouchStartY = e.touches[0].clientY;
			}
		}, { passive: true });

		document.addEventListener('touchend', function(e) {
			if (!activeCardSlider) return;
			const diffX = e.changedTouches[0].clientX - cardTouchStartX;
			const diffY = e.changedTouches[0].clientY - cardTouchStartY;

			if (Math.abs(diffX) > 35 && Math.abs(diffX) > Math.abs(diffY)) {
				const total = parseInt(activeCardSlider.getAttribute('data-total') || '1', 10);
				if (total > 1) {
					let current = parseInt(activeCardSlider.getAttribute('data-current') || '0', 10);
					if (diffX < 0) {
						current = (current + 1) % total;
					} else {
						current = (current - 1 + total) % total;
					}
					updateCardSlider(activeCardSlider, current);
				}
			}
			activeCardSlider = null;
		}, { passive: true });
	}

	function updateCardSlider(slider, index) {
		slider.setAttribute('data-current', index);
		const track = slider.querySelector('.mb-card-slider-track');
		if (track) {
			track.style.transform = `translateX(-${index * 100}%)`;
		}
		const dots = slider.querySelectorAll('.mb-slider-dot');
		dots.forEach((d, idx) => {
			if (idx === index) {
				d.classList.add('is-active');
			} else {
				d.classList.remove('is-active');
			}
		});
	}

	/**
	 * Mobile Sticky Reservation Bar Controller.
	 * Only active on mobile viewport (< 900px).
	 * Strictly hides over hero/gallery slider and appears when scrolled past hero images.
	 * Hides when the booking form card is fully in view.
	 */
	function initMobileStickyBar() {
		const stickyBar = document.getElementById('mb-mobile-sticky-bar');
		if (!stickyBar) return;

		const bookBtn = document.getElementById('mb-mobile-book-btn');
		const heroEl = document.querySelector('.mb-gallery-mosaic, .mb-hero-slider-wrap, .mb-rental-slider-wrap, .mb-doctor-hero, .mb-salon-hero, .mb-shop-hero, .mb-listing-header');
		const bookingCard = document.querySelector('.mb-sticky-card, .mb-booking-card, #mb-start-booking-btn, .mb-booking-funnel-trigger, #mb-trigger-date-picker');

		function checkScroll() {
			if (window.innerWidth > 900) {
				stickyBar.classList.remove('is-visible');
				return;
			}

			// Determine bottom of the hero/gallery section
			let heroBottom = 350;
			if (heroEl) {
				const rect = heroEl.getBoundingClientRect();
				heroBottom = window.scrollY + rect.bottom;
			}

			const scrollY = window.scrollY || window.pageYOffset;

			// If user is viewing the hero gallery slider, never obscure it
			if (scrollY < heroBottom) {
				stickyBar.classList.remove('is-visible');
				return;
			}

			// If the user has scrolled all the way to the booking card, hide sticky bar
			if (bookingCard) {
				const cardRect = bookingCard.getBoundingClientRect();
				const cardVisible = cardRect.top < (window.innerHeight - 80) && cardRect.bottom > 80;
				if (cardVisible) {
					stickyBar.classList.remove('is-visible');
					return;
				}
			}

			// User is scrolled past hero/gallery and booking card is not visible
			stickyBar.classList.add('is-visible');
		}

		// Throttle scroll checks with requestAnimationFrame
		let ticking = false;
		window.addEventListener('scroll', function() {
			if (!ticking) {
				window.requestAnimationFrame(function() {
					checkScroll();
					ticking = false;
				});
				ticking = true;
			}
		}, { passive: true });

		window.addEventListener('resize', checkScroll);
		checkScroll();

		// Tapping Book Now smoothly scrolls to the booking card and highlights it
		if (bookBtn) {
			bookBtn.addEventListener('click', function(e) {
				e.preventDefault();
				if (bookingCard) {
					bookingCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
					bookingCard.style.transition = 'box-shadow 0.3s ease';
					bookingCard.style.boxShadow = '0 0 0 4px rgba(37, 99, 235, 0.35)';
					setTimeout(() => {
						bookingCard.style.boxShadow = '';
					}, 1400);

					// If there is an interactive button on the card, focus it
					const reserveBtn = document.getElementById('mb-start-booking-btn');
					if (reserveBtn) {
						reserveBtn.focus();
					}
				}
			});
		}
	}
})();

