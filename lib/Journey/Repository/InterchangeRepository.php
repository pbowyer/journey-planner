<?php


namespace JourneyPlanner\Lib\Journey\Repository;

use JourneyPlanner\Lib\Cache\Cache;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class InterchangeRepository {

    const CACHE_KEY = "|INTERCHANGE|";

    private $db;
    private $cache;

    /**
     * @param PDO $pdo
     * @param \JourneyPlanner\Lib\Cache\Cache $cache
     */
    public function __construct(PDO $pdo, Cache $cache) {
        $this->db = $pdo;
        $this->cache = $cache;
    }

    /**
     * Returns a hashmap of station => interchange time
     *
     * @return int[]
     */
    public function getInterchange(): array {
        return $this->cache->cacheMethod(self::CACHE_KEY, [$this, 'getInterchangeFromDB']);
    }

    /**
     * @return array
     */
    public function getInterchangeFromDB(): array {
        $stmt = $this->db->query("SELECT from_stop_id, min_transfer_time FROM transfers");
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['from_stop_id']] = $row['min_transfer_time'];
        }

        return $results;
    }

}