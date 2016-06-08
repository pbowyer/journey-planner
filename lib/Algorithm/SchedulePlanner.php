<?php

namespace JourneyPlanner\Lib\Algorithm;

use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TransferPattern;
use JourneyPlanner\Lib\Network\TimetableConnection;
use Exception;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class SchedulePlanner implements JourneyPlanner {

    /**
     * Stores the list of connections. Please note that this timetable must be time ordered
     *
     * @var TransferPatternSchedule
     */
    private $schedule;

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
     * @param TransferPatternSchedule $schedule
     * @param NonTimetableConnection[] $nonTimetable
     * @param array $interchangeTimes
     */
    public function __construct(TransferPatternSchedule $schedule, array $nonTimetable, array $interchangeTimes) {
        $this->schedule = $schedule;
        $this->nonTimetable = $nonTimetable;
        $this->interchangeTimes = $interchangeTimes;
    }

    /**
     * This journey planner uses a transfer pattern (precalculated legs) to scan a set of schedules and
     * return a set of viable journeys
     *
     * @param  string $origin
     * @param  string $destination
     * @param  string $departureTime
     * @return Connection[]
     */
    public function getJourneys($origin, $destination, $departureTime) {
        $journeys = [];
        $legs = $this->schedule->getLegs();
        
        try {
            // create a journey for each connection in the first leg
            foreach (array_shift($legs) as $connection) {
                if ($connection->getDepartureTime() < $departureTime) {
                    continue;
                }
                // check we don't need a transfer to get to the origin
                if ($connection->getOrigin() === $origin) {
                    $journey = [new Leg([$connection])];
                }
                else {
                    $journey = [
                        new Leg([$this->getTransfer($origin, $connection->getOrigin())]),
                        new Leg([$connection])
                    ];
                }

                // if there is only one leg, create the journey and return
                if ($connection->getDestination() === $destination) {
                    $journeys[] = new Journey($journey);
                }
                else {
                    $journeys[] = $this->getJourneyAfter($connection, $legs, $destination, $journey);
                }
            }
        }
        catch (PlanningException $e) {}

        return $journeys;
    }

    /**
     * Recursive method that pops the next leg off the array of legs to find the first reachable connecting service.
     *
     * Once found the method calls itself again to do the next leg until there are no more legs left.
     *
     * @param  TimetableConnection $previousConnection
     * @param  array $legs
     * @param  string $destination
     * @param  Leg[] $journey
     * @return Journey
     * @throws PlanningException
     */
    private function getJourneyAfter(TimetableConnection $previousConnection, array $legs, $destination, array &$journey) {
        $transferTime = 0;
        // if these connections aren't linked, we might need a non-timetable connection to link us
        if ($previousConnection->getDestination() !== $legs[0][0]->getOrigin()) {
            $transfer = $this->getTransfer($previousConnection->getDestination(), $legs[0][0]->getOrigin());
            $journey[] = new Leg([$transfer]);
            $transferTime = $transfer->getDuration();
        }

        foreach (array_shift($legs) as $connection) {
            if ($previousConnection->getArrivalTime() + $transferTime + $this->getInterchange($connection->getOrigin()) <= $connection->getDepartureTime()) {
                $journey[] = new Leg([$connection]);

                if ($connection->getDestination() === $destination) {
                    return new Journey($journey);
                }
                else {
                    return $this->getJourneyAfter($connection, $legs, $destination, $journey);
                }
            }
        }

        throw new PlanningException("Ran out of connections before reaching the destination");
    }

    /**
     * @param  string $station
     * @return int
     */
    private function getInterchange($station) {
        return isset($this->interchangeTimes[$station]) ? $this->interchangeTimes[$station] : 0;
    }

    /**
     * @param  string $origin
     * @param  string $destination
     * @return NonTimetableConnection
     * @throws PlanningException
     */
    private function getTransfer($origin, $destination) {
        foreach ($this->nonTimetable[$origin] as $transfer) {
            if ($transfer->getDestination() === $destination) {
                return $transfer;
            }
        }

        // need to check the data range too
        throw new PlanningException("No connection between {$origin} and {$destination}");
    }
}
