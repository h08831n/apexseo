<?php
namespace ApexSEO\Core\Contracts;

use ApexSEO\Core\Container\ContainerInterface;

/**
 * Interface for services or modules requiring an explicit boot sequence.
 */
interface BootableInterface {
    /**
     * Boot the service with the container instance.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function boot(ContainerInterface $container);
}
