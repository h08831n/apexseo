<?php
namespace ApexSEO\Analytics\Tracker;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * Keyword Ranking & Position Tracker Service.
 */
class RankTracker implements ServiceContractInterface {
    /**
     * @var DatabaseManager
     */
    protected $db;

    /**
     * In-memory tracked keywords.
     *
     * @var array<string, array{keyword: string, position: int, previous_position: int, change: int, url: string}>
     */
    protected $keywords = [];

    /**
     * Constructor.
     *
     * @param DatabaseManager $db
     */
    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }

    /**
     * Track a keyword position.
     *
     * @param string $keyword
     * @param int $position Current rank position.
     * @param string $url
     * @return void
     */
    public function trackKeyword($keyword, $position, $url = '') {
        $key = strtolower(trim($keyword));
        $prev = isset($this->keywords[$key]) ? $this->keywords[$key]['position'] : $position;
        $change = $prev - $position; // positive means improved rank

        $this->keywords[$key] = [
            'keyword'           => $keyword,
            'position'          => (int) $position,
            'previous_position' => (int) $prev,
            'change'            => $change,
            'url'               => $url,
        ];
    }

    /**
     * Get all tracked keywords.
     *
     * @return array
     */
    public function getTrackedKeywords() {
        return array_values($this->keywords);
    }
}
