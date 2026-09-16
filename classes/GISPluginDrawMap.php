<?php

namespace Grav\Plugin\Gis;

use Grav\Common\Grav;
use Grav\Common\Utils;

class GISPluginDrawMap
{
    /** @var array<float> Coordinates used when neither the call nor the config provide usable ones */
    private const FALLBACK_CENTER = [51.505, -0.093];
    /** @var int Zoom used when neither the call nor the config provide a usable one */
    private const FALLBACK_ZOOM = 13;
    /** @var int Map height used when neither the call nor the config provide a usable one */
    private const FALLBACK_HEIGHT = 340;

    private $template_html    = 'partials/leaflet.html.twig';
    private $template_vars    = [];

    /**
     * drawMap
     *
     * @param  array<mixed> $params
     * @return string $output
     */
    public function drawMap(array $params)
    {
        $config = Grav::instance()['config'];

        // Every value below lands inside an inline script or an HTML attribute,
        // where escaping alone wouldn't help: a payload made of plain characters
        // goes through untouched. So they are validated rather than interpolated
        $center = $this->parseCenter($params['center'] ?? null)
            ?? $this->parseCenter($config->get('plugins.gis.public.center'))
            ?? self::FALLBACK_CENTER;

        $zoom = $this->parseInt($params['zoom'] ?? null)
            ?? $this->parseInt($config->get('plugins.gis.public.zoom'))
            ?? self::FALLBACK_ZOOM;

        $height = $this->parseInt($params['height'] ?? null)
            ?? $this->parseInt($config->get('plugins.gis.public.height'))
            ?? self::FALLBACK_HEIGHT;

        $this->template_vars = [
            'id'            =>      $this->parseId($params['id'] ?? null) ?? $this->uniqueId(),
            'height'        =>      $height,
            'center_lat'    =>      $center[0],
            'center_lng'    =>      $center[1],
            'zoom'          =>      $zoom,
            // Resolved through the locator so the plugin keeps working when Grav
            // is served from a subdirectory or plugins live outside user/plugins
            'icons_url'     =>      Utils::url('plugins://gis/assets/images', false, true),
            'shadow_url'    =>      Utils::url('plugins://gis/lib/leaflet/images/marker-shadow.png', false, true),
        ];

        $page = Grav::instance()['page'];
        $header = (array) $page->header();
        $this->template_vars['markers'] = $params['markers'] ?? $header['markers'] ?? [];

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
     *
     * @return string
     */
    private function uniqueId(): string
    {
        return bin2hex(random_bytes(4));
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

        if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
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
