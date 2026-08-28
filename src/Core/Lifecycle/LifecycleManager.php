<?php
namespace ApexSEO\Core\Lifecycle;

use ApexSEO\Core\Bootstrap\Plugin;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Database\MigrationRunner;
use ApexSEO\Core\Configuration\ConfigurationManager;

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
