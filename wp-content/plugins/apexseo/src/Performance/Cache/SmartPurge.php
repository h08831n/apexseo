<?php
namespace ApexSEO\Performance\Cache;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Environment\Server\ServerAdapterInterface;
use ApexSEO\Core\Logging\LoggerInterface;

/**
 * Multi-Tier Smart Cache Invalidation Manager.
 */
class SmartPurge implements ServiceContractInterface {
    /**
     * @var StaticFileWriter
     */
    protected $fileWriter;

    /**
     * @var ServerAdapterInterface
     */
    protected $serverAdapter;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(
        StaticFileWriter $fileWriter,
        ServerAdapterInterface $serverAdapter,
        $logger = null
    ) {
        $this->fileWriter = $fileWriter;
        $this->serverAdapter = $serverAdapter;
        $this->logger = $logger;
    }

    /**
     * Purge specific post or URL cache across disk and server engine.
     *
     * @param int|string $target Post ID or URL.
     * @return bool
     */
    public function purge($target) {
        $key = (string) $target;
        $filePurged = $this->fileWriter->deleteCache($key);

        // Web Server Tier (LiteSpeed/OLS tag purge)
        $tag = is_numeric($target) ? 'apex_post_' . $target : 'apex_url_' . md5($target);
        $serverPurged = $this->serverAdapter->flushServerCache($tag);

        if ($this->logger) {
            $this->logger->info(sprintf('SmartPurge executed for target [%s]', $target), [
                'file_purged'   => $filePurged,
                'server_purged' => $serverPurged,
            ]);
        }

        return true;
    }

    /**
     * Purge entire site cache across all tiers.
     *
     * @return bool
     */
    public function purgeAll() {
        $this->fileWriter->purgeAll();
        $this->serverAdapter->flushServerCache('*');

        if ($this->logger) {
            $this->logger->info('SmartPurge complete site flush executed across all tiers.');
        }

        return true;
    }
}
