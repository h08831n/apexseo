<?php
namespace ApexSEO\Core\Environment\Server;

class NginxAdapter extends GenericServerAdapter {
    public function getName(): string {
        return 'nginx';
    }

    public function isSupported(): bool {
        return isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false;
    }
}
