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
        $transferLegs = $this->schedule->getTransferLegs();
        $transferLeg = array_shift($transferLegs);

        try {
            // create a journey for each connection in the first leg
            /** @var Leg $leg */
            foreach ($transferLeg->getLegs() as $leg) {
                if ($leg->getDepartureTime() < $departureTime) {
                    continue;
                }

                // check if we need a transfer to get to the origin
                if ($leg->getOrigin() !== $origin) {
                    $legs = [$this->getTransfer($origin, $leg->getOrigin()), $leg];
                }
                else {
                    $legs = [$leg];
                }
                
                // if there is only one leg, create the journey and return
                if ($leg->getDestination() === $destination) {
                    $journeys[] = new Journey($legs);
                }
                else {
                    $journeys[] = $this->getJourneyAfter($leg, $transferLegs, $destination, $legs);
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
     * @param  Leg $previousLeg
     * @param  TransferPatternLeg[] $transferLegs
     * @param  string $destination
     * @param  Leg[] $legs
     * @return Journey
     * @throws PlanningException
     */
    private function getJourneyAfter(Leg $previousLeg, array $transferLegs, $destination, array &$legs) {
        $transferTime = 0;
        $currentTransferLeg = array_shift($transferLegs);
        
        // if these connections aren't linked, we might need a non-timetable connection to link us
        if ($previousLeg->getDestination() !== $currentTransferLeg->getOrigin()) {
            $transfer = $this->getTransfer($previousLeg->getDestination(), $currentTransferLeg->getOrigin());
            $legs[] = $transfer;
            $transferTime = $transfer->getDuration();
        }

        foreach ($currentTransferLeg->getLegs() as $leg) {
            if ($previousLeg->getArrivalTime() + $transferTime + $this->getInterchange($leg->getOrigin()) <= $leg->getDepartureTime()) {
                $legs[] = $leg;

                if ($leg->getDestination() === $destination) {
                    return new Journey($legs);
                }
                else {
                    return $this->getJourneyAfter($leg, $transferLegs, $destination, $legs);
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
     * @return Leg
     * @throws PlanningException
     */
    private function getTransfer($origin, $destination) {
        if (!isset($this->nonTimetable[$origin])) {
            throw new PlanningException("No connection between {$origin} and {$destination}");
        }

        foreach ($this->nonTimetable[$origin] as $transfer) {
            if ($transfer->getDestination() === $destination) {
                return new Leg([$transfer]);
            }
        }

        // need to check the data range too
        throw new PlanningException("No connection between {$origin} and {$destination}");
    }
}
