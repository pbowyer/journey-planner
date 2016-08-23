<?php

namespace JourneyPlanner\Lib\Storage;

use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class DatabaseLoader implements ScheduleProvider {

    const TP_CACHE_KEY = "|TRANSFER_PATTERN|",
          TT_CACHE_KEY = "|TIMETABLE|";

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
        $this->db = $pdo;
        $this->cache = $cache;
    }
    
    /**
     * Grab all connections after the target time
     *
     * @param  string $startTimestamp
     * @return TimetableConnection[]
     */
    public function getTimetableConnections($startTimestamp) {
        $dow = lcfirst(date('l', $startTimestamp));

        $stmt = $this->db->prepare("
            SELECT TIME_TO_SEC(departureTime) as departureTime, TIME_TO_SEC(arrivalTime) as arrivalTime, origin, destination, service, operator, type as mode
            FROM timetable_connection
            WHERE departureTime >= :startTime
            AND startDate <= :startDate AND endDate >= :startDate
            AND {$dow} = 1
            ORDER BY arrivalTime
        ");

        $stmt->execute([
            'startTime' => date("H:i:s", $startTimestamp),
            'startDate' => date("Y-m-d", $startTimestamp),
        ]);

        return $stmt->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'JourneyPlanner\Lib\Network\TimetableConnection', ['','','','','','']);
    }
    
    /**
     * @param int $targetTimestamp
     * @return NonTimetableConnection[]
     */
    public function getNonTimetableConnections($targetTimestamp) {
        $dow = lcfirst(date('l', $targetTimestamp));

        $stmt = $this->db->prepare("
            SELECT 
                from_stop_id as origin, 
                to_stop_id as destination, 
                link_secs as duration, 
                mode, 
                TIME_TO_SEC(start_time) as startTime,
                TIME_TO_SEC(end_time) as endTime
            FROM links
            WHERE start_date <= :targetDate AND end_date >= :targetDate
            AND {$dow} = 1

        ");

        $stmt->execute([
            "targetDate" => date("Y-m-d", $targetTimestamp)
        ]);

        $results = [];

        foreach ($stmt->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'JourneyPlanner\Lib\Network\NonTimetableConnection', ['','','','','']) as $c) {
            if (isset($results[$c->getOrigin()])) {
                $results[$c->getOrigin()][] = $c;
            }
            else {
                $results[$c->getOrigin()] = [$c];
            }
        }

        return $results;
    }

    /**
     * @return array
     */
    public function getInterchangeTimes() {
        $stmt = $this->db->query("SELECT station, duration FROM interchange");
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['station']] = $row['duration'];
        }

        return $results;
    }

    /**
     * @return array
     */
    public function getLocations() {
        $stmt = $this->db->query("SELECT stop_code, stop_name FROM stops WHERE stop_code != ''");
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['stop_code']] = $row['stop_name'];
        }

        return $results;
    }
    
    /**
     * @param $origin
     * @param $destination
     * @param $startTimestamp
     * @return TransferPatternSchedule[]
     */
    public function getTimetableInOneQuery($origin, $destination, $startTimestamp) {
        $dow = lcfirst(date('l', $startTimestamp));

        $stmt = $this->db->prepare("
            SELECT 
              leg.transfer_pattern as transfer_pattern,
              leg.id as transfer_leg,
              dept.service,
              dept.origin,
              arrv.destination,
              TIME_TO_SEC(dept.departureTime) as departureTime,
              TIME_TO_SEC(arrv.arrivalTime) as arrivalTime,
              arrv.operator,
              arrv.type
            FROM transfer_pattern
            JOIN transfer_pattern_leg leg ON transfer_pattern.id = leg.transfer_pattern
            JOIN timetable_connection dept ON leg.origin = dept.origin
            JOIN timetable_connection arrv ON leg.destination = arrv.destination AND dept.service = arrv.service
            WHERE arrv.arrivalTime > dept.departureTime
            AND transfer_pattern.origin = :origin
            AND transfer_pattern.destination = :destination
            AND dept.departureTime >= :departureTime
            AND dept.startDate <= :startDate AND dept.endDate >= :startDate
            AND dept.{$dow} = 1
            ORDER BY leg.transfer_pattern, leg.id, arrv.arrivalTime, dept.service
        ");

        $stmt->execute([
            'departureTime' => date("H:i:s", $startTimestamp),
            'startDate' => date("Y-m-d", $startTimestamp),
            'origin' => $origin,
            'destination' => $destination
        ]);

        $factory = new TransferPatternScheduleFactory();

        return $factory->getSchedulesFromTimetable($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @param $origin
     * @param $destination
     * @param $startTimestamp
     * @return TransferPatternSchedule[]
     */
    public function getTimetable($origin, $destination, $startTimestamp) {
        $dow = lcfirst(date('l', $startTimestamp));
        $results = [];

        foreach ($this->getTransferPatterns($origin, $destination) as $row) {
            $timetable = $this->getScheduleSegment($row["origin"], $row["destination"], $startTimestamp, $dow);

            foreach ($timetable as $result) {
                $result["transfer_pattern"] = $row["transfer_pattern"];
                $result["transfer_leg"] = $row["leg"];

                $results[] = $result;
            }
        }

        $factory = new TransferPatternScheduleFactory();

        return $factory->getSchedulesFromTimetable($results);
    }

    private function getTransferPatterns($origin, $destination) {
        $cachedValue = $this->cache->getObject(self::TP_CACHE_KEY.$origin.$destination);

        if ($cachedValue !== false) {
            return $cachedValue;
        }

        $stmt = $this->db->prepare("
            SELECT 
              leg.transfer_pattern as transfer_pattern,
              leg.id as leg,
              leg.origin,
              leg.destination
            FROM transfer_pattern
            JOIN transfer_pattern_leg leg ON transfer_pattern.id = leg.transfer_pattern
            WHERE transfer_pattern.origin = :origin
            AND transfer_pattern.destination = :destination
            ORDER BY leg.transfer_pattern, leg.id
        ");

        $stmt->execute([
            'origin' => $origin,
            'destination' => $destination
        ]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->cache->setObject(self::TP_CACHE_KEY.$origin.$destination, $result);

        return $result;
    }

    private function getScheduleSegment($origin, $destination, $startTimestamp, $dow) {
        $cachedValue = $this->cache->getObject(self::TT_CACHE_KEY.$origin.$destination.$startTimestamp.$dow);

        if ($cachedValue !== false) {
            return $cachedValue;
        }

        $stmt = $this->db->prepare("
            SELECT 
              dept.service,
              dept.origin,
              arrv.destination,
              TIME_TO_SEC(dept.departureTime) as departureTime,
              TIME_TO_SEC(arrv.arrivalTime) as arrivalTime,
              arrv.operator,
              arrv.type
            FROM timetable_connection dept
            JOIN timetable_connection arrv ON dept.service = arrv.service
            WHERE arrv.arrivalTime > dept.departureTime
            AND dept.origin = :origin
            AND arrv.destination = :destination
            AND dept.departureTime >= :departureTime
            AND dept.startDate <= :startDate AND dept.endDate >= :startDate
            AND dept.{$dow} = 1
            ORDER BY arrv.arrivalTime, dept.service
        ");

        $stmt->execute([
            'departureTime' => date("H:i:s", $startTimestamp),
            'startDate' => date("Y-m-d", $startTimestamp),
            'origin' => $origin,
            'destination' => $destination
        ]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->cache->setObject(self::TT_CACHE_KEY.$origin.$destination.$startTimestamp.$dow, $result);

        return $result;
    }

    /**
     * @param $origin
     * @param $destination
     * @param $startTimestamp
     * @return TransferPatternSchedule[]
     */
    public function getTimetableWithCallingPoints($origin, $destination, $startTimestamp) {
        $dow = lcfirst(date('l', $startTimestamp));

        $stmt = $this->db->prepare("
            SELECT 
              leg.transfer_pattern as transfer_pattern,
              leg.id as transfer_leg,
              tt.service,
              tt.origin,
              tt.destination,
              TIME_TO_SEC(tt.departureTime) as departureTime,
              TIME_TO_SEC(tt.arrivalTime) as arrivalTime,
              tt.operator,
              tt.type
            FROM transfer_pattern
            JOIN transfer_pattern_leg leg ON transfer_pattern.id = leg.transfer_pattern
            JOIN timetable_connection dept ON leg.origin = dept.origin
            JOIN timetable_connection arrv ON leg.destination = arrv.destination AND dept.service = arrv.service
            JOIN timetable_connection tt ON tt.service = dept.service AND tt.departureTime >= dept.departureTime AND tt.arrivalTime <= arrv.arrivalTime
            WHERE arrv.arrivalTime > dept.departureTime
            AND transfer_pattern.origin = :origin
            AND transfer_pattern.destination = :destination
            AND dept.departureTime >= :departureTime
            AND dept.startDate <= :startDate AND dept.endDate >= :startDate
            AND dept.{$dow} = 1
            ORDER BY leg.transfer_pattern, leg.id, arrv.arrivalTime, tt.service, tt.departureTime
        ");

        $stmt->execute([
            'departureTime' => date("H:i:s", $startTimestamp),
            'startDate' => date("Y-m-d", $startTimestamp),
            'origin' => $origin,
            'destination' => $destination
        ]);

        $factory = new TransferPatternScheduleFactory();

        return $factory->getSchedulesFromTimetable($stmt->fetchAll(PDO::FETCH_ASSOC));
    }


    /**
     * @param $station
     * @return string[]
     */
    public function getRelevantStations($station) {
        if (strlen($station) === 3) {
            return [$station];
        }

        $stmt = $this->db->prepare("SELECT member_crs FROM group_station WHERE group_nlc = ?");
        $stmt->execute([$station]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
