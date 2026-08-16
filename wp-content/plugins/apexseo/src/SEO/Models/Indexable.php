<?php
namespace ApexSEO\SEO\Models;

/**
 * Data Model representing an Indexable record in wp_apex_indexables.
 */
class Indexable {
    /** @var int|null */
    public $id = null;

    /** @var int */
    public $object_id = 0;

    /** @var string */
    public $object_type = 'post';

    /** @var string */
    public $object_sub_type = 'post';

    /** @var string */
    public $permalink = '';

    /** @var string */
    public $permalink_hash = '';

    /** @var string|null */
    public $title = null;

    /** @var string|null */
    public $description = null;

    /** @var string|null */
    public $canonical_url = null;

    /** @var bool */
    public $is_robots_noindex = false;

    /** @var bool */
    public $is_robots_nofollow = false;

    /** @var bool */
    public $is_robots_noarchive = false;

    /** @var bool */
    public $is_robots_nosnippet = false;

    /** @var bool */
    public $is_robots_noimageindex = false;

    /** @var string|null */
    public $og_title = null;

    /** @var string|null */
    public $og_description = null;

    /** @var string|null */
    public $og_image = null;

    /** @var int|null */
    public $og_image_id = null;

    /** @var string|null */
    public $twitter_title = null;

    /** @var string|null */
    public $twitter_description = null;

    /** @var string|null */
    public $twitter_image = null;

    /** @var string|null */
    public $primary_focus_keyword = null;

    /** @var array */
    public $secondary_keywords = [];

    /** @var int */
    public $seo_score = 0;

    /** @var int */
    public $readability_score = 0;

    /** @var int */
    public $link_count_internal = 0;

    /** @var int */
    public $link_count_inbound = 0;

    /** @var int */
    public $link_count_external = 0;

    /** @var bool */
    public $is_cornerstone = false;

    /** @var string */
    public $schema_type = 'Article';

    /** @var string|null */
    public $created_at = null;

    /** @var string|null */
    public $updated_at = null;

    /**
     * Hydrate model from database array/object.
     *
     * @param array|object $data
     * @return self
     */
    public static function fromArray($data) {
        $data = (array) $data;
        $model = new self();

        foreach ($data as $key => $val) {
            if (property_exists($model, $key)) {
                if (in_array($key, ['is_robots_noindex', 'is_robots_nofollow', 'is_robots_noarchive', 'is_robots_nosnippet', 'is_robots_noimageindex', 'is_cornerstone'], true)) {
                    $model->$key = (bool) $val;
                } elseif ($key === 'secondary_keywords' && is_string($val)) {
                    $decoded = json_decode($val, true);
                    $model->$key = is_array($decoded) ? $decoded : [];
                } elseif (in_array($key, ['id', 'object_id', 'og_image_id', 'seo_score', 'readability_score', 'link_count_internal', 'link_count_inbound', 'link_count_external'], true)) {
                    $model->$key = $val !== null ? (int) $val : null;
                } else {
                    $model->$key = $val;
                }
            }
        }

        if (empty($model->permalink_hash) && !empty($model->permalink)) {
            $model->permalink_hash = md5($model->permalink);
        }

        return $model;
    }

    /**
     * Convert model to array for database persistence.
     *
     * @return array
     */
    public function toArray() {
        return [
            'object_id'              => (int) $this->object_id,
            'object_type'            => (string) $this->object_type,
            'object_sub_type'        => (string) $this->object_sub_type,
            'permalink'              => (string) $this->permalink,
            'permalink_hash'         => !empty($this->permalink_hash) ? $this->permalink_hash : md5($this->permalink),
            'title'                  => $this->title,
            'description'            => $this->description,
            'canonical_url'          => $this->canonical_url,
            'is_robots_noindex'      => $this->is_robots_noindex ? 1 : 0,
            'is_robots_nofollow'     => $this->is_robots_nofollow ? 1 : 0,
            'is_robots_noarchive'    => $this->is_robots_noarchive ? 1 : 0,
            'is_robots_nosnippet'    => $this->is_robots_nosnippet ? 1 : 0,
            'is_robots_noimageindex' => $this->is_robots_noimageindex ? 1 : 0,
            'og_title'               => $this->og_title,
            'og_description'         => $this->og_description,
            'og_image'               => $this->og_image,
            'og_image_id'            => $this->og_image_id,
            'twitter_title'          => $this->twitter_title,
            'twitter_description'    => $this->twitter_description,
            'twitter_image'          => $this->twitter_image,
            'primary_focus_keyword'  => $this->primary_focus_keyword,
            'secondary_keywords'     => !empty($this->secondary_keywords) ? json_encode($this->secondary_keywords) : null,
            'seo_score'              => (int) $this->seo_score,
            'readability_score'      => (int) $this->readability_score,
            'link_count_internal'    => (int) $this->link_count_internal,
            'link_count_inbound'     => (int) $this->link_count_inbound,
            'link_count_external'    => (int) $this->link_count_external,
            'is_cornerstone'         => $this->is_cornerstone ? 1 : 0,
            'schema_type'            => (string) $this->schema_type,
        ];
    }
}
