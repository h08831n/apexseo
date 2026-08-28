<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Bootstrap\Plugin;
use ApexSEO\API\RestApiRouter;
use ApexSEO\API\Controllers\SettingsRestController;
use ApexSEO\API\Controllers\MetaRestController;
use ApexSEO\API\Controllers\SchemaRestController;
use ApexSEO\API\Controllers\RedirectsRestController;
use ApexSEO\API\Controllers\NotFoundRestController;
use ApexSEO\API\Controllers\LinksRestController;
use ApexSEO\API\Controllers\AnalyticsRestController;
use ApexSEO\API\Controllers\CacheRestController;
use ApexSEO\API\Controllers\MediaRestController;
use ApexSEO\API\Controllers\MigrationRestController;
use ApexSEO\API\Controllers\AnalysisRestController;

class RestApiResolutionTest extends TestCase {
    public function testRestRouterAndAllControllersResolveFromContainer() {
        $plugin = Plugin::getInstance();
        $container = $plugin->getContainer();

        // 1. Resolve RestApiRouter
        $router = $container->get(RestApiRouter::class);
        $this->assertInstanceOf(RestApiRouter::class, $router);

        // 2. Resolve every individual REST controller
        $controllers = [
            SettingsRestController::class,
            MetaRestController::class,
            SchemaRestController::class,
            RedirectsRestController::class,
            NotFoundRestController::class,
            LinksRestController::class,
            AnalyticsRestController::class,
            CacheRestController::class,
            MediaRestController::class,
            MigrationRestController::class,
            AnalysisRestController::class,
        ];

        foreach ($controllers as $controllerClass) {
            $instance = $container->get($controllerClass);
            $this->assertInstanceOf($controllerClass, $instance, "Failed resolving {$controllerClass} from Container.");
        }

        // 3. Verify router initialized all 11 controller instances
        $routerControllers = $router->getControllers();
        $this->assertCount(11, $routerControllers, "Expected 11 controllers registered in RestApiRouter.");
    }
}
