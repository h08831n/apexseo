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

            // 3. Custom Field lookup %%cf_<custom_field_name>%%
            if (strpos($key, 'cf_') === 0 && !empty($contextArray['object_id'])) {
                $metaKey = substr($key, 3);
                if (function_exists('get_post_meta')) {
                    $metaVal = get_post_meta($contextArray['object_id'], $metaKey, true);
                    if (is_scalar($metaVal)) {
                        return (string) $metaVal;
                    }
                }
            }

            // 4. Default fallback
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
