<?php
namespace ApexSEO\Core\Contracts;

use ApexSEO\Core\Container\ContainerInterface;

/**
 * Contract for modular functional extensions in Apex SEO.
 */
interface ModuleInterface {
    /**
     * Get unique module identifier string.
     *
     * @return string
     */
    public function getId();

    /**
     * Get human-readable module name.
     *
     * @return string
     */
    public function getName();

    /**
     * Get module semantic version.
     *
     * @return string
     */
    public function getVersion();

    /**
     * Determine if the module is enabled based on configuration and context.
     *
     * @return bool
     */
    public function isEnabled();

    /**
     * Register module service bindings into the container.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function register(ContainerInterface $container);

    /**
     * Boot the module after all services are registered.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function boot(ContainerInterface $container);
}
