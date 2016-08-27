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
     * HashMap of station => interchange time required at that station when changing service
     *
     * @var int[]
     */
    private $interchangeTimes;

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
     * Use the connection scan algorithm to find the fastest path from $origin to
     * $destination
     *
     * @param  string $origin
     * @param  string $destination
     * @param  string $departureTime
     * @return Journey[]
     */
    public function getJourneys($origin, $destination, $departureTime) {
        $this->arrivals = [$origin => $departureTime];
        $this->connections = [];

        $this->setFastestConnections($origin, $destination);
        
        $legs = $this->getLegsFromConnections($origin, $destination);
        
        if (count($legs) > 0) {
            return [new Journey($legs)];
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
    private function setFastestConnections($startStation, $finalDestination) {
        // check for non timetable connections at the origin station
        $this->checkForBetterNonTimetableConnections($startStation, $this->arrivals[$startStation]);
        $seenDestination = false;

        foreach ($this->timetable as $connection) {
            if ($this->canGetToThisConnection($connection) && $this->thisConnectionIsBetter($connection)) {
                $this->connections[$connection->getDestination()] = $connection;
                $this->arrivals[$connection->getDestination()] = $connection->getArrivalTime();

                $this->checkForBetterNonTimetableConnections($connection->getDestination(), $connection->getArrivalTime());
                $seenDestination = $seenDestination || $connection->getDestination() === $finalDestination;
            }

            // if this connection departs after the earliest arrival at the destination no connection
            // after will ever be faster so we can just return
            if ($seenDestination && $connection->getDepartureTime() > $this->arrivals[$finalDestination]) {
                return;
            }
        }
    }

    private function canGetToThisConnection(TimetableConnection $connection) {
        return isset($this->arrivals[$connection->getOrigin()]) &&
               $connection->getDepartureTime() >= $this->arrivals[$connection->getOrigin()] + $this->getInterchangeTime($connection);
    }

    private function getInterchangeTime(TimetableConnection $connection) {
        return isset($this->connections[$connection->getOrigin()]) &&
               isset($this->interchangeTimes[$connection->getOrigin()]) &&
               $this->connections[$connection->getOrigin()]->requiresInterchangeWith($connection) ? $this->interchangeTimes[$connection->getOrigin()] : 0;
    }

    protected function thisConnectionIsBetter(Connection $connection) {
        $noExistingConnection = !isset($this->arrivals[$connection->getDestination()]);

        if ($connection instanceof NonTimetableConnection) {
            return $noExistingConnection || $this->arrivals[$connection->getDestination()] > $this->arrivals[$connection->getOrigin()] + $connection->getDuration();
        }
        else if ($connection instanceof TimetableConnection) {
            return $noExistingConnection || $this->arrivals[$connection->getDestination()] > $connection->getArrivalTime();
        }
        
        throw new PlanningException("Unknown connection type " . get_class($connection));
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
                if ($connection->isAvailableAt($time) && $this->thisConnectionIsBetter($connection)) {
                    $this->connections[$connection->getDestination()] = $connection;
                    $this->arrivals[$connection->getDestination()] = $this->arrivals[$connection->getOrigin()] + $connection->getDuration();
                }
            }
        }
    }

    /**
     * Given a Hash Map of fastest connections trace back the route from the target
     * destination to the origin. If no route is found an empty array is returned.
     *
     * This method will also compare all connections along the way to ensure the
     * latest possible depart is selected, assuming it still arrives at the
     * earliest arrival time.
     *
     * @param  string $origin
     * @param  string $destination
     * @return Leg[]
     */
    private function getLegsFromConnections($origin, $destination) {
        $legs = [];
        $callingPoints = [];
        $previousConnection = null;

        while (isset($this->connections[$destination])) {
            $thisConnection = $this->connections[$destination];

            if ($previousConnection && $previousConnection->requiresInterchangeWith($thisConnection)) {
                $legs[] = new Leg(array_reverse($callingPoints));
                $callingPoints = [];
            }

            $callingPoints[] = $thisConnection;
            $previousConnection = $thisConnection;
            $destination = $this->connections[$destination]->getOrigin();
        }

        // if we found a route back to the origin
        if ($origin === $destination) {
            $legs[] = new Leg(array_reverse($callingPoints));
            return array_reverse($legs);
        }
        else {
            return null;
        }
    }

    /**
     * Slightly modified version of the CSA that returns the shortest journeys
     * to each station from the given origin.
     *
     * @param string $origin
     * @param int $departureTime
     * @return array
     */
    public function getShortestPathTree($origin, $departureTime) {
        $this->arrivals = [$origin => $departureTime];
        $this->connections = [];

        $this->setAllFastestConnections($origin);
        $tree = [];

        foreach (array_keys($this->connections) as $destination) {
            $legs = $this->getLegsFromConnections($origin, $destination);

            if (count($legs) > 0) {
                $tree[$destination] = new Journey($legs);
            }
        }

        return $tree;
    }

    /**
     * This method differs only slightly to getConnections in that it does not stop once the earliest
     * arrival at the destination has been found. It does this in order to get the fastest connections to
     * every station in the timetable.
     *
     * @param string $startStation
     */
    private function setAllFastestConnections($startStation) {
        // check for non timetable connections at the origin station
        $this->checkForBetterNonTimetableConnections($startStation, $this->arrivals[$startStation]);

        foreach ($this->timetable as $connection) {
            if ($this->canGetToThisConnection($connection) && $this->thisConnectionIsBetter($connection)) {
                $this->connections[$connection->getDestination()] = $connection;
                $this->arrivals[$connection->getDestination()] = $connection->getArrivalTime();

                $this->checkForBetterNonTimetableConnections($connection->getDestination(), $connection->getArrivalTime());
            }
        }

    }

}
