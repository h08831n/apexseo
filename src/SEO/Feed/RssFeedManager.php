<?php
namespace ApexSEO\SEO\Feed;

class RssFeedManager {
    public function enhanceFeedItem(string $content, string $backlink = ''): string {
        if (!empty($backlink)) {
            $content .= sprintf('<p><a href="%s">Original Article on %s</a></p>', esc_url($backlink), esc_html(get_bloginfo('name')));
        }
        return $content;
    }
}
