<?php
namespace ApexSEO\AI\Generators;

class MetadataAiGenerator {
    public function generateTitle(string $content): string {
        $clean = strip_tags($content);
        $words = explode(' ', $clean);
        $slice = array_slice($words, 0, 6);
        return ucwords(implode(' ', $slice)) . ' | ' . get_bloginfo('name');
    }

    public function generateDescription(string $content): string {
        $clean = strip_tags($content);
        return mb_substr($clean, 0, 150) . '...';
    }
}
