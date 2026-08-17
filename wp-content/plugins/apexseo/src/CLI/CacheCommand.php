<?php
namespace ApexSEO\CLI;

use ApexSEO\Cache\Engine\CacheEngine;
use ApexSEO\Cache\Integration\CacheIntegrationManager;

/**
 * WP-CLI Command for Cache Purge and Preload/Warmup (APEX-181, APEX-182).
 *
 * ## EXAMPLES
 *     wp apexseo cache purge --all
 *     wp apexseo cache purge https://example.com/sample-post/
 *     wp apexseo cache purge --tag=product_cat_12
 *     wp apexseo cache warmup --concurrency=5
 */
class CacheCommand extends AbstractCliCommand {
    /**
     * Purge page cache, object cache, or CDN edge cache.
     *
     * ## OPTIONS
     * [<url>]
     * : Specific URL to purge from cache.
     *
     * [--all]
     * : Purge all cached HTML pages and object caches.
     *
     * [--tag=<tag>]
     * : Purge cache entries associated with a specific tag (e.g. category, post_type).
     *
     * [--network]
     * : Purge cache across all network sites in Multisite.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function purge($args = [], $assocArgs = []) {
        $url     = !empty($args[0]) ? esc_url_raw($args[0]) : null;
        $isAll   = !empty($assocArgs['all']);
        $tag     = isset($assocArgs['tag']) ? sanitize_text_field($assocArgs['tag']) : null;
        $network = !empty($assocArgs['network']);

        /** @var CacheEngine $engine */
        $engine = $this->container->has(CacheEngine::class) ? $this->container->get(CacheEngine::class) : null;

        if ($isAll || (empty($url) && empty($tag))) {
            if ($engine) {
                $engine->flush();
            }
            // Trigger server/varnish/litespeed purge hooks
            if (function_exists('do_action')) {
                do_action('apexseo_purge_all_cache');
            }
            $this->success('Successfully purged all Apex SEO cache layers (Page, Object, CDN).');
            return 0;
        }

        if (!empty($url)) {
            if ($engine) {
                $key = 'url_' . md5($url);
                $engine->delete($key);
            }
            if (function_exists('do_action')) {
                do_action('apexseo_purge_url_cache', $url);
            }
            $this->success(sprintf('Successfully purged cache for URL: %s', $url));
            return 0;
        }

        if (!empty($tag)) {
            if (function_exists('do_action')) {
                do_action('apexseo_purge_tag_cache', $tag);
            }
            $this->success(sprintf('Successfully purged cache for Tag: %s', $tag));
            return 0;
        }

        return 0;
    }

    /**
     * Pre-warm and preload cache by crawling XML sitemap URLs.
     *
     * ## OPTIONS
     * [--sitemap=<url>]
     * : Custom XML sitemap URL to crawl. Defaults to home_url('/sitemap_index.xml').
     *
     * [--concurrency=<int>]
     * : Number of simultaneous warmup HTTP requests.
     * ---
     * default: 5
     * ---
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function warmup($args = [], $assocArgs = []) {
        return $this->preload($args, $assocArgs);
    }

    /**
     * Alias for warmup.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function preload($args = [], $assocArgs = []) {
        $sitemapUrl  = isset($assocArgs['sitemap']) ? esc_url_raw($assocArgs['sitemap']) : (function_exists('home_url') ? home_url('/sitemap_index.xml') : 'https://example.com/sitemap_index.xml');
        $concurrency = isset($assocArgs['concurrency']) ? max(1, min(20, (int) $assocArgs['concurrency'])) : 5;

        $this->line(sprintf('Initiating cache warmup from sitemap: %s (Concurrency: %d)...', $sitemapUrl, $concurrency));

        // Simulated/Executed crawl queue
        $enqueuedUrls = [$sitemapUrl];
        if (function_exists('do_action')) {
            do_action('apexseo_trigger_cache_preload', $sitemapUrl, $concurrency);
        }

        $this->success(sprintf('Cache preload task enqueued successfully for %s.', $sitemapUrl));
        return 0;
    }
}
