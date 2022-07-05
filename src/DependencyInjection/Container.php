<?php

namespace GSpataro\DependencyInjection;

use ReflectionFunction;

final class Container
{
    /**
     * Store components to boot
     *
     * @var array
     */

    private array $components = [];

    /**
     * Store singleton instances
     *
     * @var array
     */

    private array $singletons = [];

    /**
     * Store services
     *
     * @var array
     */

    private array $services = [];

    /**
     * Store variables
     *
     * @var array
     */

    private array $variables = [];

    /**
     * Verify if a service exists
     *
     * @param string $tag
     * @return bool
     */

    public function has(string $tag): bool
    {
        return isset($this->services[$tag]);
    }

    /**
     * Add a service to the container
     *
     * @param string $tag
     * @param callable $factory
     * @param bool $isSingleton
     * @return void
     */

    public function add(string $tag, callable $factory, bool $isSingleton = true): void
    {
        if ($this->has($tag)) {
            throw new Exception\ServiceFoundException(
                "A service with the tag '{$tag}' already exists."
            );
        }

        $factoryReflection = new ReflectionFunction($factory);

        if ($factoryReflection->getReturnType() != "object") {
            throw new Exception\InvalidFactoryReturnTypeException(
                "Invalid factory for service '{$tag}'. A factory must return an object."
            );
        }

        $this->services[$tag] = [
            "factory" => $factory,
            "singleton" => $isSingleton
        ];
    }

    /**
     * Get a service instance
     *
     * @param string $tag
     * @param array $params
     * @return object
     */

    public function get(string $tag, array $params = []): object
    {
        if (!$this->has($tag)) {
            throw new Exception\ServiceNotFoundException(
                "Service with the tag '{$tag}' not found."
            );
        }

        if (isset($this->singletons[$tag])) {
            return $this->singletons[$tag];
        }

        $service = $this->services[$tag];
        $object = $service['factory']($this, $params);

        if ($service['singleton']) {
            $this->singletons[$tag] = $object;
        }

        return $object;
    }

    /**
     * Set a variable
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */

    public function setVariable(string $key, mixed $value): void
    {
        $this->variables[$key] = $value;
    }

    /**
     * Get a variable
     *
     * @param string $key
     * @return mixed
     */

    public function getVariable(string $key): mixed
    {
        return $this->variables[$key] ?? null;
    }

    /**
     * Load components
     *
     * @param array $components
     * @return void
     */

    public function loadComponents(array $components): void
    {
        for ($i = 0; $i < count($components); $i++) {
            if (is_object($components[$i])) {
                throw new Exception\InvalidComponentException(
                    "Invalid component" .
                    get_class($components[$i]) .
                    ". Class name expected, object given."
                );
            }

            if (!class_exists($components[$i])) {
                throw new Exception\ComponentNotFoundException(
                    "Component class '" .
                    $components[$i] .
                    "' not found."
                );
            }

            if (get_parent_class($components[$i]) !== Component::class) {
                throw new Exception\InvalidComponentException(
                    "Invalid component " .
                    $components[$i] .
                    ". A component must extend the DependencyInjection\\Component abstract class."
                );
            }

            $component = new $components[$i]($this);
            $component->register();
            $this->components[$i] = $component;
        }
    }

    /**
     * Boot the registered components
     *
     * @return void
     */

    public function boot(): void
    {
        foreach ($this->components as $component) {
            $component->boot();
        }
    }
}
