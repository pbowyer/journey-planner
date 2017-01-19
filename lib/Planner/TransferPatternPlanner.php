<?php

namespace JourneyPlanner\Lib\Planner;

use JourneyPlanner\Lib\Journey\FixedLeg;
use JourneyPlanner\Lib\Journey\Journey;
use JourneyPlanner\Lib\Journey\Leg;
use JourneyPlanner\Lib\Journey\TimetableLeg;
use JourneyPlanner\Lib\TransferPattern\PatternSegment;
use JourneyPlanner\Lib\TransferPattern\TransferPattern;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternPlanner {

    /**
     * Stores the list of connections. Please note that this timetable must be time ordered
     *
     * @var TransferPattern
     */
    private $transferPattern;

    /**
     * Stores the list of fixed legs
     *
     * @var FixedLeg[]
     */
    private $fixedLegs;

    /**
     * HashMap of station => interchange time required at that station when changing service
     *
     * @var array
     */
    private $interchangeTimes;

    /**
     * @param TransferPattern $schedule
     * @param FixedLeg[] $nonTimetable
     * @param array $interchangeTimes
     */
    public function __construct(TransferPattern $schedule, array $nonTimetable, array $interchangeTimes) {
        $this->transferPattern = $schedule;
        $this->fixedLegs = $nonTimetable;
        $this->interchangeTimes = $interchangeTimes;
    }

    /**
     * This journey planner uses a transfer pattern (precalculated legs) to scan a set of schedules and
     * return a set of viable journeys
     *
     * @param  string $origin
     * @param  string $destination
     * @param int $departureTime
     * @return array|Journey[]
     */
    public function getJourneys(string $origin, string $destination, int $departureTime): array {
        $journeys = [];
        $patternSegments = $this->transferPattern->getSegments();
        $segment = array_shift($patternSegments);

        try {
            // create a journey for each connection in the first leg
            foreach ($segment->getLegs() as $leg) {
                // check if we need a transfer to get to the origin
                if ($leg->getOrigin() !== $origin) {
                    $legs = [$this->getTransfer($origin, $leg->getOrigin(), $departureTime), $leg];
                }
                else {
                    $legs = [$leg];
                }
                
                // if there is only one leg, create the journey and return
                if ($leg->getDestination() === $destination) {
                    $journeys[] = new Journey($legs);
                }
                else {
                    $journeys[] = $this->getJourneyAfter($leg, $patternSegments, $destination, $legs);
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
     * @param  TimetableLeg $previousLeg
     * @param  PatternSegment[] $patternSegments
     * @param  string $destination
     * @param  Leg[] $legs
     * @return Journey
     * @throws PlanningException
     */
    private function getJourneyAfter(TimetableLeg $previousLeg, array $patternSegments, $destination, array &$legs): Journey {
        $transferTime = 0;
        $currentTransferLeg = array_shift($patternSegments);

        // if we've run out of legs to scan maybe there is a transfer at the end
        if ($currentTransferLeg === null) {
            $legs[] = $this->getTransfer($previousLeg->getDestination(), $destination, $previousLeg->getArrivalTime());
            
            return new Journey($legs);
        }

        // if these connections aren't linked, we might need a non-timetable connection to link us
        if ($previousLeg->getDestination() !== $currentTransferLeg->getOrigin()) {
            $transfer = $this->getTransfer($previousLeg->getDestination(), $currentTransferLeg->getOrigin(), $previousLeg->getArrivalTime());
            $legs[] = $transfer;
            $transferTime = $transfer->getDuration();
        }

        foreach ($currentTransferLeg->getLegs() as $leg) {
            if ($previousLeg->getArrivalTime() + $transferTime + $this->interchange($leg->getOrigin()) <= $leg->getDepartureTime()) {
                $legs[] = $leg;

                if ($leg->getDestination() === $destination) {
                    return new Journey($legs);
                }
                else {
                    return $this->getJourneyAfter($leg, $patternSegments, $destination, $legs);
                }
            }
        }

        throw new PlanningException("Ran out of connections before reaching the destination");
    }

    /**
     * @param  string $station
     * @return int
     */
    private function interchange($station): int {
        return isset($this->interchangeTimes[$station]) ? $this->interchangeTimes[$station] : 0;
    }

    /**
     * Return a transfer that is available between the given stations at the given time or throw an exception
     *
     * @param  string $origin
     * @param  string $destination
     * @param  int $time
     * @return FixedLeg
     * @throws PlanningException
     */
    private function getTransfer($origin, $destination, $time): FixedLeg {
        if (!isset($this->fixedLegs[$origin])) {
            throw new PlanningException("No connection between {$origin} and {$destination}");
        }

        /** @var FixedLeg $transfer */
        foreach ($this->fixedLegs[$origin] as $transfer) {
            if ($transfer->getDestination() === $destination && $transfer->isAvailableAt($time)) {
                return $transfer;
            }
        }

        throw new PlanningException("No fixed leg between {$origin} and {$destination}");
    }
}
