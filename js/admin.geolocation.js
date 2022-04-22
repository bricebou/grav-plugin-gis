const center = document.currentScript.getAttribute('center');
const zoom = document.currentScript.getAttribute('zoom');

function commaStringToLatLng(string) {
	let latlng = string.split(',');
	latlng[0] = parseFloat(latlng[0]);
	latlng[1] = parseFloat(latlng[1]);

	return new L.LatLng(latlng[0], latlng[1]);
}

function updateInputValue(target,value) {
	target.value = value;
}

function appendLeafletMap(node, lat, lng) {
	let coordinates = [];
	let marker;

	let mapContainer = document.createElement('div');
	mapContainer.classList.add('leaflet-map');
	mapContainer.style.height = '300px';

	node.appendChild(mapContainer);

	let leafletMapContainer = node.querySelector('.leaflet-map');

	let map = L.map(leafletMapContainer);
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
	}).addTo(map);
	autoCenterZoom();

	if (lat.value && lng.value) {
		let string = lat.value + ',' + lng.value;
		addMarker(commaStringToLatLng(string));
		autoCenterZoom();
	}

	function autoCenterZoom() {
		if (!coordinates.flat().length) {
			map.setView(commaStringToLatLng(center), zoom)
		}
		else {
			let bounds = new L.LatLngBounds(coordinates);
			map.fitBounds(bounds, {maxZoom : +zoom});
		}
	}

	function addMarker(latlng) {
		const markerOptions = {
			draggable: true,
		}
		marker = L.marker(latlng, markerOptions).addTo(map);
		coordinates.push(latlng);

		marker.on('dragend', function(event){
			coordinates[0] = event.target._latlng;
			autoCenterZoom();
			updateInputValue(lat, event.target._latlng.lat);
			updateInputValue(lng, event.target._latlng.lng);
		});
	}

	map.on('click', function (e) {
		if (marker) {
			delete coordinates[0];
			map.removeLayer(marker);
			autoCenterZoom();
		}
		addMarker(e.latlng);
		autoCenterZoom();
		updateInputValue(lat, e.latlng.lat);
		updateInputValue(lng, e.latlng.lng);
	});
}

// select the observer's target node
const markersList = document.querySelector('.form-list-wrapper[data-type="collection"] [data-collection-holder="header.markers"]');

if (markersList) {
	let markersListItems = markersList.getElementsByTagName("li");
	for (let item of markersListItems) {
		let latInput = item.children[3].children[1].querySelector('input');
		let lngInput = item.children[4].children[1].querySelector('input');

		appendLeafletMap(item, latInput, lngInput);
	}

	// create an observer instance
	let observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {
			let li = mutation.addedNodes[0];

			let latInput = li.children[2].children[1].querySelector('input');
			let lngInput = li.children[3].children[1].querySelector('input');

			appendLeafletMap(li, latInput, lngInput);
		});
	});

	// configuration of the observer:
	let config = { attributes: true, childList: true, characterData: true }

	// pass in the target node, as well as the observer options
	observer.observe(markersList, config);
}