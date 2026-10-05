<?php

namespace Twig;

final class TwigFunction
{
    /**
     * @param callable|array{class-string, string}|null $callable
     * @param array<string, mixed> $options
     */
    public function __construct(string $name, $callable = null, array $options = [])
    {
    }
}

class Environment
{
    public function addFunction(TwigFunction $function): void
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $name, array $context = []): string
    {
    }
}
