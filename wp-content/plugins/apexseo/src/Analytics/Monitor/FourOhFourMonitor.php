<?php
namespace ApexSEO\Analytics\Monitor;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * Real-time 404 Error URL & Traffic Monitor.
 */
class FourOhFourMonitor implements ServiceContractInterface {
    /**
     * @var DatabaseManager
     */
    protected $db;

    /**
     * In-memory log buffer.
     *
     * @var array<array{url: string, ip: string, user_agent: string, timestamp: string, hits: int}>
     */
    protected $logBuffer = [];

    /**
     * Constructor.
     *
     * @param DatabaseManager $db
     */
    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }

    /**
     * Log a 404 occurrence.
     *
     * @param string $url
     * @param string $ip
     * @param string $userAgent
     * @return void
     */
    public function record404($url, $ip = '127.0.0.1', $userAgent = '') {
        $cleanUrl = esc_url_raw($url);
        if (isset($this->logBuffer[$cleanUrl])) {
            $this->logBuffer[$cleanUrl]['hits']++;
            $this->logBuffer[$cleanUrl]['timestamp'] = date('Y-m-d H:i:s');
        } else {
            $this->logBuffer[$cleanUrl] = [
                'url'        => $cleanUrl,
                'ip'         => $ip,
                'user_agent' => $userAgent,
                'timestamp'  => date('Y-m-d H:i:s'),
                'hits'       => 1,
            ];
        }
    }

    /**
     * Get recorded 404 entries.
     *
     * @return array
     */
    public function getRecent404s() {
        return array_values($this->logBuffer);
    }
}
