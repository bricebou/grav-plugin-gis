<?php

namespace Grav\Plugin\Shortcodes;

use Grav\Plugin\GIS\GISPluginDrawMap;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class GISPluginShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('gis', function (ShortcodeInterface $sc) {

            $args = [];
            $markers = [];
            $parameters = $sc->getParameters();
            $parametersMarkersKeys = preg_grep('/^marker[0-9]*$/i', array_keys($parameters));

            foreach ($parametersMarkersKeys as $key => $value) {
                $markerProperties = explode(',', $parameters[$value]);
                $markers[] = [
                    'name' => $markerProperties[0],
                    'latitude' => $markerProperties[1],
                    'longitude' => $markerProperties[2],
                    'icon' => trim($markerProperties[3])
                ];
            }

            $args['id'] = $sc->getParameter('id') ?? null;
            $args['height'] = $sc->getParameter('height') ?? null;
            $args['center'] = $sc->getParameter('center') ?? null;
            $args['zoom'] = $sc->getParameter('zoom') ?? null;
            $args['markers'] = $markers;

            $map = new GisPluginDrawMap();
            return $map->drawMap($args);
        });
    }
}
