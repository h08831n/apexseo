<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\SEO\Social\OpenGraphPresenter;
use ApexSEO\SEO\Social\TwitterCardPresenter;

class MetaTagManager {
    private $titlePresenter;
    private $descPresenter;
    private $canonicalPresenter;
    private $robotsPresenter;
    private $ogPresenter;
    private $twitterPresenter;

    public function __construct(
        TitlePresenter $titlePresenter,
        DescriptionPresenter $descPresenter,
        CanonicalPresenter $canonicalPresenter,
        RobotsPresenter $robotsPresenter,
        OpenGraphPresenter $ogPresenter,
        TwitterCardPresenter $twitterPresenter
    ) {
        $this->titlePresenter = $titlePresenter;
        $this->descPresenter = $descPresenter;
        $this->canonicalPresenter = $canonicalPresenter;
        $this->robotsPresenter = $robotsPresenter;
        $this->ogPresenter = $ogPresenter;
        $this->twitterPresenter = $twitterPresenter;
    }

    public function renderHead(array $context = []): string {
        $output = [];
        $output[] = $this->titlePresenter->renderHtmlTag($context);
        $output[] = $this->descPresenter->renderHtmlTag($context);
        $output[] = $this->canonicalPresenter->renderHtmlTag($context);
        $output[] = $this->robotsPresenter->renderHtmlTag($context);
        $output[] = $this->ogPresenter->renderHtml($context);
        $output[] = $this->twitterPresenter->renderHtml($context);

        return implode("
", array_filter($output));
    }
}
