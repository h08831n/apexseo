<?php
namespace ApexSEO\AI\LlmsTxt;

class LlmsTxtGenerator {
    public function generate(): string {
        $siteName = get_bloginfo('name');
        $siteDesc = get_bloginfo('description');
        $siteUrl = home_url('/');

        $output = "# {$siteName}\n\n";
        $output .= "> {$siteDesc}\n\n";
        $output .= "## Canonical Site URL\n- {$siteUrl}\n";

        return $output;
    }
}
