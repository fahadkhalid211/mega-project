# Booking Engine - Multi-Model Booking & Appointment System

A high-performance, lightweight, and secure WordPress booking engine built to eliminate fragmented booking models, slow `wp_postmeta` database bloat, and primitive branch-only dropdowns.

---

## 🌟 Key Innovations & Architecture

### 1. 4-in-1 Universal Booking Engine
Standard plugins force website owners to purchase separate plugins for doctor appointments, car rentals, hotel stays, and event tickets. **Booking Engine** unifies all four under a single architecture:

| Engine Model | Best Used For | Scheduling Logic |
| :--- | :--- | :--- |
| **`hourly_slot`** | Salons, Doctors, Coaching, Spas | Duration in minutes (e.g. 30m, 60m) + prep/cleaning buffers |
| **`day_rental`** | Car rentals, Machinery, Cameras | Multi-day date spans (pickup date to return date) |
| **`night_stay`** | Hotels, Villas, Bed & Breakfasts | Nightly check-in/check-out hours + weekend pricing |
| **`capacity_roster`** | Classes, Workshops, Tours, Concerts | Scheduled event window with live remaining seats counter |

---

### 2. Worldwide Postal / ZIP Code Search & Spatial Haversine Engine
- **Global Lookup**: Users enter a postal code or city from anywhere in the world (e.g. `90210`, `SW1A 1AA`, `75001`, `Toronto`).
- **Zero-Config Geocoding**: Powered by **OpenStreetMap (Nominatim)** out-of-the-box with compliant user-agent headers and 30-day transient caching. Optional switch to **Google Maps Geocoding API**.
- **Haversine Trigonometric SQL**:
  $$\text{Distance} = 2R \cdot \arcsin\left(\sqrt{\sin^2\left(\frac{\Delta \phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta \lambda}{2}\right)}\right)$$
- **Index Bounding-Box Pre-filter**: Calculates rough latitude/longitude bounding-box boundaries prior to trigonometric calculation, ensuring sub-millisecond query execution even with tens of thousands of database rows.

---

### 3. Dedicated Custom Database Tables (Zero `postmeta` Bloat)
Created automatically via `dbDelta()` upon activation:
- `wp_mb_locations`: `id`, `post_id`, `postal_code`, `city`, `country_code`, `latitude (DECIMAL 10,8)`, `longitude (DECIMAL 11,8)` (Indexed).
- `wp_mb_availabilities`: `id`, `entity_id`, `rule_type`, `day_of_week`, `start_date`, `end_date`, `start_time`, `end_time`, `capacity` (Indexed).
- `wp_mb_bookings`: `id`, `entity_id`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `booking_start`, `booking_end`, `capacity_booked`, `status`, `order_id`, `total_price`, `created_at` (Indexed).

---

### 4. Concurrency Mutex Locking (Race Condition Prevention)
During the checkout window, a transient mutex lock (`mb_lock_{entity_id}_{hash}`) is acquired for the customer's session. If another user attempts to book the identical slot at the exact same second, they receive an immediate lock notification, preventing overbooking.

---

### 5. Native WooCommerce Integration
- Custom Product Type / Bridge: `booking_entity`.
- Dynamic Cart Item injection (`woocommerce_add_cart_item_data`).
- Order Line Item metadata persistence.
- Automatic Order Status Synchronizer:
  - `wc-completed` / `wc-processing` $\rightarrow$ Booking marked as `confirmed`.
  - `wc-cancelled` / `wc-refunded` $\rightarrow$ Booking marked as `cancelled` and locked slot released.

---

## 📁 Directory Structure

```
my-booking-engine/
├── assets/
│   ├── css/
│   │   ├── admin.css                 # Admin settings and meta box styling
│   │   └── frontend.css              # Responsive search bar, filter cards, booking drawer
│   ├── js/
│   │   ├── admin.js                  # Dynamic fields switcher and auto-geocoding AJAX
│   │   └── frontend.js               # REST search, live radius slider, slot calendar, modal
│   └── images/
│       └── placeholder.svg           # Entity SVG placeholder
├── includes/
│   ├── Autoloader.php                # PSR-4 Class Autoloader
│   ├── Plugin.php                    # Central singleton coordinator
│   ├── Database/
│   │   └── Schema.php                # dbDelta migration and index schema
│   ├── Models/
│   │   ├── BookingEntity.php         # Entity model (Pricing, buffers, capacities)
│   │   ├── Availability.php          # Date schedule & blackout evaluator
│   │   └── Booking.php               # Booking records query and status updater
│   ├── Geo/
│   │   ├── Geocoder.php              # Nominatim / Google geocoding with 30-day transient caching
│   │   └── SpatialQuery.php          # Haversine distance calculator & bounding-box optimizer
│   ├── Booking/
│   │   ├── SlotEngine.php            # Unified 4-model availability & slot calculator
│   │   ├── BufferManager.php         # Turnaround / prep buffer calculations
│   │   ├── MutexLock.php             # Transient mutex concurrency engine
│   │   └── TimezoneConverter.php     # Multi-timezone offset converter
│   ├── Integrations/
│   │   └── WooCommerce/
│   │       ├── ProductType.php       # WC product bridge
│   │       ├── CartManager.php       # Cart items, custom pricing, and lock validation
│   │       └── OrderSync.php         # WC order status listener
│   ├── Api/
│   │   ├── RestController.php        # Base REST controller with nonces & permission checks
│   │   ├── SearchEndpoint.php        # GET /wp-json/my-booking-engine/v1/search
│   │   ├── SlotsEndpoint.php         # GET /wp-json/my-booking-engine/v1/slots
│   │   └── BookingEndpoint.php       # POST /wp-json/my-booking-engine/v1/book
│   └── Admin/
│       ├── AdminMenu.php             # Admin menus, submenus, and action router
│       ├── PostType.php              # CPT mb_booking_entity and taxonomy mb_entity_type
│       ├── MetaBoxes.php             # Tabbed entity editor with auto-geocoding
│       ├── BookingsListTable.php     # WP_List_Table for reservation management
│       └── SettingsPage.php          # Options page (KM/Miles, Geocoder, Concurrency)
├── templates/
│   ├── frontend/
│   │   ├── search-filters.php        # Global search bar with radius slider and live grid
│   │   ├── entity-card.php           # Entity list card with distance chip
│   │   └── booking-modal.php         # Interactive booking drawer / modal
│   └── admin/
│       ├── metabox-entity-details.php# Meta box tabbed interface
│       └── settings-view.php         # Settings view with Pro features spotlight
├── languages/
│   └── my-booking-engine.pot         # Translation catalog template
├── .github/
│   └── workflows/
│       └── ci.yml                    # Automated PHPCS, PHPStan, and make-pot CI pipeline
├── phpcs.xml.dist                    # WordPress coding standards configuration
├── phpstan.neon.dist                 # Static analysis configuration (Level 6)
├── uninstall.php                     # Clean uninstallation hook
├── readme.txt                        # WordPress.org standard readme
└── my-booking-engine.php             # Main plugin bootstrap
```

---

## 🚀 Shortcodes Guide

1. **`[mb_search_filter]`**:
   Renders the full worldwide postal code search bar, live radius slider, category filters, and AJAX results grid.
2. **`[mb_booking_form id="123"]`**:
   Renders the interactive booking drawer / calendar for entity post ID 123.
3. **`[mb_entities type="rental" limit="6"]`**:
   Displays a responsive card grid for published entities of a specific category.

---

## 🔒 Security & WordPress.org Compliance
- **Strict Sanitization**: Every input is sanitized using `sanitize_text_field()`, `absint()`, `floatval()`, `sanitize_key()`, `sanitize_email()`, and `wp_unslash()`.
- **Late Escaping**: Output strings are escaped at the point of rendering (`esc_html()`, `esc_attr()`, `esc_url()`).
- **Nonces & Permissions**: All REST endpoints and AJAX calls verify nonces (`X-WP-Nonce`, `wp_verify_nonce()`) and capabilities (`manage_options`).
- **Prepared Statements**: All raw SQL queries are parameterized via `$wpdb->prepare()`.
- **Freemium Clean Separation**: No annoying review nags or intrusive banners. Free version is complete, self-sustaining, and 100% GPL-2.0+ compatible.
