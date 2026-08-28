<?php
/**
 * Real WordPress Runtime PHPUnit Integration Bootstrap.
 *
 * This file boots the genuine WordPress core from /var/www/html/wp-load.php.
 */

$wp_load = getenv('WP_LOAD_PATH') ?: '/var/www/html/wp-load.php';

if (!file_exists($wp_load)) {
    // Attempt local fallback paths
    $possible_paths = [
        dirname(__DIR__, 2) . '/wp-load.php',
        '/var/www/html/wp-load.php',
    ];
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $wp_load = $path;
            break;
        }
    }
}

if (file_exists($wp_load)) {
    define('WP_USE_THEMES', false);
    require_once $wp_load;
} else {
    fwrite(STDERR, "NOTICE: Real WordPress wp-load.php not found at $wp_load. Running in static syntax check mode.\n");
}

// Autoload plugin classes
$autoloader_paths = [
    '/var/www/html/wp-content/plugins/apexseo/src/Autoloader.php',
    dirname(__DIR__, 2) . '/src/Autoloader.php',
    dirname(__DIR__, 2) . '/wp-content/plugins/apexseo/src/Autoloader.php',
];

foreach ($autoloader_paths as $autoloader) {
    if (file_exists($autoloader)) {
        require_once $autoloader;
        if (class_exists('\\ApexSEO\\Autoloader')) {
            \ApexSEO\Autoloader::register();
        }
        break;
    }
}
