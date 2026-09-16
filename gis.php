<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Plugin\GIS\GISPluginDrawMap;
use Twig_SimpleFunction;

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
            'onBuildTwigSandboxPolicy' => ['onBuildTwigSandboxPolicy', 0],
            'onGetPageBlueprints' => ['onGetPageBlueprints', 0],
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onTwigInitialized' => ['onTwigInitialized', 0],
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
            $this->grav['assets']->addJs('plugins://' . $this->name . '/assets/js/admin.geolocation.js', [
                'loading' => 'defer',
                'zoom' => $zoom,
                'center' => $center,
                'icons' => $this->assetsUrl('assets/images'),
                'shadow' => $this->assetsUrl('lib/leaflet/images/marker-shadow.png')
            ]);
        }

        if (!$this->isAdmin() && $this->config->get('plugins.gis.public.load')) {
            $this->loadLeaflet();
        }
    }

    /**
     * onBuildTwigSandboxPolicy
     *
     * Grav 2 runs editor authored Twig through a sandbox that only exposes an
     * allowlist, so `{{ gis() }}` written in a page fails silently until the
     * function is declared here. Exposing it to content authors is the same
     * trust boundary as registering it in the first place.
     *
     * The event doesn't exist on Grav 1.x, where subscribing to it is a no-op.
     *
     * @param  mixed $event
     * @return void
     */
    public function onBuildTwigSandboxPolicy($event): void
    {
        // Read-modify-write: the event arguments are returned by value
        $functions = $event['functions'];
        $functions[] = 'gis';
        $event['functions'] = $functions;
    }

    /**
     * onGetPageBlueprints
     *
     * @param  mixed $event
     * @return void
     */
    public function onGetPageBlueprints($event): void
    {
        $types = $event->types;
        $types->scanBlueprints('plugins://' . $this->name . '/blueprints');
    }

    /**
     * Initialize configuration
     *
     * @param Event $e
     */
    public function onShortcodeHandlers()
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }

    /**
     * onTwigInitialized
     *
     * @return void
     */
    public function onTwigInitialized()
    {
        $this->grav['twig']->twig()->addFunction(
            new Twig_SimpleFunction('gis', [$this, 'gisTwigFunction'], ['is_safe' => ['html']])
        );
    }

    /**
     * gisTwigFunction
     *
     * @param  array<mixed> $args
     * @return string
     */
    public function gisTwigFunction(array $args = [])
    {
        if (array_key_exists('center', $args) && is_array($args['center'])) {
            $args['center'] = implode(',', $args['center']);
        }

        if (array_key_exists('markers', $args)) {
            $args['markers'] = !$args['markers'] ? [] : $args['markers'];
        }

        $map = new GisPluginDrawMap();
        return $map->drawMap($args);
    }

    /**
     * onTwigTemplatePaths
     *
     * @param  mixed $event
     * @return void
     */
    public function onTwigTemplatePaths($event): void
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Resolves a plugin asset to a public URL
     *
     * Goes through the locator so the plugin keeps working when Grav is served
     * from a subdirectory or plugins live outside user/plugins
     *
     * @param  string $path Path relative to the plugin root
     * @return string
     */
    private function assetsUrl(string $path): string
    {
        return (string) Utils::url('plugins://' . $this->name . '/' . $path, false, true);
    }

    /**
     * loadLeaflet
     *
     * @return void
     */
    private function loadLeaflet(): void
    {
        $this->grav['assets']->addJs('plugins://' . $this->name . '/lib/leaflet/leaflet.js', ['loading' => 'defer']);
        $this->grav['assets']->addCss('plugins://' . $this->name . '/lib/leaflet/leaflet.css');
        // Loaded after Leaflet's own stylesheet: it undoes what a theme's
        // [role="button"] reset does to markers and controls
        $this->grav['assets']->addCss('plugins://' . $this->name . '/assets/css/gis.css');
    }

    /**
     * markersList
     *
     * @return array $options
     */
    public static function markersList()
    {
        $options = [];
        $icons = glob(dirname(__FILE__) . '/assets/images/marker-*-2x.png');

        foreach ($icons as $key => $value) {
            $matches = [];
            preg_match('/marker-([a-z]*)-2x.png/', $value, $matches);

            $options[$matches[1]] = 'PLUGIN_GIS.MARKER_' . strtoupper($matches[1]);
        }

        return $options;
    }
}
