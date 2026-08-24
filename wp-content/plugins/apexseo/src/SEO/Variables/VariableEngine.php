<?php
namespace ApexSEO\SEO\Variables;

use ApexSEO\SEO\Models\SeoContext;

/**
 * Dynamic Template Variable Engine for SEO title and description interpolation.
 */
class VariableEngine {
    /**
     * Custom registered variable resolvers.
     *
     * @var array<string, callable>
     */
    protected $resolvers = [];

    /**
     * Default fallback token values.
     *
     * @var array<string, string>
     */
    protected $defaults = [
        'sep' => '-',
    ];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->registerCoreVariables();
    }

    /**
     * Register core dynamic variable resolvers.
     *
     * @return void
     */
    protected function registerCoreVariables() {
        $this->registerVariable('currentyear', function() {
            return date('Y');
        });

        $this->registerVariable('currentmonth', function() {
            return date('F');
        });

        $this->registerVariable('currentday', function() {
            return date('j');
        });

        $this->registerVariable('date', function($ctx) {
            return isset($ctx['date']) ? $ctx['date'] : date(get_option('date_format', 'F j, Y'));
        });

        $this->registerVariable('modified', function($ctx) {
            return isset($ctx['modified']) ? $ctx['modified'] : (isset($ctx['date']) ? $ctx['date'] : '');
        });

        $this->registerVariable('sitename', function($ctx) {
            return !empty($ctx['sitename']) ? $ctx['sitename'] : get_option('blogname', 'WordPress');
        });

        $this->registerVariable('sitedesc', function($ctx) {
            return !empty($ctx['sitedesc']) ? $ctx['sitedesc'] : get_option('blogdescription', '');
        });

        $this->registerVariable('sep', function($ctx) {
            return !empty($ctx['sep']) ? $ctx['sep'] : '-';
        });

        $this->registerVariable('title', function($ctx) {
            return isset($ctx['title']) ? $ctx['title'] : '';
        });

        $this->registerVariable('excerpt', function($ctx) {
            return isset($ctx['excerpt']) ? $ctx['excerpt'] : '';
        });

        $this->registerVariable('author', function($ctx) {
            return isset($ctx['author']) ? $ctx['author'] : (isset($ctx['author_name']) ? $ctx['author_name'] : '');
        });

        $this->registerVariable('author_name', function($ctx) {
            return isset($ctx['author_name']) ? $ctx['author_name'] : (isset($ctx['author']) ? $ctx['author'] : '');
        });

        $this->registerVariable('category', function($ctx) {
            return isset($ctx['category']) ? $ctx['category'] : '';
        });

        $this->registerVariable('tag', function($ctx) {
            return isset($ctx['tag']) ? $ctx['tag'] : '';
        });

        $this->registerVariable('term', function($ctx) {
            return isset($ctx['term']) ? $ctx['term'] : (isset($ctx['term_name']) ? $ctx['term_name'] : '');
        });

        $this->registerVariable('post_type', function($ctx) {
            return isset($ctx['post_type']) ? $ctx['post_type'] : '';
        });

        $this->registerVariable('page', function($ctx) {
            return isset($ctx['page']) ? $ctx['page'] : '';
        });

        $this->registerVariable('pagenumber', function($ctx) {
            return isset($ctx['pagenumber']) ? (string) $ctx['pagenumber'] : '1';
        });

        $this->registerVariable('searchphrase', function($ctx) {
            return isset($ctx['searchphrase']) ? $ctx['searchphrase'] : (isset($ctx['search_query']) ? $ctx['search_query'] : '');
        });

        $this->registerVariable('search_query', function($ctx) {
            return isset($ctx['search_query']) ? $ctx['search_query'] : (isset($ctx['searchphrase']) ? $ctx['searchphrase'] : '');
        });

        $this->registerVariable('total_pages', function($ctx) {
            return isset($ctx['total_pages']) ? (string) $ctx['total_pages'] : (isset($ctx['max_num_pages']) ? (string) $ctx['max_num_pages'] : '1');
        });

        $this->registerVariable('max_page', function($ctx) {
            return isset($ctx['total_pages']) ? (string) $ctx['total_pages'] : (isset($ctx['max_num_pages']) ? (string) $ctx['max_num_pages'] : '1');
        });

        $this->registerVariable('term_description', function($ctx) {
            return isset($ctx['term_description']) ? $ctx['term_description'] : (isset($ctx['excerpt']) ? $ctx['excerpt'] : '');
        });

        $this->registerVariable('taxonomy', function($ctx) {
            return isset($ctx['taxonomy']) ? $ctx['taxonomy'] : (isset($ctx['object_sub_type']) ? $ctx['object_sub_type'] : '');
        });

        $this->registerVariable('author_bio', function($ctx) {
            return isset($ctx['author_bio']) ? $ctx['author_bio'] : (isset($ctx['excerpt']) ? $ctx['excerpt'] : '');
        });

        $this->registerVariable('post_link', function($ctx) {
            $url = isset($ctx['permalink']) ? $ctx['permalink'] : (isset($ctx['url']) ? $ctx['url'] : '');
            $title = isset($ctx['title']) ? $ctx['title'] : (isset($ctx['post_title']) ? $ctx['post_title'] : 'Post');
            return '<a href="' . esc_url($url) . '">' . esc_html($title) . '</a>';
        });

        $this->registerVariable('post_title', function($ctx) {
            return isset($ctx['title']) ? $ctx['title'] : (isset($ctx['post_title']) ? $ctx['post_title'] : '');
        });

        $this->registerVariable('blog_link', function($ctx) {
            $url = function_exists('home_url') ? home_url('/') : 'http://localhost';
            $sitename = !empty($ctx['sitename']) ? $ctx['sitename'] : (function_exists('get_option') ? get_option('blogname', 'WordPress') : 'WordPress');
            return '<a href="' . esc_url($url) . '">' . esc_html($sitename) . '</a>';
        });

        $this->registerVariable('blog_title', function($ctx) {
            return !empty($ctx['sitename']) ? $ctx['sitename'] : (function_exists('get_option') ? get_option('blogname', 'WordPress') : 'WordPress');
        });

        $this->registerVariable('author_link', function($ctx) {
            $authorName = isset($ctx['author_name']) ? $ctx['author_name'] : (isset($ctx['author']) ? $ctx['author'] : 'Author');
            $authorId = isset($ctx['author_id']) ? (int) $ctx['author_id'] : 0;
            $url = ($authorId > 0 && function_exists('get_author_posts_url')) ? get_author_posts_url($authorId) : '#';
            return '<a href="' . esc_url($url) . '">' . esc_html($authorName) . '</a>';
        });
    }

    /**
     * Register a custom variable handler.
     *
     * @param string $variableName Variable key without %% (e.g. 'brand_name')
     * @param callable $callback
     * @return self
     */
    public function registerVariable($variableName, $callback) {
        $this->resolvers[strtolower(trim($variableName))] = $callback;
        return $this;
    }

    /**
     * Replace dynamic tokens in a template string given an evaluation context.
     *
     * @param string $template Template string containing %%token%%
     * @param SeoContext|array $context Context object or key-value array
     * @return string Parsed and interpolated string
     */
    public function replace($template, $context = []) {
        if (empty($template)) {
            return '';
        }

        if ($context instanceof SeoContext) {
            $contextArray = $context->toArray();
        } else {
            $contextArray = (array) $context;
        }

        // Allow third-party plugins to register or alter context via WP Filter
        if (function_exists('apply_filters')) {
            $contextArray = apply_filters('apexseo_replacement_context', $contextArray, $template);
        }

        // Replace all %%key%% tokens
        $replaced = preg_replace_callback('/%%([a-zA-Z0-9_\-]+)%%/', function($matches) use ($contextArray) {
            $key = strtolower($matches[1]);

            // 1. Direct match in provided context array
            if (isset($contextArray[$key]) && is_scalar($contextArray[$key])) {
                return (string) $contextArray[$key];
            }

            // 2. Match in registered resolver callbacks
            if (isset($this->resolvers[$key]) && is_callable($this->resolvers[$key])) {
                $val = call_user_func($this->resolvers[$key], $contextArray);
                if (is_scalar($val)) {
                    return (string) $val;
                }
            }

            // 3. Custom Field lookup %%cf_<name>%% or %%custom_field_<name>%%
            if ((strpos($key, 'cf_') === 0 || strpos($key, 'custom_field_') === 0) && !empty($contextArray['object_id'])) {
                $metaKey = strpos($key, 'cf_') === 0 ? substr($key, 3) : substr($key, 13);
                $objId = (int) $contextArray['object_id'];
                
                // Try ACF if available
                if (function_exists('get_field')) {
                    $acfVal = get_field($metaKey, $objId);
                    if (is_scalar($acfVal)) {
                        return (string) $acfVal;
                    }
                }
                
                if (function_exists('get_post_meta')) {
                    $metaVal = get_post_meta($objId, $metaKey, true);
                    if (is_scalar($metaVal)) {
                        return (string) $metaVal;
                    }
                }
            }

            // 4. Term Meta lookup %%ct_<name>%% or %%term_meta_<name>%%
            if ((strpos($key, 'ct_') === 0 || strpos($key, 'term_meta_') === 0) && !empty($contextArray['object_id'])) {
                $metaKey = strpos($key, 'ct_') === 0 ? substr($key, 3) : substr($key, 10);
                $termId = (int) $contextArray['object_id'];
                if (function_exists('get_term_meta')) {
                    $metaVal = get_term_meta($termId, $metaKey, true);
                    if (is_scalar($metaVal)) {
                        return (string) $metaVal;
                    }
                }
            }

            // 5. User / Author Meta lookup %%um_<name>%% or %%user_meta_<name>%%
            if ((strpos($key, 'um_') === 0 || strpos($key, 'user_meta_') === 0)) {
                $metaKey = strpos($key, 'um_') === 0 ? substr($key, 3) : substr($key, 10);
                $userId = !empty($contextArray['author_id']) ? (int) $contextArray['author_id'] : (!empty($contextArray['object_id']) ? (int) $contextArray['object_id'] : 0);
                if ($userId > 0 && function_exists('get_user_meta')) {
                    $metaVal = get_user_meta($userId, $metaKey, true);
                    if (is_scalar($metaVal)) {
                        return (string) $metaVal;
                    }
                }
            }

            // 6. Default fallback
            if (isset($this->defaults[$key])) {
                return $this->defaults[$key];
            }

            return '';
        }, $template);

        // Normalize whitespace and dangling separators (e.g. " - " at end or " - - ")
        $replaced = $this->cleanDanglingSeparators($replaced, isset($contextArray['sep']) ? $contextArray['sep'] : '-');

        if (function_exists('apply_filters')) {
            $replaced = apply_filters('apexseo_replace_vars', $replaced, $template, $contextArray);
        }

        return trim($replaced);
    }

    /**
     * Clean up dangling, duplicate, or leading/trailing separators created by empty tokens.
     *
     * @param string $str
     * @param string $sep
     * @return string
     */
    protected function cleanDanglingSeparators($str, $sep = '-') {
        $sepEscaped = preg_quote(trim($sep), '/');
        if (empty($sepEscaped)) {
            $sepEscaped = '\-';
        }

        // Collapse multiple spaces
        $str = preg_replace('/\s+/', ' ', $str);

        // Collapse duplicate separators: e.g. " | | " -> " | "
        $str = preg_replace('/(\s*' . $sepEscaped . '\s*){2,}/', ' ' . trim($sep) . ' ', $str);

        // Remove leading separator: " | Title" -> "Title"
        $str = preg_replace('/^\s*' . $sepEscaped . '\s*/', '', $str);

        // Remove trailing separator: "Title | " -> "Title"
        $str = preg_replace('/\s*' . $sepEscaped . '\s*$/', '', $str);

        return trim($str);
    }
}
