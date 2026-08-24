<?php
namespace ApexSEO\SEO\Social;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * Renders high-fidelity Open Graph meta tags (APEX-031, APEX-032, APEX-034, APEX-035, APEX-037, APEX-039).
 */
class OpenGraphPresenter {
    /**
     * Variable engine.
     *
     * @var VariableEngine
     */
    protected $variableEngine;

    /**
     * Configuration manager.
     *
     * @var ConfigurationManager|null
     */
    protected $config;

    /**
     * Option keys.
     */
    const OPTION_DEFAULT_IMAGE = 'apexseo_og_default_image';
    const OPTION_FB_APP_ID     = 'apexseo_fb_app_id';
    const OPTION_FB_ADMINS     = 'apexseo_fb_admins';
    const OPTION_FB_PUBLISHER  = 'apexseo_fb_publisher';
    const OPTION_PINTEREST_VERIFY = 'apexseo_pinterest_verify';

    /**
     * Constructor.
     *
     * @param VariableEngine|null $variableEngine
     * @param ConfigurationManager|null $config
     */
    public function __construct($variableEngine = null, $config = null) {
        $this->variableEngine = $variableEngine !== null ? $variableEngine : new VariableEngine();
        $this->config = $config;
    }

    /**
     * Render OpenGraph meta tags HTML block.
     *
     * @param SeoContext|Indexable|array $context
     * @return string
     */
    public function render($context) {
        $tags = $this->buildTags($context);
        $output = '';

        foreach ($tags as $property => $content) {
            if ($content === null || $content === '') {
                continue;
            }

            if (is_array($content)) {
                foreach ($content as $item) {
                    if ($item !== null && $item !== '') {
                        $output .= $this->formatSingleTag($property, $item);
                    }
                }
            } else {
                $output .= $this->formatSingleTag($property, $content);
            }
        }

        // Pinterest domain verification tag (APEX-039)
        $pinterestVerify = $this->getPinterestVerification();
        if (!empty($pinterestVerify)) {
            $output .= sprintf('<meta name="p:domain_verify" content="%s" />' . "\n", esc_attr($pinterestVerify));
        }

        return $output;
    }

    /**
     * Format a single meta tag line.
     *
     * @param string $property
     * @param string $content
     * @return string
     */
    protected function formatSingleTag($property, $content) {
        if (strpos($property, 'image') !== false || strpos($property, 'url') !== false || $property === 'article:author' || $property === 'article:publisher') {
            $escapedContent = function_exists('esc_url') ? esc_url($content) : htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        } else {
            $escapedContent = function_exists('esc_attr') ? esc_attr($content) : htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        }

        // Use name for fb:app_id and fb:admins if preferred or property
        $attr = (strpos($property, 'fb:') === 0 || strpos($property, 'p:') === 0) ? 'property' : 'property';
        return sprintf('<meta %s="%s" content="%s" />' . "\n", $attr, esc_attr($property), $escapedContent);
    }

    /**
     * Build raw property => value map for Open Graph tags.
     *
     * @param SeoContext|Indexable|array $context
     * @return array<string, mixed>
     */
    public function buildTags($context) {
        $tags = [];

        $siteName = function_exists('get_option') ? get_option('blogname', 'WordPress') : 'WordPress';
        $locale = function_exists('get_locale') ? get_locale() : 'en_US';

        $tags['og:locale'] = $locale;
        $tags['og:site_name'] = $siteName;

        // Facebook App ID and Admins (APEX-035)
        $fbAppId = $this->getFbAppId();
        if (!empty($fbAppId)) {
            $tags['fb:app_id'] = $fbAppId;
        }

        $fbAdmins = $this->getFbAdmins();
        if (!empty($fbAdmins)) {
            $tags['fb:admins'] = $fbAdmins;
        }

        // Resolve context fields
        $title = '';
        $description = '';
        $url = '';
        $type = 'website';
        $image = '';
        $imageWidth = null;
        $imageHeight = null;
        $imageType = null;
        $imageAlt = null;
        $datePublished = null;
        $dateModified = null;
        $authorName = null;
        $section = null;
        $tagsList = [];

        if ($context instanceof Indexable) {
            $type = ($context->object_sub_type === 'post' || $context->object_type === 'post') ? 'article' : 'website';
            $title = !empty($context->og_title) ? $context->og_title : $context->title;
            $description = !empty($context->og_description) ? $context->og_description : $context->description;
            $url = $context->canonical_url ? $context->canonical_url : $context->permalink;
            $image = $this->resolveImageCascade($context->og_image, $context->featured_image);
        } elseif ($context instanceof SeoContext) {
            $type = ($context->page_type === 'single' && $context->object_sub_type !== 'page') ? 'article' : 'website';
            $title = !empty($context->og_title) ? $context->og_title : (!empty($context->title) ? $context->title : $siteName);
            $description = !empty($context->og_description) ? $context->og_description : $context->excerpt;
            $url = !empty($context->canonical_url) ? $context->canonical_url : $context->permalink;
            $image = $this->resolveImageCascade($context->og_image, $context->featured_image);
            $datePublished = $context->date_published;
            $dateModified = $context->date_modified;
            $authorName = $context->author_name;
            $section = !empty($context->category) ? $context->category : null;
        } elseif (is_array($context)) {
            $type = isset($context['og_type']) ? $context['og_type'] : ((isset($context['page_type']) && $context['page_type'] === 'single') ? 'article' : 'website');
            $title = isset($context['og_title']) ? $context['og_title'] : (isset($context['title']) ? $context['title'] : $siteName);
            $description = isset($context['og_description']) ? $context['og_description'] : (isset($context['description']) ? $context['description'] : '');
            $url = isset($context['canonical_url']) ? $context['canonical_url'] : (isset($context['permalink']) ? $context['permalink'] : '');
            $image = $this->resolveImageCascade(
                isset($context['og_image']) ? $context['og_image'] : null,
                isset($context['featured_image']) ? $context['featured_image'] : null
            );
            $datePublished = isset($context['date_published']) ? $context['date_published'] : null;
            $dateModified = isset($context['date_modified']) ? $context['date_modified'] : null;
            $authorName = isset($context['author_name']) ? $context['author_name'] : (isset($context['author']) ? $context['author'] : null);
            $section = isset($context['category']) ? $context['category'] : (isset($context['section']) ? $context['section'] : null);
        }

        $tags['og:type'] = $type;
        $tags['og:title'] = $title;
        $tags['og:description'] = $description;
        $tags['og:url'] = $url;

        // Image tags & dimensions (APEX-032, APEX-034)
        if (!empty($image)) {
            $tags['og:image'] = $image;
            $meta = $this->getImageDimensions($image);
            if (!empty($meta['width'])) {
                $tags['og:image:width'] = (string) $meta['width'];
            }
            if (!empty($meta['height'])) {
                $tags['og:image:height'] = (string) $meta['height'];
            }
            if (!empty($meta['type'])) {
                $tags['og:image:type'] = $meta['type'];
            }
            if (!empty($meta['alt'])) {
                $tags['og:image:alt'] = $meta['alt'];
            }
        }

        // Article specific tags (APEX-037)
        if ($type === 'article') {
            if (!empty($datePublished)) {
                $tags['article:published_time'] = $datePublished;
            }
            if (!empty($dateModified)) {
                $tags['article:modified_time'] = $dateModified;
            }
            if (!empty($authorName)) {
                $tags['article:author'] = $authorName;
            }
            if (!empty($section)) {
                $tags['article:section'] = $section;
            }

            $fbPublisher = $this->getFbPublisher();
            if (!empty($fbPublisher)) {
                $tags['article:publisher'] = $fbPublisher;
            }
        }

        return $tags;
    }

    /**
     * Resolve image fallback cascade (APEX-032, APEX-034):
     * 1. Explicit OG Image
     * 2. Featured Image
     * 3. Site Default Social Image
     *
     * @param string|null $explicitImage
     * @param string|null $featuredImage
     * @return string
     */
    public function resolveImageCascade($explicitImage = null, $featuredImage = null) {
        if (!empty($explicitImage)) {
            return $this->cleanImageUrl($explicitImage);
        }

        if (!empty($featuredImage)) {
            return $this->cleanImageUrl($featuredImage);
        }

        // Site global default social image (APEX-034)
        $defaultImg = $this->getDefaultSocialImage();
        if (!empty($defaultImg)) {
            return $this->cleanImageUrl($defaultImg);
        }

        return '';
    }

    /**
     * Clean and validate social image URL.
     *
     * @param string $url
     * @return string
     */
    public function cleanImageUrl($url) {
        $url = trim($url);
        if (empty($url) || preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return '';
        }
        return $url;
    }

    /**
     * Get image dimensions and mime type (APEX-032).
     *
     * @param string $imageUrl
     * @return array{width: int|null, height: int|null, type: string|null, alt: string|null}
     */
    public function getImageDimensions($imageUrl) {
        $res = [
            'width'  => null,
            'height' => null,
            'type'   => null,
            'alt'    => null,
        ];

        if (empty($imageUrl)) {
            return $res;
        }

        // Default standard OG dimension if not dynamically read
        $res['width'] = 1200;
        $res['height'] = 630;

        $pathExt = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        if ($pathExt === 'png') {
            $res['type'] = 'image/png';
        } elseif ($pathExt === 'webp') {
            $res['type'] = 'image/webp';
        } elseif (in_array($pathExt, ['jpg', 'jpeg'], true)) {
            $res['type'] = 'image/jpeg';
        } elseif ($pathExt === 'gif') {
            $res['type'] = 'image/gif';
        }

        return $res;
    }

    /**
     * Get site default social image URL.
     *
     * @return string
     */
    public function getDefaultSocialImage() {
        if ($this->config !== null) {
            $img = $this->config->get('og_default_image', '');
            if (!empty($img)) {
                return (string) $img;
            }
        }

        if (function_exists('get_option')) {
            return (string) get_option(self::OPTION_DEFAULT_IMAGE, '');
        }

        return '';
    }

    /**
     * Get Facebook App ID (APEX-035).
     *
     * @return string
     */
    public function getFbAppId() {
        if ($this->config !== null) {
            $id = $this->config->get('fb_app_id', '');
            if (!empty($id)) {
                return trim((string) $id);
            }
        }

        if (function_exists('get_option')) {
            return trim((string) get_option(self::OPTION_FB_APP_ID, ''));
        }

        return '';
    }

    /**
     * Get Facebook Admins user IDs (APEX-035).
     *
     * @return string
     */
    public function getFbAdmins() {
        if ($this->config !== null) {
            $val = $this->config->get('fb_admins', '');
            if (!empty($val)) {
                return trim((string) $val);
            }
        }

        if (function_exists('get_option')) {
            return trim((string) get_option(self::OPTION_FB_ADMINS, ''));
        }

        return '';
    }

    /**
     * Get Facebook Publisher Page URL (APEX-037).
     *
     * @return string
     */
    public function getFbPublisher() {
        if ($this->config !== null) {
            $url = $this->config->get('fb_publisher', '');
            if (!empty($url)) {
                return trim((string) $url);
            }
        }

        if (function_exists('get_option')) {
            return trim((string) get_option(self::OPTION_FB_PUBLISHER, ''));
        }

        return '';
    }

    /**
     * Get Pinterest Domain Verification token (APEX-039).
     *
     * @return string
     */
    public function getPinterestVerification() {
        if ($this->config !== null) {
            $token = $this->config->get('pinterest_verify', '');
            if (!empty($token)) {
                return trim((string) $token);
            }
        }

        if (function_exists('get_option')) {
            return trim((string) get_option(self::OPTION_PINTEREST_VERIFY, ''));
        }

        return '';
    }
}
