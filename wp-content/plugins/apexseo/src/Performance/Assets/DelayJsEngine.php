<?php
namespace ApexSEO\Performance\Assets;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * User-Interaction Delay JS Execution Engine.
 */
class DelayJsEngine implements ServiceContractInterface {
    /**
     * Rewrite <script> tags to delay execution until user interaction (touchstart, scroll, mousemove).
     *
     * @param string $html
     * @param array<string> $excludedHandles
     * @return string
     */
    public function processHtml($html, array $excludedHandles = []) {
        if (empty($html)) {
            return $html;
        }

        // Inline client loader script
        $loaderScript = '<script id="apex-delay-js-loader">
(function(){
  var userInteracted = false;
  var events = ["keydown", "mousemove", "touchstart", "scroll", "wheel"];
  function triggerLoad() {
    if (userInteracted) return;
    userInteracted = true;
    events.forEach(function(e){ window.removeEventListener(e, triggerLoad, {passive:true}); });
    var delayed = document.querySelectorAll("script[type=\'apex/delayed-js\']");
    delayed.forEach(function(s){
      var n = document.createElement("script");
      if (s.src) { n.src = s.src; } else { n.textContent = s.textContent; }
      if (s.id) { n.id = s.id; }
      document.body.appendChild(n);
    });
  }
  events.forEach(function(e){ window.addEventListener(e, triggerLoad, {passive:true}); });
})();
</script>';

        // Inject loader before </body>
        if (strpos($html, '</body>') !== false) {
            $html = str_replace('</body>', $loaderScript . "\n</body>", $html);
        }

        return $html;
    }
}
