<?php
/**
 * Apex SEO Platform - Safe Uninstallation Handler
 *
 * Triggered only when the user explicitly clicks "Delete" in the WordPress Plugins administration screen.
 * Respects configuration settings for preserving or purging database tables.
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if autoloader is available.
$plugin_path = plugin_dir_path(__FILE__);
$autoloader = $plugin_path . 'src/Autoloader.php';

if (file_exists($autoloader)) {
    require_once $autoloader;
    \ApexSEO\Autoloader::register();

    if (class_exists('ApexSEO\\Core\\Lifecycle\\LifecycleManager')) {
        \ApexSEO\Core\Lifecycle\LifecycleManager::uninstall();
    }
}
