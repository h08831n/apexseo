<?php
namespace ApexSEO\SEO\Variables;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * High-Performance SEO Dynamic Template Variable Engine.
 */
class VariableEngine implements ServiceContractInterface {
    /**
     * Custom registered variable callbacks.
     *
     * @var array<string, callable>
     */
    protected $customVariables = [];

    /**
     * Register a custom replacement variable token.
     *
     * @param string $token Token name without %% delimiters (e.g. 'custom_field').
     * @param callable $callback Resolver callback receiving ($context, $token).
     * @return self
     */
    public function registerVariable($token, callable $callback) {
        $this->customVariables[strtolower(trim($token, '%'))] = $callback;
        return $this;
    }

    /**
     * Replace all dynamic template variables in a text string.
     *
     * @param string $template Text with %%var%% tokens.
     * @param array $context Context dictionary (e.g. ['post_id' => 123, 'post' => $post, 'term_id' => 5]).
     * @return string Interpolated text.
     */
    public function replace($template, array $context = []) {
        if (empty($template) || strpos($template, '%%') === false) {
            return (string) $template;
        }

        return preg_replace_callback('/%%([a-zA-Z0-9_\-]+)%%/', function($matches) use ($context) {
            $token = strtolower($matches[1]);
            return $this->resolveToken($token, $context);
        }, $template);
    }

    /**
     * Resolve a single token value.
     *
     * @param string $token Token name.
     * @param array $context
     * @return string
     */
    public function resolveToken($token, array $context = []) {
        // 1. Custom registered callbacks take top priority
        if (isset($this->customVariables[$token])) {
            return (string) call_user_func($this->customVariables[$token], $context, $token);
        }

        // 2. Core Site & System Variables
        switch ($token) {
            case 'sitename':
                return function_exists('get_bloginfo') ? (string) get_bloginfo('name') : 'Apex SEO Site';
            case 'sitedesc':
                return function_exists('get_bloginfo') ? (string) get_bloginfo('description') : '';
            case 'siteurl':
                return function_exists('home_url') ? (string) home_url() : 'https://example.com';
            case 'currentyear':
                return date('Y');
            case 'currentmonth':
                return date('F');
            case 'currentdate':
                return date('F j, Y');
            case 'sep':
                return isset($context['sep']) ? (string) $context['sep'] : '-';
        }

        // Context object inspection (Post/Term)
        $post = isset($context['post']) ? $context['post'] : null;
        $postId = isset($context['post_id']) ? (int) $context['post_id'] : (isset($post->ID) ? (int) $post->ID : 0);

        // 3. Post / Page Variables
        if ($postId > 0 || $post !== null) {
            switch ($token) {
                case 'title':
                    if (isset($context['title'])) {
                        return (string) $context['title'];
                    }
                    if (function_exists('get_the_title')) {
                        return (string) get_the_title($postId);
                    }
                    return isset($post->post_title) ? (string) $post->post_title : '';

                case 'excerpt':
                    if (isset($context['excerpt'])) {
                        return (string) $context['excerpt'];
                    }
                    if (function_exists('get_the_excerpt')) {
                        return (string) wp_strip_all_tags(get_the_excerpt($postId));
                    }
                    if (isset($post->post_excerpt) && !empty($post->post_excerpt)) {
                        return (string) wp_strip_all_tags($post->post_excerpt);
                    }
                    if (isset($post->post_content)) {
                        return (string) substr(wp_strip_all_tags($post->post_content), 0, 160);
                    }
                    return '';

                case 'date':
                    if (function_exists('get_the_date')) {
                        return (string) get_the_date('', $postId);
                    }
                    return isset($post->post_date) ? (string) date('F j, Y', strtotime($post->post_date)) : '';

                case 'modified':
                    if (function_exists('get_the_modified_date')) {
                        return (string) get_the_modified_date('', $postId);
                    }
                    return isset($post->post_modified) ? (string) date('F j, Y', strtotime($post->post_modified)) : '';

                case 'id':
                    return (string) $postId;

                case 'slug':
                    return isset($post->post_name) ? (string) $post->post_name : '';

                case 'author_name':
                    if (function_exists('get_the_author_meta') && isset($post->post_author)) {
                        return (string) get_the_author_meta('display_name', $post->post_author);
                    }
                    return isset($context['author_name']) ? (string) $context['author_name'] : 'Admin';

                case 'category':
                    if (function_exists('get_the_category')) {
                        $cats = get_the_category($postId);
                        return !empty($cats) && isset($cats[0]->name) ? (string) $cats[0]->name : '';
                    }
                    return isset($context['category']) ? (string) $context['category'] : '';

                case 'tag':
                    if (function_exists('get_the_tags')) {
                        $tags = get_the_tags($postId);
                        return !empty($tags) && isset($tags[0]->name) ? (string) $tags[0]->name : '';
                    }
                    return isset($context['tag']) ? (string) $context['tag'] : '';
            }

            // Custom Field tokens: %%cf_field_name%%
            if (strpos($token, 'cf_') === 0) {
                $fieldName = substr($token, 3);
                if (function_exists('get_post_meta')) {
                    $val = get_post_meta($postId, $fieldName, true);
                    return is_scalar($val) ? (string) $val : '';
                }
            }
        }

        // 4. Term / Taxonomy Variables
        if (isset($context['term_id']) || isset($context['term'])) {
            switch ($token) {
                case 'term_title':
                    return isset($context['term_title']) ? (string) $context['term_title'] : '';
                case 'term_description':
                    return isset($context['term_description']) ? (string) $context['term_description'] : '';
            }
        }

        return '';
    }
}
