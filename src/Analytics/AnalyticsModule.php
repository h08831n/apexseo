<?php
namespace ApexSEO\Analytics;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Analytics\Monitor\FourOhFourMonitor;

class AnalyticsModule implements ModuleInterface {
    private $monitor;

    public function __construct(FourOhFourMonitor $monitor) {
        $this->monitor = $monitor;
    }

    public function getName(): string {
        return 'analytics';
    }

    public function boot(): void {}

    public function registerHooks(): void {
        add_action('template_redirect', [$this, 'catch404']);
    }

    public function catch404(): void {
        if (is_404()) {
            $this->monitor->log(
                $_SERVER['REQUEST_URI'] ?? '',
                $_SERVER['HTTP_REFERER'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['REMOTE_ADDR'] ?? ''
            );
        }
    }
}
