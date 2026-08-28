<?php
/**
 * Plugin Name: Apex SEO Platform
 * Plugin URI: https://apexseo.io
 * Description: Production-grade unified WordPress SEO, Schema, AI, Performance, Cache & Analytics Platform.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Apex SEO Team
 * Author URI: https://apexseo.io
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: apexseo
 * Domain Path: /languages
 */

// Strict ABSPATH execution protection.
defined('ABSPATH') || exit;

// Authoritative Plugin Constants.
if (!defined('APEXSEO_VERSION')) {
    define('APEXSEO_VERSION', '1.0.0');
}
if (!defined('APEXSEO_DB_VERSION')) {
    define('APEXSEO_DB_VERSION', '1.0.0');
}
if (!defined('APEXSEO_MIN_PHP')) {
    define('APEXSEO_MIN_PHP', '7.4.0');
}
if (!defined('APEXSEO_MIN_WP')) {
    define('APEXSEO_MIN_WP', '6.2.0');
}
if (!defined('APEXSEO_FILE')) {
    define('APEXSEO_FILE', __FILE__);
}
if (!defined('APEXSEO_PATH')) {
    define('APEXSEO_PATH', plugin_dir_path(__FILE__));
}
if (!defined('APEXSEO_URL')) {
    define('APEXSEO_URL', plugin_dir_url(__FILE__));
}
if (!defined('APEXSEO_BASENAME')) {
    define('APEXSEO_BASENAME', plugin_basename(__FILE__));
}

/**
 * Runtime Environment and Requirement Verification.
 *
 * @return bool True if environment satisfies all minimum requirements, false otherwise.
 */
function apexseo_verify_runtime_requirements() {
    global $wp_version;

    // Check PHP version.
    if (version_compare(PHP_VERSION, APEXSEO_MIN_PHP, '<')) {
        add_action('admin_notices', function() {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    /* translators: 1: Required PHP version, 2: Current PHP version */
                    esc_html__('Apex SEO requires PHP version %1$s or higher. Your server is running PHP %2$s. Please upgrade your PHP runtime.', 'apexseo'),
                    esc_html(APEXSEO_MIN_PHP),
                    esc_html(PHP_VERSION)
                )
            );
        });
        return false;
    }

    // Check WordPress version.
    $current_wp = isset($wp_version) ? $wp_version : '0.0.0';
    if (version_compare($current_wp, APEXSEO_MIN_WP, '<')) {
        add_action('admin_notices', function() use ($current_wp) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    /* translators: 1: Required WordPress version, 2: Current WordPress version */
                    esc_html__('Apex SEO requires WordPress version %1$s or higher. Your site is running WordPress %2$s. Please upgrade WordPress.', 'apexseo'),
                    esc_html(APEXSEO_MIN_WP),
                    esc_html($current_wp)
                )
            );
        });
        return false;
    }

    return true;
}

// Ensure deterministic PSR-4 Autoloading.
if (!class_exists('ApexSEO\\Core\\Bootstrap\\Plugin')) {
    $composer_autoloader = APEXSEO_PATH . 'vendor/autoload.php';
    if (file_exists($composer_autoloader)) {
        require_once $composer_autoloader;
    } else {
        require_once APEXSEO_PATH . 'src/Autoloader.php';
        \ApexSEO\Autoloader::register();
    }
}

// Lifecycle Hooks Registration.
register_activation_hook(APEXSEO_FILE, ['ApexSEO\\Core\\Lifecycle\\LifecycleManager', 'activate']);
register_deactivation_hook(APEXSEO_FILE, ['ApexSEO\\Core\\Lifecycle\\LifecycleManager', 'deactivate']);

/**
 * Main Plugin Initialization Hook.
 */
function apexseo_init() {
    if (!apexseo_verify_runtime_requirements()) {
        return;
    }

    // Initialize and boot Core Plugin Container and Subsystems.
    \ApexSEO\Core\Bootstrap\Plugin::getInstance()->boot();
}
add_action('plugins_loaded', 'apexseo_init', 0);
