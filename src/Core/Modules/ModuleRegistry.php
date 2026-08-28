<?php
namespace ApexSEO\Core\Modules;

use ApexSEO\Core\Contracts\ModuleInterface;

class ModuleRegistry {
    private $modules = [];

    public function register(ModuleInterface $module): void {
        $this->modules[$module->getName()] = $module;
    }

    public function get(string $name): ?ModuleInterface {
        return $this->modules[$name] ?? null;
    }

    public function getAll(): array {
        return $this->modules;
    }

    public function bootAll(): void {
        foreach ($this->modules as $module) {
            $module->boot();
            $module->registerHooks();
        }
    }
}
