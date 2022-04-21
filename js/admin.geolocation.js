function commaStringToLatLng(string) {
	let latlng = string.split(',');
	latlng[0] = parseFloat(latlng[0]);
	latlng[1] = parseFloat(latlng[1]);

	return new L.LatLng(latlng[0], latlng[1]);
}

function map(center, zoom) {
	const input = document.querySelector('input[name="data[header][coordinates]"]');
	let coordinates = [];
	let markerID = 0;

	let map = L.map('leaflet')
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
	}).addTo(map);
	autoCenterZoom();

	if (!!input.value) {
		let arrayInput = input.value.split(';');
		arrayInput.forEach(element => {
			let coordinate = commaStringToLatLng(element);
			createMarker(coordinate, true, markerID);
			markerID += 1;
		});
	}

	function editInput() {
		let values = [];
		coordinates.forEach(element => {
			values.push(element.lat + ',' + element.lng);
		});
		input.value = values.join(';');
	}

	function autoCenterZoom() {
		if (!coordinates.flat().length) {
			map.setView(center, zoom)
		}
		else {
			let bounds = new L.LatLngBounds(coordinates);
			map.fitBounds(bounds, {maxZoom : +zoom});
		}
	}

	function createMarker(latlng, draggable, id) {
		const markerOptions = {
			draggable: draggable,
			id: id,
		}

		let marker = L.marker(latlng, markerOptions).addTo(map);
		coordinates[id] = latlng;
		editInput();
		autoCenterZoom();

		marker.on('click', function(event) {
			map.removeLayer(event.target);
			// coordinates.splice(event.target.options.id, 1);
			delete coordinates[event.target.options.id];
			console.log(coordinates);
			editInput();
			autoCenterZoom();
		});

		marker.on('dragend', function(event){
			coordinates[event.target.options.id] = event.target._latlng;
			editInput();
			autoCenterZoom();
		});
	}

	map.on('click', function (e) {
		createMarker(e.latlng, true, markerID);
		markerID += 1;
	})
}

const center = document.currentScript.getAttribute('center');
const zoom = document.currentScript.getAttribute('zoom');

const geoTab = document.querySelector('a.tab__link[data-scope="data.geolocation"]');

if (geoTab) {
	geoTab.addEventListener('click', () => {
		if (!geoTab.classList.contains('maploaded')) {
			geoTab.classList.add('maploaded');
			map(commaStringToLatLng(center), zoom);
		}
	});

	if (geoTab.classList.contains('active')) {
		map(commaStringToLatLng(center), zoom);
	}
}
