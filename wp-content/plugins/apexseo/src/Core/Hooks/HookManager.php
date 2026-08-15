<?php
namespace ApexSEO\Core\Hooks;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Clean WordPress Action and Filter Hook Manager for Apex SEO Platform.
 */
class HookManager implements ServiceContractInterface {
    /**
     * Registered hook tracking list for introspection.
     *
     * @var array
     */
    protected $registeredHooks = [];

    /**
     * Add a WordPress action hook callback.
     *
     * @param string $hook Action hook name.
     * @param callable $callback Callable handler.
     * @param int $priority Priority order (default 10).
     * @param int $acceptedArgs Accepted arguments count (default 1).
     * @return bool
     */
    public function addAction($hook, $callback, $priority = 10, $acceptedArgs = 1) {
        $this->registeredHooks[] = [
            'type'         => 'action',
            'hook'         => $hook,
            'callback'     => $callback,
            'priority'     => $priority,
            'acceptedArgs' => $acceptedArgs,
        ];

        if (function_exists('add_action')) {
            return add_action($hook, $callback, $priority, $acceptedArgs);
        }

        return true;
    }

    /**
     * Add a WordPress filter hook callback.
     *
     * @param string $hook Filter hook name.
     * @param callable $callback Callable handler.
     * @param int $priority Priority order (default 10).
     * @param int $acceptedArgs Accepted arguments count (default 1).
     * @return bool
     */
    public function addFilter($hook, $callback, $priority = 10, $acceptedArgs = 1) {
        $this->registeredHooks[] = [
            'type'         => 'filter',
            'hook'         => $hook,
            'callback'     => $callback,
            'priority'     => $priority,
            'acceptedArgs' => $acceptedArgs,
        ];

        if (function_exists('add_filter')) {
            return add_filter($hook, $callback, $priority, $acceptedArgs);
        }

        return true;
    }

    /**
     * Remove an action hook callback.
     *
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     * @return bool
     */
    public function removeAction($hook, $callback, $priority = 10) {
        if (function_exists('remove_action')) {
            return remove_action($hook, $callback, $priority);
        }
        return true;
    }

    /**
     * Remove a filter hook callback.
     *
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     * @return bool
     */
    public function removeFilter($hook, $callback, $priority = 10) {
        if (function_exists('remove_filter')) {
            return remove_filter($hook, $callback, $priority);
        }
        return true;
    }

    /**
     * Execute action hook.
     *
     * @param string $hook
     * @param mixed ...$args
     * @return void
     */
    public function doAction($hook, ...$args) {
        if (function_exists('do_action')) {
            do_action($hook, ...$args);
        }
    }

    /**
     * Apply filter hook.
     *
     * @param string $hook
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    public function applyFilters($hook, $value, ...$args) {
        if (function_exists('apply_filters')) {
            return apply_filters($hook, $value, ...$args);
        }
        return $value;
    }

    /**
     * Get list of registered hooks.
     *
     * @return array
     */
    public function getRegisteredHooks() {
        return $this->registeredHooks;
    }
}
