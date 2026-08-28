<?php
namespace ApexSEO\Core\Environment;

class CapabilityRegistry {
    private $capabilities = [];

    public function __construct() {
        $this->capabilities = [
            'meta_tags' => true,
            'social_graph' => true,
            'xml_sitemaps' => true,
            'schema_graph' => true,
            'page_cache' => true,
            'asset_minification' => true,
            'image_optimization' => true,
            'four_oh_four_monitor' => true,
            'redirects_engine' => true,
            'content_analysis' => true,
            'rank_tracking' => true,
            'ai_metadata' => true,
            'cli_tools' => true,
            'rest_api' => true,
        ];
    }

    public function has(string $cap): bool {
        return !empty($this->capabilities[$cap]);
    }

    public function enable(string $cap): void {
        $this->capabilities[$cap] = true;
    }

    public function disable(string $cap): void {
        $this->capabilities[$cap] = false;
    }

    public function getAll(): array {
        return $this->capabilities;
    }
}
