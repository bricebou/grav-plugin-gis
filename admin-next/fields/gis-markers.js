/*
 * gis-markers: the page geolocation field of Admin2
 *
 * Admin2 loads this file when a blueprint uses `type: gis-markers` and
 * mounts it as <grav-gis--gis-markers>. It hands the element two
 * properties, `field` (the serialized blueprint field) and `value` (the
 * markers of the page), and listens for a `change` event carrying the new
 * value.
 *
 * A single map shows every marker above the list of their names, icons and
 * coordinates. Clicking the map moves the selected marker there, and
 * markers can be dragged. The asset URLs and the map settings are added to
 * `field.gis` by GisPlugin::onApiBlueprintResolved().
 *
 * Marker data is only ever written through textContent and form values,
 * never as HTML.
 */

const TAG = window.__GRAV_FIELD_TAG;

const DEFAULT_ICON = 'blue';

// Six decimals are about ten centimetres, more than a map click is worth
const PRECISION = 6;

let leafletLoading = null;

function loadStylesheet(href) {
    if (!href || document.querySelector(`link[rel="stylesheet"][href="${CSS.escape(href)}"]`)) {
        return;
    }

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
}

// Leaflet's UMD build sets window.L. It is loaded once for the whole
// admin session, however many fields get mounted.
function loadLeaflet(config) {
    loadStylesheet(config.leaflet_css);
    loadStylesheet(config.gis_css);

    if (window.L) {
        return Promise.resolve(window.L);
    }

    leafletLoading ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = config.leaflet_js;
        script.onload = () => resolve(window.L);
        script.onerror = () => {
            leafletLoading = null;
            reject(new Error(`Leaflet could not be loaded from ${config.leaflet_js}`));
        };
        document.head.appendChild(script);
    });

    return leafletLoading;
}

function toNumber(value) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return null;
    }

    const number = Number(String(value).trim());

    return Number.isFinite(number) ? number : null;
}

function parseCenter(value) {
    const parts = String(value ?? '').split(',').map(toNumber);

    return parts.length === 2 && parts[0] !== null && parts[1] !== null ? parts : [51.505, -0.093];
}

function normalizeMarkers(value) {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .filter((marker) => marker && typeof marker === 'object')
        .map((marker) => ({
            name: marker.name === null || marker.name === undefined ? '' : String(marker.name),
            icon: marker.icon ? String(marker.icon) : DEFAULT_ICON,
            latitude: marker.latitude === null || marker.latitude === undefined ? '' : String(marker.latitude),
            longitude: marker.longitude === null || marker.longitude === undefined ? '' : String(marker.longitude),
        }));
}

function create(tag, attributes = {}, text = null) {
    const element = document.createElement(tag);

    for (const [name, value] of Object.entries(attributes)) {
        element.setAttribute(name, value);
    }

    if (text !== null) {
        element.textContent = text;
    }

    return element;
}

const STYLE = `
${TAG} { display: block; }
${TAG} .gis-markers-map { width: 100%; border: 1px solid var(--border, #d4d4d8); border-radius: 6px; z-index: 0; }
${TAG} .gis-markers-hint { margin: 0.5rem 0; font-size: 0.85em; color: var(--muted-foreground, #71717a); }
${TAG} .gis-markers-list { list-style: none; margin: 0; padding: 0; }
${TAG} .gis-markers-item { display: grid; grid-template-columns: auto repeat(3, minmax(0, 1fr)) auto; grid-template-areas: "pick name name name remove" "pick icon latitude longitude ."; gap: 0.4rem 0.5rem; align-items: center; padding: 0.5rem; border: 1px solid transparent; border-radius: 6px; }
${TAG} .gis-markers-pick { grid-area: pick; align-self: stretch; }
${TAG} .gis-markers-name { grid-area: name; }
${TAG} .gis-markers-icon { grid-area: icon; }
${TAG} .gis-markers-latitude { grid-area: latitude; }
${TAG} .gis-markers-longitude { grid-area: longitude; }
${TAG} .gis-markers-remove { grid-area: remove; }
${TAG} .gis-markers-item.is-selected { border-color: var(--primary, #2563eb); }
${TAG} .gis-markers-item input, ${TAG} .gis-markers-item select { width: 100%; min-width: 0; padding: 0.35rem 0.5rem; border: 1px solid var(--border, #d4d4d8); border-radius: 4px; background: var(--background, #fff); color: var(--foreground, inherit); font: inherit; }
${TAG} .gis-markers-item input[aria-invalid="true"] { border-color: var(--destructive, #dc2626); }
${TAG} .gis-markers-item button, ${TAG} .gis-markers-add { padding: 0.35rem 0.6rem; border: 1px solid var(--border, #d4d4d8); border-radius: 4px; background: transparent; color: var(--foreground, inherit); font: inherit; cursor: pointer; }
${TAG} .gis-markers-item .gis-markers-pick { padding: 0.25rem 0.4rem; }
${TAG} .gis-markers-pick img { display: block; width: 13px; height: 21px; max-width: none; }
${TAG} .gis-markers-add { margin-top: 0.5rem; }
${TAG} button:disabled, ${TAG} input:disabled, ${TAG} select:disabled { cursor: not-allowed; opacity: 0.6; }
@media (max-width: 40rem) {
    ${TAG} .gis-markers-item { grid-template-columns: auto minmax(0, 1fr) auto; grid-template-areas: "pick name remove" "pick icon icon" "pick latitude latitude" "pick longitude longitude"; }
}
`;

class GisMarkersField extends HTMLElement {
    constructor() {
        super();
        this._field = {};
        this._markers = [];
        this._selected = 0;
        this._map = null;
        this._layers = [];
        this._built = false;
    }

    set field(field) {
        this._field = field || {};
    }

    get field() {
        return this._field;
    }

    // Admin2 sets the value again after every change, our own included:
    // only rebuild when it actually differs, or typing would lose focus
    set value(value) {
        const markers = normalizeMarkers(value);

        if (JSON.stringify(markers) === JSON.stringify(this._markers)) {
            return;
        }

        this._markers = markers;
        this._selected = Math.min(this._selected, Math.max(markers.length - 1, 0));

        if (this._built) {
            this._renderList();
            this._renderMarkers(true);
        }
    }

    get value() {
        return this._markers;
    }

    get _config() {
        return this._field.gis || {};
    }

    get _labels() {
        return this._config.labels || {};
    }

    get _readonly() {
        return Boolean(this._field.readonly || this._field.disabled);
    }

    connectedCallback() {
        if (this._built) {
            return;
        }

        this._built = true;
        this._build();
        this._renderList();

        loadLeaflet(this._config)
            .then((L) => this._initMap(L))
            .catch((error) => {
                this._mapElement.textContent = error.message;
            });
    }

    disconnectedCallback() {
        this._resizeObserver?.disconnect();
        this._map?.remove();
        this._map = null;
        this._layers = [];
        this._built = false;
        this.replaceChildren();
    }

    _build() {
        const style = create('style');
        style.textContent = STYLE;

        this._mapElement = create('div', { class: 'gis-markers-map' });
        this._mapElement.style.height = `${this._config.height || 340}px`;

        const hint = create('p', { class: 'gis-markers-hint' }, this._labels.markers_hint || '');

        this._list = create('ol', { class: 'gis-markers-list' });

        this._addButton = create('button', { type: 'button', class: 'gis-markers-add' }, `+ ${this._labels.add_marker || 'Add a marker'}`);
        this._addButton.disabled = this._readonly;
        this._addButton.addEventListener('click', () => this._add());

        this.replaceChildren(style, this._mapElement, hint, this._list, this._addButton);
    }

    _initMap(L) {
        if (!this._built || this._map) {
            return;
        }

        this._L = L;
        this._map = L.map(this._mapElement);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(this._map);

        this._map.on('click', (event) => this._placeSelected(event.latlng));

        // The Geolocation tab may be hidden when the field mounts: Leaflet
        // measures its container, so redraw once it gets a size
        this._resizeObserver = new ResizeObserver(() => this._map?.invalidateSize());
        this._resizeObserver.observe(this._mapElement);

        this._renderMarkers(true);
    }

    _icon(name) {
        const base = this._config.icons_url || '';

        return this._L.icon({
            iconUrl: `${base}/marker-${name}.png`,
            iconRetinaUrl: `${base}/marker-${name}-2x.png`,
            shadowUrl: this._config.shadow_url,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            tooltipAnchor: [16, -28],
            shadowSize: [41, 41],
        });
    }

    _renderMarkers(fit = false) {
        if (!this._map) {
            return;
        }

        this._layers.forEach((layer) => layer?.remove());
        this._layers = this._markers.map((marker, index) => {
            const latitude = toNumber(marker.latitude);
            const longitude = toNumber(marker.longitude);

            if (latitude === null || longitude === null) {
                return null;
            }

            const layer = this._L.marker([latitude, longitude], {
                icon: this._icon(marker.icon),
                draggable: !this._readonly,
                opacity: index === this._selected ? 1 : 0.6,
                title: marker.name,
            }).addTo(this._map);

            layer.on('click', () => this._select(index));
            layer.on('dragend', () => {
                this._select(index);
                this._setPosition(index, layer.getLatLng());
            });

            return layer;
        });

        if (fit) {
            this._fit();
        }
    }

    _fit() {
        const points = this._layers.filter(Boolean).map((layer) => layer.getLatLng());
        const zoom = this._config.zoom || 13;

        if (points.length) {
            this._map.fitBounds(this._L.latLngBounds(points), { maxZoom: zoom, padding: [30, 30] });
        } else {
            this._map.setView(parseCenter(this._config.center), zoom);
        }
    }

    _renderList() {
        const icons = this._config.icons || [{ value: DEFAULT_ICON, label: DEFAULT_ICON }];

        this._list.replaceChildren(...this._markers.map((marker, index) => {
            const item = create('li', { class: 'gis-markers-item' });
            item.classList.toggle('is-selected', index === this._selected);

            const select = create('button', { type: 'button', class: 'gis-markers-pick', title: this._labels.select_marker || '', 'aria-label': this._labels.select_marker || '', 'aria-pressed': String(index === this._selected) });
            select.appendChild(create('img', { src: `${this._config.icons_url || ''}/marker-${marker.icon}.png`, alt: '' }));
            select.addEventListener('click', () => this._select(index));

            const name = create('input', { type: 'text', class: 'gis-markers-name', placeholder: this._labels.marker_name || '', title: this._labels.marker_name || '', 'aria-label': this._labels.marker_name || '' });
            name.value = marker.name;
            name.addEventListener('input', () => this._update(index, { name: name.value }, false));
            name.addEventListener('focus', () => this._select(index, false));

            const icon = create('select', { class: 'gis-markers-icon', title: this._labels.marker_image || '', 'aria-label': this._labels.marker_image || '' });
            for (const option of icons) {
                const element = create('option', { value: option.value }, option.label);
                element.selected = option.value === marker.icon;
                icon.appendChild(element);
            }
            icon.addEventListener('change', () => this._update(index, { icon: icon.value }, true));

            const latitude = this._coordinateInput('latitude', marker.latitude, this._labels.latitude, -90, 90);
            latitude.addEventListener('input', () => this._updateCoordinate(index, latitude, 'latitude', -90, 90));
            latitude.addEventListener('focus', () => this._select(index, false));

            const longitude = this._coordinateInput('longitude', marker.longitude, this._labels.longitude, -180, 180);
            longitude.addEventListener('input', () => this._updateCoordinate(index, longitude, 'longitude', -180, 180));
            longitude.addEventListener('focus', () => this._select(index, false));

            const remove = create('button', { type: 'button', class: 'gis-markers-remove', title: this._labels.remove_marker || '', 'aria-label': this._labels.remove_marker || '' }, '✕');
            remove.addEventListener('click', () => this._remove(index));

            for (const control of [name, icon, latitude, longitude, remove]) {
                control.disabled = this._readonly;
            }

            item.append(select, name, icon, latitude, longitude, remove);

            return item;
        }));
    }

    _coordinateInput(key, value, label, min, max) {
        const input = create('input', { type: 'text', inputmode: 'decimal', class: `gis-markers-${key}`, placeholder: label || '', title: label || '', 'aria-label': label || '' });
        input.value = value;
        this._flagCoordinate(input, min, max);

        return input;
    }

    _flagCoordinate(input, min, max) {
        const number = toNumber(input.value);
        const valid = input.value.trim() === '' || (number !== null && number >= min && number <= max);
        input.setAttribute('aria-invalid', String(!valid));

        return valid;
    }

    _updateCoordinate(index, input, key, min, max) {
        this._flagCoordinate(input, min, max);
        this._update(index, { [key]: input.value.trim() }, false);
        this._renderMarkers(false);
    }

    _select(index, redraw = true) {
        if (index === this._selected) {
            return;
        }

        this._selected = index;
        [...this._list.children].forEach((item, position) => {
            item.classList.toggle('is-selected', position === index);
            item.firstElementChild?.setAttribute('aria-pressed', String(position === index));
        });

        if (redraw) {
            this._renderMarkers(false);
        } else {
            this._layers.forEach((layer, position) => layer?.setOpacity(position === index ? 1 : 0.6));
        }
    }

    _placeSelected(latlng) {
        if (this._readonly) {
            return;
        }

        if (!this._markers.length) {
            this._markers = [{ name: '', icon: DEFAULT_ICON, latitude: '', longitude: '' }];
            this._selected = 0;
        }

        this._setPosition(this._selected, latlng);
    }

    _setPosition(index, latlng) {
        this._markers = this._markers.map((marker, position) => position === index
            ? { ...marker, latitude: latlng.lat.toFixed(PRECISION), longitude: latlng.lng.toFixed(PRECISION) }
            : marker);
        this._renderList();
        this._renderMarkers(false);
        this._emit();
    }

    _update(index, changes, rerender) {
        this._markers = this._markers.map((marker, position) => position === index ? { ...marker, ...changes } : marker);

        if (rerender) {
            this._renderList();
            this._renderMarkers(false);
        }

        this._emit();
    }

    _add() {
        this._markers = [...this._markers, { name: '', icon: DEFAULT_ICON, latitude: '', longitude: '' }];
        this._selected = this._markers.length - 1;
        this._renderList();
        this._renderMarkers(false);
        this._list.lastElementChild?.querySelector('input')?.focus();
        this._emit();
    }

    _remove(index) {
        this._markers = this._markers.filter((marker, position) => position !== index);
        this._selected = Math.min(this._selected, Math.max(this._markers.length - 1, 0));
        this._renderList();
        this._renderMarkers(true);
        this._emit();
    }

    _emit() {
        this.dispatchEvent(new CustomEvent('change', { detail: this._markers.map((marker) => ({ ...marker })) }));
    }
}

if (TAG && !customElements.get(TAG)) {
    customElements.define(TAG, GisMarkersField);
}
