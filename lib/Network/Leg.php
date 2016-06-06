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
        if ($this->isTransfer()) {
            return $this->connections[0]->getDuration();
        }
        else {
            return end($this->connections)->getArrivalTime() - $this->connections[0]->getDepartureTime();
        }
    }

    /**
     * @return bool
     */
    public function isTransfer() {
        return $this->connections[0] instanceof NonTimetableConnection;
    }

    /**
     * @return string
     */
    public function getMode() {
        return $this->connections[0]->getMode();
    }

    /**
     * @return Connection
     */
    public function getFirstConnection() {
        return $this->connections[0];
    }

    /**
     * @return Conection
     */
    public function getLastConnection() {
        return end($this->connections);
    }

    /**
     * @return Conection[]
     */
    public function getConnections() {
        return $this->connections;
    }
}