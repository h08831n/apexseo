<?php
namespace ApexSEO\Media;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Media\LazyLoad\ImageLazyLoader;
use ApexSEO\Media\Optimizer\LcpOptimizer;

class MediaModule implements ModuleInterface {
    private $lazyLoader;
    private $lcpOptimizer;

    public function __construct(ImageLazyLoader $lazyLoader, LcpOptimizer $lcpOptimizer) {
        $this->lazyLoader = $lazyLoader;
        $this->lcpOptimizer = $lcpOptimizer;
    }

    public function getName(): string {
        return 'media';
    }

    public function boot(): void {}

    public function registerHooks(): void {
        add_filter('the_content', [$this, 'filterContentMedia'], 99);
    }

    public function filterContentMedia(string $content): string {
        $content = $this->lcpOptimizer->optimizeLcpImages($content);
        return $this->lazyLoader->processHtml($content, 1);
    }
}
