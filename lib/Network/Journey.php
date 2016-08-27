<?php

namespace JourneyPlanner\Lib\Network;
use InvalidArgumentException;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class Journey {

    /**
     * @var Leg[]
     */
    private $legs;

    /**
     * @param Leg[] $legs
     */
    public function __construct(array $legs) {
        if (count($legs) === 0) {
            throw new InvalidArgumentException("A journey must have at least one leg");
        }

        $this->legs = $legs;
    }

    /**
     * @return string
     */
    public function getOrigin() {
        return $this->legs[0]->getOrigin();
    }

    /**
     * @return string
     */
    public function getDestination() {
        return end($this->legs)->getDestination();
    }

    /**
     * @return int
     */
    public function getDepartureTime() {
        $transferDuration = 0;

        foreach ($this->legs as $leg) {
            if ($leg->isTransfer()) {
                $transferDuration += $leg->getDuration();
            }
            else {
                return $leg->getDepartureTime() - $transferDuration;
            }
        }
    }

    /**
     * @return int
     */
    public function getArrivalTime() {
        $transferDuration = 0;

        for ($i = count($this->legs) - 1; $i >= 0; $i--) {
            if ($this->legs[$i]->isTransfer()) {
                $transferDuration += $this->legs[$i]->getDuration();
            }
            else {
                return $this->legs[$i]->getArrivalTime() + $transferDuration;
            }
        }
    }

    /**
     * @return int
     */
    public function getDuration() {
        return $this->getArrivalTime() - $this->getDepartureTime();
    }

    /**
     * @return Leg[]
     */
    public function getLegs() {
        return $this->legs;
    }


    /**
     * @param string $origin
     * @param string $destination
     * @return string
     */
    public function getHash($origin, $destination) {
        $hash = $origin.$destination;

        foreach ($this->getTimetableLegs() as $leg) {
            $hash .= $leg->getOrigin().$leg->getDestination();
        }

        return $hash;
    }

    /**
     * @return Leg[]
     */
    public function getTimetableLegs() {
        return array_filter($this->legs, function(Leg $leg) { return !$leg->isTransfer(); });
    }
}