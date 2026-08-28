#!/usr/bin/env python3
import os

files = {}

def add_file(path, content):
    files[path] = content.strip() + "\n"

# ============================================================================
# AUTOLOADER & EXCEPTIONS & CONTRACTS & CONTAINER
# ============================================================================

add_file('src/Autoloader.php', """<?php
namespace ApexSEO;

class Autoloader {
    private static $registered = false;
    private static $prefix = 'ApexSEO\\\\';
    private static $baseDir;

    public static function register(?string $baseDir = null): void {
        if (self::$registered) {
            return;
        }
        self::$baseDir = $baseDir ?: dirname(__DIR__) . '/src/';
        spl_autoload_register([__CLASS__, 'loadClass']);
        self::$registered = true;
    }

    public static function loadClass(string $class): bool {
        $prefixLen = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $prefixLen) !== 0) {
            return false;
        }

        $relativeClass = substr($class, $prefixLen);
        $file = self::$baseDir . str_replace('\\\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }

    public static function reset(): void {
        if (self::$registered) {
            spl_autoload_unregister([__CLASS__, 'loadClass']);
            self::$registered = false;
        }
    }
}
""")

add_file('src/Core/Exceptions/ApexException.php', """<?php
namespace ApexSEO\\Core\\Exceptions;

class ApexException extends \\Exception {}
""")

add_file('src/Core/Exceptions/ContainerException.php', """<?php
namespace ApexSEO\\Core\\Exceptions;

class ContainerException extends ApexException {}
""")

add_file('src/Core/Exceptions/ConfigurationException.php', """<?php
namespace ApexSEO\\Core\\Exceptions;

class ConfigurationException extends ApexException {}
""")

add_file('src/Core/Exceptions/DatabaseException.php', """<?php
namespace ApexSEO\\Core\\Exceptions;

class DatabaseException extends ApexException {}
""")

add_file('src/Core/Exceptions/NotFoundException.php', """<?php
namespace ApexSEO\\Core\\Exceptions;

class NotFoundException extends ApexException {}
""")

add_file('src/Core/Exceptions/SecurityException.php', """<?php
namespace ApexSEO\\Core\\Exceptions;

class SecurityException extends ApexException {}
""")

add_file('src/Core/Contracts/BootableInterface.php', """<?php
namespace ApexSEO\\Core\\Contracts;

interface BootableInterface {
    public function boot(): void;
}
""")

add_file('src/Core/Contracts/HookableInterface.php', """<?php
namespace ApexSEO\\Core\\Contracts;

interface HookableInterface {
    public function registerHooks(): void;
}
""")

add_file('src/Core/Contracts/ModuleInterface.php', """<?php
namespace ApexSEO\\Core\\Contracts;

interface ModuleInterface extends BootableInterface, HookableInterface {
    public function getName(): string;
}
""")

add_file('src/Core/Contracts/ServiceContractInterface.php', """<?php
namespace ApexSEO\\Core\\Contracts;

interface ServiceContractInterface {
    public function getId(): string;
}
""")

add_file('src/Core/Container/ContainerInterface.php', """<?php
namespace ApexSEO\\Core\\Container;

interface ContainerInterface {
    public function get(string $id);
    public function has(string $id): bool;
    public function set(string $id, $concrete): void;
    public function singleton(string $id, $concrete): void;
    public function factory(string $id, $concrete): void;
    public function lazy(string $id, callable $factory): void;
    public function alias(string $alias, string $target): void;
}
""")

add_file('src/Core/Container/Container.php', """<?php
namespace ApexSEO\\Core\\Container;

use ApexSEO\\Core\\Exceptions\\ContainerException;
use ReflectionClass;
use ReflectionParameter;

class Container implements ContainerInterface {
    private $bindings = [];
    private $instances = [];
    private $aliases = [];
    private $building = [];

    public function get(string $id) {
        $id = $this->resolveAlias($id);

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->building[$id])) {
            throw new ContainerException("Circular dependency detected for: {$id}");
        }

        if (isset($this->bindings[$id])) {
            $binding = $this->bindings[$id];
            $type = $binding['type'];
            $resolver = $binding['resolver'];

            $this->building[$id] = true;
            try {
                if (is_callable($resolver)) {
                    $object = $resolver($this);
                } elseif (is_string($resolver) && class_exists($resolver)) {
                    $object = $this->autoWire($resolver);
                } else {
                    $object = $resolver;
                }
            } finally {
                unset($this->building[$id]);
            }

            if ($type === 'singleton' || $type === 'lazy') {
                $this->instances[$id] = $object;
            }

            return $object;
        }

        if (class_exists($id)) {
            $this->building[$id] = true;
            try {
                $object = $this->autoWire($id);
            } finally {
                unset($this->building[$id]);
            }
            return $object;
        }

        throw new ContainerException("Service not found: {$id}");
    }

    public function has(string $id): bool {
        $id = $this->resolveAlias($id);
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    public function set(string $id, $concrete): void {
        $this->instances[$id] = $concrete;
    }

    public function singleton(string $id, $concrete): void {
        $this->bindings[$id] = [
            'type' => 'singleton',
            'resolver' => $concrete,
        ];
    }

    public function factory(string $id, $concrete): void {
        $this->bindings[$id] = [
            'type' => 'factory',
            'resolver' => $concrete,
        ];
    }

    public function lazy(string $id, callable $factory): void {
        $this->bindings[$id] = [
            'type' => 'lazy',
            'resolver' => $factory,
        ];
    }

    public function alias(string $alias, string $target): void {
        $this->aliases[$alias] = $target;
    }

    private function resolveAlias(string $id): string {
        return isset($this->aliases[$id]) ? $this->resolveAlias($this->aliases[$id]) : $id;
    }

    private function autoWire(string $className) {
        $reflector = new ReflectionClass($className);
        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Target class [{$className}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $param) {
            $dependencyClass = null;
            if (method_exists($param, 'getType')) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $dependencyClass = $type->getName();
                }
            } elseif (method_exists($param, 'getClass')) {
                $cls = $param->getClass();
                if ($cls) {
                    $dependencyClass = $cls->getName();
                }
            }

            if ($dependencyClass !== null) {
                $dependencies[] = $this->get($dependencyClass);
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } elseif ($param->isOptional()) {
                $dependencies[] = null;
            } else {
                throw new ContainerException("Cannot resolve un-typehinted parameter \\${$param->name} in {$className}");
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
""")

# ============================================================================
# LOGGING, HOOKS, CONFIGURATION
# ============================================================================

add_file('src/Core/Logging/LoggerInterface.php', """<?php
namespace ApexSEO\\Core\\Logging;

interface LoggerInterface {
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
    public function log(string $level, string $message, array $context = []): void;
}
""")

add_file('src/Core/Logging/Logger.php', """<?php
namespace ApexSEO\\Core\\Logging;

class Logger implements LoggerInterface {
    private $channel;

    public function __construct(string $channel = 'apexseo') {
        $this->channel = $channel;
    }

    public function emergency(string $message, array $context = []): void {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void {
        $this->log('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void {
        $this->log('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void {
        $this->log('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void {
        $this->log('DEBUG', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void {
        $formatted = sprintf('[%s] [%s] [%s] %s %s', date('Y-m-d H:i:s'), $this->channel, strtoupper($level), $message, !empty($context) ? json_encode($context) : '');
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($formatted);
        }
    }
}
""")

add_file('src/Core/Hooks/HookManager.php', """<?php
namespace ApexSEO\\Core\\Hooks;

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
""")

add_file('src/Core/Configuration/ConfigurationManager.php', """<?php
namespace ApexSEO\\Core\\Configuration;

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
""")

# ============================================================================
# ENVIRONMENT & SERVERS
# ============================================================================

add_file('src/Core/Environment/CapabilityRegistry.php', """<?php
namespace ApexSEO\\Core\\Environment;

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
""")

add_file('src/Core/Environment/Server/ServerAdapterInterface.php', """<?php
namespace ApexSEO\\Core\\Environment\\Server;

interface ServerAdapterInterface {
    public function getName(): string;
    public function isSupported(): bool;
    public function purgeCache(string $url): bool;
    public function flushRules(): bool;
    public function writeDirectives(string $rules): bool;
}
""")

add_file('src/Core/Environment/Server/GenericServerAdapter.php', """<?php
namespace ApexSEO\\Core\\Environment\\Server;

class GenericServerAdapter implements ServerAdapterInterface {
    public function getName(): string {
        return 'generic';
    }

    public function isSupported(): bool {
        return true;
    }

    public function purgeCache(string $url): bool {
        return true;
    }

    public function flushRules(): bool {
        return true;
    }

    public function writeDirectives(string $rules): bool {
        return true;
    }
}
""")

add_file('src/Core/Environment/Server/DirectServerAdapter.php', """<?php
namespace ApexSEO\\Core\\Environment\\Server;

class DirectServerAdapter extends GenericServerAdapter {
    public function getName(): string {
        return 'direct';
    }
}
""")

add_file('src/Core/Environment/Server/ApacheAdapter.php', """<?php
namespace ApexSEO\\Core\\Environment\\Server;

class ApacheAdapter extends GenericServerAdapter {
    public function getName(): string {
        return 'apache';
    }

    public function isSupported(): bool {
        return isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false;
    }

    public function flushRules(): bool {
        global $wp_rewrite;
        if ($wp_rewrite) {
            $wp_rewrite->flush_rules(true);
        }
        return true;
    }
}
""")

add_file('src/Core/Environment/Server/NginxAdapter.php', """<?php
namespace ApexSEO\\Core\\Environment\\Server;

class NginxAdapter extends GenericServerAdapter {
    public function getName(): string {
        return 'nginx';
    }

    public function isSupported(): bool {
        return isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false;
    }
}
""")

add_file('src/Core/Environment/Server/LiteSpeedAdapter.php', """<?php
namespace ApexSEO\\Core\\Environment\\Server;

class LiteSpeedAdapter extends GenericServerAdapter {
    public function getName(): string {
        return 'litespeed';
    }

    public function isSupported(): bool {
        return isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'litespeed') !== false;
    }
}
""")

add_file('src/Core/Environment/Server/OpenLiteSpeedAdapter.php', """<?php
namespace ApexSEO\\Core\\Environment\\Server;

class OpenLiteSpeedAdapter extends LiteSpeedAdapter {
    public function getName(): string {
        return 'openlitespeed';
    }
}
""")

add_file('src/Core/Environment/EnvironmentDetector.php', """<?php
namespace ApexSEO\\Core\\Environment;

use ApexSEO\\Core\\Environment\\Server\\ServerAdapterInterface;
use ApexSEO\\Core\\Environment\\Server\\ApacheAdapter;
use ApexSEO\\Core\\Environment\\Server\\NginxAdapter;
use ApexSEO\\Core\\Environment\\Server\\LiteSpeedAdapter;
use ApexSEO\\Core\\Environment\\Server\\GenericServerAdapter;

class EnvironmentDetector {
    public function isCli(): bool {
        return (php_sapi_name() === 'cli' || defined('WP_CLI'));
    }

    public function isMultisite(): bool {
        return is_multisite();
    }

    public function getPhpVersion(): string {
        return PHP_VERSION;
    }

    public function getWordPressVersion(): string {
        global $wp_version;
        return $wp_version ?: '6.4.0';
    }

    public function getServerSoftware(): string {
        return $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    }

    public function isSsl(): bool {
        return is_ssl();
    }

    public function getMemoryLimit(): string {
        return ini_get('memory_limit') ?: '256M';
    }

    public function detectServerAdapter(): ServerAdapterInterface {
        $apache = new ApacheAdapter();
        if ($apache->isSupported()) {
            return $apache;
        }

        $nginx = new NginxAdapter();
        if ($nginx->isSupported()) {
            return $nginx;
        }

        $litespeed = new LiteSpeedAdapter();
        if ($litespeed->isSupported()) {
            return $litespeed;
        }

        return new GenericServerAdapter();
    }
}
""")

# ============================================================================
# DATABASE & 8 LOCKED TABLES MIGRATION
# ============================================================================

add_file('src/Core/Database/DatabaseManager.php', """<?php
namespace ApexSEO\\Core\\Database;

class DatabaseManager {
    const TABLE_INDEXABLES = 'apex_indexables';
    const TABLE_SCHEMA = 'apex_schema';
    const TABLE_REDIRECTS = 'apex_redirects';
    const TABLE_404_LOGS = 'apex_404_logs';
    const TABLE_LINKS = 'apex_links';
    const TABLE_IMAGE_HISTORY = 'apex_image_history';
    const TABLE_ANALYTICS = 'apex_analytics';
    const TABLE_RANK_TRACKING = 'apex_rank_tracking';

    /**
     * @var \\wpdb
     */
    protected $wpdb;

    /**
     * @var string|null
     */
    protected $prefix = null;

    public function __construct($wpdb = null) {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function setPrefix(?string $prefix): void {
        $this->prefix = $prefix;
    }

    public function getPrefix(): string {
        if ($this->prefix !== null) {
            return $this->prefix;
        }
        return $this->wpdb->prefix ?? 'wp_';
    }

    public function getTableName(string $tableKey): string {
        $prefix = $this->getPrefix();
        if (strpos($tableKey, $prefix) === 0) {
            return $tableKey;
        }
        return $prefix . $tableKey;
    }

    public function hasTable(string $fullTableName): bool {
        if (!$this->wpdb) {
            return false;
        }
        if (isset($this->wpdb->mock_tables)) {
            return in_array($fullTableName, $this->wpdb->mock_tables, true);
        }
        $query = $this->wpdb->prepare("SHOW TABLES LIKE %s", $fullTableName);
        return $this->wpdb->get_var($query) === $fullTableName;
    }

    public function query(string $sql) {
        return $this->wpdb ? $this->wpdb->query($sql) : false;
    }

    public function prepare(string $sql, ...$args): string {
        return $this->wpdb ? $this->wpdb->prepare($sql, ...$args) : $sql;
    }

    public function insert(string $table, array $data, array $format = []) {
        return $this->wpdb ? $this->wpdb->insert($table, $data, $format) : false;
    }

    public function update(string $table, array $data, array $where, array $format = [], array $whereFormat = []) {
        return $this->wpdb ? $this->wpdb->update($table, $data, $where, $format, $whereFormat) : false;
    }

    public function delete(string $table, array $where, array $whereFormat = []) {
        return $this->wpdb ? $this->wpdb->delete($table, $where, $whereFormat) : false;
    }

    public function get_row(string $query, string $output = OBJECT) {
        return $this->wpdb ? $this->wpdb->get_row($query, $output) : null;
    }

    public function get_results(string $query, string $output = OBJECT) {
        return $this->wpdb ? $this->wpdb->get_results($query, $output) : [];
    }

    public function get_var(string $query) {
        return $this->wpdb ? $this->wpdb->get_var($query) : null;
    }

    public function getWpdb() {
        return $this->wpdb;
    }
}
""")

add_file('src/Core/Database/MigrationInterface.php', """<?php
namespace ApexSEO\\Core\\Database;

interface MigrationInterface {
    public function getVersion(): string;
    public function getDescription(): string;
    public function up(DatabaseManager $db): bool;
    public function down(DatabaseManager $db): bool;
}
""")

add_file('src/Core/Database/SchemaVersion.php', """<?php
namespace ApexSEO\\Core\\Database;

class SchemaVersion {
    const OPTION_NAME = 'apexseo_schema_version';

    public static function getInstalledVersion(): ?string {
        return get_option(self::OPTION_NAME, null);
    }

    public static function setInstalledVersion(string $version): void {
        update_option(self::OPTION_NAME, $version);
    }

    public static function removeInstalledVersion(): void {
        delete_option(self::OPTION_NAME);
    }
}
""")

add_file('src/Core/Database/Migrations/Migration_1_0_0_CreateLockedTables.php', """<?php
namespace ApexSEO\\Core\\Database\\Migrations;

use ApexSEO\\Core\\Database\\DatabaseManager;
use ApexSEO\\Core\\Database\\MigrationInterface;

class Migration_1_0_0_CreateLockedTables implements MigrationInterface {
    public function getVersion(): string {
        return '1.0.0';
    }

    public function getDescription(): string {
        return 'Create initial locked 8 production database tables for APEX SEO';
    }

    public function up(DatabaseManager $db): bool {
        $prefix = $db->getPrefix();
        $wpdb = $db->getWpdb();
        $charsetCollate = ($wpdb && method_exists($wpdb, 'get_charset_collate')) ? $wpdb->get_charset_collate() : 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

        $tables = [
            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_indexables` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `object_id` bigint(20) unsigned NOT NULL,
                `object_type` varchar(32) NOT NULL DEFAULT 'post',
                `object_sub_type` varchar(32) NOT NULL DEFAULT 'post',
                `permalink` text,
                `canonical_url` text,
                `title` text,
                `description` text,
                `robots_index` tinyint(1) NOT NULL DEFAULT 1,
                `robots_follow` tinyint(1) NOT NULL DEFAULT 1,
                `primary_focus_keyword` varchar(191) DEFAULT NULL,
                `keyword_density` decimal(5,2) DEFAULT NULL,
                `readability_score` int(11) DEFAULT NULL,
                `content_analysis` longtext DEFAULT NULL,
                `is_cornerstone` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_object` (`object_id`, `object_type`),
                KEY `idx_permalink_hash` (`object_type`, `object_sub_type`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_schema` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `object_id` bigint(20) unsigned NOT NULL,
                `object_type` varchar(32) NOT NULL DEFAULT 'post',
                `schema_type` varchar(64) NOT NULL,
                `schema_json` longtext NOT NULL,
                `is_custom` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_object` (`object_id`, `object_type`),
                KEY `idx_schema_type` (`schema_type`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_redirects` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `source_path` varchar(191) NOT NULL,
                `target_url` text NOT NULL,
                `status_code` smallint(5) unsigned NOT NULL DEFAULT 301,
                `match_type` varchar(16) NOT NULL DEFAULT 'exact',
                `hits` bigint(20) unsigned NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_source_path` (`source_path`),
                KEY `idx_active` (`is_active`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_404_logs` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `request_uri` varchar(191) NOT NULL,
                `referrer` text,
                `user_agent` text,
                `ip_address` varchar(45) DEFAULT NULL,
                `hits` bigint(20) unsigned NOT NULL DEFAULT 1,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_request_uri` (`request_uri`),
                KEY `idx_hits` (`hits`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_links` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `source_id` bigint(20) unsigned NOT NULL,
                `target_id` bigint(20) unsigned DEFAULT NULL,
                `target_url` text NOT NULL,
                `anchor_text` text,
                `link_type` varchar(16) NOT NULL DEFAULT 'internal',
                `is_nofollow` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_source` (`source_id`),
                KEY `idx_target` (`target_id`),
                KEY `idx_type` (`link_type`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_image_history` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `attachment_id` bigint(20) unsigned NOT NULL,
                `original_size` bigint(20) unsigned NOT NULL,
                `optimized_size` bigint(20) unsigned NOT NULL,
                `saved_bytes` bigint(20) unsigned NOT NULL,
                `mime_type` varchar(64) NOT NULL,
                `optimizer_used` varchar(64) NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_attachment` (`attachment_id`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_analytics` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `metric_type` varchar(64) NOT NULL,
                `metric_key` varchar(191) NOT NULL,
                `metric_value` decimal(12,4) NOT NULL,
                `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_metric_lookup` (`metric_type`, `metric_key`),
                KEY `idx_recorded` (`recorded_at`)
            ) {$charsetCollate};",

            "CREATE TABLE IF NOT EXISTS `{$prefix}apex_rank_tracking` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `keyword` varchar(191) NOT NULL,
                `url` text NOT NULL,
                `position` int(11) DEFAULT NULL,
                `previous_position` int(11) DEFAULT NULL,
                `device` varchar(16) NOT NULL DEFAULT 'desktop',
                `checked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_keyword` (`keyword`),
                KEY `idx_checked` (`checked_at`)
            ) {$charsetCollate};"
        ];

        foreach ($tables as $sql) {
            $db->query($sql);
        }

        return true;
    }

    public function down(DatabaseManager $db): bool {
        $prefix = $db->getPrefix();
        $tableNames = [
            "{$prefix}apex_indexables",
            "{$prefix}apex_schema",
            "{$prefix}apex_redirects",
            "{$prefix}apex_404_logs",
            "{$prefix}apex_links",
            "{$prefix}apex_image_history",
            "{$prefix}apex_analytics",
            "{$prefix}apex_rank_tracking",
        ];

        foreach ($tableNames as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`;");
        }

        return true;
    }
}
""")

add_file('src/Core/Database/MigrationRunner.php', """<?php
namespace ApexSEO\\Core\\Database;

use ApexSEO\\Core\\Database\\Migrations\\Migration_1_0_0_CreateLockedTables;

class MigrationRunner {
    private $db;
    private $migrations = [];

    public function __construct(DatabaseManager $db) {
        $this->db = $db;
        $this->registerMigration(new Migration_1_0_0_CreateLockedTables());
    }

    public function registerMigration(MigrationInterface $migration): void {
        $this->migrations[$migration->getVersion()] = $migration;
    }

    public function migrate(): array {
        $executed = [];
        $installed = SchemaVersion::getInstalledVersion();

        foreach ($this->migrations as $version => $migration) {
            if ($installed === null || version_compare($version, $installed, '>')) {
                $migration->up($this->db);
                SchemaVersion::setInstalledVersion($version);
                $executed[] = $version;
            }
        }

        return $executed;
    }

    public function rollback(string $targetVersion = '0.0.0'): array {
        $rolledBack = [];
        $installed = SchemaVersion::getInstalledVersion();

        $reversed = array_reverse($this->migrations, true);
        foreach ($reversed as $version => $migration) {
            if ($installed !== null && version_compare($version, $targetVersion, '>')) {
                $migration->down($this->db);
                $rolledBack[] = $version;
            }
        }

        if ($targetVersion === '0.0.0') {
            SchemaVersion::removeInstalledVersion();
        } else {
            SchemaVersion::setInstalledVersion($targetVersion);
        }

        return $rolledBack;
    }
}
""")

add_file('src/Core/Lifecycle/LifecycleManager.php', """<?php
namespace ApexSEO\\Core\\Lifecycle;

use ApexSEO\\Core\\Bootstrap\\Plugin;
use ApexSEO\\Core\\Database\\DatabaseManager;
use ApexSEO\\Core\\Database\\MigrationRunner;
use ApexSEO\\Core\\Configuration\\ConfigurationManager;

class LifecycleManager {
    public static function activate(bool $networkWide = false): void {
        $plugin = Plugin::getInstance();
        $db = $plugin->getContainer()->get(DatabaseManager::class);
        $runner = new MigrationRunner($db);
        $runner->migrate();

        update_option('apexseo_activated_at', time());

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
        }
    }

    public static function deactivate(bool $networkWide = false): void {
        wp_clear_scheduled_hook('apexseo_daily_cron');
        wp_clear_scheduled_hook('apexseo_hourly_cron');

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
        }
    }

    public static function uninstall(): void {
        $plugin = Plugin::getInstance();
        $config = $plugin->getContainer()->get(ConfigurationManager::class);
        $dropDb = $config->get('general.uninstall_drop_db', false);

        if ($dropDb) {
            $db = $plugin->getContainer()->get(DatabaseManager::class);
            $runner = new MigrationRunner($db);
            $runner->rollback('0.0.0');
        }

        delete_option('apexseo_settings');
        delete_option('apexseo_activated_at');
        delete_option('apexseo_schema_version');
    }
}
""")

add_file('src/Core/Multisite/MultisiteManager.php', """<?php
namespace ApexSEO\\Core\\Multisite;

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
""")

add_file('src/Core/Security/SecurityUtils.php', """<?php
namespace ApexSEO\\Core\\Security;

class SecurityUtils {
    public static function sanitizeInput(string $str): string {
        return sanitize_text_field($str);
    }

    public static function sanitizePath(string $path): string {
        $path = str_replace(['../', '..\\\\', chr(0)], '', $path);
        return $path;
    }

    public static function validateSafeUrl(string $url): bool {
        $parsed = wp_parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return false;
        }
        $host = strtolower($parsed['host']);
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || strpos($host, '169.254.') === 0) {
            return false;
        }
        return in_array($parsed['scheme'] ?? '', ['http', 'https'], true);
    }
}
""")

add_file('src/Core/Security/SecurityManager.php', """<?php
namespace ApexSEO\\Core\\Security;

class SecurityManager {
    public function verifyNonce(string $nonce, string $action): bool {
        return (bool) wp_verify_nonce($nonce, $action);
    }

    public function checkAdminPermission(): bool {
        return current_user_can('manage_options');
    }

    public function checkEditorPermission(): bool {
        return current_user_can('edit_posts');
    }

    public function checkUploadPermission(): bool {
        return current_user_can('upload_files');
    }

    public function sanitizeString(string $input): string {
        return sanitize_text_field($input);
    }

    public function sanitizeArray(array $input): array {
        $output = [];
        foreach ($input as $k => $v) {
            $cleanKey = sanitize_key($k);
            if (is_array($v)) {
                $output[$cleanKey] = $this->sanitizeArray($v);
            } elseif (is_string($v)) {
                $output[$cleanKey] = sanitize_text_field($v);
            } else {
                $output[$cleanKey] = $v;
            }
        }
        return $output;
    }

    public function validateRedirect(string $url): string {
        return wp_sanitize_redirect($url);
    }
}
""")

# ============================================================================
# MODULE REGISTRY, REST MANAGER, CLI MANAGER, BOOTSTRAP PLUGIN
# ============================================================================

add_file('src/Core/Modules/ModuleRegistry.php', """<?php
namespace ApexSEO\\Core\\Modules;

use ApexSEO\\Core\\Contracts\\ModuleInterface;

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
""")

add_file('src/Core/REST/RestManager.php', """<?php
namespace ApexSEO\\Core\\REST;

use ApexSEO\\API\\RestApiRouter;

class RestManager {
    private $router;

    public function __construct(RestApiRouter $router) {
        $this->router = $router;
    }

    public function registerRoutes(): void {
        $this->router->registerRoutes();
    }
}
""")

add_file('src/Core/CLI/CliManager.php', """<?php
namespace ApexSEO\\Core\\CLI;

use ApexSEO\\Core\\Container\\ContainerInterface;
use ApexSEO\\CLI\\IndexCommand;
use ApexSEO\\CLI\\CacheCommand;
use ApexSEO\\CLI\\MediaCommand;
use ApexSEO\\CLI\\RedirectCommand;
use ApexSEO\\CLI\\DatabaseCommand;
use ApexSEO\\CLI\\MigrateCommand;
use ApexSEO\\CLI\\SitemapCommand;
use ApexSEO\\CLI\\DoctorCommand;
use ApexSEO\\CLI\\SchemaCommand;

class CliManager {
    private $commands = [];

    public function __construct() {
        $this->registerDefaultCommands();
    }

    private function registerDefaultCommands(): void {
        $this->commands['index'] = IndexCommand::class;
        $this->commands['cache'] = CacheCommand::class;
        $this->commands['media'] = MediaCommand::class;
        $this->commands['redirect'] = RedirectCommand::class;
        $this->commands['db'] = DatabaseCommand::class;
        $this->commands['migrate'] = MigrateCommand::class;
        $this->commands['sitemap'] = SitemapCommand::class;
        $this->commands['doctor'] = DoctorCommand::class;
        $this->commands['report'] = DoctorCommand::class;
        $this->commands['schema'] = SchemaCommand::class;
    }

    public function getCommands(): array {
        return $this->commands;
    }

    public function registerWpCli(ContainerInterface $container): void {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }

        foreach ($this->commands as $name => $class) {
            $instance = $container->get($class);
            \\WP_CLI::add_command("apexseo {$name}", $instance);
        }
    }
}
""")

add_file('src/Core/Bootstrap/Plugin.php', """<?php
namespace ApexSEO\\Core\\Bootstrap;

use ApexSEO\\Core\\Container\\Container;
use ApexSEO\\Core\\Container\\ContainerInterface;
use ApexSEO\\Core\\Environment\\EnvironmentDetector;
use ApexSEO\\Core\\Environment\\CapabilityRegistry;
use ApexSEO\\Core\\Configuration\\ConfigurationManager;
use ApexSEO\\Core\\Database\\DatabaseManager;
use ApexSEO\\Core\\Security\\SecurityManager;
use ApexSEO\\Core\\Hooks\\HookManager;
use ApexSEO\\Core\\Logging\\Logger;
use ApexSEO\\Core\\Logging\\LoggerInterface;
use ApexSEO\\Core\\Modules\\ModuleRegistry;
use ApexSEO\\Core\\REST\\RestManager;
use ApexSEO\\Core\\CLI\\CliManager;
use ApexSEO\\API\\RestApiRouter;
use ApexSEO\\SEO\\SeoModule;
use ApexSEO\\Schema\\SchemaModule;
use ApexSEO\\Performance\\PerformanceModule;
use ApexSEO\\Media\\MediaModule;
use ApexSEO\\AI\\AiModule;
use ApexSEO\\Analytics\\AnalyticsModule;

class Plugin {
    /**
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var bool
     */
    private $booted = false;

    private function __construct() {
        $this->container = new Container();
        $this->registerCoreServices();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void {
        self::$instance = null;
    }

    public function getContainer(): ContainerInterface {
        return $this->container;
    }

    public function isBooted(): bool {
        return $this->booted;
    }

    public function boot(): void {
        if ($this->booted) {
            return;
        }

        $moduleRegistry = $this->container->get(ModuleRegistry::class);
        $moduleRegistry->bootAll();

        // REST API init hook
        add_action('rest_api_init', function() {
            $restRouter = $this->container->get(RestApiRouter::class);
            $restRouter->registerRoutes();
        });

        // WP-CLI registration
        if (defined('WP_CLI') && WP_CLI) {
            $cliManager = $this->container->get(CliManager::class);
            $cliManager->registerWpCli($this->container);
        }

        $this->booted = true;
    }

    private function registerCoreServices(): void {
        $c = $this->container;

        $c->singleton(ContainerInterface::class, $c);
        $c->singleton(EnvironmentDetector::class, EnvironmentDetector::class);
        $c->singleton(CapabilityRegistry::class, CapabilityRegistry::class);
        $c->singleton(ConfigurationManager::class, ConfigurationManager::class);
        $c->singleton(SecurityManager::class, SecurityManager::class);
        $c->singleton(HookManager::class, HookManager::class);
        $c->singleton(LoggerInterface::class, function() {
            return new Logger('apexseo');
        });
        $c->singleton(DatabaseManager::class, function() {
            global $wpdb;
            return new DatabaseManager($wpdb);
        });

        $c->singleton(ModuleRegistry::class, function($container) {
            $registry = new ModuleRegistry();
            $registry->register($container->get(SeoModule::class));
            $registry->register($container->get(SchemaModule::class));
            $registry->register($container->get(PerformanceModule::class));
            $registry->register($container->get(MediaModule::class));
            $registry->register($container->get(AiModule::class));
            $registry->register($container->get(AnalyticsModule::class));
            return $registry;
        });

        $c->singleton(RestApiRouter::class, RestApiRouter::class);
        $c->singleton(CliManager::class, CliManager::class);
    }
}
""")

# ============================================================================
# WRITE ALL GENERATED FILES
# ============================================================================

for path, content in files.items():
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"Successfully generated {len(files)} source files.")
