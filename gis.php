<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;

/**
 * Class GISPlugin
 * @package Grav\Plugin
 */
class GISPlugin extends Plugin
{
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
            'onGetPageBlueprints' => ['onGetPageBlueprints', 0],
            'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', 0],
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

    public function onAdminTwigTemplatePaths($event): void
    {
        $paths = $event['paths'];
        $paths[] = __DIR__ . '/templates';
        $event['paths'] = $paths;
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

	public function markersList()
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
