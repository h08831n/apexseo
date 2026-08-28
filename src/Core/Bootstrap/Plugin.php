<?php
namespace ApexSEO\Core\Bootstrap;

use ApexSEO\Core\Container\Container;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\Core\Environment\EnvironmentDetector;
use ApexSEO\Core\Environment\CapabilityRegistry;
use ApexSEO\Core\Environment\Server\ServerAdapterInterface;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Hooks\HookManager;
use ApexSEO\Core\Logging\Logger;
use ApexSEO\Core\Logging\LoggerInterface;
use ApexSEO\Core\Modules\ModuleRegistry;
use ApexSEO\Core\REST\RestManager;
use ApexSEO\Core\CLI\CliManager;
use ApexSEO\API\RestApiRouter;
use ApexSEO\SEO\SeoModule;
use ApexSEO\Schema\SchemaModule;
use ApexSEO\Performance\PerformanceModule;
use ApexSEO\Media\MediaModule;
use ApexSEO\AI\AiModule;
use ApexSEO\Analytics\AnalyticsModule;

class Plugin {
    /**
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var bool
     */
    private $booted = false;

    private function __construct() {
        $this->container = new Container();
        $this->registerCoreServices();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void {
        self::$instance = null;
    }

    public function getContainer(): ContainerInterface {
        return $this->container;
    }

    public function isBooted(): bool {
        return $this->booted;
    }

    public function boot(): void {
        if ($this->booted) {
            return;
        }

        $moduleRegistry = $this->container->get(ModuleRegistry::class);
        $moduleRegistry->bootAll();

        // REST API init hook
        add_action('rest_api_init', function() {
            $restRouter = $this->container->get(RestApiRouter::class);
            $restRouter->registerRoutes();
        });

        // WP-CLI registration
        if (defined('WP_CLI') && WP_CLI) {
            $cliManager = $this->container->get(CliManager::class);
            $cliManager->registerWpCli($this->container);
        }

        $this->booted = true;
    }

    private function registerCoreServices(): void {
        $c = $this->container;

        $c->singleton(ContainerInterface::class, $c);
        $c->singleton(EnvironmentDetector::class, EnvironmentDetector::class);
        $c->singleton(ServerAdapterInterface::class, function($container) {
            $detector = $container->get(EnvironmentDetector::class);
            return $detector->detectServerAdapter();
        });
        $c->singleton(CapabilityRegistry::class, CapabilityRegistry::class);
        $c->singleton(ConfigurationManager::class, ConfigurationManager::class);
        $c->singleton(SecurityManager::class, SecurityManager::class);
        $c->singleton(HookManager::class, HookManager::class);
        $c->singleton(LoggerInterface::class, function() {
            return new Logger('apexseo');
        });
        $c->singleton(DatabaseManager::class, function() {
            global $wpdb;
            return new DatabaseManager($wpdb);
        });

        $c->singleton(ModuleRegistry::class, function($container) {
            $registry = new ModuleRegistry();
            $registry->register($container->get(SeoModule::class));
            $registry->register($container->get(SchemaModule::class));
            $registry->register($container->get(PerformanceModule::class));
            $registry->register($container->get(MediaModule::class));
            $registry->register($container->get(AiModule::class));
            $registry->register($container->get(AnalyticsModule::class));
            return $registry;
        });

        $c->singleton(RestApiRouter::class, RestApiRouter::class);
        $c->singleton(CliManager::class, CliManager::class);
    }
}
