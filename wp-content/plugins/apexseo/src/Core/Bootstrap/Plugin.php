<?php
namespace ApexSEO\Core\Bootstrap;

use ApexSEO\Core\Container\Container;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\Core\Environment\EnvironmentDetector;
use ApexSEO\Core\Environment\CapabilityRegistry;
use ApexSEO\Core\Environment\Server\ServerAdapterInterface;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Database\MigrationRunner;
use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Logging\LoggerInterface;
use ApexSEO\Core\Logging\Logger;
use ApexSEO\Core\Multisite\MultisiteManager;
use ApexSEO\Core\Hooks\HookManager;
use ApexSEO\Core\Modules\ModuleRegistry;
use ApexSEO\Core\REST\RestManager;
use ApexSEO\Core\CLI\CliManager;
use ApexSEO\Core\Lifecycle\LifecycleManager;
use ApexSEO\SEO\SeoModule;
use ApexSEO\Schema\SchemaModule;
use ApexSEO\Performance\PerformanceModule;
use ApexSEO\Media\MediaModule;
use ApexSEO\AI\AiModule;
use ApexSEO\Analytics\AnalyticsModule;

/**
 * Apex SEO Platform Main Bootstrap Coordinator.
 */
class Plugin {
    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    protected static $instance = null;

    /**
     * Service container instance.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Indicates whether the plugin has completed its boot sequence.
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * Private constructor for singleton pattern.
     */
    protected function __construct() {
        $this->container = new Container();
        $this->registerCoreServices();
    }

    /**
     * Get global plugin singleton instance.
     *
     * @return Plugin
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Reset the singleton instance (useful for test isolation).
     *
     * @return void
     */
    public static function reset() {
        self::$instance = null;
    }

    /**
     * Get the service container.
     *
     * @return ContainerInterface
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * Register core infrastructure services into the container.
     *
     * @return void
     */
    protected function registerCoreServices() {
        $c = $this->container;

        // Container self-binding
        $c->set(ContainerInterface::class, $c);
        $c->set(Container::class, $c);

        // 1. Environment & Server
        $c->singleton(EnvironmentDetector::class, function() {
            return new EnvironmentDetector();
        });

        $c->singleton(ServerAdapterInterface::class, function(ContainerInterface $container) {
            return $container->get(EnvironmentDetector::class)->getServerAdapter();
        });

        // 2. Capability Registry
        $c->singleton(CapabilityRegistry::class, function(ContainerInterface $container) {
            return new CapabilityRegistry($container->get(EnvironmentDetector::class));
        });

        // 3. Centralized Configuration
        $c->singleton(ConfigurationManager::class, function() {
            return new ConfigurationManager();
        });

        // 4. Logging Service (Lazy resolution to preserve frontend performance)
        $c->lazy(LoggerInterface::class, function(ContainerInterface $container) {
            $config = $container->get(ConfigurationManager::class);
            $minLevel = $config->get('general.log_level', 'INFO');
            return new Logger(null, $minLevel);
        });
        $c->alias(Logger::class, LoggerInterface::class);

        // 5. Database & Multisite
        $c->singleton(DatabaseManager::class, function() {
            return new DatabaseManager();
        });

        $c->singleton(MultisiteManager::class, function(ContainerInterface $container) {
            return new MultisiteManager($container->get(DatabaseManager::class));
        });

        $c->singleton(MigrationRunner::class, function(ContainerInterface $container) {
            return new MigrationRunner(
                $container->get(DatabaseManager::class),
                $container->get(LoggerInterface::class)
            );
        });

        // 6. Security Manager
        $c->singleton(SecurityManager::class, function() {
            return new SecurityManager();
        });

        // 7. Hooks & Lifecycle
        $c->singleton(HookManager::class, function() {
            return new HookManager();
        });

        // 8. Module Registry
        $c->singleton(ModuleRegistry::class, function(ContainerInterface $container) {
            $registry = new ModuleRegistry(
                $container->get(ConfigurationManager::class),
                $container->get(LoggerInterface::class)
            );

            // Register Core Subsystems
            $registry->registerModule(new SeoModule());
            $registry->registerModule(new SchemaModule());
            $registry->registerModule(new PerformanceModule());
            $registry->registerModule(new MediaModule());
            $registry->registerModule(new AiModule());
            $registry->registerModule(new AnalyticsModule());

            return $registry;
        });

        // 9. REST API Foundation
        $c->singleton(RestManager::class, function(ContainerInterface $container) {
            return new RestManager($container->get(SecurityManager::class));
        });

        // 10. WP-CLI Foundation
        $c->singleton(CliManager::class, function() {
            return new CliManager();
        });
    }

    /**
     * Boot the core plugin and all active modules.
     *
     * @return void
     */
    public function boot() {
        if ($this->booted) {
            return;
        }

        $c = $this->container;

        // Register hooks for core systems
        $restManager = $c->get(RestManager::class);
        $restManager->registerHooks();

        $cliManager = $c->get(CliManager::class);
        $cliManager->registerHooks();

        // Check if database schema upgrade is needed (in admin or CLI only)
        if (is_admin() || (defined('WP_CLI') && WP_CLI)) {
            LifecycleManager::checkUpgrade($c->get(MigrationRunner::class));
        }

        // Register module services and boot modules
        $moduleRegistry = $c->get(ModuleRegistry::class);
        $moduleRegistry->registerServices($c);
        $moduleRegistry->bootModules($c);

        $this->booted = true;
    }

    /**
     * Check if plugin is booted.
     *
     * @return bool
     */
    public function isBooted() {
        return $this->booted;
    }
}
