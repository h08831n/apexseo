<?php
namespace ApexSEO\SEO;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\SEO\Meta\MetaTagManager;

class SeoModule implements ModuleInterface {
    private $metaTagManager;

    public function __construct(MetaTagManager $metaTagManager) {
        $this->metaTagManager = $metaTagManager;
    }

    public function getName(): string {
        return 'seo';
    }

    public function boot(): void {}

    public function registerHooks(): void {
        add_action('wp_head', [$this, 'renderHeadTags'], 1);
    }

    public function renderHeadTags(): void {
        echo $this->metaTagManager->renderHead();
    }
}
