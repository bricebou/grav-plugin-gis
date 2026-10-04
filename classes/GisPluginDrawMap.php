<?php

namespace Grav\Plugin\Gis;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Grav\Plugin\GisPlugin;

class GisPluginDrawMap
{
    /**
     * @var array<float> Coordinates used when neither the call nor the config provide usable ones
     */
    private const FALLBACK_CENTER = [51.505, -0.093];

    /**
     * @var int Zoom used when neither the call nor the config provide a usable one
     */
    private const FALLBACK_ZOOM = 13;

    /**
     * @var int Map height used when neither the call nor the config provide a usable one
     */
    private const FALLBACK_HEIGHT = 340;

    /**
     * @var string Map width used when the call doesn't provide a usable one
     */
    private const FALLBACK_WIDTH = '100%';

    /**
     * @var string Icon used when a marker doesn't specify a known one
     */
    private const DEFAULT_ICON = 'blue';

    private $template_html = 'partials/leaflet.html.twig';

    private $template_vars = [];

    /**
     * Assets a frontend map needs, in loading order
     *
     * Kept in one place because they are added from two spots: the Twig
     * function adds them on every request, while the shortcode hands them to
     * shortcode-core, which stores them alongside the cached page content
     *
     * @return array<array{0: string, 1: string, 2: array<string, mixed>}> Type, path and options
     */
    public static function assets(): array
    {
        return [
            [
                'js', 'plugins://gis/lib/leaflet/leaflet.js', [
                    'loading' => 'defer',
                ]],
            [
                'js', 'plugins://gis/assets/js/gis.js', [
                    'loading' => 'defer',
                ]],
            ['css', 'plugins://gis/lib/leaflet/leaflet.css', []],
            // Loaded after Leaflet's own stylesheet: it undoes what a theme's
            // [role="button"] reset does to markers and controls
            ['css', 'plugins://gis/assets/css/gis.css', []],
        ];
    }

    /**
     * @param  array<mixed> $params
     * @return string
     */
    public function drawMap(array $params)
    {
        $config = Grav::instance()['config'];

        // Every value below ends up in the map's data attribute, then in
        // Leaflet calls: they are validated rather than passed through
        $center = $this->parseCenter($params['center'] ?? null)
            ?? $this->parseCenter($config->get('plugins.gis.public.center'))
            ?? self::FALLBACK_CENTER;

        $zoom = $this->parseInt($params['zoom'] ?? null)
            ?? $this->parseInt($config->get('plugins.gis.public.zoom'))
            ?? self::FALLBACK_ZOOM;

        $height = $this->parseInt($params['height'] ?? null)
            ?? $this->parseInt($config->get('plugins.gis.public.height'))
            ?? self::FALLBACK_HEIGHT;

        $page = Grav::instance()['page'];
        $header = (array) $page->header();
        $markers = $this->parseMarkers($params['markers'] ?? $header['markers'] ?? []);

        // Resolved through the locator so the plugin keeps working when Grav
        // is served from a subdirectory or plugins live outside user/plugins
        $icons_url = Utils::url('plugins://gis/assets/images', false, true);
        $shadow_url = Utils::url('plugins://gis/lib/leaflet/images/marker-shadow.png', false, true);

        $this->template_vars = [
            'id' => $this->parseId($params['id'] ?? null) ?? $this->uniqueId(),
            'width' => $this->parseWidth($params['width'] ?? null) ?? self::FALLBACK_WIDTH,
            'height' => $height,
            // Everything assets/js/gis.js needs to draw the map
            'map' => [
                'center' => $center,
                'zoom' => $zoom,
                'icons' => $icons_url,
                'shadow' => $shadow_url,
                'markers' => $markers,
            ],
            // Still handed over so a theme overriding the template with the
            // former inline script keeps a working map
            'center_lat' => $center[0],
            'center_lng' => $center[1],
            'zoom' => $zoom,
            'markers' => $markers,
            'icons_url' => $icons_url,
            'shadow_url' => $shadow_url,
        ];

        return Grav::instance()['twig']->twig()->render($this->template_html, $this->template_vars);
    }

    /**
     * Builds a container id that stays unique across the whole page
     *
     * A per request counter isn't enough: a page can mix maps drawn by the
     * shortcode, whose output Grav caches, with maps drawn by the Twig
     * function, rendered again on every request. On a cached page the counter
     * restarts, the ids collide, and every map after the first duplicate binds
     * to an already initialised container and never appears.
     */
    private function uniqueId(): string
    {
        return bin2hex(random_bytes(4));
    }

    /**
     * Keeps the markers Leaflet can draw, whatever their origin
     *
     * The shortcode parses its own arguments, but markers coming from the page
     * frontmatter or from the Twig function arrive as they were typed
     *
     * @param  mixed $markers
     * @return array<array<string, mixed>>
     */
    private function parseMarkers($markers): array
    {
        if (! is_array($markers)) {
            return [];
        }

        $icons = array_keys(GisPlugin::markersList());
        $parsed = [];

        foreach ($markers as $marker) {
            if (! is_array($marker)) {
                continue;
            }

            $latitude = $marker['latitude'] ?? null;
            $longitude = $marker['longitude'] ?? null;

            // Leaflet throws on non numeric coordinates, which would take the
            // whole map down: skip the faulty marker and keep drawing the others
            if (! is_numeric($latitude) || ! is_numeric($longitude)) {
                continue;
            }

            $name = $marker['name'] ?? '';
            $icon = $marker['icon'] ?? '';

            $parsed[] = [
                'name' => is_scalar($name) ? trim((string) $name) : '',
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                // An unknown icon would only point at a missing image
                'icon' => in_array($icon, $icons, true) ? $icon : self::DEFAULT_ICON,
            ];
        }

        return $parsed;
    }

    /**
     * Turns a `latitude, longitude` value into two floats
     *
     * @param  mixed $center A `lat, lng` string or a two entries array
     * @return array<float>|null Null when the value can't be used
     */
    private function parseCenter($center): ?array
    {
        $parts = is_array($center) ? $center : explode(',', (string) $center);
        $parts = array_map('trim', array_map('strval', $parts));

        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        return [(float) $parts[0], (float) $parts[1]];
    }

    /**
     * @param  mixed $value
     * @return int|null Null when the value isn't a number
     */
    private function parseInt($value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Keeps a width that is safe to drop in the container's style attribute
     *
     * @param  mixed $width A number of pixels, or a number followed by `px`, `%`, `em`, `rem` or `vw`
     * @return string|null Null when the width holds anything else
     */
    private function parseWidth($width): ?string
    {
        if (! is_scalar($width)) {
            return null;
        }

        $width = strtolower(trim((string) $width));

        if (! preg_match('/^(\d+(?:\.\d+)?)(px|%|em|rem|vw)?$/', $width, $matches) || (float) $matches[1] <= 0) {
            return null;
        }

        return $matches[1] . ($matches[2] ?? 'px');
    }

    /**
     * Keeps an id that is safe to drop in both an HTML attribute and a selector
     *
     * @param  mixed $id
     * @return string|null Null when the id holds anything else than word characters
     */
    private function parseId($id): ?string
    {
        $id = trim((string) $id);

        return $id !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $id) ? $id : null;
    }
}
