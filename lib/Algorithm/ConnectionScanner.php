<?php

namespace JourneyPlanner\Lib\Algorithm;

use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class ConnectionScanner implements JourneyPlanner, MinimumSpanningTreeGenerator {

    /**
     * Stores the list of connections. Please note that this timetable must be time ordered
     *
     * @var TimetableConnection[]
     */
    private $timetable;

    /**
     * Stores the list of non timetabled connections
     *
     * @var NonTimetableConnection[]
     */
    private $nonTimetable;

    /**
     * HashMap of station => interchange time required at that station when changing service
     *
     * @var int[]
     */
    private $interchangeTimes;

    /**
     * HashMap storing the fastest available to connection to each station that can actually be
     * made based on previous connections.
     *
     * @var Connection[]
     */
    protected $connections;

    /**
     * HashMap storing each connections earliest arrival time, it's used for convenience
     * when comparing connections with each other.
     *
     * @var int[]
     */
    protected $arrivals;

    /**
     * @var Journey[][]
     */
    protected $journeysTo;
    private $startStation;
    private $departureTime;

    /**
     * @param array $timetable
     * @param array $nonTimetable
     * @param array $interchangeTimes
     */
    public function __construct(array $timetable, array $nonTimetable, array $interchangeTimes) {
        $this->timetable = $timetable;
        $this->nonTimetable = $nonTimetable;
        $this->interchangeTimes = $interchangeTimes;
    }

    /**
     * Find the journey with the earliest arrival time and latest departure time using
     * a modified version of the connection scan algorithm.
     *
     * @param  string $origin
     * @param  string $destination
     * @param  string $departureTime
     * @return Journey[]
     */
    public function getJourneys($origin, $destination, $departureTime) {
        $this->journeysTo = [];
        $this->startStation = $origin;
        $this->departureTime = $departureTime;

        $this->setFastestConnections($origin, $destination, $departureTime);
//print_r($this->journeysTo);die();
//        $legs = $this->getLegsFromConnections($origin, $destination);
//
//        if (count($legs) > 0) {
//            return [new Journey($legs)];
//        }
//        else {
//            return [];
//        }

        if (isset($this->journeysTo[$destination]) && count($this->journeysTo[$destination]) > 0) {
            return [$this->getBestJourneyTo($destination)];
        }
        else {
            return [];
        }
    }

    /**
     * Create a HashMap containing the best connections to each station. At present
     * the fastest connection is considered best.
     *
     * @param string $startStation
     * @param string $finalDestination
     */
    private function setFastestConnections($startStation, $finalDestination, $departureTime) {
        // check for non timetable connections at the origin station
        $this->checkForBetterNonTimetableConnections($startStation, $departureTime);
        $seenDestination = false;

        foreach ($this->timetable as $connection) {
            $this->addConnection($connection);

            $seenDestination = $seenDestination || isset($this->journeysTo[$finalDestination]) && count($this->journeysTo[$finalDestination]) > 0;

            // if this connection departs after the earliest arrival at the destination no connection
            // after will ever be faster so we can just return
            if ($seenDestination && $connection->getDepartureTime() > $this->journeysTo[$finalDestination][0]->getArrivalTime()) {
                return;
            }
        }
    }

    private function addConnection(Connection $connection) {
        if (!isset($this->journeysTo[$connection->getDestination()])) {
            $this->journeysTo[$connection->getDestination()] = [];
        }

        if ($connection->getOrigin() === $this->startStation && $connection->isAvailableAt($this->departureTime)) {
            $newJourney = new Journey([new Leg([$connection])]);
            $this->journeysTo[$connection->getDestination()][] = $newJourney;

            if ($connection instanceof TimetableConnection) {
                $this->checkForBetterNonTimetableConnections($connection->getDestination(), $connection->getArrivalTime());
            }

            return;
        }

        if (isset($this->journeysTo[$connection->getOrigin()])) {
            $i = 0;
            /** @var Journey $journey */
            foreach ($this->journeysTo[$connection->getOrigin()] as $journey) {
                if ($i++ > 10) {
                    break;
                }

                if ($connection->isAvailableAt($journey->getArrivalTime() + $this->getInterchangeTime($journey, $connection))) {
                    $newJourney = $journey->addConnection($connection);
                    $this->journeysTo[$connection->getDestination()][] = $newJourney;

                    if ($connection instanceof TimetableConnection) {
                        $this->checkForBetterNonTimetableConnections($connection->getDestination(), $connection->getArrivalTime());
                    }
                }
            }
        }
    }

    private function getInterchangeTime(Journey $journey, Connection $connection) {
        return $journey->requiresInterchangeWith($connection) &&
               isset($this->interchangeTimes[$connection->getOrigin()]) ? $this->interchangeTimes[$connection->getOrigin()] : 0;
    }

    /**
     * For the given station for better non-timetabled connections by calculating the potential arrival time
     * at the non timetabled connections destination as the arrival at the origin + the duration.
     *
     * There is an assumption that the arrival at the given origin station can be made and as such $this->arrivals[$origin]
     * is set.
     *
     * @param string $origin
     * @param int $time
     */
    private function checkForBetterNonTimetableConnections($origin, $time) {
        // check if there is a non timetable connection starting at the destination, and process it's connections
        if (isset($this->nonTimetable[$origin])) {
            /** @var NonTimetableConnection $connection */
            foreach ($this->nonTimetable[$origin] as $connection) {
                if ($connection->isAvailableAt($time)) {
                    $this->addConnection($connection);
                }
            }
        }
    }

    /**
     * Slightly modified version of the CSA that returns the shortest journeys
     * to each station from the given origin.
     *
     * @param string $origin
     * @param int $departureTime
     * @return Journey[]
     */
    public function getShortestPathTree($origin, $departureTime) {
        $this->journeysTo = [];
        $this->startStation = $origin;
        $this->departureTime = $departureTime;

        $this->setFastestConnections($origin, null, $departureTime);
        $tree = [];

        foreach (array_keys($this->journeysTo) as $destination) {
            if (isset($this->journeysTo[$destination]) && count($this->journeysTo[$destination]) > 0) {
                $tree[$destination] = $this->getBestJourneyTo($destination);
            }
        }

        return $tree;
    }

    private function getBestJourneyTo($destination) {
        $leastChanges = $this->journeysTo[$destination][0];
        $earliestArrival = $this->journeysTo[$destination][0];
        $shortestJourney = $this->journeysTo[$destination][0];

//print_r($this->journeysTo[$destination]);
        foreach ($this->journeysTo[$destination] as $journey) {
            $arrivesEarlier = $journey->getArrivalTime() < $earliestArrival->getArrivalTime();
            $sameArrival = $journey->getArrivalTime() === $earliestArrival->getArrivalTime();
            $isQuicker = $journey->getDuration() < $earliestArrival->getDuration();
            $sameSpeed = $journey->getDuration() === $earliestArrival->getDuration();
            $hasLessChanges = $journey->getNumChanges() < $earliestArrival->getNumChanges();
            //$sameChanges = $journey->getNumChanges() < $earliestArrival->getNumChanges();

            if ($arrivesEarlier || $sameArrival && $isQuicker || $sameArrival && $sameSpeed && $hasLessChanges) {
                $earliestArrival = $journey;
            }
        }

        return $earliestArrival;
    }

}
