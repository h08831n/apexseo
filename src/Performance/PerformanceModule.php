<?php
namespace ApexSEO\Performance;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Performance\Assets\HtmlMinifier;

class PerformanceModule implements ModuleInterface {
    private $htmlMinifier;

    public function __construct(HtmlMinifier $htmlMinifier) {
        $this->htmlMinifier = $htmlMinifier;
    }

    public function getName(): string {
        return 'performance';
    }

    public function boot(): void {}

    public function registerHooks(): void {
        add_action('template_redirect', [$this, 'startBuffer'], 1);
    }

    public function startBuffer(): void {
        if (!is_admin()) {
            ob_start([$this->htmlMinifier, 'minify']);
        }
    }
}
