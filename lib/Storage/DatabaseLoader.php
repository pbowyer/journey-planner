<?php

namespace JourneyPlanner\Lib\Storage;

use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;
use PDO;

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
     * @return NonTimetableConnection[]
     */
    public function getNonTimetableConnections() {
        $stmt = $this->db->query("SELECT origin, destination, duration, mode FROM non_timetable_connection");
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
     * @param  int $dateTime
     * @return TimetableConnection[]
     */
    public function getScheduleFromTransferPattern($origin, $destination, $dateTime) {
        $stmt = $this->db->query("
            SELECT leg.transfer_pattern, leg.id, dept.trip_id, dept.stop_id, dept.departure_time, arrv.stop_id, arrv.arrival_time
            FROM transfer_pattern
            JOIN transfer_pattern_leg leg ON transfer_pattern.id = leg.transfer_pattern
            JOIN stop_times as dept ON dept.stop_id = leg.origin
            JOIN stop_times as arrv ON arrv.trip_id = dept.trip_id and arrv.stop_id = leg.destination
            JOIN trips on dept.trip_id = trips.trip_id
            JOIN calendar USING(service_id)
            WHERE arrv.stop_sequence > dept.stop_sequence
            AND transfer_pattern.origin = :origin
            AND transfer_pattern.destination = :destination
            AND dept.departure_time > :departureTime
            AND startDate <= :startDate AND endDate >= :startDate
            AND {$dow} = 1
            ORDER BY leg.transfer_pattern, leg.id, trip_id, dept.departure_time
        ");

        // TODO dateTime
    }
}
