<?php
namespace ApexSEO\Core\Environment\Server;

class OpenLiteSpeedAdapter extends LiteSpeedAdapter {
    public function getName(): string {
        return 'openlitespeed';
    }
}
