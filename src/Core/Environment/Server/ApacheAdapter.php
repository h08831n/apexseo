<?php
namespace ApexSEO\Core\Environment\Server;

class ApacheAdapter extends GenericServerAdapter {
    public function getName(): string {
        return 'apache';
    }

    public function isSupported(): bool {
        return isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false;
    }

    public function flushRules(): bool {
        global $wp_rewrite;
        if ($wp_rewrite) {
            $wp_rewrite->flush_rules(true);
        }
        return true;
    }
}
