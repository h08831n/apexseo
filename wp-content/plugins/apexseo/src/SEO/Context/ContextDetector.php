<?php
namespace ApexSEO\SEO\Context;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Templates\TemplateManager;

/**
 * Detects current WordPress runtime query context and constructs a populated SeoContext object.
 */
class ContextDetector {
    /**
     * Template manager.
     *
     * @var TemplateManager
     */
    protected $templateManager;

    /**
     * Constructor.
     *
     * @param TemplateManager|null $templateManager
     */
    public function __construct($templateManager = null) {
        $this->templateManager = $templateManager !== null ? $templateManager : new TemplateManager();
    }

    /**
     * Detect and construct current SeoContext based on WordPress globals and query functions.
     *
     * @return SeoContext
     */
    public function detectContext() {
        $context = new SeoContext();
        $context->site_name = get_option('blogname', 'WordPress');
        $context->site_description = get_option('blogdescription', '');
        $context->sep = $this->templateManager->getTitleSeparator();

        // 1. Front Page / Home
        if (function_exists('is_front_page') && is_front_page()) {
            $context->page_type = 'front_page';
            if (get_option('show_on_front') === 'page') {
                $frontPageId = (int) get_option('page_on_front');
                if ($frontPageId > 0) {
                    $context->object_id = $frontPageId;
                    $context->object_type = 'post';
                    $context->object_sub_type = 'page';
                    $this->populateFromPost($context, get_post($frontPageId));
                }
            } else {
                $context->title = $context->site_name;
                $context->excerpt = $context->site_description;
                $context->permalink = home_url('/');
                $context->canonical_url = home_url('/');
                $context->og_type = 'website';
            }
            $this->detectPagination($context);
            return $context;
        }

        if (function_exists('is_home') && is_home()) {
            $context->page_type = 'home';
            $postsPageId = (int) get_option('page_for_posts');
            if ($postsPageId > 0) {
                $context->object_id = $postsPageId;
                $context->object_type = 'post';
                $context->object_sub_type = 'page';
                $this->populateFromPost($context, get_post($postsPageId));
            } else {
                $context->title = $context->site_name;
                $context->excerpt = $context->site_description;
                $context->permalink = home_url('/');
                $context->canonical_url = home_url('/');
                $context->og_type = 'website';
            }
            $this->detectPagination($context);
            return $context;
        }

        // 2. Singular (Post, Page, Custom Post Type, Attachment)
        if (function_exists('is_singular') && is_singular()) {
            $post = get_queried_object();
            if ($post && isset($post->ID)) {
                $context->page_type = 'single';
                $context->object_id = (int) $post->ID;
                $context->object_type = 'post';
                $context->object_sub_type = $post->post_type;
                $context->queried_object = $post;
                $this->populateFromPost($context, $post);
                $this->detectPagination($context);
                return $context;
            }
        }

        // 3. Taxonomy / Terms (Category, Tag, Custom Taxonomy)
        if (function_exists('is_tax') && (is_category() || is_tag() || is_tax())) {
            $term = get_queried_object();
            if ($term && isset($term->term_id)) {
                $context->page_type = 'term';
                $context->object_id = (int) $term->term_id;
                $context->object_type = 'term';
                $context->object_sub_type = $term->taxonomy;
                $context->queried_object = $term;
                $context->term_name = $term->name;
                $context->taxonomy = $term->taxonomy;
                $context->title = $term->name;
                $context->excerpt = !empty($term->description) ? wp_strip_all_tags($term->description) : '';
                $termLink = get_term_link($term);
                $context->permalink = !is_wp_error($termLink) ? $termLink : '';
                $context->canonical_url = $context->permalink;
                $context->og_type = 'website';
                $this->detectPagination($context);
                return $context;
            }
        }

        // 4. Author Archive
        if (function_exists('is_author') && is_author()) {
            $author = get_queried_object();
            if ($author && isset($author->ID)) {
                $context->page_type = 'author';
                $context->object_id = (int) $author->ID;
                $context->object_type = 'user';
                $context->object_sub_type = 'author';
                $context->queried_object = $author;
                $context->author_id = (int) $author->ID;
                $context->author_name = $author->display_name;
                $context->title = $author->display_name;
                $bio = get_the_author_meta('description', $author->ID);
                $context->excerpt = !empty($bio) ? wp_strip_all_tags($bio) : '';
                $context->permalink = get_author_posts_url($author->ID);
                $context->canonical_url = $context->permalink;
                $context->og_type = 'profile';
                $this->detectPagination($context);
                return $context;
            }
        }

        // 5. Date Archive
        if (function_exists('is_date') && is_date()) {
            $context->page_type = 'date';
            $context->object_type = 'archive';
            $context->object_sub_type = 'date';
            $context->title = single_month_title(' ', false);
            $context->date_published = $context->title;
            $context->permalink = get_year_link(get_query_var('year'));
            $context->canonical_url = $context->permalink;
            $context->og_type = 'website';
            $this->detectPagination($context);
            return $context;
        }

        // 6. Search Results
        if (function_exists('is_search') && is_search()) {
            $context->page_type = 'search';
            $context->object_type = 'search';
            $context->object_sub_type = 'search';
            $context->search_query = get_search_query();
            $context->title = $context->search_query;
            $context->permalink = get_search_link($context->search_query);
            $context->canonical_url = $context->permalink;
            $context->robots_noindex = $this->templateManager->isDefaultNoindex('search');
            $context->og_type = 'website';
            $this->detectPagination($context);
            return $context;
        }

        // 7. 404 Error Page
        if (function_exists('is_404') && is_404()) {
            $context->page_type = '404';
            $context->object_type = '404';
            $context->object_sub_type = '404';
            $context->title = 'Page Not Found';
            $context->robots_noindex = true;
            $context->robots_nofollow = true;
            return $context;
        }

        // 8. Generic Post Type Archive
        if (function_exists('is_post_type_archive') && is_post_type_archive()) {
            $postType = get_query_var('post_type');
            if (is_array($postType)) {
                $postType = reset($postType);
            }
            $context->page_type = 'archive';
            $context->object_type = 'archive';
            $context->object_sub_type = $postType;
            $postTypeObj = get_post_type_object($postType);
            $context->title = $postTypeObj ? $postTypeObj->labels->name : $postType;
            $context->permalink = get_post_type_archive_link($postType);
            $context->canonical_url = $context->permalink;
            $context->og_type = 'website';
            $this->detectPagination($context);
            return $context;
        }

        $this->detectPagination($context);
        return $context;
    }

    /**
     * Populate context data from a WP_Post object.
     *
     * @param SeoContext $context
     * @param object|null $post
     * @return void
     */
    protected function populateFromPost(SeoContext $context, $post) {
        if (!$post) {
            return;
        }

        $context->title = !empty($post->post_title) ? $post->post_title : '';
        $context->permalink = get_permalink($post->ID);
        $context->canonical_url = $context->permalink;
        $context->author_id = (int) $post->post_author;
        $context->author_name = get_the_author_meta('display_name', $post->post_author);
        $context->date_published = !empty($post->post_date) ? date('c', strtotime($post->post_date)) : '';
        $context->date_modified = !empty($post->post_modified) ? date('c', strtotime($post->post_modified)) : '';

        // Excerpt calculation
        if (!empty($post->post_excerpt)) {
            $context->excerpt = wp_strip_all_tags($post->post_excerpt);
        } elseif (!empty($post->post_content)) {
            $cleanContent = wp_strip_all_tags(strip_shortcodes($post->post_content));
            $context->excerpt = mb_substr($cleanContent, 0, 160);
        }

        // Primary Category
        $categories = get_the_category($post->ID);
        if (!empty($categories) && !is_wp_error($categories)) {
            $context->category = $categories[0]->name;
        }

        // Tags
        $tags = get_the_tags($post->ID);
        if (!empty($tags) && !is_wp_error($tags)) {
            $tagNames = [];
            foreach ($tags as $tag) {
                $tagNames[] = $tag->name;
            }
            $context->tag = implode(', ', $tagNames);
        }

        // Featured Image
        if (function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID)) {
            $thumbId = get_post_thumbnail_id($post->ID);
            $context->featured_image_id = $thumbId;
            $thumbUrl = wp_get_attachment_image_url($thumbId, 'full');
            if ($thumbUrl) {
                $context->featured_image = $thumbUrl;
                $context->og_image = $thumbUrl;
                $context->twitter_image = $thumbUrl;
            }
        }

        $context->og_type = $post->post_type === 'page' ? 'website' : 'article';
    }

    /**
     * Detect pagination parameters from global query.
     *
     * @param SeoContext $context
     * @return void
     */
    protected function detectPagination(SeoContext $context) {
        $paged = 1;
        if (function_exists('get_query_var')) {
            $pagedVar = get_query_var('paged');
            $pageVar = get_query_var('page');
            $paged = max(1, (int) $pagedVar, (int) $pageVar);
        }

        $context->page_number = $paged;
        $context->is_paged = $paged > 1;

        global $wp_query;
        if (isset($wp_query->max_num_pages) && $wp_query->max_num_pages > 0) {
            $context->total_pages = (int) $wp_query->max_num_pages;
        } else {
            $context->total_pages = 1;
        }
    }
}
