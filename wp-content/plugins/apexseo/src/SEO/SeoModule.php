<?php
namespace ApexSEO\SEO;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Meta\TitlePresenter;
use ApexSEO\SEO\Meta\DescriptionPresenter;
use ApexSEO\SEO\Meta\CanonicalPresenter;
use ApexSEO\SEO\Meta\RobotsPresenter;
use ApexSEO\SEO\Social\OpenGraphPresenter;
use ApexSEO\SEO\Social\TwitterCardPresenter;
use ApexSEO\SEO\Meta\MetaTagManager;
use ApexSEO\SEO\Breadcrumbs\BreadcrumbGenerator;
use ApexSEO\SEO\Sitemap\SitemapGenerator;
use ApexSEO\SEO\Redirects\RedirectManager;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * Apex SEO Subsystem Module.
 */
class SeoModule implements ModuleInterface {
    const ID = 'seo';
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
        return 'Apex SEO Subsystem';
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
        // Variable Engine
        $container->singleton(VariableEngine::class, function() {
            return new VariableEngine();
        });

        // Presenters
        $container->singleton(TitlePresenter::class, function(ContainerInterface $c) {
            return new TitlePresenter($c->get(VariableEngine::class));
        });

        $container->singleton(DescriptionPresenter::class, function(ContainerInterface $c) {
            return new DescriptionPresenter($c->get(VariableEngine::class));
        });

        $container->singleton(CanonicalPresenter::class, function() {
            return new CanonicalPresenter();
        });

        $container->singleton(RobotsPresenter::class, function() {
            return new RobotsPresenter();
        });

        $container->singleton(OpenGraphPresenter::class, function(ContainerInterface $c) {
            return new OpenGraphPresenter($c->get(VariableEngine::class));
        });

        $container->singleton(TwitterCardPresenter::class, function(ContainerInterface $c) {
            return new TwitterCardPresenter($c->get(VariableEngine::class));
        });

        // Main Meta Coordinator
        $container->singleton(MetaTagManager::class, function(ContainerInterface $c) {
            return new MetaTagManager(
                $c->get(TitlePresenter::class),
                $c->get(DescriptionPresenter::class),
                $c->get(CanonicalPresenter::class),
                $c->get(RobotsPresenter::class),
                $c->get(OpenGraphPresenter::class),
                $c->get(TwitterCardPresenter::class)
            );
        });

        // Breadcrumbs & Sitemap
        $container->singleton(BreadcrumbGenerator::class, function() {
            return new BreadcrumbGenerator();
        });

        $container->singleton(SitemapGenerator::class, function() {
            return new SitemapGenerator();
        });

        // Redirects
        $container->singleton(RedirectManager::class, function(ContainerInterface $c) {
            return new RedirectManager($c->get(DatabaseManager::class));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container) {
        // Hook into wp_head when running inside WordPress
        if (function_exists('add_action')) {
            add_action('wp_head', function() use ($container) {
                $metaManager = $container->get(MetaTagManager::class);
                echo $metaManager->renderHeadHtml();
            }, 1);
        }
    }
}
