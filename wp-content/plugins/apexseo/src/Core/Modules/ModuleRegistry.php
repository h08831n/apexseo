<?php
namespace ApexSEO\Core\Modules;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Logging\LoggerInterface;
use Exception;

/**
 * Modular Architecture Registry and Lifecycle Manager for Apex SEO Platform.
 */
class ModuleRegistry implements ServiceContractInterface {
    const STATE_REGISTERED = 'REGISTERED';
    const STATE_ENABLED    = 'ENABLED';
    const STATE_DISABLED   = 'DISABLED';
    const STATE_BOOTED     = 'BOOTED';
    const STATE_FAILED     = 'FAILED';

    /**
     * Registered module instances.
     *
     * @var ModuleInterface[]
     */
    protected $modules = [];

    /**
     * Lifecycle states per module.
     *
     * @var array
     */
    protected $states = [];

    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config = null;

    /**
     * Logger instance.
     *
     * @var LoggerInterface|null
     */
    protected $logger = null;

    /**
     * Constructor.
     *
     * @param ConfigurationManager|null $config
     * @param LoggerInterface|null $logger
     */
    public function __construct($config = null, $logger = null) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Register a module into the registry.
     *
     * @param ModuleInterface $module
     * @return self
     */
    public function registerModule(ModuleInterface $module) {
        $id = $module->getId();
        $this->modules[$id] = $module;
        $this->states[$id]  = self::STATE_REGISTERED;

        return $this;
    }

    /**
     * Register and bind services for all eligible modules into the container.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function registerServices(ContainerInterface $container) {
        foreach ($this->modules as $id => $module) {
            $isEnabled = $this->isModuleEligible($module);

            if (!$isEnabled) {
                $this->states[$id] = self::STATE_DISABLED;
                continue;
            }

            $this->states[$id] = self::STATE_ENABLED;

            try {
                $module->register($container);
            } catch (Exception $e) {
                $this->states[$id] = self::STATE_FAILED;
                if ($this->logger !== null) {
                    $this->logger->error(sprintf('Module [%s] failed during registration: %s', $id, $e->getMessage()), [
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Boot all enabled modules sequentially.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function bootModules(ContainerInterface $container) {
        foreach ($this->modules as $id => $module) {
            if ($this->states[$id] !== self::STATE_ENABLED) {
                continue;
            }

            try {
                $module->boot($container);
                $this->states[$id] = self::STATE_BOOTED;
            } catch (Exception $e) {
                $this->states[$id] = self::STATE_FAILED;
                if ($this->logger !== null) {
                    $this->logger->error(sprintf('Module [%s] failed during boot: %s', $id, $e->getMessage()), [
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Determine if a module is eligible for registration and boot in current request lifecycle.
     *
     * @param ModuleInterface $module
     * @return bool
     */
    public function isModuleEligible(ModuleInterface $module) {
        $id = $module->getId();

        // 1. Check configuration override
        if ($this->config !== null && !$this->config->isModuleEnabled($id)) {
            return false;
        }

        // 2. Check module's own internal readiness constraint
        return $module->isEnabled();
    }

    /**
     * Get module instance by ID.
     *
     * @param string $id
     * @return ModuleInterface|null
     */
    public function getModule($id) {
        return isset($this->modules[$id]) ? $this->modules[$id] : null;
    }

    /**
     * Get all registered modules.
     *
     * @return ModuleInterface[]
     */
    public function getModules() {
        return $this->modules;
    }

    /**
     * Get lifecycle state of a module.
     *
     * @param string $id
     * @return string
     */
    public function getModuleState($id) {
        return isset($this->states[$id]) ? $this->states[$id] : self::STATE_DISABLED;
    }

    /**
     * Check if a module is successfully booted.
     *
     * @param string $id
     * @return bool
     */
    public function isModuleBooted($id) {
        return isset($this->states[$id]) && $this->states[$id] === self::STATE_BOOTED;
    }
}
