<?php

namespace JourneyPlanner\Lib\Algorithm;

use JourneyPlanner\Lib\Network\Connection;
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
    private $connections;

    /**
     * HashMap storing each connections earliest arrival time, it's used for convenience
     * when comparing connections with each other.
     *
     * @var array
     */
    private $arrivals;

    /**
     * HashMap of station => interchange time required at that station when changing service
     *
     * @var array
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
     * @return array
     */
    public function getRoute($origin, $destination, $departureTime) {
        $this->arrivals = [$origin => $departureTime];
        $this->connections = [];

        $this->getConnections($origin, $destination);
        return $this->getRouteFromConnections($origin, $destination);
    }

    /**
     * Create a HashMap containing the best connections to each station. At present
     * the fastest connection is considered best.
     *
     * @param string $startStation
     * @param string $finalDestination
     */
    private function getConnections($startStation, $finalDestination) {
        // check for non timetable connections at the origin station
        $this->checkForBetterNonTimetableConnections($startStation);
        $seenDestination = false;

        foreach ($this->timetable as $connection) {
            if ($this->canGetToThisConnection($connection) && $this->thisConnectionIsBetter($connection)) {
                $this->connections[$connection->getDestination()] = $connection;
                $this->arrivals[$connection->getDestination()] = $connection->getArrivalTime();

                $this->checkForBetterNonTimetableConnections($connection->getDestination());
                $seenDestination = $seenDestination || $connection->getDestination() === $finalDestination;
            }

            // if this connection departs after the earliest arrival at the destination no connection
            // after will ever be faster so we can just return
            if ($seenDestination && $connection->getDepartureTime() > $this->arrivals[$connection->getDestination()]) {
                return;
            }
        }
    }

    private function canGetToThisConnection(TimetableConnection $connection) {
        return isset($this->arrivals[$connection->getOrigin()]) &&
               $connection->getDepartureTime() >= $this->arrivals[$connection->getOrigin()] + $this->getInterchangeTime($connection);
    }

    private function getInterchangeTime(Connection $connection) {
        return isset($this->connections[$connection->getOrigin()]) &&
               isset($this->interchangeTimes[$connection->getOrigin()]) &&
               $this->connections[$connection->getOrigin()]->requiresInterchangeWith($connection) ? $this->interchangeTimes[$connection->getOrigin()] : 0;
    }

    private function thisConnectionIsBetter(TimetableConnection $connection) {
        return !isset($this->arrivals[$connection->getDestination()]) ||
               $this->arrivals[$connection->getDestination()] > $connection->getArrivalTime();
    }

    /**
     * For the given station for better non-timetabled connnections by calculating the potential arrival time
     * at the non timetabled connections destination as the arrival at the origin + the duration.
     *
     * There is an assumption that the arrival at the given origin station can be made and as such $this->arrivals[$origin]
     * is set.
     *
     * @param string $origin
     */
    private function checkForBetterNonTimetableConnections($origin) {
        // check if there is a non timetable connection starting at the destination, and process it's connections
        if (isset($this->nonTimetable[$origin])) {
            foreach ($this->nonTimetable[$origin] as $connection) {
                $thisConnectionIsBetter = !isset($this->arrivals[$connection->getDestination()]) || $this->arrivals[$connection->getDestination()] > $this->arrivals[$connection->getOrigin()] + $connection->getDuration();

                if ($thisConnectionIsBetter) {
                    $this->connections[$connection->getDestination()] = $connection;
                    $this->arrivals[$connection->getDestination()] = $this->arrivals[$connection->getOrigin()] + $connection->getDuration();
                }
            }
        }
    }

    /**
     * Given a Hash Map of fastest connections trace back the route from the target
     * destination to the origin. If no route is found an empty array is returned
     *
     * @param  string $origin
     * @param  string $destination
     * @return array
     */
    private function getRouteFromConnections($origin, $destination) {
        $route = [];

        while (isset($this->connections[$destination])) {
            $route[] = $this->connections[$destination];
            $destination = $this->connections[$destination]->getOrigin();
        }

        // if we found a route back to the origin
        if ($origin === $destination) {
            return array_reverse($route);
        }
        else {
            return [];
        }

    }

    /**
     * Slightly modified version of the CSA that returns the shortest journeys
     * to each station from the given origin.
     *
     * @param  $origin
     * @return array
     */
    public function getShortestPathTree($origin) {
        $this->arrivals = [$origin => 0];
        $this->connections = [];

        $this->getAllConnections($origin);
        $tree = [];

        foreach (array_keys($this->connections) as $destination) {
            $tree[$destination] = $this->getRouteFromConnections($origin, $destination);
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
    private function getAllConnections($startStation) {
        // check for non timetable connections at the origin station
        $this->checkForBetterNonTimetableConnections($startStation);

        foreach ($this->timetable as $connection) {
            if ($this->canGetToThisConnection($connection) && $this->thisConnectionIsBetter($connection)) {
                $this->connections[$connection->getDestination()] = $connection;
                $this->arrivals[$connection->getDestination()] = $connection->getArrivalTime();

                $this->checkForBetterNonTimetableConnections($connection->getDestination());
            }
        }
    }
}
