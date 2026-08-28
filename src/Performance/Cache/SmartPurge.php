<?php
namespace ApexSEO\Performance\Cache;

use ApexSEO\Core\Environment\Server\ServerAdapterInterface;

class SmartPurge {
    private $fileWriter;
    private $serverAdapter;

    public function __construct(StaticFileWriter $fileWriter, ServerAdapterInterface $serverAdapter) {
        $this->fileWriter = $fileWriter;
        $this->serverAdapter = $serverAdapter;
    }

    public function purge(string $url): bool {
        $fileDeleted = $this->fileWriter->deleteCache($url);
        $serverPurged = $this->serverAdapter->purgeCache($url);
        return $fileDeleted && $serverPurged;
    }

    public function purgeAll(): bool {
        return $this->fileWriter->flushAll();
    }
}
