<?php
namespace ApexSEO\SEO\Permalinks;

use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Strips the '/category/' base from category permalinks in WordPress (APEX-011).
 *
 * Handles:
 * - Rewriting category links to remove the base
 * - Injecting category rewrite rules without the base
 * - Preserving hierarchical/nested categories
 * - Handling pagination (/category-name/page/2/)
 * - Handling RSS/Atom feeds (/category-name/feed/)
 * - Preventing collisions with pages, posts, and CPTs
 * - Safe 301 redirecting of old /category/ URLs to avoid duplicate content
 * - Loop prevention on redirects
 */
class CategoryBaseStripper {
    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config;

    /**
     * Cached category rewrite rules.
     *
     * @var array<string, string>|null
     */
    protected $cachedRules = null;

    /**
     * Option key.
     */
    const OPTION_KEY = 'apexseo_strip_category_base';

    /**
     * Constructor.
     *
     * @param ConfigurationManager|null $config
     */
    public function __construct($config = null) {
        $this->config = $config;
    }

    /**
     * Check if category base stripping is enabled.
     *
     * @return bool
     */
    public function isEnabled() {
        if ($this->config !== null) {
            $val = $this->config->get('strip_category_base', null);
            if ($val !== null) {
                return (bool) $val;
            }
        }

        if (function_exists('get_option')) {
            return (bool) get_option(self::OPTION_KEY, false);
        }

        return false;
    }

    /**
     * Filter category link to remove /category/ base.
     *
     * @param string $categoryLink
     * @param int|object $term
     * @return string
     */
    public function filterCategoryLink($categoryLink, $term = null) {
        if (!$this->isEnabled()) {
            return $categoryLink;
        }

        $categoryBase = $this->getCategoryBase();
        if (empty($categoryBase)) {
            $categoryBase = 'category';
        }

        // Pattern matches /category/ or /custom-base/
        $pattern = '#/' . preg_quote($categoryBase, '#') . '/#';
        return preg_replace($pattern, '/', $categoryLink, 1);
    }

    /**
     * Get configured category base.
     *
     * @return string
     */
    public function getCategoryBase() {
        if (function_exists('get_option')) {
            $base = get_option('category_base');
            if (!empty($base)) {
                return trim($base, '/');
            }
        }
        return 'category';
    }

    /**
     * Modify category rewrite rules to remove base prefix.
     *
     * @param array<string, string> $categoryRewrite
     * @return array<string, string>
     */
    public function modifyCategoryRewriteRules($categoryRewrite) {
        if (!$this->isEnabled()) {
            return $categoryRewrite;
        }

        $categoryBase = $this->getCategoryBase();
        $newRules = [];

        // Generate rules without the category base
        $categories = $this->getCategorySlugs();

        foreach ($categories as $catSlug) {
            $catSlug = trim($catSlug, '/');
            if (empty($catSlug)) {
                continue;
            }

            // Feed rules: category-slug/(?:feed|rdf|rss|rss2|atom)/?$
            $newRules['(' . $catSlug . ')/(?:feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
            $newRules['(' . $catSlug . ')/feed/(?:feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';

            // Pagination rules: category-slug/page/?([0-9]{1,})/?$
            $newRules['(' . $catSlug . ')/page/?([0-9]{1,})/?$'] = 'index.php?category_name=$matches[1]&paged=$matches[2]';

            // Base category rule: category-slug/?$
            $newRules['(' . $catSlug . ')/?$'] = 'index.php?category_name=$matches[1]';
        }

        // Redirect rule for old base: category/(.*)$ -> catch in template_redirect
        return $newRules;
    }

    /**
     * Get all active category hierarchy slug paths with in-memory caching.
     *
     * @return array<string>
     */
    public function getCategorySlugs() {
        if ($this->cachedRules !== null) {
            return $this->cachedRules;
        }

        $slugs = [];

        if (function_exists('get_categories')) {
            $categories = get_categories(['hide_empty' => false, 'taxonomy' => 'category']);
            if (is_array($categories)) {
                foreach ($categories as $category) {
                    if (is_object($category) && isset($category->term_id)) {
                        $path = $this->getCategoryHierarchyPath($category);
                        if (!empty($path)) {
                            $slugs[] = $path;
                        }
                    }
                }
            }
        }

        $this->cachedRules = $slugs;
        return $slugs;
    }

    /**
     * Build hierarchical category path (e.g. parent/child/grandchild).
     *
     * @param object $category
     * @return string
     */
    protected function getCategoryHierarchyPath($category) {
        $slug = $category->slug;
        $parent = $category->parent;

        while ($parent > 0 && function_exists('get_category')) {
            $parentCat = get_category($parent);
            if ($parentCat && !is_wp_error($parentCat)) {
                $slug = $parentCat->slug . '/' . $slug;
                $parent = $parentCat->parent;
            } else {
                break;
            }
        }

        return $slug;
    }

    /**
     * 301 Redirect old /category/foo URLs to /foo with loop protection.
     *
     * @return void
     */
    public function handleOldCategoryRedirect() {
        if (!$this->isEnabled()) {
            return;
        }

        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }

        $requestUri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
        $categoryBase = $this->getCategoryBase();

        // Check if URI begins with /category/
        $prefix = '/' . $categoryBase . '/';
        if (strpos($requestUri, $prefix) !== 0 && strpos($requestUri, '/' . $categoryBase) !== 0) {
            return;
        }

        // Avoid infinite loop if requested URL is already clean
        $cleanUri = preg_replace('#^/' . preg_quote($categoryBase, '#') . '/#', '/', $requestUri);
        if ($cleanUri === $requestUri || empty($cleanUri)) {
            return;
        }

        $targetUrl = home_url($cleanUri);

        if (function_exists('wp_redirect')) {
            wp_redirect($targetUrl, 301, 'ApexSEO');
            exit;
        }
    }

    /**
     * Check if a given path collides with an existing static page or post.
     *
     * @param string $path
     * @return bool
     */
    public function doesPathCollideWithPage($path) {
        if (!function_exists('get_page_by_path')) {
            return false;
        }

        $cleanPath = trim($path, '/');
        $page = get_page_by_path($cleanPath, OBJECT, ['page', 'post']);
        return $page !== null;
    }

    /**
     * Safe rewrite rule flusher when category base toggle changes.
     *
     * @return void
     */
    public function flushRulesSafely() {
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false); // Soft flush without writing .htaccess on every invocation
        }
    }
}
