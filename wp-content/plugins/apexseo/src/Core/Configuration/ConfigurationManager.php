<?php
namespace ApexSEO\Core\Configuration;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Exceptions\ConfigurationException;

/**
 * Centralized, namespaced configuration manager for Apex SEO Platform.
 */
class ConfigurationManager implements ServiceContractInterface {
    const OPTION_GENERAL  = 'apex_general_settings';
    const OPTION_SEO      = 'apex_seo_settings';
    const OPTION_SCHEMA   = 'apex_schema_settings';
    const OPTION_PERF     = 'apex_perf_settings';
    const OPTION_MODULES  = 'apex_active_modules';
    const OPTION_VERSION  = 'apex_config_version';

    /**
     * In-memory cached settings groups.
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Default values for all configuration domains.
     *
     * @var array
     */
    protected $defaults = [
        'general' => [
            'version'            => '1.0.0',
            'log_level'          => 'INFO',
            'uninstall_drop_db'  => false,
            'telemetry_enabled'  => false,
        ],
        'seo' => [
            'title_separator'        => '-',
            'strip_category_base'    => false,
            'auto_redirect_slug'     => true,
            'enable_404_monitor'     => true,
            'enable_link_assistant'  => true,
            'enable_breadcrumbs'     => true,
            'sitemap_entries_per_page' => 1000,
        ],
        'schema' => [
            'site_type'             => 'Organization',
            'enable_automatic_graph' => true,
            'output_pretty_json'    => false,
        ],
        'perf' => [
            'page_cache_enabled'    => true,
            'cache_lifespan_hours'  => 24,
            'minify_html'           => false,
            'minify_css'            => false,
            'minify_js'             => false,
            'delay_js'              => false,
            'lazyload_images'       => true,
            'lazyload_iframes'      => true,
            'instant_click'         => true,
        ],
        'modules' => [
            'seo'         => true,
            'schema'      => true,
            'media'       => true,
            'performance' => true,
            'cache'       => true,
            'server'      => true,
            'cdn'         => true,
            'ai'          => false,
            'analytics'   => false,
            'database'    => true,
            'migration'   => true,
            'api'         => true,
            'cli'         => true,
            'admin'       => true,
            'woocommerce' => true,
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->loadSettings();
    }

    /**
     * Load settings from WordPress options or defaults.
     *
     * @return void
     */
    public function loadSettings() {
        $this->settings['general'] = $this->getOption(self::OPTION_GENERAL, $this->defaults['general']);
        $this->settings['seo']     = $this->getOption(self::OPTION_SEO, $this->defaults['seo']);
        $this->settings['schema']  = $this->getOption(self::OPTION_SCHEMA, $this->defaults['schema']);
        $this->settings['perf']    = $this->getOption(self::OPTION_PERF, $this->defaults['perf']);
        $this->settings['modules'] = $this->getOption(self::OPTION_MODULES, $this->defaults['modules']);
    }

    /**
     * Get a configuration value using dot notation (e.g. 'seo.title_separator').
     *
     * @param string $key Dot-notation key.
     * @param mixed $default Optional default fallback.
     * @return mixed
     */
    public function get($key, $default = null) {
        $parts = explode('.', $key);
        $domain = $parts[0];

        if (!isset($this->settings[$domain])) {
            return $default;
        }

        if (count($parts) === 1) {
            return $this->settings[$domain];
        }

        $current = $this->settings[$domain];
        for ($i = 1; $i < count($parts); $i++) {
            $subKey = $parts[$i];
            if (!is_array($current) || !array_key_exists($subKey, $current)) {
                return $default;
            }
            $current = $current[$subKey];
        }

        return $current;
    }

    /**
     * Set a configuration value in-memory.
     *
     * @param string $key Dot-notation key.
     * @param mixed $value Value to set.
     * @return self
     */
    public function set($key, $value) {
        $parts = explode('.', $key);
        $domain = $parts[0];

        if (!isset($this->settings[$domain])) {
            $this->settings[$domain] = [];
        }

        if (count($parts) === 1) {
            if (!is_array($value)) {
                throw new ConfigurationException(sprintf('Setting domain [%s] must be an array.', $domain));
            }
            $this->settings[$domain] = $value;
            return $this;
        }

        $current = &$this->settings[$domain];
        for ($i = 1; $i < count($parts) - 1; $i++) {
            $subKey = $parts[$i];
            if (!isset($current[$subKey]) || !is_array($current[$subKey])) {
                $current[$subKey] = [];
            }
            $current = &$current[$subKey];
        }

        $finalKey = $parts[count($parts) - 1];
        $current[$finalKey] = $value;

        return $this;
    }

    /**
     * Save a specific domain's configuration or all domains to WordPress options.
     *
     * @param string|null $domain Specific domain (e.g., 'seo', 'perf') or null for all.
     * @return bool
     */
    public function save($domain = null) {
        $domains = $domain !== null ? [$domain] : array_keys($this->settings);
        $success = true;

        $optionMap = [
            'general' => self::OPTION_GENERAL,
            'seo'     => self::OPTION_SEO,
            'schema'  => self::OPTION_SCHEMA,
            'perf'    => self::OPTION_PERF,
            'modules' => self::OPTION_MODULES,
        ];

        foreach ($domains as $d) {
            if (isset($optionMap[$d]) && isset($this->settings[$d])) {
                $saved = $this->updateOption($optionMap[$d], $this->settings[$d]);
                if (!$saved) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Get all settings across all domains.
     *
     * @return array
     */
    public function getAll() {
        return $this->settings;
    }

    /**
     * Check if a module is enabled in configuration.
     *
     * @param string $moduleId Module ID (e.g., 'seo', 'perf', 'cache').
     * @return bool
     */
    public function isModuleEnabled($moduleId) {
        return (bool) $this->get('modules.' . $moduleId, true);
    }

    /**
     * Enable or disable a module.
     *
     * @param string $moduleId Module ID.
     * @param bool $enabled Status.
     * @return self
     */
    public function setModuleStatus($moduleId, $enabled) {
        $this->set('modules.' . $moduleId, (bool) $enabled);
        return $this;
    }

    /**
     * Get default settings array for a domain.
     *
     * @param string $domain Domain key.
     * @return array
     */
    public function getDefaults($domain) {
        return isset($this->defaults[$domain]) ? $this->defaults[$domain] : [];
    }

    /**
     * Safe wrapper around WordPress get_option.
     *
     * @param string $name Option name.
     * @param mixed $default Fallback value.
     * @return mixed
     */
    protected function getOption($name, $default) {
        if (function_exists('get_option')) {
            $val = get_option($name, $default);
            return is_array($val) && is_array($default) ? array_merge($default, $val) : $val;
        }
        return $default;
    }

    /**
     * Safe wrapper around WordPress update_option.
     *
     * @param string $name Option name.
     * @param mixed $value Value to persist.
     * @return bool
     */
    protected function updateOption($name, $value) {
        if (function_exists('update_option')) {
            return update_option($name, $value, false); // Autoload = false to prevent unneeded autoload bloat
        }
        return true;
    }
}
