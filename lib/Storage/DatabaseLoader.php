<?php

namespace JourneyPlanner\Lib\Storage;

use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPattern;
use JourneyPlanner\Lib\Network\TransferPatternLeg;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;
use PDO;
use PDOStatement;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class DatabaseLoader {

    /**
     * @var PDO
     */
    private $db;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Get any connections that are relevant to this query
     *
     * @param  int $startTimestamp
     * @param  string $origin    e.g. CHX
     * @return TimetableConnection[]
     */
    public function getTimetableConnections($startTimestamp, $origin) {
        $dow = lcfirst(date('l', $startTimestamp));

        $stmt = $this->db->prepare("
            SELECT TIME_TO_SEC(c.departureTime) as departureTime, TIME_TO_SEC(c.arrivalTime) as arrivalTime, c.origin, c.destination, c.service
            FROM timetable_connection c
            JOIN shortest_path sp
              ON c.destination = sp.destination
              AND :origin = sp.origin
            WHERE departureTime > SEC_TO_TIME(:startTime + sp.duration)
            AND startDate <= :startDate AND endDate >= :startDate
            AND {$dow} = 1
            ORDER BY arrivalTime
        ");

        $stmt->execute([
            'startTime' => strtotime('1970-01-01 '.date("H:i:s", $startTimestamp)),
            'startDate' => date("Y-m-d", $startTimestamp),
            'origin' => $origin
        ]);

        return $stmt->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'JourneyPlanner\Lib\Network\TimetableConnection', ['','','','','']);
    }

    /**
     * Grap all connections after the target time
     *
     * @param  string $startTimestamp
     * @return TimetableConnection[]
     */
    public function getUnprunedTimetableConnections($startTimestamp) {
        $dow = lcfirst(date('l', $startTimestamp));

        $stmt = $this->db->prepare("
            SELECT TIME_TO_SEC(departureTime) as departureTime, TIME_TO_SEC(arrivalTime) as arrivalTime, origin, destination, service
            FROM timetable_connection
            WHERE departureTime > SEC_TO_TIME(:startTime)
            AND startDate <= :startDate AND endDate >= :startDate
            AND {$dow} = 1
            ORDER BY arrivalTime
        ");

        $stmt->execute([
            'startTime' => strtotime('1970-01-01 '.date("H:i:s", $startTimestamp)),
            'startDate' => date("Y-m-d", $startTimestamp),
        ]);

        return $stmt->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'JourneyPlanner\Lib\Network\TimetableConnection', ['','','','','']);
    }

    /**
     * Return all the pre-cached fastest connections between two stops
     *
     * @return array
     */
    public function getFastestConnections() {
        $stmt = $this->db->query("SELECT TIME_TO_SEC(departureTime) as departureTime, TIME_TO_SEC(arrivalTime) as arrivalTime, origin, destination, service FROM fastest_connection");

        return $stmt->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'JourneyPlanner\Lib\Network\TimetableConnection', ['','','','','']);
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
     * @param  string $origin
     * @param  string $destination
     * @param  int $startTimestamp
     * @return TransferPatternSchedule[]
     */
    public function getScheduleFromTransferPattern($origin, $destination, $startTimestamp) {
        $dow = lcfirst(date('l', $startTimestamp));

        $stmt = $this->db->prepare("
            SELECT
              leg.transfer_pattern as transfer_pattern,
              leg.id as transfer_leg,
              train_uid as service,
              cstation.parent_station as station,
              TIME_TO_SEC(calling.arrival_time) as arrivalTime,
              TIME_TO_SEC(calling.departure_time) as departureTime
            FROM transfer_pattern
            JOIN transfer_pattern_leg leg ON transfer_pattern.id = leg.transfer_pattern
            JOIN stops ostation ON ostation.parent_station = leg.origin
            JOIN stops dstation ON dstation.parent_station = leg.destination
            JOIN stop_times as dept ON dept.stop_id = ostation.stop_id
            JOIN stop_times as arrv ON arrv.trip_id = dept.trip_id and arrv.stop_id = dstation.stop_id
            JOIN trips on dept.trip_id = trips.trip_id
            JOIN calendar USING(service_id)
            JOIN stop_times as calling ON dept.trip_id = calling.trip_id AND calling.stop_sequence >= dept.stop_sequence AND calling.stop_sequence <= arrv.stop_sequence
            JOIN stops cstation ON cstation.stop_id = calling.stop_id   
            WHERE arrv.stop_sequence > dept.stop_sequence
            AND transfer_pattern.origin = :origin
            AND transfer_pattern.destination = :destination
            AND dept.departure_time >= SEC_TO_TIME(:departureTime)
            AND start_date <= :startDate AND end_date >= :startDate
            AND {$dow} = 1
            ORDER BY leg.transfer_pattern, leg.id, calling.trip_id, calling.stop_sequence
        ");

        $stmt->execute([
            'departureTime' => strtotime('1970-01-01 '.date("H:i:s", $startTimestamp)),
            'startDate' => date("Y-m-d", $startTimestamp),
            'origin' => $origin,
            'destination' => $destination
        ]);

        $factory = new TransferPatternScheduleFactory();

        return $factory->getSchedules($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    

}
