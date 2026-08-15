<?php
namespace ApexSEO\Core\Multisite;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * Multisite and Network Context Manager for Apex SEO Platform.
 */
class MultisiteManager implements ServiceContractInterface {
    /**
     * Blog switching stack to guarantee safe restore depth.
     *
     * @var array
     */
    protected $blogStack = [];

    /**
     * Database manager instance.
     *
     * @var DatabaseManager|null
     */
    protected $db;

    /**
     * Constructor.
     *
     * @param DatabaseManager|null $db
     */
    public function __construct($db = null) {
        $this->db = $db;
    }

    /**
     * Check if WordPress is running in Multisite mode.
     *
     * @return bool
     */
    public function isMultisite() {
        return function_exists('is_multisite') && is_multisite();
    }

    /**
     * Check if currently in Network Admin dashboard.
     *
     * @return bool
     */
    public function isNetworkAdmin() {
        return function_exists('is_network_admin') && is_network_admin();
    }

    /**
     * Check if current site is the primary/main site in the network.
     *
     * @return bool
     */
    public function isMainSite() {
        if (!$this->isMultisite()) {
            return true;
        }
        return function_exists('is_main_site') && is_main_site();
    }

    /**
     * Get current blog ID.
     *
     * @return int
     */
    public function getCurrentBlogId() {
        return function_exists('get_current_blog_id') ? (int) get_current_blog_id() : 1;
    }

    /**
     * Get main network site ID.
     *
     * @return int
     */
    public function getMainSiteId() {
        if (!$this->isMultisite()) {
            return 1;
        }
        return function_exists('get_main_site_id') ? (int) get_main_site_id() : 1;
    }

    /**
     * Switch context to a target blog safely with stack tracking.
     *
     * @param int $blogId Target blog ID.
     * @return bool True if switched.
     */
    public function switchBlog($blogId) {
        if (!$this->isMultisite()) {
            return false;
        }

        $currentId = $this->getCurrentBlogId();
        $this->blogStack[] = $currentId;

        if (function_exists('switch_to_blog')) {
            switch_to_blog($blogId);
            if ($this->db !== null) {
                global $wpdb;
                $this->db->setPrefix(isset($wpdb->prefix) ? $wpdb->prefix : null);
            }
            return true;
        }

        return false;
    }

    /**
     * Restore previous blog context from stack.
     *
     * @return bool True if restored.
     */
    public function restoreBlog() {
        if (!$this->isMultisite() || empty($this->blogStack)) {
            return false;
        }

        array_pop($this->blogStack);

        if (function_exists('restore_current_blog')) {
            restore_current_blog();
            if ($this->db !== null) {
                global $wpdb;
                $this->db->setPrefix(isset($wpdb->prefix) ? $wpdb->prefix : null);
            }
            return true;
        }

        return false;
    }

    /**
     * Execute a callback safely inside a target blog's context and restore cleanly even if an exception occurs.
     *
     * @param int $blogId
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    public function runInBlogContext($blogId, $callback) {
        if (!$this->isMultisite() || $blogId === $this->getCurrentBlogId()) {
            return call_user_func($callback);
        }

        $this->switchBlog($blogId);
        try {
            $result = call_user_func($callback);
        } finally {
            $this->restoreBlog();
        }

        return $result;
    }

    /**
     * Get all active site/blog IDs in the network.
     *
     * @return int[]
     */
    public function getSiteIds() {
        if (!$this->isMultisite()) {
            return [1];
        }

        if (function_exists('get_sites')) {
            $sites = get_sites(['fields' => 'ids', 'number' => 500]);
            return array_map('intval', (array) $sites);
        }

        return [1];
    }
}
