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

        // 2. Nonce verification - missing or invalid nonce MUST fail closed
        if (!isset($_POST[self::NONCE_NAME]) || !function_exists('wp_verify_nonce')) {
            return false;
        }
        $rawNonce = function_exists('wp_unslash') ? wp_unslash($_POST[self::NONCE_NAME]) : $_POST[self::NONCE_NAME];
        if (!wp_verify_nonce(sanitize_key($rawNonce), self::NONCE_ACTION)) {
            return false;
        }

        // 3. Capability check
        if (!function_exists('current_user_can') || !current_user_can('edit_post', $postId)) {
            return false;
        }

        // 4. Extract and sanitize input fields
        $overrides = [];
        if (isset($_POST['_apexseo_title'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_title']) : $_POST['_apexseo_title'];
            $title = sanitize_text_field($raw);
            update_post_meta($postId, '_apexseo_title', $title);
            $overrides['title'] = $title;
        }

        if (isset($_POST['_apexseo_description'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_description']) : $_POST['_apexseo_description'];
            $desc = sanitize_textarea_field($raw);
            update_post_meta($postId, '_apexseo_description', $desc);
            $overrides['description'] = $desc;
        }

        if (isset($_POST['_apexseo_canonical'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_canonical']) : $_POST['_apexseo_canonical'];
            $canonical = esc_url_raw($raw);
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
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_og_title']) : $_POST['_apexseo_og_title'];
            $ogTitle = sanitize_text_field($raw);
            update_post_meta($postId, '_apexseo_og_title', $ogTitle);
            $overrides['og_title'] = $ogTitle;
        }

        if (isset($_POST['_apexseo_og_description'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_og_description']) : $_POST['_apexseo_og_description'];
            $ogDesc = sanitize_textarea_field($raw);
            update_post_meta($postId, '_apexseo_og_description', $ogDesc);
            $overrides['og_description'] = $ogDesc;
        }

        if (isset($_POST['_apexseo_og_image'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_og_image']) : $_POST['_apexseo_og_image'];
            $ogImage = esc_url_raw($raw);
            update_post_meta($postId, '_apexseo_og_image', $ogImage);
            $overrides['og_image'] = $ogImage;
        }

        if (isset($_POST['_apexseo_focus_keyword'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_focus_keyword']) : $_POST['_apexseo_focus_keyword'];
            $focusKey = sanitize_text_field($raw);
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
        // 1. Nonce verification - missing or invalid nonce MUST fail closed
        if (!isset($_POST[self::NONCE_NAME]) || !function_exists('wp_verify_nonce')) {
            return false;
        }
        $rawNonce = function_exists('wp_unslash') ? wp_unslash($_POST[self::NONCE_NAME]) : $_POST[self::NONCE_NAME];
        if (!wp_verify_nonce(sanitize_key($rawNonce), self::NONCE_ACTION)) {
            return false;
        }

        // 2. Capability check
        if (!function_exists('current_user_can') || !current_user_can('edit_term', $termId)) {
            return false;
        }

        $overrides = [];
        if (isset($_POST['_apexseo_title'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_title']) : $_POST['_apexseo_title'];
            $title = sanitize_text_field($raw);
            update_term_meta($termId, '_apexseo_title', $title);
            $overrides['title'] = $title;
        }

        if (isset($_POST['_apexseo_description'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_description']) : $_POST['_apexseo_description'];
            $desc = sanitize_textarea_field($raw);
            update_term_meta($termId, '_apexseo_description', $desc);
            $overrides['description'] = $desc;
        }

        if (isset($_POST['_apexseo_canonical'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_canonical']) : $_POST['_apexseo_canonical'];
            $canonical = esc_url_raw($raw);
            update_term_meta($termId, '_apexseo_canonical', $canonical);
            $overrides['canonical_url'] = $canonical;
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

    /**
     * Save author/user SEO metadata (APEX-005).
     *
     * @param int $userId
     * @return bool
     */
    public function saveAuthorMeta($userId) {
        // 1. Nonce verification - missing or invalid nonce MUST fail closed
        if (!isset($_POST[self::NONCE_NAME]) || !function_exists('wp_verify_nonce')) {
            return false;
        }
        $rawNonce = function_exists('wp_unslash') ? wp_unslash($_POST[self::NONCE_NAME]) : $_POST[self::NONCE_NAME];
        if (!wp_verify_nonce(sanitize_key($rawNonce), self::NONCE_ACTION)) {
            return false;
        }

        // 2. Capability check
        if (!function_exists('current_user_can') || !current_user_can('edit_user', $userId)) {
            return false;
        }

        $overrides = [];
        if (isset($_POST['_apexseo_title'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_title']) : $_POST['_apexseo_title'];
            $title = sanitize_text_field($raw);
            update_user_meta($userId, '_apexseo_title', $title);
            $overrides['title'] = $title;
        }

        if (isset($_POST['_apexseo_description'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_description']) : $_POST['_apexseo_description'];
            $desc = sanitize_textarea_field($raw);
            update_user_meta($userId, '_apexseo_description', $desc);
            $overrides['description'] = $desc;
        }

        if (isset($_POST['_apexseo_canonical'])) {
            $raw = function_exists('wp_unslash') ? wp_unslash($_POST['_apexseo_canonical']) : $_POST['_apexseo_canonical'];
            $canonical = esc_url_raw($raw);
            update_user_meta($userId, '_apexseo_canonical', $canonical);
            $overrides['canonical_url'] = $canonical;
        }

        if (isset($_POST['_apexseo_noindex'])) {
            $noindex = (int) $_POST['_apexseo_noindex'] === 1;
            update_user_meta($userId, '_apexseo_noindex', $noindex ? 1 : 0);
            $overrides['is_robots_noindex'] = $noindex;
        }

        $indexable = $this->builder->buildFromAuthor($userId, $overrides);
        return $this->repository->save($indexable);
    }

    /**
     * Delete indexable on user deletion.
     *
     * @param int $userId
     * @return bool
     */
    public function deleteAuthorIndexable($userId) {
        return $this->repository->deleteByObject('user', $userId);
    }

    /**
     * Bulk save SEO metadata for multiple posts, terms, or users with strict authorization and validation (APEX-014).
     *
     * @param array<int, array<string, mixed>> $items Array of meta records to save
     * @return array{total: int, updated: int, failed: int, errors: array<string|array<string, mixed>>}
     */
    public function bulkSave(array $items) {
        $maxLimit = 100;
        $totalItems = count($items);

        if ($totalItems > $maxLimit) {
            return [
                'total'   => $totalItems,
                'updated' => 0,
                'failed'  => $totalItems,
                'errors'  => ["Batch payload exceeds maximum allowed limit of {$maxLimit} items."],
            ];
        }

        $results = [
            'total'   => $totalItems,
            'updated' => 0,
            'failed'  => 0,
            'errors'  => [],
        ];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $results['failed']++;
                $results['errors'][] = "Item at index {$index} must be a valid array.";
                continue;
            }

            if (!isset($item['object_id']) || !is_numeric($item['object_id']) || (int) $item['object_id'] <= 0) {
                $results['failed']++;
                $results['errors'][] = "Item at index {$index} has an invalid or missing object_id.";
                continue;
            }

            $objectId = (int) $item['object_id'];

            // Strict object_type validation: DO NOT silently downgrade invalid types to 'post'
            if (!isset($item['object_type']) || !in_array($item['object_type'], ['post', 'term', 'user'], true)) {
                $typeStr = isset($item['object_type']) && is_scalar($item['object_type']) ? (string) $item['object_type'] : 'unknown';
                $results['failed']++;
                $results['errors'][] = "Unsupported or invalid object_type '{$typeStr}' for item ID {$objectId}.";
                continue;
            }

            $objectType = (string) $item['object_type'];
            $overrides = [];

            if (isset($item['title'])) {
                $overrides['title'] = sanitize_text_field((string) $item['title']);
            }

            if (isset($item['description'])) {
                $overrides['description'] = sanitize_textarea_field((string) $item['description']);
            }

            if (isset($item['canonical_url'])) {
                $cleanUrl = esc_url_raw((string) $item['canonical_url']);
                if (!empty($item['canonical_url']) && empty($cleanUrl)) {
                    $results['failed']++;
                    $results['errors'][] = "Malformed canonical_url provided for {$objectType} ID {$objectId}.";
                    continue;
                }
                $overrides['canonical_url'] = $cleanUrl;
            }

            if (isset($item['is_robots_noindex'])) {
                $overrides['is_robots_noindex'] = (bool) $item['is_robots_noindex'];
            }

            if (isset($item['is_robots_nofollow'])) {
                $overrides['is_robots_nofollow'] = (bool) $item['is_robots_nofollow'];
            }

            if (isset($item['primary_focus_keyword'])) {
                $overrides['primary_focus_keyword'] = sanitize_text_field((string) $item['primary_focus_keyword']);
            }

            // Per-object existence and capability authorization checks
            if ($objectType === 'post') {
                $post = function_exists('get_post') ? get_post($objectId) : null;
                if (!$post) {
                    $results['failed']++;
                    $results['errors'][] = "Post with ID {$objectId} does not exist.";
                    continue;
                }

                if (function_exists('current_user_can') && !current_user_can('edit_post', $objectId)) {
                    $results['failed']++;
                    $results['errors'][] = "Permission denied: Current user cannot edit post ID {$objectId}.";
                    continue;
                }

                if (isset($overrides['title'])) {
                    update_post_meta($objectId, '_apexseo_title', $overrides['title']);
                }
                if (isset($overrides['description'])) {
                    update_post_meta($objectId, '_apexseo_description', $overrides['description']);
                }
                if (isset($overrides['canonical_url'])) {
                    update_post_meta($objectId, '_apexseo_canonical', $overrides['canonical_url']);
                }
                if (isset($overrides['is_robots_noindex'])) {
                    update_post_meta($objectId, '_apexseo_noindex', $overrides['is_robots_noindex'] ? 1 : 0);
                }
                if (isset($overrides['primary_focus_keyword'])) {
                    update_post_meta($objectId, '_apexseo_focus_keyword', $overrides['primary_focus_keyword']);
                }

                $indexable = $this->builder->buildFromPost($post, $overrides);
            } elseif ($objectType === 'term') {
                $taxonomy = isset($item['object_sub_type']) ? sanitize_key($item['object_sub_type']) : 'category';
                $term = function_exists('get_term') ? get_term($objectId, $taxonomy) : null;
                if (!$term || is_wp_error($term)) {
                    $results['failed']++;
                    $results['errors'][] = "Term with ID {$objectId} does not exist in taxonomy '{$taxonomy}'.";
                    continue;
                }

                if (function_exists('current_user_can') && !current_user_can('edit_term', $objectId)) {
                    $results['failed']++;
                    $results['errors'][] = "Permission denied: Current user cannot edit term ID {$objectId}.";
                    continue;
                }

                if (isset($overrides['title'])) {
                    update_term_meta($objectId, '_apexseo_title', $overrides['title']);
                }
                if (isset($overrides['description'])) {
                    update_term_meta($objectId, '_apexseo_description', $overrides['description']);
                }
                if (isset($overrides['canonical_url'])) {
                    update_term_meta($objectId, '_apexseo_canonical', $overrides['canonical_url']);
                }
                if (isset($overrides['is_robots_noindex'])) {
                    update_term_meta($objectId, '_apexseo_noindex', $overrides['is_robots_noindex'] ? 1 : 0);
                }

                $indexable = $this->builder->buildFromTerm($term, $taxonomy, $overrides);
            } elseif ($objectType === 'user') {
                $userData = function_exists('get_userdata') ? get_userdata($objectId) : null;
                if (!$userData) {
                    $results['failed']++;
                    $results['errors'][] = "User with ID {$objectId} does not exist.";
                    continue;
                }

                if (function_exists('current_user_can') && !current_user_can('edit_user', $objectId)) {
                    $results['failed']++;
                    $results['errors'][] = "Permission denied: Current user cannot edit user ID {$objectId}.";
                    continue;
                }

                if (isset($overrides['title'])) {
                    update_user_meta($objectId, '_apexseo_title', $overrides['title']);
                }
                if (isset($overrides['description'])) {
                    update_user_meta($objectId, '_apexseo_description', $overrides['description']);
                }
                if (isset($overrides['canonical_url'])) {
                    update_user_meta($objectId, '_apexseo_canonical', $overrides['canonical_url']);
                }
                if (isset($overrides['is_robots_noindex'])) {
                    update_user_meta($objectId, '_apexseo_noindex', $overrides['is_robots_noindex'] ? 1 : 0);
                }

                $indexable = $this->builder->buildFromAuthor($userData, $overrides);
            }

            if (isset($indexable) && $this->repository->save($indexable)) {
                $results['updated']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Database save failed for {$objectType} ID {$objectId}.";
            }
        }

        return $results;
    }
}
