<?php

namespace Grav\Common;

use Grav\Common\Config\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @implements \ArrayAccess<string, mixed>
 */
class Grav implements \ArrayAccess
{
    /**
     * @param array<string, mixed> $values
     */
    public static function instance(array $values = []): self
    {
    }

    public function offsetExists(mixed $offset): bool
    {
    }

    public function offsetGet(mixed $offset): mixed
    {
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
    }
}

class Plugin implements EventSubscriberInterface
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var Grav
     */
    protected $grav;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents()
    {
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
    }

    /**
     * @param array<string, mixed> $events
     */
    protected function enable(array $events)
    {
    }
}

class Utils
{
    /**
     * @param string|object $input
     * @param bool $domain
     * @param bool $fail_gracefully
     * @param string|bool|null $lang
     * @return string|false
     */
    public static function url($input, $domain = false, $fail_gracefully = false, $lang = null)
    {
    }
}

namespace Grav\Common\Config;

class Config
{
    /**
     * @param string $name
     * @param mixed $default
     * @param string|null $separator
     * @return mixed
     */
    public function get($name, $default = null, $separator = null)
    {
    }
}

namespace Grav\Common\Twig;
use Twig\Environment;

class Twig
{
    /**
     * @var array<int, string>
     */
    public $twig_paths;

    /**
     * @return Environment
     */
    public function twig()
    {
    }
}

namespace Symfony\Component\EventDispatcher;

interface EventSubscriberInterface
{
    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents();
}

namespace Grav\Common\Page;

class Types
{
    /**
     * @param string $uri
     * @return void
     */
    public function scanBlueprints($uri)
    {
    }
}

namespace Grav\Events;

use Grav\Common\Page\Types;
use RocketTheme\Toolbox\Event\Event;

class TypesEvent extends Event
{
    /** @var Types */
    public $types;
}

namespace RocketTheme\Toolbox\Event;

/**
 * @implements \ArrayAccess<string, mixed>
 */
class Event implements \ArrayAccess
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(array $items = [])
    {
    }

    public function offsetExists(mixed $offset): bool
    {
    }

    public function offsetGet(mixed $offset): mixed
    {
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
    }
}
