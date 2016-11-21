<?php

namespace JourneyPlanner\Lib\Storage\Schedule;

use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;
use JourneyPlanner\Lib\Storage\Cache\Cache;
use JourneyPlanner\Lib\Storage\Schedule\DefaultProvider;
use JourneyPlanner\Lib\Storage\Schedule\ScheduleProvider;
use JourneyPlanner\Lib\Storage\Schedule\TransferPatternScheduleFactory;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class CachedProvider extends DefaultProvider implements ScheduleProvider {

    const TP_CACHE_KEY = "|TRANSFER_PATTERN|",
          TT_CACHE_KEY = "|TIMETABLE|",
          NT_CACHE_KEY = "|NON_TIMETABLE|",
          IN_CACHE_KEY = "|INTERCHANGE|";

    const NUM_PATTERNS = 10;

    /**
     * @var PDO
     */
    private $db;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param PDO $pdo
     * @param Cache $cache
     */
    public function __construct(PDO $pdo, Cache $cache) {
        parent::__construct($pdo);

        $this->db = $pdo;
        $this->cache = $cache;
    }

    /**
     * Lookup the transfer patterns and schedule separately in order to use
     * the cache.
     *
     * @param $origin
     * @param $destination
     * @param $startTimestamp
     * @return TransferPatternSchedule[]
     */
    public function getTimetable($origin, $destination, $startTimestamp) {
        $dow = lcfirst(gmdate('l', $startTimestamp));
        $results = [];
        $patternCount = 0;
        $patternId = null;

        foreach ($this->getTransferPatterns($origin, $destination) as $row) {
            $timetable = $this->getScheduleSegment($row["origin"], $row["destination"], $startTimestamp, $dow);

            foreach ($timetable as $result) {
                $result["transfer_pattern"] = $row["transfer_pattern"];
                $result["transfer_leg"] = $row["leg"];

                $results[] = $result;
            }

            if ($patternId !== $row["transfer_pattern"]) {
                $patternId = $row["transfer_pattern"];

                if (++$patternCount > self::NUM_PATTERNS) {
                    break;
                }
            }
        }

        $factory = new TransferPatternScheduleFactory();

        return $factory->getSchedulesFromTimetable($results);
    }

    /**
     * @param $origin
     * @param $destination
     * @return array
     */
    private function getTransferPatterns($origin, $destination) {
        $cachedValue = $this->cache->getObject(self::TP_CACHE_KEY.$origin.$destination);

        if ($cachedValue !== false) {
            return $cachedValue;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM pattern
            WHERE origin = :origin
            AND destination = :destination
        ");

        $stmt->execute([
            'origin' => $origin,
            'destination' => $destination
        ]);

        $result = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $legs = str_split($row["id"], 3);
            $legLength = count($legs);

            for ($i = 2; $i < $legLength; $i += 2) {
                $result[] = [
                    "origin" => $legs[$i],
                    "destination" => $legs[$i+1],
                    "transfer_pattern" => $row["id"],
                    "leg" => $i
                ];
            }
        }

        $this->cache->setObject(self::TP_CACHE_KEY.$origin.$destination, $result);

        return $result;
    }

    /**
     * @param $origin
     * @param $destination
     * @param $startTimestamp
     * @param $dow
     * @return array
     */
    private function getScheduleSegment($origin, $destination, $startTimestamp, $dow) {
        $cachedValue = $this->cache->getObject(self::TT_CACHE_KEY.$origin.$destination.$startTimestamp);

        if ($cachedValue !== false) {
            return $cachedValue;
        }

        $stmt = $this->db->prepare("
            SELECT 
              dept.service,
              dept.origin,
              arrv.destination,
              TIME_TO_SEC(dept.departure_time) as departure_time,
              TIME_TO_SEC(arrv.arrival_time) as arrival_time,
              arrv.operator,
              arrv.type
            FROM timetable_connection dept
            JOIN timetable_connection arrv ON dept.service = arrv.service
            WHERE arrv.arrival_time > dept.departure_time
            AND dept.origin = :origin
            AND arrv.destination = :destination
            AND dept.departure_time >= :departureTime
            AND dept.start_date <= :startDate AND dept.end_date >= :startDate
            AND dept.{$dow} = 1
            ORDER BY arrv.arrival_time, dept.service
        ");

        $stmt->execute([
            'departureTime' => gmdate("H:i:s", $startTimestamp),
            'startDate' => gmdate("Y-m-d", $startTimestamp),
            'origin' => $origin,
            'destination' => $destination
        ]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->cache->setObject(self::TT_CACHE_KEY.$origin.$destination.$startTimestamp.$dow, $result);

        return $result;
    }

    /**
     * @param int $targetTimestamp
     * @return NonTimetableConnection[]
     */
    public function getNonTimetableConnections($targetTimestamp) {
        $result = $this->cache->getObject(self::NT_CACHE_KEY.$targetTimestamp);

        if ($result !== false) {
            return $result;
        }

        $result = parent::getNonTimetableConnections($targetTimestamp);
        $this->cache->setObject(self::NT_CACHE_KEY.$targetTimestamp, $result);

        return $result;
    }

    /**
     * @return array
     */
    public function getInterchangeTimes() {
        $result = $this->cache->getObject(self::IN_CACHE_KEY);

        if ($result !== false) {
            return $result;
        }

        $result = parent::getInterchangeTimes();
        $this->cache->setObject(self::IN_CACHE_KEY, $result);

        return $result;
    }
}