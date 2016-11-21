<?php

namespace JourneyPlanner\Lib\Storage\Schedule;

use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class DefaultProvider implements ScheduleProvider {

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
     * Grab all connections after the target time
     *
     * @param  string $startTimestamp
     * @return TimetableConnection[]
     */
    public function getTimetableConnections($startTimestamp) {
        $dow = lcfirst(gmdate('l', $startTimestamp));

        $stmt = $this->db->prepare("
            SELECT TIME_TO_SEC(departure_time) as departure_time, TIME_TO_SEC(arrival_time) as arrival_time, origin, destination, service, operator, type as mode
            FROM timetable_connection
            WHERE departure_time >= :startTime
            AND start_date <= :startDate AND end_date >= :startDate
            AND {$dow} = 1
            ORDER BY arrival_time
        ");

        $stmt->execute([
            'startTime' => gmdate("H:i:s", $startTimestamp),
            'startDate' => gmdate("Y-m-d", $startTimestamp),
        ]);

        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new TimetableConnection(
                $row["origin"],
                $row["destination"],
                $row["departure_time"],
                $row["arrival_time"],
                $row["service"],
                $row["operator"],
                $row["mode"]
            );
        }

        return $results;
    }

    /**
     * @param int $targetTimestamp
     * @return NonTimetableConnection[]
     */
    public function getNonTimetableConnections($targetTimestamp) {
        $dow = lcfirst(gmdate('l', $targetTimestamp));

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

        $stmt->execute([
            "targetDate" => gmdate("Y-m-d", $targetTimestamp)
        ]);

        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $connection = new NonTimetableConnection(
                $row["origin"],
                $row["destination"],
                $row["duration"],
                $row["mode"]
            );

            if (isset($results[$row["origin"]])) {
                $results[$row["origin"]][] = $connection;
            }
            else {
                $results[$row["origin"]] = [$connection];
            }
        }

        return $results;
    }

    /**
     * @return array
     */
    public function getInterchangeTimes() {
        $stmt = $this->db->query("SELECT from_stop_id, min_transfer_time FROM transfers");
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['from_stop_id']] = $row['min_transfer_time'];
        }

        return $results;
    }

    /**
     * @param $origin
     * @param $destination
     * @param $startTimestamp
     * @return TransferPatternSchedule[]
     */
    public function getTimetable($origin, $destination, $startTimestamp) {
        $dow = lcfirst(gmdate('l', $startTimestamp));

        $stmt = $this->db->prepare("
            SELECT 
              leg.transfer_pattern as transfer_pattern,
              leg.id as transfer_leg,
              dept.service,
              dept.origin,
              arrv.destination,
              TIME_TO_SEC(dept.departure_time) as departure_time,
              TIME_TO_SEC(arrv.arrival_time) as arrival_time,
              arrv.operator,
              arrv.type
            FROM transfer_pattern
            JOIN transfer_pattern_leg leg ON transfer_pattern.id = leg.transfer_pattern
            JOIN timetable_connection dept ON leg.origin = dept.origin
            JOIN timetable_connection arrv ON leg.destination = arrv.destination AND dept.service = arrv.service
            WHERE arrv.arrival_time > dept.departure_time
            AND transfer_pattern.origin = :origin
            AND transfer_pattern.destination = :destination
            AND dept.departure_time >= :departureTime
            AND dept.start_date <= :startDate AND dept.endDate >= :startDate
            AND dept.{$dow} = 1
            ORDER BY leg.transfer_pattern, leg.id, arrv.arrival_time, dept.service
        ");

        $stmt->execute([
            'departureTime' => gmdate("H:i:s", $startTimestamp),
            'startDate' => gmdate("Y-m-d", $startTimestamp),
            'origin' => $origin,
            'destination' => $destination
        ]);

        $factory = new TransferPatternScheduleFactory();

        return $factory->getSchedulesFromTimetable($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

}