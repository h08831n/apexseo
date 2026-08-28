<?php
namespace ApexSEO\AI;

use ApexSEO\Core\Contracts\ModuleInterface;

class AiModule implements ModuleInterface {
    public function getName(): string {
        return 'ai';
    }
    public function boot(): void {}
    public function registerHooks(): void {}
}
