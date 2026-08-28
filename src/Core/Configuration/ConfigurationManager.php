<?php
namespace ApexSEO\Core\Configuration;

class ConfigurationManager {
    const OPTION_KEY = 'apexseo_settings';
    private $settings = [];

    public function __construct() {
        $this->load();
    }

    public function load(): void {
        $stored = get_option(self::OPTION_KEY, []);
        $defaults = $this->getDefaults();
        $this->settings = is_array($stored) ? array_replace_recursive($defaults, $stored) : $defaults;
    }

    public function save(): bool {
        return update_option(self::OPTION_KEY, $this->settings);
    }

    public function get(string $key, $default = null) {
        $keys = explode('.', $key);
        $curr = $this->settings;
        foreach ($keys as $k) {
            if (!is_array($curr) || !array_key_exists($k, $curr)) {
                return $default;
            }
            $curr = $curr[$k];
        }
        return $curr;
    }

    public function set(string $key, $value): void {
        $keys = explode('.', $key);
        $curr = &$this->settings;
        foreach ($keys as $k) {
            if (!isset($curr[$k]) || !is_array($curr[$k])) {
                $curr[$k] = [];
            }
            $curr = &$curr[$k];
        }
        $curr = $value;
    }

    public function has(string $key): bool {
        return $this->get($key) !== null;
    }

    public function all(): array {
        return $this->settings;
    }

    public function reset(): void {
        $this->settings = $this->getDefaults();
        $this->save();
    }

    public function getDefaults(): array {
        return [
            'general' => [
                'site_name' => get_bloginfo('name') ?: 'My Site',
                'title_separator' => '|',
                'uninstall_drop_db' => false,
                'meta_keywords_enabled' => false,
                'category_base_strip' => false,
            ],
            'titles' => [
                'post_title_template' => '%%title%% %%sep%% %%sitename%%',
                'page_title_template' => '%%title%% %%sep%% %%sitename%%',
                'post_desc_template' => '%%excerpt%%',
            ],
            'social' => [
                'og_enabled' => true,
                'twitter_enabled' => true,
                'default_og_image' => '',
                'twitter_card_type' => 'summary_large_image',
            ],
            'sitemap' => [
                'enabled' => true,
                'items_per_page' => 1000,
                'exclude_post_types' => [],
            ],
            'media' => [
                'lazy_load' => true,
                'lcp_optimization' => true,
                'webp_conversion' => true,
                'avif_conversion' => false,
                'compression_quality' => 85,
            ],
            'performance' => [
                'minify_css' => true,
                'minify_js' => true,
                'minify_html' => true,
                'delay_js' => false,
                'resource_hints' => true,
                'page_cache' => true,
            ],
            'schema' => [
                'enabled' => true,
                'default_type' => 'Organization',
            ],
            'analytics' => [
                'monitor_404' => true,
                'rank_tracking' => true,
            ],
            'ai' => [
                'enabled' => false,
                'provider' => 'gemini',
            ]
        ];
    }
}
