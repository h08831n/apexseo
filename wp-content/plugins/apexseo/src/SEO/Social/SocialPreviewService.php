<?php
namespace ApexSEO\SEO\Social;

use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\SEO\Meta\TitlePresenter;
use ApexSEO\SEO\Meta\DescriptionPresenter;

/**
 * Generates live social and search SERP previews for the editor (APEX-038).
 */
class SocialPreviewService {
    /**
     * Variable engine.
     *
     * @var VariableEngine
     */
    protected $variableEngine;

    /**
     * Open Graph presenter.
     *
     * @var OpenGraphPresenter
     */
    protected $openGraphPresenter;

    /**
     * Twitter Card presenter.
     *
     * @var TwitterCardPresenter
     */
    protected $twitterPresenter;

    /**
     * Constructor.
     *
     * @param VariableEngine|null $variableEngine
     * @param OpenGraphPresenter|null $openGraphPresenter
     * @param TwitterCardPresenter|null $twitterPresenter
     */
    public function __construct($variableEngine = null, $openGraphPresenter = null, $twitterPresenter = null) {
        $this->variableEngine = $variableEngine !== null ? $variableEngine : new VariableEngine();
        $this->openGraphPresenter = $openGraphPresenter !== null ? $openGraphPresenter : new OpenGraphPresenter($this->variableEngine);
        $this->twitterPresenter = $twitterPresenter !== null ? $twitterPresenter : new TwitterCardPresenter($this->variableEngine);
    }

    /**
     * Generate complete live preview payload for Facebook, Twitter, and Google SERP.
     *
     * @param array<string, mixed> $inputData
     * @return array{
     *   facebook: array<string, mixed>,
     *   twitter: array<string, mixed>,
     *   google: array<string, mixed>
     * }
     */
    public function generatePreview(array $inputData) {
        $siteName = function_exists('get_option') ? get_option('blogname', 'WordPress') : 'WordPress';
        $domain = function_exists('home_url') ? parse_url(home_url(), PHP_URL_HOST) : 'example.com';

        // 1. Facebook OpenGraph Preview
        $ogTags = $this->openGraphPresenter->buildTags($inputData);
        $ogTitle = !empty($ogTags['og:title']) ? $ogTags['og:title'] : $siteName;
        $ogDesc = !empty($ogTags['og:description']) ? $ogTags['og:description'] : '';
        $ogImage = !empty($ogTags['og:image']) ? $ogTags['og:image'] : '';

        $facebookPreview = [
            'title'        => $ogTitle,
            'description'  => $ogDesc,
            'image'        => $ogImage,
            'domain'       => strtoupper((string) $domain),
            'site_name'    => $siteName,
            'title_chars'  => mb_strlen($ogTitle, 'UTF-8'),
            'desc_chars'   => mb_strlen($ogDesc, 'UTF-8'),
        ];

        // 2. Twitter Card Preview
        $twTags = $this->twitterPresenter->buildTags($inputData);
        $twTitle = !empty($twTags['twitter:title']) ? $twTags['twitter:title'] : $ogTitle;
        $twDesc = !empty($twTags['twitter:description']) ? $twTags['twitter:description'] : $ogDesc;
        $twImage = !empty($twTags['twitter:image']) ? $twTags['twitter:image'] : $ogImage;
        $cardType = !empty($twTags['twitter:card']) ? $twTags['twitter:card'] : 'summary_large_image';

        $twitterPreview = [
            'card'         => $cardType,
            'title'        => $twTitle,
            'description'  => $twDesc,
            'image'        => $twImage,
            'domain'       => (string) $domain,
            'site_handle'  => isset($twTags['twitter:site']) ? $twTags['twitter:site'] : '',
            'creator'      => isset($twTags['twitter:creator']) ? $twTags['twitter:creator'] : '',
        ];

        // 3. Google SERP Snippet Preview
        $serpTitle = isset($inputData['title']) ? $this->variableEngine->replace($inputData['title'], $inputData) : $siteName;
        $serpDesc = isset($inputData['description']) ? $this->variableEngine->replace($inputData['description'], $inputData) : '';
        $serpUrl = isset($inputData['permalink']) ? $inputData['permalink'] : (function_exists('home_url') ? home_url('/') : 'https://example.com/');

        $googlePreview = [
            'title'        => $serpTitle,
            'description'  => $serpDesc,
            'url'          => $serpUrl,
            'domain'       => (string) $domain,
            'title_length' => mb_strlen($serpTitle, 'UTF-8'),
            'desc_length'  => mb_strlen($serpDesc, 'UTF-8'),
            'pixel_width'  => $this->estimateTitlePixelWidth($serpTitle),
        ];

        return [
            'facebook' => $facebookPreview,
            'twitter'  => $twitterPreview,
            'google'   => $googlePreview,
        ];
    }

    /**
     * Estimate desktop Google title pixel width (standard cutoff is ~600px).
     *
     * @param string $title
     * @return int Estimated pixels
     */
    public function estimateTitlePixelWidth($title) {
        // Average Arial 20px character width estimation
        $len = mb_strlen($title, 'UTF-8');
        return (int) round($len * 9.5);
    }
}
