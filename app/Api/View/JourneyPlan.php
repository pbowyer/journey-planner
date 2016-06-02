<?php

namespace JourneyPlanner\App\Api\View;

use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JsonSerializable;
use stdClass;

class JourneyPlan implements JsonSerializable {

    /**
     * @var Connection[]
     */
    private $route;

    /**
     * @param Connection[] $route
     */
    public function __construct($route) {
        $this->route = $route;
    }

    /**
     * @return array
     */
    public function jsonSerialize() {
        return count($this->route) > 0 ? [$this->getRouteJson()] : [];
    }

    /**
     * @param  array  $route
     * @return stdClass
     */
    private function getRouteJson() {
        $json = new stdClass();
        $json->origin = $this->route[0]->getOrigin();
        $json->destination = $this->route[count($this->route)-1]->getDestination();
        $json->departureTime = $this->getTime($this->getDepartureTime($this->route));
        $json->arrivalTime = $this->getTime($this->getArrivalTime($this->route));
        $json->legs = array_map([$this, 'getLeg'], $this->groupLegsByService());

        return $json;
    }

    private function getDepartureTime(array $connections) {
        $transferDuration = 0;

        foreach ($connections as $connection) {
            if ($connection instanceof TimetableConnection) {
                return $connection->getDepartureTime() - $transferDuration;
            }
            else {
                $transferDuration += $connection->getDuration();
            }
        }
    }

    private function getArrivalTime(array $connections) {
        $transferDuration = 0;

        for ($i = count($connections) - 1; $i >= 0; $i--) {
            if ($connections[$i] instanceof TimetableConnection) {
                return $connections[$i]->getArrivalTime() + $transferDuration;
            }
            else {
                $transferDuration += $connections[$i]->getDuration();
            }
        }
    }

    private function groupLegsByService() {
        $legs = [];
        $previousServiceId = null;

        foreach ($this->route as $i => $connection) {
            if ($connection instanceof TimetableConnection) {
                if ($connection->getService() === $previousServiceId) {
                    $legs[$connection->getService()][] = $connection;
                }
                else {
                    $legs[$connection->getService()] = [$connection];
                    $previousServiceId = $connection->getService();
                }
            }
            else {
                $legs[$i] = [$connection];
            }
        }

        return array_values($legs);
    }

    private function getLeg($connections) {
        $leg = new stdClass;
        $leg->mode = $connections[0]->getMode();

        if ($connections[0] instanceof NonTimetableConnection) {
            $leg->origin = $connections[0]->getOrigin();
            $leg->destination = $connections[0]->getDestination();
            $leg->duration = $this->getDuration($connections[0]->getDuration());

            return $leg;
        }

        $leg->service = $connections[0]->getService();
        $leg->callingPoints = [
            $this->getCallingPoint($connections[0]->getOrigin(), $connections[0]->getDepartureTime())
        ];

        foreach ($connections as $c) {
            $leg->callingPoints[] = $this->getCallingPoint($c->getDestination(), $c->getArrivalTime());
        }

        return $leg;
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
