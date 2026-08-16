<?php
namespace ApexSEO\SEO\Admin;

use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Builder\IndexableBuilder;

/**
 * Handles secure admin persistence of SEO metadata with capability checks and nonce validation.
 */
class MetaSaver {
    /**
     * Indexable repository.
     *
     * @var IndexableRepository
     */
    protected $repository;

    /**
     * Indexable builder.
     *
     * @var IndexableBuilder
     */
    protected $builder;

    /**
     * Nonce action name.
     */
    const NONCE_ACTION = 'apexseo_save_meta';

    /**
     * Nonce field name.
     */
    const NONCE_NAME = 'apexseo_meta_nonce';

    /**
     * Constructor.
     *
     * @param IndexableRepository $repository
     * @param IndexableBuilder $builder
     */
    public function __construct(IndexableRepository $repository, IndexableBuilder $builder) {
        $this->repository = $repository;
        $this->builder = $builder;
    }

    /**
     * Save post SEO metadata from $_POST payload.
     *
     * @param int $postId
     * @param object|null $post
     * @return bool True if saved, false otherwise
     */
    public function savePostMeta($postId, $post = null) {
        // 1. Skip autosave, revision, or empty payload
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        if (function_exists('wp_is_post_revision') && wp_is_post_revision($postId)) {
            return false;
        }

        if (function_exists('wp_is_post_autosave') && wp_is_post_autosave($postId)) {
            return false;
        }

        // 2. Capability check
        if (!function_exists('current_user_can') || !current_user_can('edit_post', $postId)) {
            return false;
        }

        // 3. Nonce verification if present in $_POST
        if (isset($_POST[self::NONCE_NAME])) {
            if (!wp_verify_nonce(sanitize_key($_POST[self::NONCE_NAME]), self::NONCE_ACTION)) {
                return false;
            }
        }

        // 4. Extract and sanitize input fields
        $overrides = [];
        if (isset($_POST['_apexseo_title'])) {
            $title = sanitize_text_field($_POST['_apexseo_title']);
            update_post_meta($postId, '_apexseo_title', $title);
            $overrides['title'] = $title;
        }

        if (isset($_POST['_apexseo_description'])) {
            $desc = sanitize_textarea_field($_POST['_apexseo_description']);
            update_post_meta($postId, '_apexseo_description', $desc);
            $overrides['description'] = $desc;
        }

        if (isset($_POST['_apexseo_canonical'])) {
            $canonical = esc_url_raw($_POST['_apexseo_canonical']);
            update_post_meta($postId, '_apexseo_canonical', $canonical);
            $overrides['canonical_url'] = $canonical;
        }

        if (isset($_POST['_apexseo_noindex'])) {
            $noindex = (int) $_POST['_apexseo_noindex'] === 1;
            update_post_meta($postId, '_apexseo_noindex', $noindex ? 1 : 0);
            $overrides['is_robots_noindex'] = $noindex;
        }

        if (isset($_POST['_apexseo_nofollow'])) {
            $nofollow = (int) $_POST['_apexseo_nofollow'] === 1;
            update_post_meta($postId, '_apexseo_nofollow', $nofollow ? 1 : 0);
            $overrides['is_robots_nofollow'] = $nofollow;
        }

        if (isset($_POST['_apexseo_og_title'])) {
            $ogTitle = sanitize_text_field($_POST['_apexseo_og_title']);
            update_post_meta($postId, '_apexseo_og_title', $ogTitle);
            $overrides['og_title'] = $ogTitle;
        }

        if (isset($_POST['_apexseo_og_description'])) {
            $ogDesc = sanitize_textarea_field($_POST['_apexseo_og_description']);
            update_post_meta($postId, '_apexseo_og_description', $ogDesc);
            $overrides['og_description'] = $ogDesc;
        }

        if (isset($_POST['_apexseo_og_image'])) {
            $ogImage = esc_url_raw($_POST['_apexseo_og_image']);
            update_post_meta($postId, '_apexseo_og_image', $ogImage);
            $overrides['og_image'] = $ogImage;
        }

        if (isset($_POST['_apexseo_focus_keyword'])) {
            $focusKey = sanitize_text_field($_POST['_apexseo_focus_keyword']);
            update_post_meta($postId, '_apexseo_focus_keyword', $focusKey);
            $overrides['primary_focus_keyword'] = $focusKey;
        }

        // 5. Build and save Indexable in wp_apex_indexables
        $indexable = $this->builder->buildFromPost($postId, $overrides);
        return $this->repository->save($indexable);
    }

    /**
     * Save taxonomy term SEO metadata.
     *
     * @param int $termId
     * @param int $ttId
     * @param string $taxonomy
     * @return bool
     */
    public function saveTermMeta($termId, $ttId = 0, $taxonomy = 'category') {
        if (!function_exists('current_user_can') || !current_user_can('edit_term', $termId)) {
            return false;
        }

        if (isset($_POST[self::NONCE_NAME])) {
            if (!wp_verify_nonce(sanitize_key($_POST[self::NONCE_NAME]), self::NONCE_ACTION)) {
                return false;
            }
        }

        $overrides = [];
        if (isset($_POST['_apexseo_title'])) {
            $title = sanitize_text_field($_POST['_apexseo_title']);
            update_term_meta($termId, '_apexseo_title', $title);
            $overrides['title'] = $title;
        }

        if (isset($_POST['_apexseo_description'])) {
            $desc = sanitize_textarea_field($_POST['_apexseo_description']);
            update_term_meta($termId, '_apexseo_description', $desc);
            $overrides['description'] = $desc;
        }

        if (isset($_POST['_apexseo_noindex'])) {
            $noindex = (int) $_POST['_apexseo_noindex'] === 1;
            update_term_meta($termId, '_apexseo_noindex', $noindex ? 1 : 0);
            $overrides['is_robots_noindex'] = $noindex;
        }

        $indexable = $this->builder->buildFromTerm($termId, $taxonomy, $overrides);
        return $this->repository->save($indexable);
    }

    /**
     * Delete indexable on post deletion.
     *
     * @param int $postId
     * @return bool
     */
    public function deletePostIndexable($postId) {
        return $this->repository->deleteByObject('post', $postId);
    }

    /**
     * Delete indexable on term deletion.
     *
     * @param int $termId
     * @return bool
     */
    public function deleteTermIndexable($termId) {
        return $this->repository->deleteByObject('term', $termId);
    }
}
