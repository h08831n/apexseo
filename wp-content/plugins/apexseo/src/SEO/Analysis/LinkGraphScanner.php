<?php
namespace ApexSEO\SEO\Analysis;

use ApexSEO\Core\Database\DatabaseManager;

/**
 * APEX-051: Internal Link Graph Scanner & Inbound Counter.
 *
 * Scans HTML content for links, classifies internal vs external destinations,
 * evaluates rel attributes (nofollow, sponsored, ugc), extracts anchor texts,
 * persists the link graph in `wp_apex_links`, and maintains inbound link counts
 * and orphan page detection.
 */
class LinkGraphScanner {
    /**
     * Database manager.
     *
     * @var DatabaseManager|null
     */
    protected $db;

    /**
     * Site base URL for internal link detection.
     *
     * @var string
     */
    protected $siteUrl;

    /**
     * Constructor.
     *
     * @param DatabaseManager|null $db
     * @param string|null $siteUrl
     */
    public function __construct(DatabaseManager $db = null, $siteUrl = null) {
        $this->db = $db;
        $this->siteUrl = $siteUrl ? rtrim($siteUrl, '/') : (function_exists('home_url') ? rtrim(home_url(), '/') : 'http://example.com');
    }

    /**
     * Set the base site URL for internal link classification.
     *
     * @param string $url
     * @return self
     */
    public function setSiteUrl($url) {
        $this->siteUrl = rtrim($url, '/');
        return $this;
    }

    /**
     * Normalize URL (strip fragment, trim, lowercase host).
     *
     * @param string $rawUrl
     * @return string|null Normalized URL or null if invalid
     */
    public function normalizeUrl($rawUrl) {
        $rawUrl = trim($rawUrl);
        if ($rawUrl === '' || $rawUrl === '#' || strpos($rawUrl, 'javascript:') === 0 || strpos($rawUrl, 'mailto:') === 0 || strpos($rawUrl, 'tel:') === 0) {
            return null;
        }

        // Remove hash fragment
        $hashPos = strpos($rawUrl, '#');
        if ($hashPos !== false) {
            $rawUrl = substr($rawUrl, 0, $hashPos);
        }

        $rawUrl = trim($rawUrl);
        if ($rawUrl === '') {
            return null;
        }

        // Convert relative URLs to absolute using site URL
        if (strpos($rawUrl, '/') === 0) {
            $rawUrl = $this->siteUrl . $rawUrl;
        } elseif (!preg_match('~^https?://~i', $rawUrl)) {
            $rawUrl = $this->siteUrl . '/' . ltrim($rawUrl, '/');
        }

        return $rawUrl;
    }

    /**
     * Check if a given URL is internal to the current site.
     *
     * @param string $url
     * @return bool
     */
    public function isInternalUrl($url) {
        $siteHost = parse_url($this->siteUrl, PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);

        if (empty($urlHost)) {
            return true; // Relative is internal
        }

        return strtolower($siteHost) === strtolower($urlHost);
    }

    /**
     * Scan HTML content and extract all link occurrences with metadata.
     *
     * @param string $html
     * @return array Array of extracted link descriptors
     */
    public function scanHtml($html) {
        if (empty($html)) {
            return [];
        }

        $links = [];
        $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1([^>]*)>(.*?)<\/a>/is';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rawHref = $match[2];
                $attributes = $match[3];
                $rawAnchor = $match[4];

                $normalizedUrl = $this->normalizeUrl($rawHref);
                if ($normalizedUrl === null) {
                    continue;
                }

                $anchorText = trim(strip_tags(html_entity_decode($rawAnchor, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                $isInternal = $this->isInternalUrl($normalizedUrl);

                // Check rel attributes
                $isNofollow = false;
                $isUgc = false;
                $isSponsored = false;

                if (preg_match('/rel=(["\'])(.*?)\1/i', $attributes, $relMatch)) {
                    $relValues = preg_split('/\s+/', strtolower($relMatch[2]));
                    $isNofollow = in_array('nofollow', $relValues);
                    $isUgc = in_array('ugc', $relValues);
                    $isSponsored = in_array('sponsored', $relValues);
                }

                $links[] = [
                    'url'           => $normalizedUrl,
                    'url_hash'      => md5($normalizedUrl),
                    'anchor_text'   => $anchorText,
                    'link_type'     => $isInternal ? 'internal' : 'external',
                    'is_nofollow'   => $isNofollow,
                    'is_ugc'        => $isUgc,
                    'is_sponsored'  => $isSponsored,
                ];
            }
        }

        return $links;
    }

    /**
     * Persist scanned links into the `wp_apex_links` database table for a post.
     *
     * @param int $postId
     * @param array $links
     * @return int Number of persisted links
     */
    public function persistPostLinks($postId, array $links) {
        if (!$this->db || $postId <= 0) {
            return 0;
        }

        $wpdb = $this->db->getWpdb();
        $prefix = $this->db->getPrefix();
        $tableName = "{$prefix}apex_links";

        // 1. Remove previous outbound links for this post
        $wpdb->delete($tableName, ['post_id' => (int) $postId], ['%d']);

        // 2. Insert fresh link records
        $insertedCount = 0;
        foreach ($links as $link) {
            $inserted = $wpdb->insert($tableName, [
                'post_id'        => (int) $postId,
                'target_post_id' => null, // Optional resolved post ID
                'url'            => $link['url'],
                'url_hash'       => $link['url_hash'],
                'anchor_text'    => $link['anchor_text'],
                'link_type'      => $link['link_type'],
                'is_nofollow'    => $link['is_nofollow'] ? 1 : 0,
                'is_ugc'         => $link['is_ugc'] ? 1 : 0,
                'is_sponsored'   => $link['is_sponsored'] ? 1 : 0,
            ], ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d']);

            if ($inserted) {
                $insertedCount++;
            }
        }

        // 3. Update indexable summary counts
        $this->updateIndexableLinkCounts($postId, $links);

        return $insertedCount;
    }

    /**
     * Update link counts in `wp_apex_indexables`.
     *
     * @param int $postId
     * @param array $links
     * @return void
     */
    protected function updateIndexableLinkCounts($postId, array $links) {
        if (!$this->db) {
            return;
        }

        $internalCount = 0;
        $externalCount = 0;

        foreach ($links as $link) {
            if ($link['link_type'] === 'internal') {
                $internalCount++;
            } else {
                $externalCount++;
            }
        }

        $wpdb = $this->db->getWpdb();
        $prefix = $this->db->getPrefix();
        $indexablesTable = "{$prefix}apex_indexables";

        $wpdb->update(
            $indexablesTable,
            [
                'link_count_internal' => $internalCount,
                'link_count_external' => $externalCount,
            ],
            [
                'object_id'   => (int) $postId,
                'object_type' => 'post',
            ],
            ['%d', '%d'],
            ['%d', '%s']
        );
    }

    /**
     * Get the inbound internal link count for a specific target URL or post.
     *
     * @param string $targetUrl
     * @return int
     */
    public function getInboundLinkCount($targetUrl) {
        if (!$this->db || empty($targetUrl)) {
            return 0;
        }

        $wpdb = $this->db->getWpdb();
        $prefix = $this->db->getPrefix();
        $tableName = "{$prefix}apex_links";

        $urlHash = md5($this->normalizeUrl($targetUrl) ?? $targetUrl);
        $sql = "SELECT COUNT(*) FROM `{$tableName}` WHERE `url_hash` = %s AND `link_type` = 'internal'";
        $prepared = $wpdb->prepare($sql, $urlHash);

        return (int) $wpdb->get_var($prepared);
    }

    /**
     * Check if an indexable post is orphaned (i.e. has 0 inbound internal links).
     *
     * @param string $url
     * @return bool
     */
    public function isOrphaned($url) {
        return $this->getInboundLinkCount($url) === 0;
    }

    /**
     * Complete scan and analysis summary for content.
     *
     * @param string $html
     * @param int|null $postId
     * @return array
     */
    public function scan($html, $postId = null) {
        $links = $this->scanHtml($html);

        $internalCount = 0;
        $externalCount = 0;
        $nofollowCount = 0;

        foreach ($links as $link) {
            if ($link['link_type'] === 'internal') {
                $internalCount++;
            } else {
                $externalCount++;
            }
            if ($link['is_nofollow']) {
                $nofollowCount++;
            }
        }

        if ($postId && $this->db) {
            $this->persistPostLinks($postId, $links);
        }

        return [
            'total_links'     => count($links),
            'internal_links'  => $internalCount,
            'external_links'  => $externalCount,
            'nofollow_links'  => $nofollowCount,
            'links'           => $links,
            'has_links'       => !empty($links),
        ];
    }
}
