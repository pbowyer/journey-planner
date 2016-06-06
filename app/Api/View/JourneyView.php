<?php

namespace JourneyPlanner\App\Api\View;

use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\Journey;
use JsonSerializable;
use stdClass;

class JourneyView implements JsonSerializable {

    /**
     * @var Journey
     */
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
    public function jsonSerialize() {
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
    private function getLeg(Leg $leg) {
        $json = new stdClass;
        $json->mode = $leg->getMode();

        if ($leg->isTransfer()) {
            $json->origin = $leg->getOrigin();
            $json->destination = $leg->getDestination();
            $json->duration = $this->getDuration($leg->getDuration());

            return $json;
        }

        $json->service = $leg->getFirstConnection()->getService();
        $json->callingPoints = [
            $this->getCallingPoint($leg->getOrigin(), $leg->getFirstConnection()->getDepartureTime())
        ];

        foreach ($leg->getConnections() as $c) {
            $json->callingPoints[] = $this->getCallingPoint($c->getDestination(), $c->getArrivalTime());
        }

        return $json;
    }

    private function getCallingPoint($station, $time) {
        $point = new stdClass;
        $point->station = $station;
        $point->time = $this->getTime($time);

        return $point;
    }

    /**
     * @param  int $time
     * @return string
     */
    private function getTime($time) {
        return date("H:i:s", $time % 86400);
    }

    /**
     * @param  int $time
     * @return string
     */
    private function getDuration($time) {
        return intval(date("i", $time)). " mins";
    }

}
