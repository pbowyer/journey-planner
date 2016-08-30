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
class ConnectionScannerArriveBy implements JourneyPlanner {

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
    protected $departures;

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
        $this->nonTimetable = [];
        $this->interchangeTimes = $interchangeTimes;

        foreach ($nonTimetable as $origin => $connections) {
            foreach ($connections as $connection) {
                if (!isset($this->nonTimetable[$connection->getDestination()])) {
                    $this->nonTimetable[$connection->getDestination()] = [];
                }

                $this->nonTimetable[$connection->getDestination()][] = $connection;
            }
        }

    }

    /**
     * Find the journey with the earliest arrival time and latest departure time using
     * a modified version of the connection scan algorithm.
     *
     * @param  string $origin
     * @param  string $destination
     * @param  string $arrivalTime
     * @return Journey[]
     */
    public function getJourneys($origin, $destination, $arrivalTime) {
        $this->departures = [$destination => $arrivalTime];
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
     * @param string $origin
     * @param string $destination
     */
    private function setFastestConnections($origin, $destination) {
        // check for non timetable connections at the origin station
        $this->checkForBetterNonTimetableConnections($destination, $this->departures[$destination]);
        $seenOrigin = false;
        $numConnections = count($this->timetable) - 1;

        for ($i = $numConnections; $i >= 0; $i--) {
            $connection = $this->timetable[$i];

            if ($this->canGetToThisConnection($connection) && $this->thisConnectionIsBetter($connection)) {
                $this->connections[$connection->getOrigin()] = $connection;
                $this->departures[$connection->getOrigin()] = $connection->getDepartureTime();

                $this->checkForBetterNonTimetableConnections($connection->getOrigin(), $connection->getDepartureTime());
                $seenOrigin = $seenOrigin || $connection->getOrigin() === $origin;
            }

            // if this connection departs after the earliest arrival at the destination no connection
            // after will ever be faster so we can just return
            if ($seenOrigin && $connection->getArrivalTime() < $this->departures[$origin]) {
                return;
            }
        }
    }

    private function canGetToThisConnection(TimetableConnection $connection) {
        return isset($this->departures[$connection->getDestination()]) &&
        $connection->getArrivalTime() <= $this->departures[$connection->getDestination()] - $this->getInterchangeTime($connection);
    }

    private function getInterchangeTime(TimetableConnection $connection) {
        return isset($this->connections[$connection->getDestination()]) &&
        isset($this->interchangeTimes[$connection->getDestination()]) &&
        $this->connections[$connection->getDestination()]->requiresInterchangeWith($connection) ? $this->interchangeTimes[$connection->getDestination()] : 0;
    }

    protected function thisConnectionIsBetter(Connection $connection) {
        $noExistingConnection = !isset($this->departures[$connection->getOrigin()]);

        if ($connection instanceof NonTimetableConnection) {
            return $noExistingConnection || $this->departures[$connection->getOrigin()] < $this->departures[$connection->getDestination()] - $connection->getDuration();
        }
        else if ($connection instanceof TimetableConnection) {
            return $noExistingConnection || $this->departures[$connection->getOrigin()] < $connection->getDepartureTime();
        }

        throw new PlanningException("Unknown connection type " . get_class($connection));
    }

    /**
     * For the given station for better non-timetabled connections by calculating the potential departure time
     * at the non timetabled connections origin as the departure at the destination - the duration.
     *
     * There is an assumption that the departure at the given departure station can be made and as such
     * $this->departures[$destination] is set.
     *
     * @param $destination
     * @param int $time
     */
    private function checkForBetterNonTimetableConnections($destination, $time) {
        // check if there is a non timetable connection starting at the destination, and process it's connections
        if (isset($this->nonTimetable[$destination])) {
            /** @var NonTimetableConnection $connection */
            foreach ($this->nonTimetable[$destination] as $connection) {
                if ($connection->isAvailableAt($time) && $this->thisConnectionIsBetter($connection)) {
                    $this->connections[$connection->getOrigin()] = $connection;
                    $this->departures[$connection->getOrigin()] = $this->departures[$connection->getDestination()] - $connection->getDuration();
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

        while (isset($this->connections[$origin])) {
            $thisConnection = $this->connections[$origin];

            if ($previousConnection && $previousConnection->requiresInterchangeWith($thisConnection)) {
                $legs[] = new Leg($callingPoints);
                $callingPoints = [];
            }

            $callingPoints[] = $thisConnection;
            $previousConnection = $thisConnection;
            $origin = $this->connections[$origin]->getDestination();
        }

        // if we found a route back to the origin
        if ($destination === $origin) {
            $legs[] = new Leg($callingPoints);
            return $legs;
        }
        else {
            return null;
        }
    }
}
