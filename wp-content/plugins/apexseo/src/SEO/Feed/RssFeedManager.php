<?php
namespace ApexSEO\SEO\Feed;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Contracts\HookableInterface;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Templates\TemplateManager;
use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Manages dynamic RSS feed header and footer injection (APEX-015).
 */
class RssFeedManager implements ServiceContractInterface, HookableInterface {
    /**
     * Variable engine.
     *
     * @var VariableEngine
     */
    protected $variableEngine;

    /**
     * Template manager.
     *
     * @var TemplateManager
     */
    protected $templateManager;

    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param VariableEngine|null $variableEngine
     * @param TemplateManager|null $templateManager
     * @param ConfigurationManager|null $config
     */
    public function __construct($variableEngine = null, $templateManager = null, $config = null) {
        $this->variableEngine = $variableEngine !== null ? $variableEngine : new VariableEngine();
        $this->templateManager = $templateManager !== null ? $templateManager : new TemplateManager($config);
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function registerHooks() {
        if (function_exists('add_filter')) {
            add_filter('the_content_feed', [$this, 'injectFeedContent'], 10, 1);
            add_filter('the_excerpt_rss', [$this, 'injectFeedContent'], 10, 1);
        }
    }

    /**
     * Marker to prevent duplicate injection on same feed content.
     */
    const INJECTION_MARKER = '<!-- apexseo-rss-injected -->';

    /**
     * Inject custom RSS header and footer into feed items (APEX-015).
     *
     * @param string $content
     * @return string
     */
    public function injectFeedContent($content) {
        if (empty($content) || strpos($content, self::INJECTION_MARKER) !== false) {
            return $content;
        }

        $headerTpl = $this->templateManager->getRssHeaderTemplate();
        $footerTpl = $this->templateManager->getRssFooterTemplate();

        if (empty($headerTpl) && empty($footerTpl)) {
            return $content;
        }

        $context = $this->buildFeedContext();

        return $this->formatFeedContent($content, $context);
    }

    /**
     * Format content with header and footer interpolation.
     *
     * @param string $content
     * @param array $context
     * @return string
     */
    public function formatFeedContent($content, array $context = []) {
        if (strpos($content, self::INJECTION_MARKER) !== false) {
            return $content;
        }

        $headerTpl = $this->templateManager->getRssHeaderTemplate();
        $footerTpl = $this->templateManager->getRssFooterTemplate();

        $headerHtml = '';
        if (!empty($headerTpl)) {
            $rawHeader = $this->variableEngine->replace($headerTpl, $context);
            $headerHtml = function_exists('wp_kses_post') ? wp_kses_post($rawHeader) : $rawHeader;
            if (!empty($headerHtml) && substr($headerHtml, -1) !== "\n") {
                $headerHtml .= "\n";
            }
        }

        $footerHtml = '';
        if (!empty($footerTpl)) {
            $rawFooter = $this->variableEngine->replace($footerTpl, $context);
            $footerHtml = function_exists('wp_kses_post') ? wp_kses_post($rawFooter) : $rawFooter;
            if (!empty($footerHtml) && substr($footerHtml, 0, 1) !== "\n") {
                $footerHtml = "\n" . $footerHtml;
            }
        }

        return self::INJECTION_MARKER . "\n" . $headerHtml . $content . $footerHtml;
    }

    /**
     * Build contextual replacement values for current feed item.
     *
     * @return array
     */
    protected function buildFeedContext() {
        global $post;

        $postId = 0;
        $title = '';
        $permalink = '';
        $authorId = 0;
        $authorName = '';
        $postDate = '';

        if ($post && is_object($post)) {
            $postId = isset($post->ID) ? (int) $post->ID : 0;
            $title = isset($post->post_title) ? $post->post_title : '';
            $permalink = function_exists('get_permalink') ? (string) get_permalink($postId) : '';
            $authorId = isset($post->post_author) ? (int) $post->post_author : 0;
            $authorName = function_exists('get_the_author_meta') ? (string) get_the_author_meta('display_name', $authorId) : '';
            $postDate = isset($post->post_date) ? (string) $post->post_date : date('Y-m-d');
        }

        return [
            'object_id'    => $postId,
            'post_id'      => $postId,
            'id'           => $postId,
            'title'        => $title,
            'post_title'   => $title,
            'permalink'    => $permalink,
            'url'          => $permalink,
            'author_id'    => $authorId,
            'author_name'  => $authorName,
            'author'       => $authorName,
            'date'         => $postDate,
            'sitename'     => function_exists('get_option') ? get_option('blogname', 'WordPress') : 'WordPress',
            'sitedesc'     => function_exists('get_option') ? get_option('blogdescription', '') : '',
            'sep'          => $this->templateManager->getTitleSeparator(),
        ];
    }
}
