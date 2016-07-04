<?php

namespace JourneyPlanner\Lib\Network;


class TimetableConnection extends Connection {

    private $departureTime;
    private $arrivalTime;
    private $service;
    private $operator;

    /**
     * @param string $origin
     * @param string $destination
     * @param int $departureTime
     * @param int $arrivalTime
     * @param string $service
     * @param string $operator
     * @param string $mode
     */
    public function __construct($origin, $destination, $departureTime, $arrivalTime, $service, $operator, $mode = parent::TRAIN) {
        parent::__construct($origin, $destination, $mode);

        $this->departureTime = $departureTime;
        $this->arrivalTime = $arrivalTime;
        $this->service = $service;
        $this->operator = $operator;
    }

    /**
     * @return string
     */
    public function getService() {
        return $this->service;
    }

    /**
     * @return int
     */
    public function getDepartureTime() {
        return $this->departureTime;
    }

    /**
     * @return int
     */
    public function getArrivalTime() {
        return $this->arrivalTime;
    }

    /**
     * @return int
     */
    public function getDuration() {
        return $this->arrivalTime - $this->departureTime;
    }

    /**
     * @return string
     */
    public function getOperator() {
        return $this->operator;
    }

    /**
     * Check the service ID to see if we need to change
     *
     * @param  Connection $connection
     * @return boolean
     */
    public function requiresInterchangeWith(Connection $connection) {
        return $connection instanceof NonTimetableConnection || $this->service != $connection->getService();
    }
}
