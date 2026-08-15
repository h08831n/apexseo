<?php
namespace ApexSEO\Core\Contracts;

/**
 * Interface for services registering WordPress actions and filters.
 */
interface HookableInterface {
    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    public function registerHooks();
}
