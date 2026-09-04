=== Booking Engine - Multi-Model Booking & Appointment System ===
Contributors: bookingengine
Tags: booking, appointment, rental, hotel, calendar
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Universal booking system supporting hourly appointments, day rentals, night stays, and capacity events with worldwide postal code radius search.

== Description ==

**Booking Engine** is a lightweight, secure, and ultra-fast WordPress booking engine architected from the ground up to solve fragmented booking models and database bloat.

Instead of running slow `meta_query` operations against `wp_postmeta`, Booking Engine introduces **3 dedicated custom database tables** (`wp_mb_locations`, `wp_mb_availabilities`, and `wp_mb_bookings`) using `dbDelta()`. This ensures instant spatial queries and real-time availability calculations even on high-traffic websites.

### 4 Universal Booking Engines In One Plugin:
1. **Hourly Appointments & Time-Slots**: Doctors, beauty salons, personal trainers, consultants. Supports custom durations (30m, 45m, 60m), prep buffers, and cleaning gaps.
2. **Day-Based Rentals**: Car rentals, machinery, cameras, construction equipment, surfboards.
3. **Night-Based Accommodations**: Hotels, villas, guest houses, vacation rentals with customizable check-in / check-out hours and weekend rates.
4. **Capacity-Based Rosters**: Workshops, fitness classes, guided group tours, live concerts with dynamic remaining seats counters.

### Key Features:
* **Worldwide Postal / ZIP Code Search**: Seamlessly geocodes international postal codes and cities using **OpenStreetMap (Nominatim)** by default (zero API key setup) or Google Maps Geocoding API.
* **Spatial Distance Haversine Engine**: Ultra-fast SQL bounding-box optimization paired with spherical Haversine trigonometry (find listings within 5 km to 150 km/miles).
* **Concurrency Locking (Race-Condition Prevention)**: Transient mutex locks during checkout stop double-bookings if two customers select the same slot simultaneously.
* **Full WooCommerce Bridge**: Turns bookings into custom cart items, dynamically adjusts line pricing, and auto-syncs order statuses (`wc-completed` -> `confirmed`).
* **Multi-Timezone Engine**: Clean conversion between local site time and international client timezones.
* **Turnaround & Cleaning Buffers**: Configurable buffer times before and after appointments.

== Installation ==

1. Upload the `my-booking-engine` folder to your `/wp-content/plugins/` directory, or upload the zip archive via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Booking Engine > Settings** to configure your distance unit (km/miles), default radius, and geocoding options.
4. Go to **Booking Engine > Add New** to publish your bookable entities (Services, Rentals, Properties, or Events).
5. Add the shortcode `[mb_search_filter]` to any page or post to display the search directory with radius slider.

== Shortcodes ==

* `[mb_search_filter]` - Embeds the global search bar, radius slider, category filters, and live results grid.
* `[mb_booking_form id="123"]` - Embeds a dedicated booking form for entity ID 123.
* `[mb_entities type="rental" limit="6"]` - Embeds a responsive grid of published entities.

== Frequently Asked Questions ==

= Do I need a Google Maps API Key? =
No! Booking Engine includes zero-config geocoding via OpenStreetMap (Nominatim) out-of-the-box. Google Maps Geocoding API is supported as an optional setting.

= Is WooCommerce required? =
No. Booking Engine works completely standalone with its own direct booking confirmation workflow. If WooCommerce is activated, Booking Engine automatically integrates with WooCommerce checkout and payment gateways.

= Can I use this for both car rentals and doctor appointments on the same site? =
Yes! Booking Engine was specifically designed to bridge fragmented booking systems. Each entity can select its own distinct booking model (Hourly, Day, Night, or Capacity).

== Changelog ==

= 1.0.0 =
* Initial public release with 4 booking models, spatial Haversine radius queries, and custom indexed tables.
