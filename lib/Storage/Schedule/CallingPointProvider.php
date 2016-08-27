<?php

namespace JourneyPlanner\lib\Storage\Schedule;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;
use JourneyPlanner\Lib\Storage\Schedule\DefaultProvider;
use JourneyPlanner\Lib\Storage\Schedule\ScheduleProvider;
use JourneyPlanner\Lib\Storage\Schedule\TransferPatternScheduleFactory;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class CallingPointProvider extends DefaultProvider implements ScheduleProvider {

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
     * Return the schedules including the calling points of each leg
     *
     * @param $origin
     * @param $destination
     * @param $startTimestamp
     * @return TransferPatternSchedule[]
     */
    public function getTimetableWithCallingPoints($origin, $destination, $startTimestamp) {
        $dow = lcfirst(gmdate('l', $startTimestamp));

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
            'departureTime' => gmdate("H:i:s", $startTimestamp),
            'startDate' => gmdate("Y-m-d", $startTimestamp),
            'origin' => $origin,
            'destination' => $destination
        ]);

        $factory = new TransferPatternScheduleFactory();

        return $factory->getSchedulesFromTimetable($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

}