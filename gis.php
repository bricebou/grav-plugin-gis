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
        }

        if (!$this->isAdmin() && $this->config->get('plugins.gis.public.load')) {
            $this->loadLeaflet();
        }
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
}
