<?php

namespace JourneyPlanner\Lib\Network;


class TimetableConnection extends Connection {

    private $departureTime;
    private $arrivalTime;
    private $service;

    /**
     * @param string $origin
     * @param string $destination
     * @param int $departureTime
     * @param int $arrivalTime
     * @param string $service
     * @param string $mode
     */
    public function __construct($origin, $destination, $departureTime, $arrivalTime, $service, $mode = parent::TRAIN) {
        parent::__construct($origin, $destination, $mode);

        $this->departureTime = $departureTime;
        $this->arrivalTime = $arrivalTime;
        $this->service = $service;
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
     * Check the service ID to see if we need to change
     *
     * @param  TimetableConnection $connection
     * @return boolean
     */
    public function requiresInterchangeWith(TimetableConnection $connection) {
        return $this->service != $connection->getService();
    }
}
