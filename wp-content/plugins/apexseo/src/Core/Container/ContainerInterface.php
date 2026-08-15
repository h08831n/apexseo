<?php
namespace ApexSEO\Core\Container;

use ApexSEO\Core\Exceptions\NotFoundException;
use ApexSEO\Core\Exceptions\ContainerException;

/**
 * Service Container Contract for Apex SEO Platform.
 */
interface ContainerInterface {
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws NotFoundException No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     */
    public function get($id);

    /**
     * Returns true if the container can return an entry for the given identifier.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has($id);

    /**
     * Bind an explicit instantiated value or object to the container.
     *
     * @param string $id Identifier.
     * @param mixed $value Value or instance.
     * @return self
     */
    public function set($id, $value);

    /**
     * Register a shared singleton service.
     *
     * @param string $id Identifier.
     * @param string|callable|object|null $concrete Concrete class name, closure factory, or null to use id.
     * @return self
     */
    public function singleton($id, $concrete = null);

    /**
     * Register a transient service that creates a new instance on every resolution.
     *
     * @param string $id Identifier.
     * @param string|callable|null $concrete Concrete class name, closure factory, or null to use id.
     * @return self
     */
    public function bind($id, $concrete = null);

    /**
     * Register a factory resolver callable.
     *
     * @param string $id Identifier.
     * @param callable $factory Factory callback.
     * @param bool $shared Whether the factory result should be cached as a singleton.
     * @return self
     */
    public function factory($id, $factory, $shared = false);

    /**
     * Register a lazy-loaded service that is only instantiated upon first access.
     *
     * @param string $id Identifier.
     * @param callable $resolver Callback returning the service instance.
     * @return self
     */
    public function lazy($id, $resolver);

    /**
     * Create an alias for an existing service identifier.
     *
     * @param string $alias Alias name.
     * @param string $target Target identifier.
     * @return self
     */
    public function alias($alias, $target);

    /**
     * Resolve and instantiate an entry with optional contextual parameters.
     *
     * @param string $id Class or identifier.
     * @param array $parameters Contextual constructor parameters.
     * @return mixed
     * @throws ContainerException
     */
    public function make($id, array $parameters = []);

    /**
     * Check if a given identifier has already been instantiated/resolved into memory.
     *
     * @param string $id Identifier.
     * @return bool
     */
    public function resolved($id);
}
