<?php

namespace Grav\Plugin\ShortcodeCore;

use Thunder\Shortcode\HandlerContainer\HandlerContainer;

class ShortcodeManager
{
    /**
     * @param string|array<mixed> $actionOrAsset
     * @param string|array<mixed>|null $asset
     * @param array<string, mixed> $options
     */
    public function addAssets($actionOrAsset, $asset = null, array $options = [])
    {
    }

    /**
     * @return HandlerContainer
     */
    public function getHandlers()
    {
    }

    /**
     * @param string $directory
     * @param array<string, mixed> $options
     */
    public function registerAllShortcodes($directory, array $options = [])
    {
    }
}

namespace Grav\Plugin\Shortcodes;
use Grav\Plugin\ShortcodeCore\ShortcodeManager;

abstract class Shortcode
{
    /**
     * @var ShortcodeManager
     */
    protected $shortcode;

    public function init()
    {
    }
}

namespace Thunder\Shortcode\HandlerContainer;

final class HandlerContainer
{
    /**
     * @param string $name
     * @param callable $handler
     * @return $this
     */
    public function add($name, $handler)
    {
    }
}

namespace Thunder\Shortcode\Shortcode;

interface ShortcodeInterface
{
    /**
     * @return array<string, string|null>
     */
    public function getParameters();

    /**
     * @param string $name
     * @param string|null $default
     * @return string|null
     */
    public function getParameter($name, $default = null);
}
