<?php
namespace ApexSEO\SEO\Robots;

use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Virtual Robots.txt Generator and AI Crawler Directive Manager (APEX-025, APEX-026).
 *
 * Implements RFC 9309 compliant virtual robots.txt with:
 * - Dynamic Sitemap directive injection
 * - AI/LLM crawler rule management (GPTBot, CCBot, Google-Extended, ClaudeBot, etc.)
 * - Safe user-customized robots.txt rules editor with syntax validation
 * - Deterministic User-agent grouping and line ordering
 */
class RobotsTxtManager {
    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config;

    /**
     * Known AI / LLM crawler user-agent identifiers (APEX-026).
     *
     * @var array<string, string>
     */
    const AI_CRAWLERS = [
        'GPTBot'              => 'OpenAI Data Collector',
        'ChatGPT-User'        => 'OpenAI ChatGPT Browsing',
        'CCBot'               => 'Common Crawl Bot',
        'anthropic-ai'        => 'Anthropic AI Engine',
        'ClaudeBot'           => 'Anthropic Claude Crawler',
        'Claude-Web'          => 'Anthropic Web Client',
        'Google-Extended'     => 'Google Gemini / Vertex AI Training',
        'Bytespider'          => 'ByteDance AI Crawler',
        'PerplexityBot'       => 'Perplexity AI Search Bot',
        'FacebookBot'         => 'Meta AI Crawler',
        'Meta-ExternalAgent'  => 'Meta External Agent',
        'Diffbot'             => 'Diffbot AI Extractor',
        'cohere-ai'           => 'Cohere AI Engine',
    ];

    /**
     * Option keys.
     */
    const OPTION_CUSTOM_RULES = 'apexseo_robotstxt_custom_rules';
    const OPTION_BLOCK_AI     = 'apexseo_robotstxt_block_ai';
    const OPTION_AI_RULES     = 'apexseo_robotstxt_ai_rules';

    /**
     * Constructor.
     *
     * @param ConfigurationManager|null $config
     */
    public function __construct($config = null) {
        $this->config = $config;
    }

    /**
     * Filter WordPress robots_txt output hook.
     *
     * @param string $output Default WordPress robots.txt content
     * @param bool|int $public Blog public option value
     * @return string RFC 9309 compliant robots.txt output
     */
    public function filterRobotsTxt($output, $public = 1) {
        // If site is set to discourage search engines (blog_public = 0), WordPress handles Disallow: /
        if ((int) $public === 0) {
            return "User-agent: *\nDisallow: /\n";
        }

        return $this->generate();
    }

    /**
     * Generate complete virtual robots.txt content.
     *
     * @return string
     */
    public function generate() {
        $lines = [];

        // 1. General User-agent group
        $lines[] = 'User-agent: *';
        $lines[] = 'Disallow: /wp-admin/';
        $lines[] = 'Allow: /wp-admin/admin-ajax.php';

        // 2. AI / LLM Crawler Directives (APEX-026)
        $aiDirectives = $this->getAiCrawlerDirectives();
        if (!empty($aiDirectives)) {
            $lines[] = '';
            $lines[] = '# AI and LLM Crawler Directives (APEX-026)';
            foreach ($aiDirectives as $bot => $action) {
                $lines[] = 'User-agent: ' . $bot;
                $lines[] = ($action === 'allow' ? 'Allow: /' : 'Disallow: /');
            }
        }

        // 3. User Custom Rules (APEX-025)
        $customRules = $this->getCustomRules();
        if (!empty($customRules)) {
            $lines[] = '';
            $lines[] = '# Custom User Directives';
            $lines[] = $customRules;
        }

        // 4. Sitemap Reference
        $sitemapUrl = $this->getSitemapUrl();
        if (!empty($sitemapUrl)) {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . $sitemapUrl;
        }

        $result = implode("\n", $lines) . "\n";

        if (function_exists('apply_filters')) {
            $result = apply_filters('apexseo_robots_txt_content', $result);
        }

        return $result;
    }

    /**
     * Get active AI crawler rules map.
     *
     * @return array<string, string> Bot => 'allow'|'disallow'
     */
    public function getAiCrawlerDirectives() {
        $directives = [];
        $blockAllAi = false;

        if ($this->config !== null) {
            $blockAllAi = (bool) $this->config->get('block_all_ai_crawlers', false);
            $customAiMap = (array) $this->config->get('ai_crawler_rules', []);
        } else {
            $blockAllAi = function_exists('get_option') ? (bool) get_option(self::OPTION_BLOCK_AI, false) : false;
            $customAiMap = function_exists('get_option') ? (array) get_option(self::OPTION_AI_RULES, []) : [];
        }

        foreach (array_keys(self::AI_CRAWLERS) as $bot) {
            if ($blockAllAi) {
                $directives[$bot] = 'disallow';
            } elseif (isset($customAiMap[$bot])) {
                $directives[$bot] = strtolower($customAiMap[$bot]) === 'allow' ? 'allow' : 'disallow';
            }
        }

        return $directives;
    }

    /**
     * Get validated user-custom rules string.
     *
     * @return string
     */
    public function getCustomRules() {
        $raw = '';
        if ($this->config !== null) {
            $raw = (string) $this->config->get('robotstxt_custom_rules', '');
        } elseif (function_exists('get_option')) {
            $raw = (string) get_option(self::OPTION_CUSTOM_RULES, '');
        }

        return $this->sanitizeCustomRules($raw);
    }

    /**
     * Sanitize and validate custom robots.txt rule text.
     * Strips invalid directives, control characters, and dangerous payload injections.
     *
     * @param string $rules
     * @return string
     */
    public function sanitizeCustomRules($rules) {
        if (empty($rules)) {
            return '';
        }

        $sanitizedLines = [];
        $allowedDirectives = ['user-agent', 'disallow', 'allow', 'crawl-delay', 'sitemap', 'clean-param', 'host'];

        $lines = preg_split('/\r\n|\r|\n/', $rules);
        if (!is_array($lines)) {
            return '';
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Preserve comment lines
            if (strpos($line, '#') === 0) {
                $sanitizedLines[] = preg_replace('/[^\x20-\x7E]/', '', $line);
                continue;
            }

            if (empty($line)) {
                $sanitizedLines[] = '';
                continue;
            }

            // Check key: value structure
            if (strpos($line, ':') !== false) {
                list($key, $val) = explode(':', $line, 2);
                $key = strtolower(trim($key));
                $val = trim($val);

                if (in_array($key, $allowedDirectives, true)) {
                    // Strip non-printable ASCII
                    $valClean = preg_replace('/[^\x20-\x7E]/', '', $val);
                    $keyProper = ucfirst($key);
                    if ($key === 'user-agent') {
                        $keyProper = 'User-agent';
                    } elseif ($key === 'crawl-delay') {
                        $keyProper = 'Crawl-delay';
                    } elseif ($key === 'clean-param') {
                        $keyProper = 'Clean-param';
                    }
                    $sanitizedLines[] = $keyProper . ': ' . $valClean;
                }
            }
        }

        return implode("\n", $sanitizedLines);
    }

    /**
     * Get XML Sitemap URL for robots.txt reference.
     *
     * @return string
     */
    public function getSitemapUrl() {
        if (function_exists('home_url')) {
            return home_url('/sitemap_index.xml');
        }
        return 'http://localhost/sitemap_index.xml';
    }
}
