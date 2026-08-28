<?php
namespace ApexSEO\SEO\Context;

class ContextDetector {
    public function detectContext(): array {
        if (is_singular()) {
            return [
                'type'      => 'singular',
                'object_id' => get_the_ID(),
                'title'     => get_the_title(),
            ];
        }
        if (is_front_page()) {
            return [
                'type'  => 'front_page',
                'title' => get_bloginfo('name'),
            ];
        }
        return [
            'type'  => 'general',
            'title' => get_bloginfo('name'),
        ];
    }
}
