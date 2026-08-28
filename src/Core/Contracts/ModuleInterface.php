<?php
namespace ApexSEO\Core\Contracts;

interface ModuleInterface extends BootableInterface, HookableInterface {
    public function getName(): string;
}
