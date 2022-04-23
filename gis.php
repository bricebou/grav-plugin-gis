<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Twig_SimpleFunction;

/**
 * Class GISPlugin
 * @package Grav\Plugin
 */
class GISPlugin extends Plugin
{
    private static $instances = 0;
    private $template_html    = 'partials/leaflet.html.twig';
    private $template_vars    = [];

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                // Uncomment following line when plugin requires Grav < 1.7
                // ['autoload', 100000],
                ['onPluginsInitialized', 0]
            ]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        // Enable the main events we are interested in
        $this->enable([
            // Put your main events here
            'onAssetsInitialized' => ['onAssetsInitialized', 0],
            'onTwigInitialized' => ['onTwigInitialized', 0],
            'onGetPageBlueprints' => ['onGetPageBlueprints', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
        ]);
    }

    /**
     * onAssetsInitialized
     *
     * @return void
     */
    public function onAssetsInitialized(): void
    {
        if ($this->isAdmin() && $this->config->get('plugins.gis.private.load')) {
            $this->loadLeaflet();

            $center = $this->config->get('plugins.gis.private.center');
            $zoom = $this->config->get('plugins.gis.private.zoom');
            $this->grav['assets']->addJs('plugins://' . $this->name . '/js/admin.geolocation.js', ['loading' => 'defer', 'zoom' => $zoom, 'center' => $center ]);
        }

        if (!$this->isAdmin() && $this->config->get('plugins.gis.public.load')) {
            $this->loadLeaflet();
        }
    }

    public function onGetPageBlueprints($event): void
    {
        $types = $event->types;
        $types->scanBlueprints('plugins://' . $this->name . '/blueprints');
    }

    public function onTwigInitialized()
    {
        $this->grav['twig']->twig()->addFunction(
            new Twig_SimpleFunction('gis', [$this, 'gisTwigFunction'], ['is_safe' => ['html']])
        );
    }

    public function gisTwigFunction(array $args = [])
    {
        $this->template_vars = [
            'id'            =>      $args['id'] ?? self::$instances,
            'height'        =>      $args['height'] ?? $this->config->get('plugins.gis.public.height'),
            'center'        =>      $args['center'] ?? $this->config->get('plugins.gis.public.center'),
            'zoom'          =>      $args['zoom'] ?? $this->config->get('plugins.gis.public.zoom'),
        ];

        $defaultLatLng = explode(',', $this->config->get('plugins.gis.public.center'));
        $defaultMarker = ['latitude' => $defaultLatLng[0], 'longitude' => $defaultLatLng[1], 'icon' => 'icon'];

        $page = $this->grav['page'];
        $header = (array) $page->header();
        $markers = $args['markers'] ?? $header['markers'] ?? array($defaultMarker);
        $this->template_vars['markers'] = $markers;

        $output = $this->grav['twig']->twig()->render($this->template_html, $this->template_vars);

        self::$instances++;

        return $output;
    }

    public function onTwigTemplatePaths($event): void
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * loadLeaflet
     *
     * @return void
     */
    private function loadLeaflet(): void
    {
        $this->grav['assets']->addJs('plugins://' . $this->name . '/lib/leaflet/leaflet.js', ['loading' => 'defer']);
        $this->grav['assets']->addCss('plugins://' . $this->name . '/lib/leaflet/leaflet.min.css');
    }

    public static function markersList()
    {
        $options = [];
        $icons = glob(dirname(__FILE__) . '/lib/leaflet/images/marker-*-2x.png');

        foreach ($icons as $key => $value) {
            $matches = [];
            preg_match('/marker-([a-z]*)-2x.png/', $value, $matches);

            $options[$matches[1]] = 'PLUGIN_GIS.MARKER_' . strtoupper($matches[1]);
        }

        return $options;
    }
}
