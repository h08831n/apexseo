<?php
namespace ApexSEO\Core\REST;

use ApexSEO\API\RestApiRouter;

class RestManager {
    private $router;

    public function __construct(RestApiRouter $router) {
        $this->router = $router;
    }

    public function registerRoutes(): void {
        $this->router->registerRoutes();
    }
}
