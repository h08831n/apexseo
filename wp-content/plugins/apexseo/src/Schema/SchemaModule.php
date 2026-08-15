<?php
namespace ApexSEO\Schema;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Core\Container\ContainerInterface;

/**
 * Apex Schema Subsystem Module.
 */
class SchemaModule implements ModuleInterface {
    const ID = 'schema';
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
        return 'Apex Structured Data & Schema Subsystem';
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
        $container->singleton(SchemaRegistry::class, function() {
            return new SchemaRegistry();
        });

        $container->singleton(SchemaGraphBuilder::class, function(ContainerInterface $c) {
            return new SchemaGraphBuilder($c->get(SchemaRegistry::class));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container) {
        if (function_exists('add_action')) {
            add_action('wp_head', function() use ($container) {
                $builder = $container->get(SchemaGraphBuilder::class);
                echo $builder->renderScript();
            }, 2);
        }
    }
}
