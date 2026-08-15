<?php
namespace ApexSEO\AI\LlmsTxt;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Standard-Compliant llmstxt.org /llms.txt and /llms-full.txt Generator.
 */
class LlmsTxtGenerator implements ServiceContractInterface {
    /**
     * Generate standard /llms.txt markdown index.
     *
     * @param array $options Site info, sections, links
     * @return string Markdown
     */
    public function generateLlmsTxt(array $options = []) {
        $siteName = !empty($options['site_name']) ? $options['site_name'] : (function_exists('get_bloginfo') ? get_bloginfo('name') : 'Apex SEO Platform');
        $siteDesc = !empty($options['site_description']) ? $options['site_description'] : (function_exists('get_bloginfo') ? get_bloginfo('description') : 'Modern WordPress Site');

        $out = sprintf("# %s\n\n", $siteName);
        $out .= sprintf("> %s\n\n", $siteDesc);

        // Core Documentation / Links
        if (!empty($options['sections']) && is_array($options['sections'])) {
            foreach ($options['sections'] as $heading => $links) {
                $out .= sprintf("## %s\n", $heading);
                foreach ($links as $item) {
                    if (empty($item['url']) || empty($item['title'])) {
                        continue;
                    }
                    $desc = !empty($item['description']) ? ': ' . $item['description'] : '';
                    $out .= sprintf("- [%s](%s)%s\n", $item['title'], esc_url($item['url']), $desc);
                }
                $out .= "\n";
            }
        }

        return trim($out) . "\n";
    }

    /**
     * Generate comprehensive /llms-full.txt full plain-text stream.
     *
     * @param array<array{title: string, url: string, content: string}> $documents
     * @return string Markdown
     */
    public function generateLlmsFullTxt(array $documents = []) {
        $out = "# Full Content Directory\n\n";

        foreach ($documents as $doc) {
            $out .= sprintf("## %s\n", $doc['title']);
            $out .= sprintf("URL: %s\n\n", $doc['url']);
            $out .= sprintf("%s\n\n---\n\n", trim(strip_tags($doc['content'])));
        }

        return trim($out) . "\n";
    }
}
