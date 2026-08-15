<?php
/**
 * Test Environment Bootstrap & WordPress Core Mock Stubs.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

if (!defined('APEXSEO_FILE')) {
    define('APEXSEO_FILE', dirname(__DIR__) . '/apexseo.php');
}
if (!defined('APEXSEO_PATH')) {
    define('APEXSEO_PATH', dirname(__DIR__) . '/');
}
if (!defined('APEXSEO_VERSION')) {
    define('APEXSEO_VERSION', '1.0.0');
}
if (!defined('APEXSEO_DB_VERSION')) {
    define('APEXSEO_DB_VERSION', '1.0.0');
}

// Global Options Mock Store
global $mock_wp_options, $mock_wp_hooks, $mock_wp_transients, $wp_version;
$mock_wp_options = [];
$mock_wp_hooks = [];
$mock_wp_transients = [];
$wp_version = '6.7.0';

// Global wpdb Mock Class
if (!class_exists('wpdb')) {
    class wpdb {
        public $prefix = 'wp_';
        public $last_error = '';
        public $queries = [];
        public $tables = [];

        public function get_charset_collate() {
            return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
        }

        public function prepare($query, ...$args) {
            if (empty($args)) {
                return $query;
            }
            if (is_array($args[0]) && count($args) === 1) {
                $args = $args[0];
            }
            return vsprintf(str_replace(['%s', '%d', '%f'], ["'%s'", "%d", "%f"], $query), $args);
        }

        public function query($sql) {
            $this->queries[] = $sql;
            if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
                $this->tables[$m[1]] = true;
            }
            if (preg_match('/DROP TABLE (?:IF EXISTS )?`?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
                unset($this->tables[$m[1]]);
            }
            return 1;
        }

        public function get_var($query) {
            if (preg_match("/SHOW TABLES LIKE '([^']+)'/i", $query, $m)) {
                $table = $m[1];
                return isset($this->tables[$table]) ? $table : null;
            }
            return null;
        }

        public function get_row($query, $output = 'OBJECT') {
            return null;
        }

        public function get_results($query, $output = 'OBJECT') {
            return [];
        }
    }
}

global $wpdb;
$wpdb = new wpdb();

// WordPress Core Function Stubs
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $mock_wp_options;
        return array_key_exists($option, $mock_wp_options) ? $mock_wp_options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        global $mock_wp_options;
        $mock_wp_options[$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        global $mock_wp_options;
        unset($mock_wp_options[$option]);
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        global $mock_wp_hooks;
        $mock_wp_hooks['action'][$tag][] = ['callback' => $callback, 'priority' => $priority, 'args' => $accepted_args];
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
        global $mock_wp_hooks;
        $mock_wp_hooks['filter'][$tag][] = ['callback' => $callback, 'priority' => $priority, 'args' => $accepted_args];
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($tag, ...$args) {
        global $mock_wp_hooks;
        if (isset($mock_wp_hooks['action'][$tag])) {
            foreach ($mock_wp_hooks['action'][$tag] as $hook) {
                call_user_func_array($hook['callback'], $args);
            }
        }
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        global $mock_wp_hooks;
        if (isset($mock_wp_hooks['filter'][$tag])) {
            foreach ($mock_wp_hooks['filter'][$tag] as $hook) {
                $value = call_user_func_array($hook['callback'], array_merge([$value], $args));
            }
        }
        return $value;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('is_multisite')) {
    function is_multisite() {
        return false;
    }
}

if (!function_exists('wp_normalize_path')) {
    function wp_normalize_path($path) {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|(?<=.)/+|', '/', $path);
        if (':' === substr($path, 1, 1)) {
            $path = ucfirst($path);
        }
        return $path;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_\-]/i', '', strtolower($key));
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return strip_tags(trim((string) $str));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return strip_tags((string) $str);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('wp_validate_redirect')) {
    function wp_validate_redirect($location, $fallback = false) {
        if (empty($location)) {
            return $fallback;
        }
        if (strpos($location, 'javascript:') === 0 || strpos($location, 'data:') === 0) {
            return $fallback;
        }
        return $location;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return substr(md5($action . 'test_secret'), 0, 10);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return $nonce === substr(md5($action . 'test_secret'), 0, 10);
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args) {
        return true;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {}
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {}
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return trailingslashit(dirname($file));
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'https://example.com/wp-content/plugins/apexseo/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return 'apexseo/apexseo.php';
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($string) {
        return rtrim($string, '/\\') . '/';
    }
}

// Load plugin autoloader
require_once dirname(__DIR__) . '/src/Autoloader.php';
\ApexSEO\Autoloader::register();
