<?php
namespace ApexSEO\Core\Container;

use ApexSEO\Core\Exceptions\ContainerException;
use ReflectionClass;
use ReflectionParameter;

class Container implements ContainerInterface {
    private $bindings = [];
    private $instances = [];
    private $aliases = [];
    private $building = [];

    public function get(string $id) {
        $id = $this->resolveAlias($id);

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->building[$id])) {
            throw new ContainerException("Circular dependency detected for: {$id}");
        }

        if (isset($this->bindings[$id])) {
            $binding = $this->bindings[$id];
            $type = $binding['type'];
            $resolver = $binding['resolver'];

            $this->building[$id] = true;
            try {
                if (is_callable($resolver)) {
                    $object = $resolver($this);
                } elseif (is_string($resolver) && class_exists($resolver)) {
                    $object = $this->autoWire($resolver);
                } else {
                    $object = $resolver;
                }
            } finally {
                unset($this->building[$id]);
            }

            if ($type === 'singleton' || $type === 'lazy') {
                $this->instances[$id] = $object;
            }

            return $object;
        }

        if (class_exists($id)) {
            $this->building[$id] = true;
            try {
                $object = $this->autoWire($id);
            } finally {
                unset($this->building[$id]);
            }
            return $object;
        }

        throw new ContainerException("Service not found: {$id}");
    }

    public function has(string $id): bool {
        $id = $this->resolveAlias($id);
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    public function set(string $id, $concrete): void {
        $this->instances[$id] = $concrete;
    }

    public function singleton(string $id, $concrete): void {
        $this->bindings[$id] = [
            'type' => 'singleton',
            'resolver' => $concrete,
        ];
    }

    public function factory(string $id, $concrete): void {
        $this->bindings[$id] = [
            'type' => 'factory',
            'resolver' => $concrete,
        ];
    }

    public function lazy(string $id, callable $factory): void {
        $this->bindings[$id] = [
            'type' => 'lazy',
            'resolver' => $factory,
        ];
    }

    public function alias(string $alias, string $target): void {
        $this->aliases[$alias] = $target;
    }

    private function resolveAlias(string $id): string {
        return isset($this->aliases[$id]) ? $this->resolveAlias($this->aliases[$id]) : $id;
    }

    private function autoWire(string $className) {
        $reflector = new ReflectionClass($className);
        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Target class [{$className}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $param) {
            $dependencyClass = null;
            if (method_exists($param, 'getType')) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $dependencyClass = $type->getName();
                }
            } elseif (method_exists($param, 'getClass')) {
                $cls = $param->getClass();
                if ($cls) {
                    $dependencyClass = $cls->getName();
                }
            }

            if ($dependencyClass !== null) {
                $dependencies[] = $this->get($dependencyClass);
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } elseif ($param->isOptional()) {
                $dependencies[] = null;
            } else {
                throw new ContainerException("Cannot resolve un-typehinted parameter \${$param->name} in {$className}");
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
