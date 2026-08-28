<?php
namespace ApexSEO\Core\Environment\Server;

class DirectServerAdapter extends GenericServerAdapter {
    public function getName(): string {
        return 'direct';
    }
}
