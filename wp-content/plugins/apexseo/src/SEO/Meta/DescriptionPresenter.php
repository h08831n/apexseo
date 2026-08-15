<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\SEO\Variables\VariableEngine;

/**
 * Meta Description Presenter.
 */
class DescriptionPresenter implements ServiceContractInterface {
    /**
     * Variable engine.
     *
     * @var VariableEngine
     */
    protected $variableEngine;

    /**
     * Maximum character length limit.
     *
     * @var int
     */
    protected $maxLength = 160;

    /**
     * Constructor.
     *
     * @param VariableEngine $variableEngine
     */
    public function __construct(VariableEngine $variableEngine) {
        $this->variableEngine = $variableEngine;
    }

    /**
     * Render meta description.
     *
     * @param array $context
     * @param string|null $customTemplate
     * @return string
     */
    public function render(array $context = [], $customTemplate = null) {
        if (!empty($context['custom_description'])) {
            $desc = $this->variableEngine->replace($context['custom_description'], $context);
            return $this->truncate($desc);
        }

        if ($customTemplate !== null) {
            $desc = $this->variableEngine->replace($customTemplate, $context);
            return $this->truncate($desc);
        }

        $type = isset($context['page_type']) ? $context['page_type'] : 'single';

        switch ($type) {
            case 'home':
            case 'frontpage':
                $desc = $this->variableEngine->replace('%%sitedesc%%', $context);
                break;
            case 'category':
            case 'taxonomy':
                $desc = $this->variableEngine->replace('%%term_description%%', $context);
                break;
            case 'author':
                $desc = $this->variableEngine->replace('Articles written by %%author_name%% on %%sitename%%.', $context);
                break;
            case 'single':
            case 'page':
            default:
                $desc = $this->variableEngine->replace('%%excerpt%%', $context);
                break;
        }

        return $this->truncate($desc);
    }

    /**
     * Truncate and sanitize description cleanly.
     *
     * @param string $text
     * @return string
     */
    public function truncate($text) {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        if (mb_strlen($clean) <= $this->maxLength) {
            return $clean;
        }

        $sub = mb_substr($clean, 0, $this->maxLength);
        $lastSpace = mb_strrpos($sub, ' ');
        if ($lastSpace !== false && $lastSpace > 120) {
            return mb_substr($sub, 0, $lastSpace) . '...';
        }

        return $sub . '...';
    }
}
