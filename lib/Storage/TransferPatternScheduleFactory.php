<?php

namespace JourneyPlanner\Lib\Storage;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternLeg;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternScheduleFactory {

    /**
     * Unpack the rows returned from the database into TimetableConnections,
     * Legs, TransferLegs and TransferPatternSchedules
     *
     * This method is pretty ugly and could do with a tidy up.
     *
     * @param array $rows
     * @return TransferPatternSchedule[]
     */
    public function getSchedules(array $rows) {
        $connections = [];
        $legs = [];
        $transferLegs = [];
        $patterns = [];

        $prevStation = null;
        $prevDeparture = null;
        $prevLeg = null;
        $prevTransferLeg = null;
        $prevPattern = null;

        foreach ($rows as $row) {
            if ($prevLeg && $prevLeg !== $row["service"]) {
                $legs[] = new Leg($connections);
                $connections = [];
                $prevStation = null;
            }

            if ($prevStation) {
                $connections[] = new TimetableConnection(
                    $prevStation,
                    $row["station"],
                    $prevDeparture,
                    $row["arrivalTime"],
                    $row["service"]
                );
            }

            if ($prevTransferLeg && $prevTransferLeg !== $row["transfer_leg"]) {
                $transferLegs[] = new TransferPatternLeg($legs);
                $legs = [];
            }

            if ($prevPattern && $prevPattern !== $row["transfer_pattern"]) {
                $patterns[] = new TransferPatternSchedule($transferLegs);
                $transferLegs = [];
            }

            $prevDeparture = $row["departureTime"];
            $prevStation = $row["station"];
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