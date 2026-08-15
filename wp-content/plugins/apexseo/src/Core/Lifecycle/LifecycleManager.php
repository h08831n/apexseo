<?php
namespace ApexSEO\Core\Lifecycle;

use ApexSEO\Core\Bootstrap\Plugin;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Database\MigrationRunner;
use ApexSEO\Core\Database\SchemaVersion;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\Core\Multisite\MultisiteManager;

/**
 * Plugin Lifecycle Manager (Activation, Deactivation, Upgrade, Uninstallation).
 */
class LifecycleManager {
    /**
     * Plugin activation hook callback.
     *
     * @param bool $networkWide Whether the plugin is being activated network-wide in Multisite.
     * @return void
     */
    public static function activate($networkWide = false) {
        $plugin = Plugin::getInstance();
        $container = $plugin->getContainer();

        $db = $container->get(DatabaseManager::class);
        $multisite = $container->get(MultisiteManager::class);
        $migrationRunner = $container->get(MigrationRunner::class);

        if ($multisite->isMultisite() && $networkWide) {
            $siteIds = $multisite->getSiteIds();
            foreach ($siteIds as $siteId) {
                $multisite->runInBlogContext($siteId, function() use ($migrationRunner) {
                    $migrationRunner->migrate();
                });
            }
        } else {
            $migrationRunner->migrate();
        }

        // Set activation flag / timestamp
        if (function_exists('update_option')) {
            update_option('apexseo_activated_at', time(), false);
        }

        // Schedule rewrite rules flush
        if (function_exists('set_transient')) {
            set_transient('apexseo_flush_rewrite_rules', 1, 60);
        }
    }

    /**
     * Plugin deactivation hook callback.
     *
     * @param bool $networkWide
     * @return void
     */
    public static function deactivate($networkWide = false) {
        // Clear scheduled crons or rewrite transients
        if (function_exists('delete_transient')) {
            delete_transient('apexseo_flush_rewrite_rules');
        }

        if (function_exists('wp_clear_scheduled_hook')) {
            wp_clear_scheduled_hook('apexseo_daily_cron');
            wp_clear_scheduled_hook('apexseo_hourly_cron');
        }
    }

    /**
     * Plugin uninstall hook callback (invoked via uninstall.php).
     *
     * @return void
     */
    public static function uninstall() {
        $plugin = Plugin::getInstance();
        $container = $plugin->getContainer();

        $config = $container->get(ConfigurationManager::class);
        $db = $container->get(DatabaseManager::class);
        $multisite = $container->get(MultisiteManager::class);
        $migrationRunner = $container->get(MigrationRunner::class);

        $dropTables = (bool) $config->get('general.uninstall_drop_db', false);

        if ($dropTables) {
            if ($multisite->isMultisite()) {
                $siteIds = $multisite->getSiteIds();
                foreach ($siteIds as $siteId) {
                    $multisite->runInBlogContext($siteId, function() use ($migrationRunner) {
                        $migrationRunner->rollback('0.0.0');
                        self::deleteOptions();
                    });
                }
            } else {
                $migrationRunner->rollback('0.0.0');
                self::deleteOptions();
            }
        } else {
            // Still clean up options if configured
            self::deleteOptions();
        }
    }

    /**
     * Delete registered plugin options from wp_options table.
     *
     * @return void
     */
    protected static function deleteOptions() {
        if (!function_exists('delete_option')) {
            return;
        }

        $options = [
            ConfigurationManager::OPTION_GENERAL,
            ConfigurationManager::OPTION_SEO,
            ConfigurationManager::OPTION_SCHEMA,
            ConfigurationManager::OPTION_PERF,
            ConfigurationManager::OPTION_MODULES,
            ConfigurationManager::OPTION_VERSION,
            SchemaVersion::OPTION_NAME,
            'apexseo_activated_at',
        ];

        foreach ($options as $opt) {
            delete_option($opt);
        }
    }

    /**
     * Check and run automatic schema migration upgrades if required.
     *
     * @param MigrationRunner $migrationRunner
     * @return void
     */
    public static function checkUpgrade(MigrationRunner $migrationRunner) {
        if (SchemaVersion::isUpgradeRequired()) {
            $migrationRunner->migrate();
        }
    }
}
