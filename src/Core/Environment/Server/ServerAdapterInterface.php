<?php
namespace ApexSEO\Core\Environment\Server;

interface ServerAdapterInterface {
    public function getName(): string;
    public function isSupported(): bool;
    public function purgeCache(string $url): bool;
    public function flushRules(): bool;
    public function writeDirectives(string $rules): bool;
}
