<?php
namespace ApexSEO\SEO\Social;

class SocialPreviewService {
    private $ogPresenter;
    private $twitterPresenter;

    public function __construct(OpenGraphPresenter $ogPresenter, TwitterCardPresenter $twitterPresenter) {
        $this->ogPresenter = $ogPresenter;
        $this->twitterPresenter = $twitterPresenter;
    }

    public function generatePreview(array $context = []): array {
        return [
            'opengraph' => $this->ogPresenter->renderTags($context),
            'twitter'   => $this->twitterPresenter->renderTags($context),
        ];
    }
}
