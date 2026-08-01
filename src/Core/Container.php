<?php

namespace App\Core;

use ReflectionClass;
use ReflectionException;
use Exception;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    /**
     * Bind an interface or abstract class to a concrete class or a factory callback.
     */
    public function bind(string $abstract, mixed $concrete = null, bool $singleton = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }

    /**
     * Bind a singleton interface/concrete class.
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Check if a binding exists.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || class_exists($abstract);
    }

    /**
     * Resolve the dependency from the container.
     */
    public function get(string $abstract): mixed
    {
        // If it's a singleton and already resolved, return it.
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // If not bound, we assume it's a concrete class name we can try to instantiate.
        $concrete = $abstract;
        $isSingleton = false;

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract]['concrete'];
            $isSingleton = $this->bindings[$abstract]['singleton'];
        }

        // Resolve the concrete target.
        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        // Save if singleton.
        if ($isSingleton) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Build concrete instance using Reflection.
     */
    private function build(string $concrete): mixed
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("Class {$concrete} does not exist: " . $e->getMessage());
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // If there's no constructor, we can just instantiate the class.
        if ($constructor === null) {
            return new $concrete();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new Exception("Cannot resolve parameter {$parameter->getName()} in class {$concrete} (no type hint).");
            }

            if ($type instanceof \ReflectionUnionType) {
                throw new Exception("Union types in constructor are not supported in this container.");
            }

            $typeName = $type->getName();

            // Check if class exists or is bound.
            if ($this->has($typeName) || class_exists($typeName)) {
                $dependencies[] = $this->get($typeName);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new Exception("Cannot resolve class parameter {$parameter->getName()} of type {$typeName} in class {$concrete}.");
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
