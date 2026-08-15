<?php
namespace ApexSEO\Analytics;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Analytics\Monitor\FourOhFourMonitor;
use ApexSEO\Analytics\Tracker\RankTracker;

/**
 * Apex Analytics, 404 Monitoring & Rank Tracking Subsystem Module.
 */
class AnalyticsModule implements ModuleInterface {
    const ID = 'analytics';
    const VERSION = '1.0.0';

    /**
     * {@inheritdoc}
     */
    public function getId() {
        return self::ID;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'Apex Analytics & 404 Monitor Subsystem';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion() {
        return self::VERSION;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container) {
        $container->singleton(FourOhFourMonitor::class, function(ContainerInterface $c) {
            return new FourOhFourMonitor($c->get(DatabaseManager::class));
        });

        $container->singleton(RankTracker::class, function(ContainerInterface $c) {
            return new RankTracker($c->get(DatabaseManager::class));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container) {
        if (function_exists('add_action')) {
            add_action('template_redirect', function() use ($container) {
                if (function_exists('is_404') && is_404()) {
                    $monitor = $container->get(FourOhFourMonitor::class);
                    $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
                    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
                    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                    $monitor->record404($url, $ip, $ua);
                }
            });
        }
    }
}
