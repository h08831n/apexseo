<?php
namespace ApexSEO\SEO;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Core\Contracts\HookableInterface;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Configuration\ConfigurationManager;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Templates\TemplateManager;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Builder\IndexableBuilder;
use ApexSEO\SEO\Context\ContextDetector;
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
use ApexSEO\SEO\Integrations\WooCommerceIntegration;
use ApexSEO\SEO\Admin\MetaSaver;
use ApexSEO\SEO\Analysis\KeywordAnalyzer;
use ApexSEO\SEO\Analysis\ReadabilityScorer;
use ApexSEO\SEO\Analysis\HeadingAnalyzer;
use ApexSEO\SEO\Analysis\LinkGraphScanner;
use ApexSEO\SEO\Analysis\PassiveVoiceAnalyzer;
use ApexSEO\SEO\Analysis\TransitionWordAnalyzer;
use ApexSEO\SEO\Analysis\TextStructureAnalyzer;
use ApexSEO\SEO\Analysis\ContentAnalyzer;

/**
 * SEO Core Subsystem Module.
 */
class SeoModule implements ModuleInterface, HookableInterface {
    const ID = 'seo';
    const VERSION = '1.0.0';

    /**
     * Get module unique identifier.
     *
     * @return string
     */
    public function getId() {
        return self::ID;
    }

    /**
     * Get human-readable module name.
     *
     * @return string
     */
    public function getName() {
        return 'SEO Core Subsystem';
    }

    /**
     * Get module version.
     *
     * @return string
     */
    public function getVersion() {
        return self::VERSION;
    }

    /**
     * Determine if module is enabled.
     *
     * @return bool
     */
    public function isEnabled() {
        return true;
    }

    /**
     * Register domain services into DI Container.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function register(ContainerInterface $container) {
        $container->singleton(VariableEngine::class, function() {
            return new VariableEngine();
        });

        $container->singleton(TemplateManager::class, function(ContainerInterface $c) {
            $config = $c->has(ConfigurationManager::class) ? $c->get(ConfigurationManager::class) : null;
            return new TemplateManager($config);
        });

        $container->singleton(IndexableRepository::class, function(ContainerInterface $c) {
            return new IndexableRepository($c->get(DatabaseManager::class));
        });

        $container->singleton(IndexableBuilder::class, function(ContainerInterface $c) {
            return new IndexableBuilder(
                $c->get(VariableEngine::class),
                $c->get(TemplateManager::class)
            );
        });

        $container->singleton(ContextDetector::class, function(ContainerInterface $c) {
            return new ContextDetector($c->get(TemplateManager::class));
        });

        $container->singleton(TitlePresenter::class, function(ContainerInterface $c) {
            return new TitlePresenter(
                $c->get(VariableEngine::class),
                $c->get(TemplateManager::class)
            );
        });

        $container->singleton(DescriptionPresenter::class, function(ContainerInterface $c) {
            return new DescriptionPresenter(
                $c->get(VariableEngine::class),
                $c->get(TemplateManager::class)
            );
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

        $container->singleton(BreadcrumbGenerator::class, function() {
            return new BreadcrumbGenerator();
        });

        $container->singleton(SitemapGenerator::class, function() {
            return new SitemapGenerator();
        });

        $container->singleton(RedirectManager::class, function(ContainerInterface $c) {
            return new RedirectManager($c->get(DatabaseManager::class));
        });

        $container->singleton(WooCommerceIntegration::class, function() {
            return new WooCommerceIntegration();
        });

        $container->singleton(MetaTagManager::class, function(ContainerInterface $c) {
            return new MetaTagManager(
                $c->get(ContextDetector::class),
                $c->get(IndexableRepository::class),
                $c->get(TitlePresenter::class),
                $c->get(DescriptionPresenter::class),
                $c->get(CanonicalPresenter::class),
                $c->get(RobotsPresenter::class),
                $c->get(OpenGraphPresenter::class),
                $c->get(TwitterCardPresenter::class)
            );
        });

        $container->singleton(MetaSaver::class, function(ContainerInterface $c) {
            return new MetaSaver(
                $c->get(IndexableRepository::class),
                $c->get(IndexableBuilder::class)
            );
        });

        // Content Intelligence & On-Page Analysis Subsystem (APEX-048 to APEX-054)
        $container->singleton(KeywordAnalyzer::class, function() {
            return new KeywordAnalyzer();
        });

        $container->singleton(ReadabilityScorer::class, function() {
            return new ReadabilityScorer();
        });

        $container->singleton(HeadingAnalyzer::class, function() {
            return new HeadingAnalyzer();
        });

        $container->singleton(LinkGraphScanner::class, function(ContainerInterface $c) {
            $db = $c->has(DatabaseManager::class) ? $c->get(DatabaseManager::class) : null;
            return new LinkGraphScanner($db);
        });

        $container->singleton(PassiveVoiceAnalyzer::class, function(ContainerInterface $c) {
            return new PassiveVoiceAnalyzer($c->get(ReadabilityScorer::class));
        });

        $container->singleton(TransitionWordAnalyzer::class, function(ContainerInterface $c) {
            return new TransitionWordAnalyzer(
                $c->get(ReadabilityScorer::class),
                $c->get(KeywordAnalyzer::class)
            );
        });

        $container->singleton(TextStructureAnalyzer::class, function(ContainerInterface $c) {
            return new TextStructureAnalyzer($c->get(ReadabilityScorer::class));
        });

        $container->singleton(ContentAnalyzer::class, function(ContainerInterface $c) {
            return new ContentAnalyzer(
                $c->get(KeywordAnalyzer::class),
                $c->get(ReadabilityScorer::class),
                $c->get(HeadingAnalyzer::class),
                $c->get(LinkGraphScanner::class),
                $c->get(PassiveVoiceAnalyzer::class),
                $c->get(TransitionWordAnalyzer::class),
                $c->get(TextStructureAnalyzer::class),
                $c->has(IndexableRepository::class) ? $c->get(IndexableRepository::class) : null
            );
        });
    }

    /**
     * Boot the SEO module.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function boot(ContainerInterface $container) {
        $this->registerModuleHooks($container);
    }

    /**
     * Register WordPress runtime actions and filters.
     *
     * @return void
     */
    public function registerHooks() {
        // Required by HookableInterface if instantiated without container
    }

    /**
     * Register module hooks with container context.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function registerModuleHooks(ContainerInterface $container) {
        if (!function_exists('add_action') || !function_exists('add_filter')) {
            return;
        }

        // Frontend Head Output
        add_action('wp_head', function() use ($container) {
            if ($container->has(MetaTagManager::class)) {
                $container->get(MetaTagManager::class)->outputHead();
            }
        }, 1);

        // Document Title Filter (WP 4.4+)
        add_filter('pre_get_document_title', function($title) use ($container) {
            if ($container->has(TitlePresenter::class) && $container->has(ContextDetector::class)) {
                $detector = $container->get(ContextDetector::class);
                $presenter = $container->get(TitlePresenter::class);
                return $presenter->render($detector->detectContext());
            }
            return $title;
        }, 15);

        // Admin Metadata Persistence
        add_action('save_post', function($postId, $post = null) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->savePostMeta($postId, $post);
            }
        }, 10, 2);

        add_action('created_term', function($termId, $ttId, $taxonomy) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->saveTermMeta($termId, $ttId, $taxonomy);
            }
        }, 10, 3);

        add_action('edited_term', function($termId, $ttId, $taxonomy) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->saveTermMeta($termId, $ttId, $taxonomy);
            }
        }, 10, 3);

        add_action('delete_post', function($postId) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->deletePostIndexable($postId);
            }
        }, 10, 1);

        add_action('delete_term', function($termId) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->deleteTermIndexable($termId);
            }
        }, 10, 1);

        // Fast Redirection Interceptor
        add_action('template_redirect', function() use ($container) {
            if ($container->has(RedirectManager::class)) {
                $container->get(RedirectManager::class)->interceptAndRedirect();
            }
        }, 1);
    }
}
