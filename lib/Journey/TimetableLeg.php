<?php

namespace JourneyPlanner\Lib\Journey;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TimetableLeg extends Leg {

    private $service;
    private $operator;
    private $callingPoints;
    private $departureTime;
    private $arrivalTime;

    /**
     * @param string $origin
     * @param string $destination
     * @param string $mode
     * @param int $departureTime
     * @param int $arrivalTime
     * @param CallingPoint[] $callingPoints
     * @param string $service
     * @param string $operator
     */
    public function __construct(string $origin,
                                string $destination,
                                string $mode,
                                int $departureTime,
                                int $arrivalTime,
                                array $callingPoints,
                                string $service,
                                string $operator) {
        parent::__construct($origin, $destination, $mode);

        $this->callingPoints = $callingPoints;
        $this->service = $service;
        $this->operator = $operator;
        $this->departureTime = $departureTime;
        $this->arrivalTime = $arrivalTime;
    }

    /**
     * @return CallingPoint[]
     */
    public function getCallingPoints(): array {
        return $this->callingPoints;
    }

    /**
     * @return int
     */
    public function getDepartureTime(): int {
        return $this->departureTime;
    }

    /**
     * @return int
     */
    public function getArrivalTime(): int {
        return $this->arrivalTime;
    }

    /**
     * @return string
     */
    public function getService(): string {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getOperator(): string {
        return $this->operator;
    }



}