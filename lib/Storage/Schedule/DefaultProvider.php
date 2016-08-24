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
     * @param $origin
     * @param $destination
     * @param $startTimestamp
     * @return TransferPatternSchedule[]
     */
    public function getTimetable($origin, $destination, $startTimestamp) {
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

}