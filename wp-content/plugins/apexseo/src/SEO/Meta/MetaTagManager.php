<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\SEO\Social\OpenGraphPresenter;
use ApexSEO\SEO\Social\TwitterCardPresenter;

/**
 * SEO Meta Tag Manager & Output Coordinator.
 */
class MetaTagManager implements ServiceContractInterface {
    /**
     * @var TitlePresenter
     */
    protected $titlePresenter;

    /**
     * @var DescriptionPresenter
     */
    protected $descriptionPresenter;

    /**
     * @var CanonicalPresenter
     */
    protected $canonicalPresenter;

    /**
     * @var RobotsPresenter
     */
    protected $robotsPresenter;

    /**
     * @var OpenGraphPresenter
     */
    protected $openGraphPresenter;

    /**
     * @var TwitterCardPresenter
     */
    protected $twitterCardPresenter;

    /**
     * Constructor.
     */
    public function __construct(
        TitlePresenter $titlePresenter,
        DescriptionPresenter $descriptionPresenter,
        CanonicalPresenter $canonicalPresenter,
        RobotsPresenter $robotsPresenter,
        OpenGraphPresenter $openGraphPresenter,
        TwitterCardPresenter $twitterCardPresenter
    ) {
        $this->titlePresenter = $titlePresenter;
        $this->descriptionPresenter = $descriptionPresenter;
        $this->canonicalPresenter = $canonicalPresenter;
        $this->robotsPresenter = $robotsPresenter;
        $this->openGraphPresenter = $openGraphPresenter;
        $this->twitterCardPresenter = $twitterCardPresenter;
    }

    /**
     * Build the complete SEO head tags packet.
     *
     * @param array $context
     * @return array
     */
    public function buildHeadData(array $context = []) {
        $title = $this->titlePresenter->render($context);
        $description = $this->descriptionPresenter->render($context);
        $canonical = $this->canonicalPresenter->render($context);
        $robots = $this->robotsPresenter->render($context);

        $context['title'] = $title;
        $context['description'] = $description;
        $context['canonical_url'] = $canonical;

        $ogTags = $this->openGraphPresenter->buildTags($context);
        $twitterTags = $this->twitterCardPresenter->buildTags($context);

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => $canonical,
            'robots'      => $robots,
            'og'          => $ogTags,
            'twitter'     => $twitterTags,
        ];
    }

    /**
     * Render the full HTML block to be output in <head>.
     *
     * @param array $context
     * @return string
     */
    public function renderHeadHtml(array $context = []) {
        $data = $this->buildHeadData($context);
        $context['title'] = $data['title'];
        $context['description'] = $data['description'];
        $context['canonical_url'] = $data['canonical'];

        $out = "<!-- This site is optimized with Apex SEO Platform -->\n";
        if (!empty($data['title'])) {
            $out .= sprintf("<title>%s</title>\n", esc_html($data['title']));
        }
        if (!empty($data['description'])) {
            $out .= sprintf("<meta name=\"description\" content=\"%s\" />\n", esc_attr($data['description']));
        }
        if (!empty($data['canonical'])) {
            $out .= sprintf("<link rel=\"canonical\" href=\"%s\" />\n", esc_url($data['canonical']));
        }
        if (!empty($data['robots'])) {
            $out .= sprintf("<meta name=\"robots\" content=\"%s\" />\n", esc_attr($data['robots']));
        }

        $out .= $this->openGraphPresenter->render($context);
        $out .= $this->twitterCardPresenter->render($context);
        $out .= "<!-- / Apex SEO Platform -->\n";

        return $out;
    }
}
