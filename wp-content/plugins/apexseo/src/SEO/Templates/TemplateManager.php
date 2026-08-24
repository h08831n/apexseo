<?php
namespace ApexSEO\SEO\Templates;

use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Manages default SEO title, description, and robots rules per post type, taxonomy, and special archive.
 */
class TemplateManager {
    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config;

    /**
     * Default title templates.
     *
     * @var array<string, string>
     */
    protected $defaultTitles = [
        'front_page' => '%%sitename%% %%sep%% %%sitedesc%%',
        'home'       => '%%sitename%% %%sep%% %%sitedesc%%',
        'post'       => '%%title%% %%sep%% %%sitename%%',
        'page'       => '%%title%% %%sep%% %%sitename%%',
        'product'    => '%%title%% %%sep%% %%sitename%%',
        'category'   => '%%term%% Archives %%sep%% %%sitename%%',
        'post_tag'   => '%%term%% Tag Archives %%sep%% %%sitename%%',
        'author'     => '%%author%%, Author at %%sitename%%',
        'date'       => '%%date%% Archives %%sep%% %%sitename%%',
        'archive'    => '%%post_type%% Archives %%sep%% %%sitename%%',
        'search'     => 'Search Results for "%%searchphrase%%" %%sep%% %%sitename%%',
        '404'        => 'Page Not Found (404) %%sep%% %%sitename%%',
    ];

    /**
     * Default description templates.
     *
     * @var array<string, string>
     */
    protected $defaultDescriptions = [
        'front_page' => '%%sitedesc%%',
        'home'       => '%%sitedesc%%',
        'post'       => '%%excerpt%%',
        'page'       => '%%excerpt%%',
        'product'    => '%%excerpt%%',
        'category'   => 'Browse all articles filed under %%term%% on %%sitename%%.',
        'post_tag'   => 'Browse articles tagged with %%term%% on %%sitename%%.',
        'author'     => 'Read articles and publications written by %%author%% on %%sitename%%.',
        'search'     => 'Search results for %%searchphrase%% on %%sitename%%.',
    ];

    /**
     * Default robots noindex rules.
     *
     * @var array<string, bool>
     */
    protected $defaultNoindex = [
        'search' => true,
        '404'    => true,
    ];

    /**
     * Constructor.
     *
     * @param ConfigurationManager|null $config
     */
    public function __construct($config = null) {
        $this->config = $config;
    }

    /**
     * Get title template for a given key or object type.
     *
     * @param string $key
     * @return string
     */
    public function getTitleTemplate($key) {
        if ($this->config !== null) {
            $custom = $this->config->get("seo.templates.title.{$key}", null);
            if (!empty($custom)) {
                return (string) $custom;
            }
        }

        if (isset($this->defaultTitles[$key])) {
            return $this->defaultTitles[$key];
        }

        return '%%title%% %%sep%% %%sitename%%';
    }

    /**
     * Get description template for a given key or object type.
     *
     * @param string $key
     * @return string
     */
    public function getDescriptionTemplate($key) {
        if ($this->config !== null) {
            $custom = $this->config->get("seo.templates.description.{$key}", null);
            if (!empty($custom)) {
                return (string) $custom;
            }
        }

        if (isset($this->defaultDescriptions[$key])) {
            return $this->defaultDescriptions[$key];
        }

        return '%%excerpt%%';
    }

    /**
     * Determine if a context type should default to noindex.
     *
     * @param string $key
     * @return bool
     */
    public function isDefaultNoindex($key) {
        if ($this->config !== null) {
            $custom = $this->config->get("seo.noindex.{$key}", null);
            if ($custom !== null) {
                return (bool) $custom;
            }
        }

        return isset($this->defaultNoindex[$key]) ? $this->defaultNoindex[$key] : false;
    }

    /**
     * Get title separator character.
     *
     * @return string
     */
    public function getTitleSeparator() {
        if ($this->config !== null) {
            $sep = $this->config->get('seo.separator', null);
            if (!empty($sep)) {
                return (string) $sep;
            }
        }
        return '-';
    }

    /**
     * Get pagination title modifier template (APEX-012).
     *
     * @return string
     */
    public function getPageModifierTemplate() {
        if ($this->config !== null) {
            $modifier = $this->config->get('seo.templates.page_modifier', null);
            if (!empty($modifier)) {
                return (string) $modifier;
            }
        }
        return '%%sep%% Page %%pagenumber%% of %%total_pages%%';
    }

    /**
     * Get RSS feed content header template (APEX-015).
     *
     * @return string
     */
    public function getRssHeaderTemplate() {
        if ($this->config !== null) {
            $header = $this->config->get('seo.rss.header', null);
            if ($header !== null) {
                return (string) $header;
            }
        }
        return '';
    }

    /**
     * Get RSS feed content footer template (APEX-015).
     *
     * @return string
     */
    public function getRssFooterTemplate() {
        if ($this->config !== null) {
            $footer = $this->config->get('seo.rss.footer', null);
            if ($footer !== null) {
                return (string) $footer;
            }
        }
        return '<p>The post %%post_link%% appeared first on %%blog_link%%.</p>';
    }
}
