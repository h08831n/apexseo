<?php
namespace ApexSEO\Media;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\Core\Environment\EnvironmentDetector;
use ApexSEO\Media\LazyLoad\PlaceholderGenerator;
use ApexSEO\Media\LazyLoad\ImageLazyLoader;
use ApexSEO\Media\Optimizer\ImageOptimizer;
use ApexSEO\Media\Optimizer\LcpOptimizer;

/**
 * Apex Media Library & Image Optimization Subsystem Module.
 */
class MediaModule implements ModuleInterface {
    const ID = 'media';
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
        return 'Apex Media & Image Optimization Subsystem';
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
        $container->singleton(PlaceholderGenerator::class, function() {
            return new PlaceholderGenerator();
        });

        $container->singleton(ImageLazyLoader::class, function(ContainerInterface $c) {
            return new ImageLazyLoader($c->get(PlaceholderGenerator::class));
        });

        $container->singleton(ImageOptimizer::class, function(ContainerInterface $c) {
            return new ImageOptimizer($c->get(EnvironmentDetector::class));
        });

        $container->singleton(LcpOptimizer::class, function() {
            return new LcpOptimizer();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container) {
        if (function_exists('add_filter')) {
            // Apply lazyload to the_content
            add_filter('the_content', function($content) use ($container) {
                $lazyLoader = $container->get(ImageLazyLoader::class);
                return $lazyLoader->processHtml($content, 1);
            }, 99);
        }
    }
}
