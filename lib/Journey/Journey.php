<?php

namespace JourneyPlanner\Lib\Journey;

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
    public function getOrigin(): string {
        return $this->legs[0]->getOrigin();
    }

    /**
     * @return string
     */
    public function getDestination(): string {
        return end($this->legs)->getDestination();
    }

    /**
     * @return int
     */
    public function getDepartureTime(): int {
        $transferDuration = 0;

        foreach ($this->legs as $leg) {
            if ($leg instanceof FixedLeg) {
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
    public function getArrivalTime(): int {
        $transferDuration = 0;

        for ($i = count($this->legs) - 1; $i >= 0; $i--) {
            if ($this->legs[$i] instanceof FixedLeg) {
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
    public function getDuration(): int {
        return $this->getArrivalTime() - $this->getDepartureTime();
    }

    /**
     * @return Leg[]
     */
    public function getLegs(): array {
        return $this->legs;
    }

}