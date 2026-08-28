<?php
namespace ApexSEO\Core\Hooks;

class HookManager {
    public function addAction(string $tag, $callback, int $priority = 10, int $acceptedArgs = 1): void {
        add_action($tag, $callback, $priority, $acceptedArgs);
    }

    public function addFilter(string $tag, $callback, int $priority = 10, int $acceptedArgs = 1): void {
        add_filter($tag, $callback, $priority, $acceptedArgs);
    }

    public function doAction(string $tag, ...$args): void {
        do_action($tag, ...$args);
    }

    public function applyFilters(string $tag, $value, ...$args) {
        return apply_filters($tag, $value, ...$args);
    }

    public function removeAction(string $tag, $callback, int $priority = 10): bool {
        return remove_action($tag, $callback, $priority);
    }

    public function removeFilter(string $tag, $callback, int $priority = 10): bool {
        return remove_filter($tag, $callback, $priority);
    }
}
