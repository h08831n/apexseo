<?php
namespace ApexSEO\SEO\Builder;

use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Templates\TemplateManager;
use ApexSEO\SEO\Variables\VariableEngine;

/**
 * Builds and enriches Indexable models from WordPress entity objects.
 */
class IndexableBuilder {
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
     * Constructor.
     *
     * @param VariableEngine|null $variableEngine
     * @param TemplateManager|null $templateManager
     */
    public function __construct($variableEngine = null, $templateManager = null) {
        $this->variableEngine = $variableEngine !== null ? $variableEngine : new VariableEngine();
        $this->templateManager = $templateManager !== null ? $templateManager : new TemplateManager();
    }

    /**
     * Build an Indexable model from a WP_Post object.
     *
     * @param object|int $post
     * @param array $overrides Optional explicit overrides
     * @return Indexable
     */
    public function buildFromPost($post, array $overrides = []) {
        if (is_numeric($post)) {
            $post = get_post($post);
        }

        $indexable = new Indexable();
        if (!$post) {
            return $indexable;
        }

        $postId = (int) $post->ID;
        $indexable->object_id = $postId;
        $indexable->object_type = 'post';
        $indexable->object_sub_type = $post->post_type;
        $indexable->permalink = (string) get_permalink($postId);
        $indexable->permalink_hash = md5($indexable->permalink);

        // Populate context for template resolution
        $context = new SeoContext();
        $context->object_id = $postId;
        $context->object_type = 'post';
        $context->object_sub_type = $post->post_type;
        $context->title = $post->post_title;
        $context->permalink = $indexable->permalink;
        $context->canonical_url = $indexable->permalink;
        $context->author_id = (int) $post->post_author;
        $context->author_name = get_the_author_meta('display_name', $post->post_author);
        $context->site_name = get_option('blogname', 'WordPress');
        $context->site_description = get_option('blogdescription', '');
        $context->sep = $this->templateManager->getTitleSeparator();

        if (!empty($post->post_excerpt)) {
            $context->excerpt = wp_strip_all_tags($post->post_excerpt);
        } elseif (!empty($post->post_content)) {
            $clean = wp_strip_all_tags(strip_shortcodes($post->post_content));
            $context->excerpt = mb_substr($clean, 0, 160);
        }

        // Apply defaults from templates
        $titleTpl = $this->templateManager->getTitleTemplate($post->post_type);
        $descTpl = $this->templateManager->getDescriptionTemplate($post->post_type);

        $indexable->title = $this->variableEngine->replace($titleTpl, $context);
        $indexable->description = $this->variableEngine->replace($descTpl, $context);
        $indexable->canonical_url = $indexable->permalink;
        $indexable->is_robots_noindex = $this->templateManager->isDefaultNoindex($post->post_type);
        $indexable->schema_type = $post->post_type === 'page' ? 'WebPage' : 'Article';

        // Check for featured image
        if (function_exists('has_post_thumbnail') && has_post_thumbnail($postId)) {
            $thumbId = get_post_thumbnail_id($postId);
            $indexable->og_image_id = (int) $thumbId;
            $thumbUrl = wp_get_attachment_image_url($thumbId, 'full');
            if ($thumbUrl) {
                $indexable->og_image = $thumbUrl;
                $indexable->twitter_image = $thumbUrl;
            }
        }

        // Merge existing post meta overrides if present
        if (function_exists('get_post_meta')) {
            $metaTitle = get_post_meta($postId, '_apexseo_title', true);
            if (!empty($metaTitle)) {
                $indexable->title = $this->variableEngine->replace($metaTitle, $context);
            }

            $metaDesc = get_post_meta($postId, '_apexseo_description', true);
            if (!empty($metaDesc)) {
                $indexable->description = $this->variableEngine->replace($metaDesc, $context);
            }

            $metaCanonical = get_post_meta($postId, '_apexseo_canonical', true);
            if (!empty($metaCanonical)) {
                $indexable->canonical_url = esc_url_raw($metaCanonical);
            }

            $metaNoindex = get_post_meta($postId, '_apexseo_noindex', true);
            if ($metaNoindex !== '') {
                $indexable->is_robots_noindex = (bool) $metaNoindex;
            }

            $metaNofollow = get_post_meta($postId, '_apexseo_nofollow', true);
            if ($metaNofollow !== '') {
                $indexable->is_robots_nofollow = (bool) $metaNofollow;
            }

            $metaOgTitle = get_post_meta($postId, '_apexseo_og_title', true);
            if (!empty($metaOgTitle)) {
                $indexable->og_title = $this->variableEngine->replace($metaOgTitle, $context);
            }

            $metaOgDesc = get_post_meta($postId, '_apexseo_og_description', true);
            if (!empty($metaOgDesc)) {
                $indexable->og_description = $this->variableEngine->replace($metaOgDesc, $context);
            }

            $metaOgImage = get_post_meta($postId, '_apexseo_og_image', true);
            if (!empty($metaOgImage)) {
                $indexable->og_image = esc_url_raw($metaOgImage);
            }

            $metaFocusKey = get_post_meta($postId, '_apexseo_focus_keyword', true);
            if (!empty($metaFocusKey)) {
                $indexable->primary_focus_keyword = sanitize_text_field($metaFocusKey);
            }
        }

        // Apply explicit parameter overrides (e.g. from save action or admin form)
        if (!empty($overrides)) {
            foreach ($overrides as $k => $v) {
                if (property_exists($indexable, $k)) {
                    $indexable->$k = $v;
                }
            }
        }

        return $indexable;
    }

    /**
     * Build an Indexable model from a WP_Term object.
     *
     * @param object|int $term
     * @param string $taxonomy
     * @param array $overrides
     * @return Indexable
     */
    public function buildFromTerm($term, $taxonomy = 'category', array $overrides = []) {
        if (is_numeric($term)) {
            $term = get_term($term, $taxonomy);
        }

        $indexable = new Indexable();
        if (!$term || is_wp_error($term)) {
            return $indexable;
        }

        $termId = (int) $term->term_id;
        $indexable->object_id = $termId;
        $indexable->object_type = 'term';
        $indexable->object_sub_type = $term->taxonomy;
        $termLink = get_term_link($term);
        $indexable->permalink = !is_wp_error($termLink) ? (string) $termLink : '';
        $indexable->permalink_hash = md5($indexable->permalink);

        $context = new SeoContext();
        $context->object_id = $termId;
        $context->object_type = 'term';
        $context->object_sub_type = $term->taxonomy;
        $context->term_name = $term->name;
        $context->taxonomy = $term->taxonomy;
        $context->title = $term->name;
        $context->excerpt = !empty($term->description) ? wp_strip_all_tags($term->description) : '';
        $context->permalink = $indexable->permalink;
        $context->canonical_url = $indexable->permalink;
        $context->site_name = get_option('blogname', 'WordPress');
        $context->site_description = get_option('blogdescription', '');
        $context->sep = $this->templateManager->getTitleSeparator();

        $titleTpl = $this->templateManager->getTitleTemplate($term->taxonomy);
        $descTpl = $this->templateManager->getDescriptionTemplate($term->taxonomy);

        $indexable->title = $this->variableEngine->replace($titleTpl, $context);
        $indexable->description = $this->variableEngine->replace($descTpl, $context);
        $indexable->canonical_url = $indexable->permalink;
        $indexable->is_robots_noindex = $this->templateManager->isDefaultNoindex($term->taxonomy);
        $indexable->schema_type = 'CollectionPage';

        if (function_exists('get_term_meta')) {
            $metaTitle = get_term_meta($termId, '_apexseo_title', true);
            if (!empty($metaTitle)) {
                $indexable->title = $this->variableEngine->replace($metaTitle, $context);
            }
            $metaDesc = get_term_meta($termId, '_apexseo_description', true);
            if (!empty($metaDesc)) {
                $indexable->description = $this->variableEngine->replace($metaDesc, $context);
            }
            $metaNoindex = get_term_meta($termId, '_apexseo_noindex', true);
            if ($metaNoindex !== '') {
                $indexable->is_robots_noindex = (bool) $metaNoindex;
            }
        }

        if (!empty($overrides)) {
            foreach ($overrides as $k => $v) {
                if (property_exists($indexable, $k)) {
                    $indexable->$k = $v;
                }
            }
        }

        return $indexable;
    }
}
