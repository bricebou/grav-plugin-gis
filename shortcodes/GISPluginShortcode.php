<?php

namespace Grav\Plugin\Shortcodes;

use Grav\Plugin\GIS\GISPluginDrawMap;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class GISPluginShortcode extends Shortcode
{
    /** @var string Icon used when a marker doesn't specify one */
    private const DEFAULT_ICON = 'blue';

    public function init()
    {
        $this->shortcode->getHandlers()->add('gis', function (ShortcodeInterface $sc) {

            $args = [];
            $markers = [];
            $parameters = $sc->getParameters();
            $parametersMarkersKeys = preg_grep('/^marker[0-9]*$/i', array_keys($parameters));

            foreach ($parametersMarkersKeys as $key => $value) {
                $marker = $this->parseMarker($parameters[$value]);

                if ($marker !== null) {
                    $markers[] = $marker;
                }
            }

            $args = [
                'id' => $sc->getParameter('id') ?? null,
                'height' => $sc->getParameter('height') ?? null,
                'center' => $sc->getParameter('center') ?? null,
                'zoom' => $sc->getParameter('zoom') ?? null,
                'markers' => $markers
            ];

            $map = new GisPluginDrawMap();
            return $map->drawMap($args);
        });
    }

    /**
     * Turns a `markerN` parameter into a marker
     *
     * @param  string|null $parameter `name, latitude, longitude[, icon]`
     * @return array<string, mixed>|null Null when the marker can't be used
     */
    private function parseMarker($parameter): ?array
    {
        // Markdown turns the surrounding double quotes into entities whenever the
        // shortcode isn't alone on its line, so decode before splitting
        $properties = explode(',', html_entity_decode((string) $parameter, ENT_QUOTES, 'UTF-8'));
        $properties = array_map('trim', $properties);

        $latitude = $properties[1] ?? '';
        $longitude = $properties[2] ?? '';

        // Leaflet throws on non numeric coordinates, which would take the whole
        // map down: skip the faulty marker and keep drawing the others
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        $icon = $properties[3] ?? '';

        return [
            // The documented syntax wraps the name in single quotes, drop them
            'name' => trim($properties[0] ?? '', " \t\n\r\0\x0B'\""),
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'icon' => $icon !== '' ? $icon : self::DEFAULT_ICON
        ];
    }
}
