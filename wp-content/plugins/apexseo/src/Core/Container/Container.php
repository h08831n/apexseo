<?php
namespace ApexSEO\Core\Container;

use ApexSEO\Core\Exceptions\ContainerException;
use ApexSEO\Core\Exceptions\NotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Closure;

/**
 * Lightweight, high-performance Dependency Injection Container for Apex SEO Platform.
 */
class Container implements ContainerInterface {
    /**
     * Instantiated singleton instances.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Service definition bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Factory resolvers.
     *
     * @var array
     */
    protected $factories = [];

    /**
     * Lazy-loaded service resolvers.
     *
     * @var array
     */
    protected $lazyResolvers = [];

    /**
     * Identifier aliases.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Circular dependency detection stack.
     *
     * @var array
     */
    protected $resolving = [];

    /**
     * Global static instance (optional default container).
     *
     * @var Container|null
     */
    protected static $instance = null;

    /**
     * Get or set the global container instance.
     *
     * @param Container|null $container
     * @return Container
     */
    public static function getInstance($container = null) {
        if ($container !== null) {
            self::$instance = $container;
        }

        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Set the globally shared container instance.
     *
     * @param Container|null $container
     * @return void
     */
    public static function setInstance($container = null) {
        self::$instance = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id) {
        $id = $this->resolveAlias($id);

        // 1. Check if already instantiated as a singleton
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Check if a lazy resolver is registered
        if (isset($this->lazyResolvers[$id])) {
            $resolver = $this->lazyResolvers[$id];
            $instance = $resolver($this);
            $this->instances[$id] = $instance;
            unset($this->lazyResolvers[$id]);
            return $instance;
        }

        // 3. Check factory bindings
        if (isset($this->factories[$id])) {
            $definition = $this->factories[$id];
            $instance = call_user_func($definition['callback'], $this);
            if ($definition['shared']) {
                $this->instances[$id] = $instance;
            }
            return $instance;
        }

        // 4. Check registered bindings
        if (isset($this->bindings[$id])) {
            $binding = $this->bindings[$id];
            $instance = $this->build($binding['concrete']);
            if ($binding['shared']) {
                $this->instances[$id] = $instance;
            }
            return $instance;
        }

        // 5. Attempt auto-wiring if class exists
        if (class_exists($id)) {
            return $this->build($id);
        }

        throw new NotFoundException(sprintf('Target identifier or class [%s] is not registered in the container.', $id));
    }

    /**
     * {@inheritdoc}
     */
    public function has($id) {
        $id = $this->resolveAlias($id);

        return isset($this->instances[$id])
            || isset($this->lazyResolvers[$id])
            || isset($this->factories[$id])
            || isset($this->bindings[$id])
            || class_exists($id);
    }

    /**
     * {@inheritdoc}
     */
    public function set($id, $value) {
        $id = $this->resolveAlias($id);
        $this->instances[$id] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function singleton($id, $concrete = null) {
        $id = $this->resolveAlias($id);
        $concrete = $concrete !== null ? $concrete : $id;

        if (is_object($concrete) && !($concrete instanceof Closure)) {
            $this->instances[$id] = $concrete;
            return $this;
        }

        $this->bindings[$id] = [
            'concrete' => $concrete,
            'shared'   => true,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bind($id, $concrete = null) {
        $id = $this->resolveAlias($id);
        $concrete = $concrete !== null ? $concrete : $id;

        $this->bindings[$id] = [
            'concrete' => $concrete,
            'shared'   => false,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function factory($id, $factory, $shared = false) {
        $id = $this->resolveAlias($id);
        $this->factories[$id] = [
            'callback' => $factory,
            'shared'   => (bool) $shared,
        ];
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function lazy($id, $resolver) {
        $id = $this->resolveAlias($id);
        $this->lazyResolvers[$id] = $resolver;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function alias($alias, $target) {
        $this->aliases[$alias] = $target;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function make($id, array $parameters = []) {
        $id = $this->resolveAlias($id);

        if (isset($this->instances[$id]) && empty($parameters)) {
            return $this->instances[$id];
        }

        return $this->build($id, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function resolved($id) {
        $id = $this->resolveAlias($id);
        return isset($this->instances[$id]);
    }

    /**
     * Check if a service is registered as lazy and not yet resolved.
     *
     * @param string $id Identifier.
     * @return bool
     */
    public function isLazy($id) {
        $id = $this->resolveAlias($id);
        return isset($this->lazyResolvers[$id]);
    }

    /**
     * Resolve alias to canonical identifier.
     *
     * @param string $id Identifier or alias.
     * @return string Canonical identifier.
     */
    protected function resolveAlias($id) {
        $visited = [];
        while (isset($this->aliases[$id])) {
            if (isset($visited[$id])) {
                throw new ContainerException(sprintf('Circular alias detected for [%s].', $id));
            }
            $visited[$id] = true;
            $id = $this->aliases[$id];
        }
        return $id;
    }

    /**
     * Build and instantiate a target concrete definition or class.
     *
     * @param mixed $concrete Class name or closure.
     * @param array $parameters Contextual parameters.
     * @return mixed
     * @throws ContainerException
     */
    protected function build($concrete, array $parameters = []) {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        if (!is_string($concrete)) {
            return $concrete;
        }

        $concrete = $this->resolveAlias($concrete);

        // Circular resolution detection
        if (isset($this->resolving[$concrete])) {
            throw new ContainerException(sprintf('Circular dependency detected while resolving [%s].', $concrete));
        }

        $this->resolving[$concrete] = true;

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            unset($this->resolving[$concrete]);
            throw new NotFoundException(sprintf('Target class [%s] does not exist.', $concrete), 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            unset($this->resolving[$concrete]);
            throw new ContainerException(sprintf('Target class [%s] is not instantiable.', $concrete));
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            unset($this->resolving[$concrete]);
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        unset($this->resolving[$concrete]);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve reflection parameters to concrete dependency instances.
     *
     * @param ReflectionParameter[] $dependencies Parameter reflections.
     * @param array $parameters Explicit contextual parameters.
     * @return array
     * @throws ContainerException
     */
    protected function resolveDependencies(array $dependencies, array $parameters = []) {
        $results = [];

        foreach ($dependencies as $parameter) {
            $name = $parameter->getName();

            // Override with explicit parameter if provided
            if (array_key_exists($name, $parameters)) {
                $results[] = $parameters[$name];
                continue;
            }

            // In PHP 7.4/8.x check class type hint safely
            $type = $parameter->getType();
            if ($type !== null && !$type->isBuiltin()) {
                $className = method_exists($type, 'getName') ? $type->getName() : (string) $type;
                try {
                    $results[] = $this->get($className);
                    continue;
                } catch (NotFoundException $e) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $results[] = $parameter->getDefaultValue();
                        continue;
                    }
                    if ($parameter->allowsNull()) {
                        $results[] = null;
                        continue;
                    }
                    throw new ContainerException(
                        sprintf('Cannot resolve dependency [%s] for parameter [%s]: %s', $className, $name, $e->getMessage()),
                        0,
                        $e
                    );
                }
            }

            // Default value fallback
            if ($parameter->isDefaultValueAvailable()) {
                $results[] = $parameter->getDefaultValue();
                continue;
            }

            // Nullable fallback
            if ($parameter->allowsNull()) {
                $results[] = null;
                continue;
            }

            throw new ContainerException(sprintf('Unresolvable dependency parameter [%s] in constructor.', $name));
        }

        return $results;
    }

    /**
     * Clear all container state (useful for tests or teardown).
     *
     * @return void
     */
    public function flush() {
        $this->instances = [];
        $this->bindings = [];
        $this->factories = [];
        $this->lazyResolvers = [];
        $this->aliases = [];
        $this->resolving = [];
    }
}
