<?php
namespace ApexSEO\Core\Environment\Server;

class GenericServerAdapter implements ServerAdapterInterface {
    public function getName(): string {
        return 'generic';
    }

    public function isSupported(): bool {
        return true;
    }

    public function purgeCache(string $url): bool {
        return true;
    }

    public function flushRules(): bool {
        return true;
    }

    public function writeDirectives(string $rules): bool {
        return true;
    }
}
