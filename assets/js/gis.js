/*
 * Draws every map the plugin rendered on the page
 *
 * Each map is an empty container whose data-gis attribute holds its centre,
 * zoom and markers, validated server side. Nothing is written into an inline
 * script any more, so no value can break out of one.
 *
 * window.GisPlugin.init(root) draws the maps found under root, for content
 * added to the page after it loaded. A container is only ever drawn once.
 */
(function () {
	'use strict';

	function createIcon(icon, options) {
		return L.icon({
			iconUrl: options.icons + '/marker-' + icon + '.png',
			iconRetinaUrl: options.icons + '/marker-' + icon + '-2x.png',
			shadowUrl: options.shadow,
			iconSize: [25, 41],
			iconAnchor: [12, 41],
			popupAnchor: [1, -34],
			tooltipAnchor: [16, -28],
			shadowSize: [41, 41]
		});
	}

	function draw(container) {
		if (container.dataset.gisDrawn) {
			return;
		}

		let options;
		try {
			options = JSON.parse(container.dataset.gis);
		} catch (e) {
			return;
		}

		container.dataset.gisDrawn = 'true';

		const map = L.map(container);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
		}).addTo(map);

		const coordinates = [];

		(options.markers || []).forEach(function (marker) {
			const latlng = L.latLng(marker.latitude, marker.longitude);
			const layer = L.marker(latlng, {
				draggable: false,
				icon: createIcon(marker.icon, options)
			}).addTo(map);

			if (marker.name) {
				// Leaflet sets a string content as HTML: hand it a text
				// node so a name is always displayed as typed
				layer.bindPopup(document.createTextNode(marker.name));
			}

			coordinates.push(latlng);
		});

		if (coordinates.length) {
			map.fitBounds(L.latLngBounds(coordinates), {maxZoom: options.zoom});
		} else {
			map.setView(L.latLng(options.center[0], options.center[1]), options.zoom);
		}
	}

	function init(root) {
		(root || document).querySelectorAll('.gis-map[data-gis]').forEach(draw);
	}

	window.GisPlugin = {init: init};

	// Loaded with defer: the document is parsed by the time this runs
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			init();
		});
	} else {
		init();
	}
})();
