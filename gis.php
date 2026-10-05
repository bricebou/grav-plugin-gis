<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Events\TypesEvent;
use Grav\Plugin\Gis\GisPluginDrawMap;
use RocketTheme\Toolbox\Event\Event;
use Twig\TwigFunction;

class GisPlugin extends Plugin
{
    /**
     * The other events are only subscribed to once the plugins are initialised
     *
     * @return array<string, array<int, array{0: string, 1: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                ['onPluginsInitialized', 0],
            ],
        ];
    }

    /**
     * Composer autoload
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
        $this->enable([
            'onAssetsInitialized' => ['onAssetsInitialized', 0],
            'onBuildTwigSandboxPolicy' => ['onBuildTwigSandboxPolicy', 0],
            'onGetPageBlueprints' => ['onGetPageBlueprints', 0],
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onTwigInitialized' => ['onTwigInitialized', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
        ]);
    }

    public function onAssetsInitialized(): void
    {
        if ($this->isAdmin() && $this->config->get('plugins.gis.private.load')) {
            $this->loadAssets(GisPluginDrawMap::assets());

            $center = $this->config->get('plugins.gis.private.center');
            $zoom = $this->config->get('plugins.gis.private.zoom');
            $this->grav['assets']->addJs('plugins://' . $this->name . '/assets/js/admin.geolocation.js', [
                'loading' => 'defer',
                'zoom' => $zoom,
                'center' => $center,
                'height' => $this->config->get('plugins.gis.private.height'),
                'icons' => $this->assetsUrl('assets/images'),
                'shadow' => $this->assetsUrl('lib/leaflet/images/marker-shadow.png'),
            ]);
        }

        // Maps load their assets on their own; this only forces them on every
        // page, for a theme that draws its own Leaflet maps or renders its
        // assets before the page content
        if (! $this->isAdmin() && $this->config->get('plugins.gis.public.load')) {
            $this->loadAssets(GisPluginDrawMap::assets());
        }
    }

    /**
     * Grav 2 runs editor authored Twig through a sandbox that only exposes an
     * allowlist, so `{{ gis() }}` written in a page fails silently until the
     * function is declared here. Exposing it to content authors is the same
     * trust boundary as registering it in the first place.
     */
    public function onBuildTwigSandboxPolicy(Event $event): void
    {
        // Read-modify-write: the event arguments are returned by value
        $functions = $event['functions'];
        $functions[] = 'gis';
        $event['functions'] = $functions;
    }

    /**
     * Adds the Gis page blueprint, a default page with a Geolocation tab
     */
    public function onGetPageBlueprints(TypesEvent $event): void
    {
        $types = $event->types;
        $types->scanBlueprints('plugins://' . $this->name . '/blueprints');
    }

    /**
     * Registers the [gis] shortcode
     */
    public function onShortcodeHandlers(): void
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }

    public function onTwigInitialized(): void
    {
        $this->grav['twig']->twig()->addFunction(
            new TwigFunction('gis', $this->gisTwigFunction(...), [
                'is_safe' => ['html'],
            ])
        );
    }

    /**
     * @param  array<mixed> $args
     * @return string
     */
    public function gisTwigFunction(array $args = [])
    {
        if (array_key_exists('center', $args) && is_array($args['center'])) {
            $args['center'] = implode(',', $args['center']);
        }

        if (array_key_exists('markers', $args)) {
            $args['markers'] = ! $args['markers'] ? [] : $args['markers'];
        }

        // Rendered again on every request, so the asset manager can be fed
        // directly. It needs a theme rendering its assets after the content,
        // as Quark does with its deferred assets block
        $this->loadAssets(GisPluginDrawMap::assets());

        $map = new GisPluginDrawMap();
        return $map->drawMap($args);
    }

    /**
     * Makes the plugin's templates available to Twig, so a theme can override them
     */
    public function onTwigTemplatePaths(): void
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
     */
    private function assetsUrl(string $path): string
    {
        return (string) Utils::url('plugins://' . $this->name . '/' . $path, false, true);
    }

    /**
     * Adds assets as listed by GisPluginDrawMap::assets()
     *
     * @param  array<array{0: string, 1: string, 2: array<string, mixed>}> $assets
     */
    private function loadAssets(array $assets): void
    {
        foreach ($assets as [$type, $path, $options]) {
            $method = 'add' . ucfirst($type);
            $this->grav['assets']->{$method}($path, $options);
        }
    }

    /**
     * Lists the marker icons shipped in assets/images, for the blueprint's select
     *
     * @return array<string, string> Icon name and its translation key
     */
    public static function markersList(): array
    {
        $options = [];
        // glob() returns false on some systems when nothing matches
        $icons = glob(__DIR__ . '/assets/images/marker-*-2x.png') ?: [];

        foreach ($icons as $value) {
            if (preg_match('/marker-([a-z]+)-2x\.png$/', $value, $matches) !== 1) {
                continue;
            }

            $options[$matches[1]] = 'PLUGIN_GIS.MARKER_' . strtoupper($matches[1]);
        }

        return $options;
    }
}
