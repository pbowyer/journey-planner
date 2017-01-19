<?php

namespace JourneyPlanner\Lib\Journey\Repository;
use DateTime;
use JourneyPlanner\Lib\Journey\FixedLeg;
use JourneyPlanner\Lib\Cache\Cache;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class FixedLegRepository {
    const CACHE_KEY = "|FIXED_LEG|";

    private $db;
    private $cache;

    /**
     * @param PDO $pdo
     * @param Cache $cache
     */
    public function __construct(PDO $pdo, Cache $cache) {
        $this->db = $pdo;
        $this->cache = $cache;
    }

    /**
     * Returns a hashmap of FixedLeg's indexed by origin
     *
     * @param DateTime $dateTime
     * @return FixedLeg[]
     */
    public function getFixedLegs(DateTime $dateTime): array {
        $date = $dateTime->format("Y-m-d");
        $dow = $dateTime->format("l");

        return $this->cache->cacheMethod(self::CACHE_KEY.$date, [$this, 'getFixedLegsFromDB'], $date, $dow);
    }

    /**
     * @param string $date
     * @param string $dow
     * @return FixedLeg[]
     */
    public function getFixedLegsFromDB(string $date, string $dow): array {
        $stmt = $this->db->prepare("
            SELECT 
                from_stop_id as origin, 
                to_stop_id as destination, 
                link_secs as duration, 
                mode, 
                TIME_TO_SEC(start_time) as start_time,
                TIME_TO_SEC(end_time) as end_time
            FROM links
            WHERE start_date <= :targetDate AND end_date >= :targetDate
            AND {$dow} = 1    
        ");

        $stmt->execute(["targetDate" => $date]);

        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $leg = new FixedLeg(
                $row["origin"],
                $row["destination"],
                $row["mode"],
                $row["duration"],
                $row["start_time"],
                $row["end_time"]
            );

            if (isset($results[$row["origin"]])) {
                $results[$row["origin"]][] = $leg;
            }
            else {
                $results[$row["origin"]] = [$leg];
            }
        }

        return $results;
    }

}