<?php
namespace ApexSEO\SEO\Meta;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Meta Robots Directives Compiler and Presenter.
 */
class RobotsPresenter implements ServiceContractInterface {
    /**
     * Build the robots directives dictionary based on settings and context.
     *
     * @param array $context
     * @return array<string, mixed>
     */
    public function getDirectives(array $context = []) {
        // Global privacy check (Settings -> Reading: Search engine visibility)
        $blogPublic = function_exists('get_option') ? (int) get_option('blog_public', 1) : 1;
        if ($blogPublic === 0) {
            return [
                'noindex' => true,
                'nofollow' => true,
            ];
        }

        $directives = [
            'index'               => true,
            'follow'              => true,
            'max-snippet'         => -1,
            'max-image-preview'   => 'large',
            'max-video-preview'   => -1,
        ];

        // 404 and search results should always be noindex
        $type = isset($context['page_type']) ? $context['page_type'] : '';
        if ($type === '404' || $type === 'search') {
            $directives['index'] = false;
            $directives['noindex'] = true;
            return $directives;
        }

        // Post-level overrides
        if (!empty($context['robots_noindex'])) {
            $directives['index'] = false;
            $directives['noindex'] = true;
        }

        if (!empty($context['robots_nofollow'])) {
            $directives['follow'] = false;
            $directives['nofollow'] = true;
        }

        if (!empty($context['robots_noarchive'])) {
            $directives['noarchive'] = true;
        }

        if (!empty($context['robots_nosnippet'])) {
            $directives['nosnippet'] = true;
        }

        if (!empty($context['robots_noimageindex'])) {
            $directives['noimageindex'] = true;
        }

        return $directives;
    }

    /**
     * Render the composite robots meta tag string.
     *
     * @param array $context
     * @return string
     */
    public function render(array $context = []) {
        $directives = $this->getDirectives($context);
        $parts = [];

        if (!empty($directives['noindex'])) {
            $parts[] = 'noindex';
        } else {
            $parts[] = 'index';
        }

        if (!empty($directives['nofollow'])) {
            $parts[] = 'nofollow';
        } else {
            $parts[] = 'follow';
        }

        if (!empty($directives['noarchive'])) {
            $parts[] = 'noarchive';
        }

        if (!empty($directives['nosnippet'])) {
            $parts[] = 'nosnippet';
        }

        if (!empty($directives['noimageindex'])) {
            $parts[] = 'noimageindex';
        }

        if (isset($directives['max-snippet'])) {
            $parts[] = 'max-snippet:' . $directives['max-snippet'];
        }

        if (isset($directives['max-image-preview'])) {
            $parts[] = 'max-image-preview:' . $directives['max-image-preview'];
        }

        if (isset($directives['max-video-preview'])) {
            $parts[] = 'max-video-preview:' . $directives['max-video-preview'];
        }

        return implode(', ', $parts);
    }
}
