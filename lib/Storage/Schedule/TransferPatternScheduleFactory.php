<?php

namespace JourneyPlanner\Lib\Storage\Schedule;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternLeg;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternScheduleFactory {

    public function getSchedulesFromTimetable(array $rows) {
        if (count($rows) === 0) {
            return [];
        }
        
        $connections = [];
        $legs = [];
        $transferLegs = [];
        $patterns = [];

        $prevLeg = null;
        $prevTransferLeg = null;
        $prevPattern = null;

        foreach ($rows as $row) {
            // if the previous service was different or the service is the same on a different pattern
            if ($prevLeg && $prevLeg !== $row["service"] || $prevTransferLeg && $prevTransferLeg !== $row["transfer_leg"]) {
                $legs[] = new Leg($connections);
                $connections = [];
            }

            $connections[] = new TimetableConnection(
                $row["origin"],
                $row["destination"],
                $row["departureTime"],
                $row["arrivalTime"],
                $row["service"],
                $row["operator"],
                $row["type"]
            );

            if ($prevTransferLeg && $prevTransferLeg !== $row["transfer_leg"]) {
                $transferLegs[] = new TransferPatternLeg($legs);
                $legs = [];
            }

            if ($prevPattern && $prevPattern !== $row["transfer_pattern"]) {
                $patterns[] = new TransferPatternSchedule($transferLegs);
                $transferLegs = [];
            }

            $prevLeg = $row["service"];
            $prevTransferLeg = $row["transfer_leg"];
            $prevPattern = $row["transfer_pattern"];
        }

        $legs[] = new Leg($connections);
        $transferLegs[] = new TransferPatternLeg($legs);
        $patterns[] = new TransferPatternSchedule($transferLegs);

        return $patterns;
    }
    
    
}