<?php
namespace ApexSEO\SEO\Robots;

use ApexSEO\SEO\Context\ContextDetector;
use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Meta\RobotsPresenter;
use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Manages HTTP X-Robots-Tag header emission for non-HTML responses, feeds, attachments, 404s, and search (APEX-027, APEX-028, APEX-029, APEX-030).
 */
class RobotsHeaderManager {
    /**
     * Context detector.
     *
     * @var ContextDetector|null
     */
    protected $contextDetector;

    /**
     * Robots presenter.
     *
     * @var RobotsPresenter|null
     */
    protected $robotsPresenter;

    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config;

    /**
     * Track whether header was already emitted.
     *
     * @var bool
     */
    protected $headerSent = false;

    /**
     * Constructor.
     *
     * @param ContextDetector|null $contextDetector
     * @param RobotsPresenter|null $robotsPresenter
     * @param ConfigurationManager|null $config
     */
    public function __construct($contextDetector = null, $robotsPresenter = null, $config = null) {
        $this->contextDetector = $contextDetector !== null ? $contextDetector : new ContextDetector();
        $this->robotsPresenter = $robotsPresenter !== null ? $robotsPresenter : new RobotsPresenter();
        $this->config = $config;
    }

    /**
     * Filter WordPress `wp_headers` array to inject X-Robots-Tag header.
     *
     * @param array<string, string> $headers Existing HTTP headers map
     * @return array<string, string>
     */
    public function filterHttpHeaders(array $headers) {
        $value = $this->determineHeaderValue();
        if (!empty($value)) {
            $headers['X-Robots-Tag'] = $value;
        }
        return $headers;
    }

    /**
     * Action callback for `send_headers` or early feed headers.
     *
     * @return void
     */
    public function sendHttpHeader() {
        if ($this->headerSent || headers_sent()) {
            return;
        }

        $value = $this->determineHeaderValue();
        if (!empty($value)) {
            header('X-Robots-Tag: ' . $value, false);
            $this->headerSent = true;
        }
    }

    /**
     * Determine appropriate X-Robots-Tag string based on query context.
     *
     * @param SeoContext|null $context Optional explicit context for unit testing
     * @return string
     */
    public function determineHeaderValue($context = null) {
        // Global privacy check (blog_public = 0)
        if (function_exists('get_option') && (string) get_option('blog_public') === '0') {
            return 'noindex, nofollow';
        }

        if ($context === null) {
            $context = $this->contextDetector->detect();
        }

        // 1. 404 Context (APEX-030)
        if ($context->page_type === '404' || (function_exists('is_404') && is_404())) {
            return 'noindex, nofollow';
        }

        // 2. Search Query Context (APEX-030)
        if ($context->page_type === 'search' || (function_exists('is_search') && is_search())) {
            return 'noindex, follow';
        }

        // 3. RSS / Atom Feed Context (APEX-029)
        if (function_exists('is_feed') && is_feed()) {
            return 'noindex, follow';
        }

        // 4. Attachment / Media Context (APEX-028)
        if (function_exists('is_attachment') && is_attachment()) {
            $noindexMedia = true;
            if ($this->config !== null) {
                $noindexMedia = (bool) $this->config->get('noindex_media', true);
            }
            if ($noindexMedia) {
                return 'noindex, follow';
            }
        }

        // 5. Explicit noindex on context
        if ($context->robots_noindex) {
            $follow = $context->robots_nofollow ? 'nofollow' : 'follow';
            return 'noindex, ' . $follow;
        }

        return '';
    }
}
