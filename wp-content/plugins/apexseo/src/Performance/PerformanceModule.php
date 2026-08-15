<?php
namespace ApexSEO\Performance;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\Core\Environment\Server\ServerAdapterInterface;
use ApexSEO\Core\Logging\LoggerInterface;
use ApexSEO\Performance\Assets\CssMinifier;
use ApexSEO\Performance\Assets\JsMinifier;
use ApexSEO\Performance\Assets\HtmlMinifier;
use ApexSEO\Performance\Assets\DelayJsEngine;
use ApexSEO\Performance\Tweaks\ResourceHints;
use ApexSEO\Performance\Cache\StaticFileWriter;
use ApexSEO\Performance\Cache\SmartPurge;

/**
 * Apex Performance, Cache & Asset Optimization Subsystem Module.
 */
class PerformanceModule implements ModuleInterface {
    const ID = 'performance';
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
        return 'Apex Performance & Cache Subsystem';
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
        // Minifiers
        $container->singleton(CssMinifier::class, function() {
            return new CssMinifier();
        });

        $container->singleton(JsMinifier::class, function() {
            return new JsMinifier();
        });

        $container->singleton(HtmlMinifier::class, function() {
            return new HtmlMinifier();
        });

        $container->singleton(DelayJsEngine::class, function() {
            return new DelayJsEngine();
        });

        // Resource hints
        $container->singleton(ResourceHints::class, function() {
            return new ResourceHints();
        });

        // Cache file writer & purger
        $container->singleton(StaticFileWriter::class, function() {
            return new StaticFileWriter();
        });

        $container->singleton(SmartPurge::class, function(ContainerInterface $c) {
            return new SmartPurge(
                $c->get(StaticFileWriter::class),
                $c->get(ServerAdapterInterface::class),
                $c->has(LoggerInterface::class) ? $c->get(LoggerInterface::class) : null
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container) {
        if (function_exists('add_action')) {
            // Resource hints in wp_head
            add_action('wp_head', function() use ($container) {
                $hints = $container->get(ResourceHints::class);
                echo $hints->renderHtml();
            }, 0);
        }
    }
}
