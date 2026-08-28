<?php
namespace ApexSEO\Core\Multisite;

class MultisiteManager {
    public function isNetworkActive(): bool {
        if (!is_multisite()) {
            return false;
        }
        if (!function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active_for_network('apexseo/apexseo.php');
    }

    public function runOnAllSites(callable $callback): array {
        if (!is_multisite()) {
            return [1 => $callback(1)];
        }

        $results = [];
        $sites = get_sites(['number' => 1000]);
        foreach ($sites as $site) {
            switch_to_blog((int)$site->blog_id);
            try {
                $results[$site->blog_id] = $callback((int)$site->blog_id);
            } finally {
                restore_current_blog();
            }
        }
        return $results;
    }
}
