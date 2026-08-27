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
use ApexSEO\SEO\Meta\MetaKeywordsPresenter;
use ApexSEO\SEO\Social\OpenGraphPresenter;
use ApexSEO\SEO\Social\TwitterCardPresenter;
use ApexSEO\SEO\Social\SocialPreviewService;
use ApexSEO\SEO\Meta\MetaTagManager;
use ApexSEO\SEO\Breadcrumbs\BreadcrumbGenerator;
use ApexSEO\SEO\Sitemap\SitemapGenerator;
use ApexSEO\SEO\Redirects\RedirectManager;
use ApexSEO\SEO\Integrations\WooCommerceIntegration;
use ApexSEO\SEO\Admin\MetaSaver;
use ApexSEO\SEO\Permalinks\CategoryBaseStripper;
use ApexSEO\SEO\Robots\RobotsTxtManager;
use ApexSEO\SEO\Robots\RobotsHeaderManager;
use ApexSEO\SEO\Feed\RssFeedManager;
use ApexSEO\SEO\Analysis\KeywordAnalyzer;
use ApexSEO\SEO\Analysis\ReadabilityScorer;
use ApexSEO\SEO\Analysis\HeadingAnalyzer;
use ApexSEO\SEO\Analysis\LinkGraphScanner;
use ApexSEO\SEO\Analysis\PassiveVoiceAnalyzer;
use ApexSEO\SEO\Analysis\TransitionWordAnalyzer;
use ApexSEO\SEO\Analysis\TextStructureAnalyzer;
use ApexSEO\SEO\Analysis\ContentAnalyzer;
use ApexSEO\SEO\Analysis\ContentAnalysisService;

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

        $container->singleton(MetaKeywordsPresenter::class, function(ContainerInterface $c) {
            $config = $c->has(ConfigurationManager::class) ? $c->get(ConfigurationManager::class) : null;
            return new MetaKeywordsPresenter($config);
        });

        $container->singleton(OpenGraphPresenter::class, function(ContainerInterface $c) {
            $config = $c->has(ConfigurationManager::class) ? $c->get(ConfigurationManager::class) : null;
            return new OpenGraphPresenter($c->get(VariableEngine::class), $config);
        });

        $container->singleton(TwitterCardPresenter::class, function(ContainerInterface $c) {
            $config = $c->has(ConfigurationManager::class) ? $c->get(ConfigurationManager::class) : null;
            return new TwitterCardPresenter($c->get(VariableEngine::class), $config);
        });

        $container->singleton(SocialPreviewService::class, function(ContainerInterface $c) {
            return new SocialPreviewService(
                $c->get(VariableEngine::class),
                $c->get(OpenGraphPresenter::class),
                $c->get(TwitterCardPresenter::class)
            );
        });

        $container->singleton(CategoryBaseStripper::class, function(ContainerInterface $c) {
            $config = $c->has(ConfigurationManager::class) ? $c->get(ConfigurationManager::class) : null;
            return new CategoryBaseStripper($config);
        });

        $container->singleton(RobotsTxtManager::class, function(ContainerInterface $c) {
            $config = $c->has(ConfigurationManager::class) ? $c->get(ConfigurationManager::class) : null;
            return new RobotsTxtManager($config);
        });

        $container->singleton(RobotsHeaderManager::class, function(ContainerInterface $c) {
            $config = $c->has(ConfigurationManager::class) ? $c->get(ConfigurationManager::class) : null;
            return new RobotsHeaderManager(
                $c->get(ContextDetector::class),
                $c->get(RobotsPresenter::class),
                $config
            );
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
                $c->get(TwitterCardPresenter::class),
                $c->get(MetaKeywordsPresenter::class)
            );
        });

        $container->singleton(MetaSaver::class, function(ContainerInterface $c) {
            return new MetaSaver(
                $c->get(IndexableRepository::class),
                $c->get(IndexableBuilder::class)
            );
        });

        $container->singleton(RssFeedManager::class, function(ContainerInterface $c) {
            return new RssFeedManager(
                $c->get(VariableEngine::class),
                $c->get(TemplateManager::class),
                $c->has(ConfigurationManager::class) ? $c->get(ConfigurationManager::class) : null
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

        $container->singleton(ContentAnalysisService::class, function(ContainerInterface $c) {
            return new ContentAnalysisService(
                $c->get(ContentAnalyzer::class),
                $c->has(DatabaseManager::class) ? $c->get(DatabaseManager::class) : null,
                $c->has(IndexableRepository::class) ? $c->get(IndexableRepository::class) : null,
                $c->has(ConfigurationManager::class) ? $c->get(ConfigurationManager::class) : null
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

        // Category Base Stripping (APEX-011)
        add_filter('category_link', function($link, $term = null) use ($container) {
            if ($container->has(CategoryBaseStripper::class)) {
                return $container->get(CategoryBaseStripper::class)->filterCategoryLink($link, $term);
            }
            return $link;
        }, 10, 2);

        add_filter('category_rewrite_rules', function($rules) use ($container) {
            if ($container->has(CategoryBaseStripper::class)) {
                return $container->get(CategoryBaseStripper::class)->modifyCategoryRewriteRules($rules);
            }
            return $rules;
        }, 10, 1);

        // Robots.txt Generator (APEX-025, APEX-026)
        add_filter('robots_txt', function($output, $public) use ($container) {
            if ($container->has(RobotsTxtManager::class)) {
                return $container->get(RobotsTxtManager::class)->filterRobotsTxt($output, $public);
            }
            return $output;
        }, 10, 2);

        // X-Robots-Tag HTTP Headers (APEX-027, APEX-028, APEX-029, APEX-030)
        add_filter('wp_headers', function(array $headers) use ($container) {
            if ($container->has(RobotsHeaderManager::class)) {
                return $container->get(RobotsHeaderManager::class)->filterHttpHeaders($headers);
            }
            return $headers;
        }, 10, 1);

        add_action('send_headers', function() use ($container) {
            if ($container->has(RobotsHeaderManager::class)) {
                $container->get(RobotsHeaderManager::class)->sendHttpHeader();
            }
        }, 10);

        // Admin Metadata Persistence
        add_action('save_post', function($postId, $post = null) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->savePostMeta($postId, $post);
            }
            if ($container->has(ContentAnalysisService::class)) {
                $container->get(ContentAnalysisService::class)->handleSavePost($postId, $post);
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
            if ($container->has(ContentAnalysisService::class)) {
                $container->get(ContentAnalysisService::class)->handleDeletePost($postId);
            }
        }, 10, 1);

        add_action('delete_term', function($termId) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->deleteTermIndexable($termId);
            }
        }, 10, 1);

        // Author Profile Metadata Persistence (APEX-005)
        add_action('personal_options_update', function($userId) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->saveAuthorMeta($userId);
            }
        }, 10, 1);

        add_action('edit_user_profile_update', function($userId) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->saveAuthorMeta($userId);
            }
        }, 10, 1);

        add_action('delete_user', function($userId) use ($container) {
            if ($container->has(MetaSaver::class)) {
                $container->get(MetaSaver::class)->deleteAuthorIndexable($userId);
            }
        }, 10, 1);

        // RSS Feed Content Filtering (APEX-015)
        add_filter('the_content_feed', function($content) use ($container) {
            if ($container->has(RssFeedManager::class)) {
                return $container->get(RssFeedManager::class)->injectFeedContent($content);
            }
            return $content;
        }, 10, 1);

        add_filter('the_excerpt_rss', function($content) use ($container) {
            if ($container->has(RssFeedManager::class)) {
                return $container->get(RssFeedManager::class)->injectFeedContent($content);
            }
            return $content;
        }, 10, 1);

        // Fast Redirection & Category Base Interceptor
        add_action('template_redirect', function() use ($container) {
            if ($container->has(CategoryBaseStripper::class)) {
                $container->get(CategoryBaseStripper::class)->handleOldCategoryRedirect();
            }
            if ($container->has(RedirectManager::class)) {
                $container->get(RedirectManager::class)->interceptAndRedirect();
            }
        }, 1);

        // Dynamic XML Sitemap Routes (APEX-022, APEX-023, APEX-024)
        add_action('init', function() use ($container) {
            if (isset($_SERVER['REQUEST_URI']) && $container->has(SitemapGenerator::class)) {
                $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $home = function_exists('home_url') ? home_url() : 'http://127.0.0.1:8080';
                $sitemapGen = $container->get(SitemapGenerator::class);

                if (in_array($path, ['/sitemap_index.xml', '/sitemap.xml'], true)) {
                    header('Content-Type: text/xml; charset=UTF-8');
                    $index = [
                        ['loc' => $home . '/post-sitemap.xml', 'lastmod' => date('c')],
                        ['loc' => $home . '/page-sitemap.xml', 'lastmod' => date('c')],
                        ['loc' => $home . '/category-sitemap.xml', 'lastmod' => date('c')],
                    ];
                    echo $sitemapGen->renderIndexSitemap($index);
                    exit;
                } elseif ($path === '/post-sitemap.xml') {
                    header('Content-Type: text/xml; charset=UTF-8');
                    $urls = [];
                    if (function_exists('get_posts')) {
                        $posts = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 100]);
                        foreach ($posts as $p) {
                            $urls[] = [
                                'loc' => get_permalink($p->ID),
                                'lastmod' => get_post_modified_time('c', true, $p->ID),
                                'changefreq' => 'weekly',
                                'priority' => 0.8
                            ];
                        }
                    }
                    echo $sitemapGen->renderUrlSitemap($urls);
                    exit;
                } elseif ($path === '/page-sitemap.xml') {
                    header('Content-Type: text/xml; charset=UTF-8');
                    $urls = [];
                    if (function_exists('get_posts')) {
                        $pages = get_posts(['post_type' => 'page', 'post_status' => 'publish', 'numberposts' => 100]);
                        foreach ($pages as $p) {
                            $urls[] = [
                                'loc' => get_permalink($p->ID),
                                'lastmod' => get_post_modified_time('c', true, $p->ID),
                                'changefreq' => 'monthly',
                                'priority' => 0.6
                            ];
                        }
                    }
                    echo $sitemapGen->renderUrlSitemap($urls);
                    exit;
                } elseif ($path === '/category-sitemap.xml') {
                    header('Content-Type: text/xml; charset=UTF-8');
                    $urls = [];
                    if (function_exists('get_terms')) {
                        $terms = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
                        if (!is_wp_error($terms) && is_array($terms)) {
                            foreach ($terms as $t) {
                                $urls[] = [
                                    'loc' => get_term_link($t),
                                    'changefreq' => 'weekly',
                                    'priority' => 0.5
                                ];
                            }
                        }
                    }
                    echo $sitemapGen->renderUrlSitemap($urls);
                    exit;
                }
            }
        });
    }
}
