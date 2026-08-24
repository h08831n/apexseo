<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;
use ApexSEO\SEO\Context\ContextDetector;
use ApexSEO\SEO\Repository\IndexableRepository;
use ApexSEO\SEO\Social\OpenGraphPresenter;
use ApexSEO\SEO\Social\TwitterCardPresenter;

/**
 * Orchestrates all frontend SEO tag generation and renders clean, valid HTML in wp_head.
 */
class MetaTagManager {
    /** @var ContextDetector */
    protected $contextDetector;

    /** @var IndexableRepository|null */
    protected $indexableRepository;

    /** @var TitlePresenter */
    protected $titlePresenter;

    /** @var DescriptionPresenter */
    protected $descriptionPresenter;

    /** @var CanonicalPresenter */
    protected $canonicalPresenter;

    /** @var RobotsPresenter */
    protected $robotsPresenter;

    /** @var OpenGraphPresenter */
    protected $openGraphPresenter;

    /** @var TwitterCardPresenter */
    protected $twitterCardPresenter;

    /** @var MetaKeywordsPresenter */
    protected $keywordsPresenter;

    /**
     * Constructor.
     */
    public function __construct(
        $contextDetector = null,
        $indexableRepository = null,
        $titlePresenter = null,
        $descriptionPresenter = null,
        $canonicalPresenter = null,
        $robotsPresenter = null,
        $openGraphPresenter = null,
        $twitterCardPresenter = null,
        $keywordsPresenter = null
    ) {
        $this->contextDetector      = $contextDetector !== null ? $contextDetector : new ContextDetector();
        $this->indexableRepository  = $indexableRepository;
        $this->titlePresenter       = $titlePresenter !== null ? $titlePresenter : new TitlePresenter();
        $this->descriptionPresenter = $descriptionPresenter !== null ? $descriptionPresenter : new DescriptionPresenter();
        $this->canonicalPresenter   = $canonicalPresenter !== null ? $canonicalPresenter : new CanonicalPresenter();
        $this->robotsPresenter      = $robotsPresenter !== null ? $robotsPresenter : new RobotsPresenter();
        $this->openGraphPresenter   = $openGraphPresenter !== null ? $openGraphPresenter : new OpenGraphPresenter();
        $this->twitterCardPresenter = $twitterCardPresenter !== null ? $twitterCardPresenter : new TwitterCardPresenter();
        $this->keywordsPresenter    = $keywordsPresenter !== null ? $keywordsPresenter : new MetaKeywordsPresenter();
    }

    /**
     * Render complete SEO head block HTML for current request context.
     *
     * @param SeoContext|Indexable|null $context Optional override
     * @return string
     */
    public function renderHead($context = null) {
        if ($context === null) {
            $context = $this->contextDetector->detectContext();

            // Check if stored indexable exists in DB repository
            if ($this->indexableRepository !== null && $context->object_id) {
                $saved = $this->indexableRepository->findByObject($context->object_type, $context->object_id);
                if ($saved) {
                    $context = $saved;
                }
            }
        }

        $html = "<!-- This site is optimized with the Apex SEO Platform -->\n";
        $html .= $this->titlePresenter->renderHtmlTag($context);
        $html .= $this->descriptionPresenter->renderHtmlTag($context);
        $html .= $this->canonicalPresenter->renderHtmlTag($context);
        $html .= $this->robotsPresenter->renderHtmlTag($context);
        $html .= $this->keywordsPresenter->renderHtmlTag($context);
        $html .= $this->openGraphPresenter->render($context);
        $html .= $this->twitterCardPresenter->render($context);
        $html .= "<!-- / Apex SEO Platform -->\n";

        return $html;
    }

    /**
     * Echo head markup into wp_head hook.
     *
     * @return void
     */
    public function outputHead() {
        echo $this->renderHead();
    }
}
