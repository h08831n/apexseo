<?php
namespace ApexSEO\Performance\Assets;

class DelayJsEngine {
    public function processHtml(string $html): string {
        if (strpos($html, '<script') === false) {
            return $html;
        }

        $loaderScript = '<script id="apex-delay-js-loader">document.addEventListener("touchstart", function(){}, {passive:true});</script>';
        return str_replace('</body>', $loaderScript . '</body>', $html);
    }
}
