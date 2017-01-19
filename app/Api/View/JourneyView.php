<?php

namespace JourneyPlanner\App\Api\View;

use JourneyPlanner\Lib\Journey\FixedLeg;
use JourneyPlanner\Lib\Journey\Journey;
use JourneyPlanner\Lib\Journey\Leg;
use JourneyPlanner\Lib\Journey\TimetableLeg;
use JsonSerializable;
use stdClass;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class JourneyView implements JsonSerializable {

    private $journey;

    /**
     * @param Journey $journey
     */
    public function __construct(Journey $journey) {
        $this->journey = $journey;
    }

    /**
     * @return stdClass
     */
    public function jsonSerialize(): stdClass {
        $json = new stdClass();
        $json->origin = $this->journey->getOrigin();
        $json->destination = $this->journey->getDestination();
        $json->departureTime = $this->getTime($this->journey->getDepartureTime());
        $json->arrivalTime = $this->getTime($this->journey->getArrivalTime());
        $json->legs = array_map([$this, 'getLeg'], $this->journey->getLegs());

        return $json;
    }

    /**
     * @param Leg $leg
     * @return stdClass
     */
    private function getLeg(Leg $leg): stdClass {
        $json = new stdClass;
        $json->mode = strtolower($leg->getMode());

        if ($leg instanceof FixedLeg) {
            $json->origin = $leg->getOrigin();
            $json->destination = $leg->getDestination();
            $json->duration = $this->getTime($leg->getDuration());

            return $json;
        }

        /** @var TimetableLeg $leg */
        $json->service = $leg->getService();
        $json->operator = $leg->getOperator();
        $json->callingPoints = [];

        foreach ($leg->getCallingPoints() as $c) {
            $json->callingPoints[] = $this->getCallingPoint($c->getStation(), $c->getArrivalTime() ?? $c->getDepartureTime());
        }

        return $json;
    }

    /**
     * @param $station
     * @param $time
     * @return stdClass
     */
    private function getCallingPoint(string $station, int $time): stdClass {
        $point = new stdClass;
        $point->station = $station;
        $point->time = $this->getTime($time); //TODO change to arrival time and departure time

        return $point;
    }

    /**
     * @param  int $time
     * @return string
     */
    private function getTime(int $time): string {
        return gmdate("H:i", $time % 86400);
    }

}
