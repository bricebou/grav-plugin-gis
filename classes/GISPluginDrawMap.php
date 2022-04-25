<?php

namespace Grav\Plugin\Gis;

use Grav\Common\Grav;

class GISPluginDrawMap
{
    private static $instances = 0;
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
        $this->template_vars = [
            'id'            =>      $params['id'] ?? self::$instances,
            'height'        =>      $params['height'] ?? Grav::instance()['config']->get('plugins.gis.public.height'),
            'center'        =>      $params['center'] ?? Grav::instance()['config']->get('plugins.gis.public.center'),
            'zoom'          =>      $params['zoom'] ?? Grav::instance()['config']->get('plugins.gis.public.zoom'),
        ];

        $page = Grav::instance()['page'];
        $header = (array) $page->header();
        $this->template_vars['markers'] = $params['markers'] ?? $header['markers'];

        $output = Grav::instance()['twig']->twig()->render($this->template_html, $this->template_vars);

        self::$instances++;

        return $output;
    }
}
