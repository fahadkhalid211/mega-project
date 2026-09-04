/**
 * Interactive Leaflet Map Controller
 * Zero API keys required (OpenStreetMap).
 * Synchronizes map markers with search directory and single listing view.
 *
 * @package MyBookingEngine
 */

(function() {
	'use strict';

	class MbMapController {
		constructor() {
			this.map = null;
			this.markers = [];
			this.tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
			this.attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
		}

		ensureLeaflet(callback) {
			if (typeof L !== 'undefined') {
				callback();
				return;
			}

			// Dynamically load Leaflet if not present
			if (!document.getElementById('mb-leaflet-css')) {
				const link = document.createElement('link');
				link.id = 'mb-leaflet-css';
				link.rel = 'stylesheet';
				link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
				document.head.appendChild(link);
			}

			if (!document.getElementById('mb-leaflet-js')) {
				const script = document.createElement('script');
				script.id = 'mb-leaflet-js';
				script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
				script.onload = () => callback();
				document.head.appendChild(script);
			} else {
				const existing = document.getElementById('mb-leaflet-js');
				existing.addEventListener('load', () => callback());
			}
		}

		initSingleMap(containerId = 'mb-single-map') {
			const container = document.getElementById(containerId);
			if (!container) return;

			const lat = parseFloat(container.dataset.lat);
			const lng = parseFloat(container.dataset.lng);
			const title = container.dataset.title || '';

			if (!lat || !lng || (lat === 0 && lng === 0)) {
				container.innerHTML = '<p class="mb-text-muted" style="padding: 20px; text-align: center;">Location map will be provided after booking confirmation.</p>';
				return;
			}

			this.ensureLeaflet(() => {
				const map = L.map(containerId, {
					scrollWheelZoom: false
				}).setView([lat, lng], 14);

				L.tileLayer(this.tileUrl, {
					attribution: this.attribution,
					maxZoom: 19
				}).addTo(map);

				const marker = L.marker([lat, lng]).addTo(map);
				if (title) {
					marker.bindPopup(`<strong>${title}</strong>`).openPopup();
				}
			});
		}

		initDirectoryMap(containerId = 'mb-directory-map', items = [], center = null) {
			const container = document.getElementById(containerId);
			if (!container) return;

			this.ensureLeaflet(() => {
				if (this.map) {
					this.map.remove();
					this.map = null;
					this.markers = [];
				}

				let defaultLat = 51.505;
				let defaultLng = -0.09;
				let defaultZoom = 12;

				if (center && center.lat && center.lng) {
					defaultLat = parseFloat(center.lat);
					defaultLng = parseFloat(center.lng);
				} else if (items.length > 0) {
					const firstWithCoords = items.find(it => it.location && it.location.lat && it.location.lng);
					if (firstWithCoords) {
						defaultLat = parseFloat(firstWithCoords.location.lat);
						defaultLng = parseFloat(firstWithCoords.location.lng);
					}
				}

				this.map = L.map(containerId, {
					scrollWheelZoom: true
				}).setView([defaultLat, defaultLng], defaultZoom);

				L.tileLayer(this.tileUrl, {
					attribution: this.attribution,
					maxZoom: 19
				}).addTo(this.map);

				const bounds = [];

				items.forEach(item => {
					if (!item.location || !item.location.lat || !item.location.lng) return;

					const lat = parseFloat(item.location.lat);
					const lng = parseFloat(item.location.lng);
					if (lat === 0 && lng === 0) return;

					bounds.push([lat, lng]);

					const marker = L.marker([lat, lng]).addTo(this.map);
					marker.entityId = item.id;

					const popupContent = `
						<div class="mb-map-popup">
							<img src="${item.thumbnail}" alt="${item.title}" style="width:100%; height:90px; object-fit:cover; border-radius:6px; margin-bottom:6px;" />
							<h4 style="margin:0 0 4px; font-size:14px;"><a href="${item.permalink}">${item.title}</a></h4>
							<p style="margin:0; font-size:12px; color:#2563eb; font-weight:700;">$${parseFloat(item.base_price).toFixed(2)}</p>
						</div>
					`;

					marker.bindPopup(popupContent);
					this.markers.push(marker);

					marker.on('click', () => {
						const card = document.querySelector(`.mb-card-item[data-entity-id="${item.id}"]`);
						if (card) {
							card.scrollIntoView({ behavior: 'smooth', block: 'center' });
							card.classList.add('mb-card-highlight');
							setTimeout(() => card.classList.remove('mb-card-highlight'), 1800);
						}
					});
				});

				if (bounds.length > 1) {
					this.map.fitBounds(bounds, { padding: [40, 40] });
				}

				// Synchronize hover on cards with markers
				document.querySelectorAll('.mb-card-item').forEach(card => {
					const id = parseInt(card.dataset.entityId, 10);
					card.addEventListener('mouseenter', () => {
						const matched = this.markers.find(m => m.entityId === id);
						if (matched) {
							matched.openPopup();
						}
					});
				});
			});
		}
	}

	window.MbMapController = new MbMapController();

	document.addEventListener('DOMContentLoaded', () => {
		// Auto initialize single map if container present
		if (document.getElementById('mb-single-map')) {
			window.MbMapController.initSingleMap('mb-single-map');
		}
	});

})();
