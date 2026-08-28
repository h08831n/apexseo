<?php
namespace ApexSEO\CLI;

use ApexSEO\Core\Container\ContainerInterface;

abstract class AbstractCliCommand {
    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }
}
