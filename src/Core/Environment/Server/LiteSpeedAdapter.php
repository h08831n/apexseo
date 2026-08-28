<?php
namespace ApexSEO\Core\Environment\Server;

class LiteSpeedAdapter extends GenericServerAdapter {
    public function getName(): string {
        return 'litespeed';
    }

    public function isSupported(): bool {
        return isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'litespeed') !== false;
    }
}
