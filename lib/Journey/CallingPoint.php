<?php

namespace JourneyPlanner\Lib\Journey;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class CallingPoint {

    private $station;
    private $arrivalTime;
    private $departureTime;

    /**
     * @param $station
     * @param $arrivalTime
     * @param $departureTime
     */
    public function __construct(string $station, int $arrivalTime = null, int $departureTime = null) {
        $this->station = $station;
        $this->arrivalTime = $arrivalTime;
        $this->departureTime = $departureTime;
    }

    /**
     * @return string
     */
    public function getStation(): string {
        return $this->station;
    }

    /**
     * @return int|null
     */
    public function getArrivalTime() {
        return $this->arrivalTime;
    }

    /**
     * @return int|null
     */
    public function getDepartureTime() {
        return $this->departureTime;
    }

}