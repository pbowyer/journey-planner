<?php

namespace JourneyPlanner\Lib\Network;

use InvalidArgumentException;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class Leg extends Connection {

    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @param array $connections
     */
    public function __construct(array $connections) {
        if (count($connections) === 0) {
            throw new InvalidArgumentException("A leg must be made up of at least one connection.");
        }

        $this->connections = $connections;
        $origin = $connections[0]->getOrigin();
        $destination = end($connections)->getDestination();

        parent::__construct($origin, $destination);
    }

    /**
     * @param  Connection $connection
     * @return boolean
     */
    public function requiresInterchangeWith(Connection $connection) {
        return true;
    }

    /**
     * @return int
     */
    public function getDuration() {
        return end($this->connections)->getArrivalTime() - $this->connections[0]->getDepartureTime();
    }

    /**
     * @return bool
     */
    public function isTransfer() {
        return $this->connections[0] instanceof NonTimetableConnection;
    }

}