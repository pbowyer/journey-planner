<?php

namespace JourneyPlanner\Lib\Algorithm;

use JourneyPlanner\Lib\Network\TransferPattern;
use JourneyPlanner\Lib\Network\TimetableConnection;
use Exception;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class SchedulePlanner implements JourneyPlanner {

    /**
     * Stores the list of connections. Please note that this timetable must be time ordered
     *
     * @var TransferPattern
     */
    private $transferPattern;

    /**
     * Stores the list of non timetabled connections
     *
     * @var array
     */
    private $nonTimetable;

    /**
     * HashMap of station => interchange time required at that station when changing service
     *
     * @var array
     */
    private $interchangeTimes;

    /**
     * @param TransferPattern[] $transferPattern
     * @param NonTimetableConnection[] $nonTimetable
     * @param array $interchangeTimes
     */
    public function __construct(TransferPattern $transferPattern, array $nonTimetable, array $interchangeTimes) {
        $this->transferPattern = $transferPattern;
        $this->nonTimetable = $nonTimetable;
        $this->interchangeTimes = $interchangeTimes;
    }

    /**
     * @param  string $origin
     * @param  string $destination
     * @param  string $departureTime
     * @return Connection[]
     */
    public function getRoute($origin, $destination, $departureTime) {
        $journeys = [];
        $legs = $this->transferPattern->getLegs();

        if (count($legs) === 1) {
            return $legs;
        }

        try {
            foreach (array_shift($legs) as $connection) {
                $journey = [$connection];
                $journeys[] = $this->getJourneyAfter($connection, $legs, $destination, $journey);
            }
        }
        finally {
            return $journeys;
        }
    }

    private function getJourneyAfter(TimetableConnection $previousConnection, array $legs, string $destination, array &$journey) {
        // if these connections aren't linked, we might need a non-timetable connection to link us
        if ($previousConnection->getDestination() !== $legs[0][0]->getOrigin()) {
            $journey[] = $this->getQuickestTransfer($previousConnection->getDestination(), $legs[0][0]->getOrigin());
        }

        foreach (array_shift($legs) as $connection) {
            if ($previousConnection->getArrivalTime() + $this->getInterchange($connection->getOrigin()) < $connection->getDepartureTime()) {
                $journey[] = $connection;

                if ($connection->getDestination() === $destination) {
                    return $journey;
                }
                else {
                    return $this->getJourneyAfter($connection, $legs, $destination, $journey);
                }
            }
        }

        throw new Exception("Ran out of connections before reaching the destination");
    }

    private function getInterchange($station) {
        return isset($this->interchange[$station]) ? $this->interchange[$station] : 0;
    }

    private function getQuickestTransfer($origin, $destination) {
        // foreach ...
        //  if o = d
        //  return c
        //
        // else throw cannot connect ...
        // need to check the data range too
        throw new Exception("No connection between {$origin} and {$destination}");
    }
}
