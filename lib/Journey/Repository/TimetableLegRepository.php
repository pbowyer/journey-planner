<?php

namespace JourneyPlanner\Lib\Journey\Repository;

use DateTime;
use JourneyPlanner\Lib\Journey\CallingPoint;
use JourneyPlanner\Lib\Journey\FixedLeg;
use JourneyPlanner\Lib\Journey\TimetableLeg;
use JourneyPlanner\Lib\Cache\Cache;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TimetableLegRepository {
    const CACHE_KEY = "|TIMETABLE_LEG|";

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
     * Returns an array of TimetableLegs
     *
     * @param string $origin
     * @param string $destination
     * @param DateTime $dateTime
     * @return array|FixedLeg[]
     */
    public function getTimetableLegs(string $origin, string $destination, DateTime $dateTime): array {
        $date = $dateTime->format("Y-m-d");
        $dow = $dateTime->format("l");
        $key = self::CACHE_KEY.$date.$origin.$destination;

        return $this->cache->cacheMethod($key, [$this, 'getLegsFromDB'], $origin, $destination, $date, $dow);
    }

    /**
     * @param string $origin
     * @param string $destination
     * @param string $date
     * @param string $dow
     * @return array
     */
    public function getLegsFromDB(string $origin, string $destination, string $date, string $dow): array {
        $stmt = $this->db->prepare("            
            SELECT 
                train_uid as service,
                sstation.parent_station as station,
                ostation.parent_station as origin, 
                dstation.parent_station as destination, 
                TIME_TO_SEC(stop.departure_time) as departure_time, 
                TIME_TO_SEC(stop.arrival_time) as arrival_time,
                TIME_TO_SEC(dept.departure_time) as leg_departure_time, 
                TIME_TO_SEC(arrv.arrival_time) as leg_arrival_time,
                atoc_code AS operator,
                IF (train_category='BS' OR train_category='BR', 'bus', 'train') AS type
            FROM stop_times AS dept
            JOIN stops AS ostation ON dept.stop_id = ostation.stop_id
            JOIN stop_times AS arrv ON arrv.trip_id = dept.trip_id AND arrv.stop_sequence > dept.stop_sequence
            JOIN stops AS dstation ON arrv.stop_id = dstation.stop_id
            JOIN stop_times AS stop ON stop.trip_id = dept.trip_id AND stop.stop_sequence BETWEEN dept.stop_sequence AND arrv.stop_sequence
            JOIN stops AS sstation ON stop.stop_id = sstation.stop_id
            JOIN trips ON dept.trip_id = trips.trip_id
            JOIN calendar USING(service_id)
            WHERE ostation.parent_station = :origin
            AND dstation.parent_station = :destination
            AND :startDate BETWEEN start_date AND end_date
            AND {$dow} = 1
            ORDER BY arrv.arrival_time, stop.trip_id, stop.stop_sequence            
        ");

        $stmt->execute([
            'startDate' => $date,
            'origin' => $origin,
            'destination' => $destination
        ]);

        $result = [];
        $callingPoints = [];
        $prev = null;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            if ($prev !== null && $prev["service"] !== $row["service"]) {
                $result[] = new TimetableLeg(
                    $prev["origin"],
                    $prev["destination"],
                    $prev["type"],
                    $prev["leg_departure_time"],
                    $prev["leg_arrival_time"],
                    $callingPoints,
                    $prev["service"],
                    $prev["operator"]
                );

                $callingPoints = [];
            }

            $prev = $row;
            $callingPoints[] = new CallingPoint($prev["station"], $prev["arrival_time"], $prev["departure_time"]);
        }

        if ($prev !== null) {
            $result[] = new TimetableLeg(
                $prev["origin"],
                $prev["destination"],
                $prev["type"],
                $prev["leg_departure_time"],
                $prev["leg_arrival_time"],
                $callingPoints,
                $prev["service"],
                $prev["operator"]
            );
        }

        return $result;
    }

}