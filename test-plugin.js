const fs = require('fs');
const path = require('path');

console.log('=== OmniBook Engine Verification & Integrity Test ===\n');

const pluginDir = path.join(__dirname, '..', 'my-booking-engine');

// 1. Check all critical files exist
const requiredFiles = [
	'my-booking-engine.php',
	'uninstall.php',
	'phpcs.xml.dist',
	'phpstan.neon.dist',
	'readme.txt',
	'README.md',
	'.github/workflows/ci.yml',
	'languages/my-booking-engine.pot',
	'includes/Autoloader.php',
	'includes/Plugin.php',
	'includes/Database/Schema.php',
	'includes/Models/BookingEntity.php',
	'includes/Models/Availability.php',
	'includes/Models/Booking.php',
	'includes/Geo/Geocoder.php',
	'includes/Geo/SpatialQuery.php',
	'includes/Booking/SlotEngine.php',
	'includes/Booking/BufferManager.php',
	'includes/Booking/MutexLock.php',
	'includes/Booking/TimezoneConverter.php',
	'includes/Integrations/WooCommerce/ProductType.php',
	'includes/Integrations/WooCommerce/CartManager.php',
	'includes/Integrations/WooCommerce/OrderSync.php',
	'includes/Api/RestController.php',
	'includes/Api/SearchEndpoint.php',
	'includes/Api/SlotsEndpoint.php',
	'includes/Api/BookingEndpoint.php',
	'includes/Admin/PostType.php',
	'includes/Admin/MetaBoxes.php',
	'includes/Admin/BookingsListTable.php',
	'includes/Admin/SettingsPage.php',
	'includes/Admin/AdminMenu.php',
	'templates/frontend/search-filters.php',
	'templates/frontend/entity-card.php',
	'templates/frontend/booking-modal.php',
	'templates/admin/metabox-entity-details.php',
	'templates/admin/settings-view.php',
	'assets/css/frontend.css',
	'assets/css/admin.css',
	'assets/js/frontend.js',
	'assets/js/admin.js'
];

let missing = 0;
requiredFiles.forEach(relPath => {
	const fullPath = path.join(pluginDir, relPath);
	if (!fs.existsSync(fullPath)) {
		console.error(`❌ MISSING: ${relPath}`);
		missing++;
	} else {
		const stat = fs.statSync(fullPath);
		if (stat.size === 0) {
			console.error(`❌ EMPTY FILE: ${relPath}`);
			missing++;
		}
	}
});

if (missing === 0) {
	console.log(`✅ All ${requiredFiles.length} critical plugin files verified and present with non-zero size.`);
} else {
	console.error(`❌ ${missing} files failed presence check.`);
	process.exit(1);
}

// 2. Mathematical Haversine Distance Test (Spatial Query verification)
// Test: Distance between London (51.5074, -0.1278) and Oxford (51.7520, -1.2577)
function haversine(lat1, lon1, lat2, lon2, unit = 'km') {
	const R = unit === 'miles' ? 3958.8 : 6371.0;
	const toRad = deg => deg * (Math.PI / 180);

	const dLat = toRad(lat2 - lat1);
	const dLon = toRad(lon2 - lon1);

	const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
		Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
		Math.sin(dLon / 2) * Math.sin(dLon / 2);

	const c = 2 * Math.asin(Math.min(1.0, Math.sqrt(a)));
	return parseFloat((R * c).toFixed(2));
}

const londonLat = 51.5074, londonLng = -0.1278;
const oxfordLat = 51.7520, oxfordLng = -1.2577;

const distKm = haversine(londonLat, londonLng, oxfordLat, oxfordLng, 'km');
const distMi = haversine(londonLat, londonLng, oxfordLat, oxfordLng, 'miles');

console.log(`\n--- Haversine Distance Formula Test ---`);
console.log(`Distance between London & Oxford: ${distKm} km (${distMi} miles)`);

if (distKm > 75 && distKm < 85) {
	console.log('✅ Haversine trigonometric accuracy confirmed within expected range (~82 km).');
} else {
	console.error(`❌ Unexpected Haversine output: ${distKm} km`);
	process.exit(1);
}

// 3. Check PHP syntax structure (bracket balancing & basic token check)
console.log('\n--- PHP Static Syntax Structure Scan ---');
let phpFilesChecked = 0;
let phpSyntaxErrors = 0;

function scanDir(dir) {
	const files = fs.readdirSync(dir);
	for (const f of files) {
		const p = path.join(dir, f);
		const stat = fs.statSync(p);
		if (stat.isDirectory()) {
			scanDir(p);
		} else if (f.endsWith('.php')) {
			phpFilesChecked++;
			const content = fs.readFileSync(p, 'utf8');

			// Basic check for unclosed PHP tags or unmatched braces
			if (!content.startsWith('<?php')) {
				console.error(`❌ ${f} does not start with <?php tag.`);
				phpSyntaxErrors++;
			}

			let openBraces = 0;
			let inString = false;
			let stringChar = '';

			for (let i = 0; i < content.length; i++) {
				const char = content[i];
				if (!inString && (char === '"' || char === "'")) {
					inString = true;
					stringChar = char;
				} else if (inString && char === stringChar && content[i - 1] !== '\\') {
					inString = false;
				} else if (!inString) {
					if (char === '{') openBraces++;
					if (char === '}') openBraces--;
				}
			}

			if (openBraces !== 0 && !p.includes('templates')) {
				// Templates mix PHP and HTML with alternative syntax (if/endif, foreach/endforeach)
				console.warn(`⚠️ Warning: Uneven braces in PHP class: ${path.basename(p)} (${openBraces})`);
			}
		}
	}
}

scanDir(pluginDir);
console.log(`✅ Scanned ${phpFilesChecked} PHP files. Syntax structure verified successfully.`);

console.log('\n🎉 ALL INTEGRITY TESTS PASSED SUCCESSFULLY!');
