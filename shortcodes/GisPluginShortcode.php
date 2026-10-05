<?php

namespace Grav\Plugin\Shortcodes;

use Grav\Plugin\Gis\GisPluginDrawMap;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class GisPluginShortcode extends Shortcode
{
    public function init(): void
    {
        $this->shortcode->getHandlers()
            ->add('gis', function (ShortcodeInterface $sc) {

                $args = [];
                $markers = [];
                $parameters = $sc->getParameters();
                $parametersMarkersKeys = preg_grep('/^marker[0-9]*$/i', array_keys($parameters)) ?: [];

                foreach ($parametersMarkersKeys as $value) {
                    $markers[] = $this->parseMarker($parameters[$value]);
                }

                $args = [
                    'id' => $sc->getParameter('id') ?? null,
                    'width' => $sc->getParameter('width') ?? null,
                    'height' => $sc->getParameter('height') ?? null,
                    'center' => $sc->getParameter('center') ?? null,
                    'zoom' => $sc->getParameter('zoom') ?? null,
                    'markers' => $markers,
                ];

                // Handed to shortcode-core rather than to the asset manager: the
                // shortcode output is cached with the page, and shortcode-core
                // adds these assets back on every request served from that cache
                foreach (GisPluginDrawMap::assets() as $asset) {
                    $this->shortcode->addAssets($asset['type'], $asset['path'], $asset['options']);
                }

                $map = new GisPluginDrawMap();
                return $map->drawMap($args);
            });
    }

    /**
     * Turns a `markerN` parameter into a marker
     *
     * Coordinates and icon are validated by GisPluginDrawMap, along with the
     * markers coming from the frontmatter
     *
     * @param  string|null $parameter `name, latitude, longitude[, icon]`
     * @return array<string, string>
     */
    private function parseMarker($parameter): array
    {
        // Markdown turns the surrounding double quotes into entities whenever the
        // shortcode isn't alone on its line, so decode before splitting
        $properties = explode(',', html_entity_decode((string) $parameter, ENT_QUOTES, 'UTF-8'));
        $properties = array_map(trim(...), $properties);

        return [
            // The documented syntax wraps the name in single quotes, drop them
            'name' => trim($properties[0] ?? '', " \t\n\r\0\x0B'\""),
            'latitude' => $properties[1] ?? '',
            'longitude' => $properties[2] ?? '',
            'icon' => $properties[3] ?? '',
        ];
    }
}
