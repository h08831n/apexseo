<?php
namespace ApexSEO\SEO\Models;

/**
 * Encapsulates runtime context for SEO metadata rendering.
 */
class SeoContext {
    /** @var string front_page|home|single|term|author|date|archive|search|404|shop|product */
    public $page_type = 'single';

    /** @var int|null */
    public $object_id = null;

    /** @var string */
    public $object_type = 'post';

    /** @var string */
    public $object_sub_type = 'post';

    /** @var object|null WP_Post or WP_Term or WP_User */
    public $queried_object = null;

    /** @var string */
    public $title = '';

    /** @var string */
    public $excerpt = '';

    /** @var string */
    public $content = '';

    /** @var string */
    public $permalink = '';

    /** @var string */
    public $canonical_url = '';

    /** @var string */
    public $author_name = '';

    /** @var int|null */
    public $author_id = null;

    /** @var string */
    public $category = '';

    /** @var string */
    public $tag = '';

    /** @var string */
    public $term_name = '';

    /** @var string */
    public $taxonomy = '';

    /** @var string */
    public $date_published = '';

    /** @var string */
    public $date_modified = '';

    /** @var string */
    public $search_query = '';

    /** @var int */
    public $page_number = 1;

    /** @var int */
    public $total_pages = 1;

    /** @var bool */
    public $is_paged = false;

    /** @var string */
    public $site_name = '';

    /** @var string */
    public $site_description = '';

    /** @var string */
    public $sep = '-';

    /** @var string|null */
    public $featured_image = null;

    /** @var int|null */
    public $featured_image_id = null;

    /** @var bool */
    public $robots_noindex = false;

    /** @var bool */
    public $robots_nofollow = false;

    /** @var bool */
    public $robots_noarchive = false;

    /** @var bool */
    public $robots_nosnippet = false;

    /** @var bool */
    public $robots_noimageindex = false;

    /** @var string|null */
    public $og_title = null;

    /** @var string|null */
    public $og_description = null;

    /** @var string|null */
    public $og_image = null;

    /** @var string */
    public $og_type = 'article';

    /** @var string|null */
    public $twitter_title = null;

    /** @var string|null */
    public $twitter_description = null;

    /** @var string|null */
    public $twitter_image = null;

    /** @var string */
    public $twitter_card = 'summary_large_image';

    /** @var array Additional custom context variables */
    public $extra = [];

    /**
     * Convert context to flat key-value array for variable resolution.
     *
     * @return array
     */
    public function toArray() {
        return array_merge([
            'page_type'          => $this->page_type,
            'object_id'          => $this->object_id,
            'object_type'        => $this->object_type,
            'object_sub_type'    => $this->object_sub_type,
            'title'              => $this->title,
            'excerpt'            => $this->excerpt,
            'permalink'          => $this->permalink,
            'canonical_url'      => $this->canonical_url,
            'author_name'        => $this->author_name,
            'author'             => $this->author_name,
            'category'           => $this->category,
            'tag'                => $this->tag,
            'term'               => $this->term_name,
            'taxonomy'           => $this->taxonomy,
            'date'               => $this->date_published,
            'modified'           => $this->date_modified,
            'search_query'       => $this->search_query,
            'searchphrase'       => $this->search_query,
            'page'               => $this->page_number > 1 ? sprintf(__('Page %d of %d', 'apexseo'), $this->page_number, $this->total_pages) : '',
            'pagenumber'         => $this->page_number,
            'pt_single'          => $this->object_sub_type,
            'pt_plural'          => $this->object_sub_type . 's',
            'post_type'          => $this->object_sub_type,
            'sitename'           => $this->site_name,
            'sitedesc'           => $this->site_description,
            'sep'                => $this->sep,
            'featured_image'     => $this->featured_image,
            'robots_noindex'     => $this->robots_noindex,
            'robots_nofollow'    => $this->robots_nofollow,
            'currentyear'        => date('Y'),
        ], $this->extra);
    }
}
