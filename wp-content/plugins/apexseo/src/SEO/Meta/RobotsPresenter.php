<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\SEO\Models\SeoContext;
use ApexSEO\SEO\Models\Indexable;

/**
 * Renders robots meta directives adhering to Google Search Central and Bing guidelines.
 */
class RobotsPresenter {
    /**
     * Build robots directives array.
     *
     * @param SeoContext|Indexable|array $context
     * @return array<string, bool|string>
     */
    public function getDirectives($context) {
        $directives = [
            'noindex'   => false,
            'nofollow'  => false,
            'noarchive' => false,
            'nosnippet' => false,
            'noimageindex' => false,
            'max-snippet' => '-1',
            'max-image-preview' => 'large',
            'max-video-preview' => '-1',
        ];

        // Check global blog_public option
        if (function_exists('get_option') && (string) get_option('blog_public') === '0') {
            $directives['noindex'] = true;
            $directives['nofollow'] = true;
            return $directives;
        }

        if ($context instanceof Indexable) {
            $directives['noindex'] = (bool) $context->is_robots_noindex;
            $directives['nofollow'] = (bool) $context->is_robots_nofollow;
            $directives['noarchive'] = (bool) $context->is_robots_noarchive;
            $directives['nosnippet'] = (bool) $context->is_robots_nosnippet;
            $directives['noimageindex'] = (bool) $context->is_robots_noimageindex;
        } elseif ($context instanceof SeoContext) {
            $directives['noindex'] = (bool) $context->robots_noindex;
            $directives['nofollow'] = (bool) $context->robots_nofollow;
            $directives['noarchive'] = (bool) $context->robots_noarchive;
            $directives['nosnippet'] = (bool) $context->robots_nosnippet;
            $directives['noimageindex'] = (bool) $context->robots_noimageindex;
        } elseif (is_array($context)) {
            if (isset($context['robots_noindex'])) {
                $directives['noindex'] = (bool) $context['robots_noindex'];
            }
            if (isset($context['robots_nofollow'])) {
                $directives['nofollow'] = (bool) $context['robots_nofollow'];
            }
            if (isset($context['noindex'])) {
                $directives['noindex'] = (bool) $context['noindex'];
            }
            if (isset($context['nofollow'])) {
                $directives['nofollow'] = (bool) $context['nofollow'];
            }
        }

        return $directives;
    }

    /**
     * Render robots content directive string.
     *
     * @param SeoContext|Indexable|array $context
     * @return string
     */
    public function render($context) {
        $directives = $this->getDirectives($context);

        $parts = [];
        $parts[] = $directives['noindex'] ? 'noindex' : 'index';
        $parts[] = $directives['nofollow'] ? 'nofollow' : 'follow';

        if (!empty($directives['noarchive'])) {
            $parts[] = 'noarchive';
        }
        if (!empty($directives['nosnippet'])) {
            $parts[] = 'nosnippet';
        }
        if (!empty($directives['noimageindex'])) {
            $parts[] = 'noimageindex';
        }

        if (!$directives['noindex']) {
            if (!empty($directives['max-snippet'])) {
                $parts[] = 'max-snippet:' . $directives['max-snippet'];
            }
            if (!empty($directives['max-image-preview'])) {
                $parts[] = 'max-image-preview:' . $directives['max-image-preview'];
            }
            if (!empty($directives['max-video-preview'])) {
                $parts[] = 'max-video-preview:' . $directives['max-video-preview'];
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Render full HTML tag: <meta name="robots" content="..." />
     *
     * @param SeoContext|Indexable|array $context
     * @return string
     */
    public function renderHtmlTag($context) {
        $content = $this->render($context);
        $escaped = function_exists('esc_attr') ? esc_attr($content) : htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        return '<meta name="robots" content="' . $escaped . '" />' . "\n";
    }
}
