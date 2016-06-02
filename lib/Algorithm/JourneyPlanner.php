<?php

namespace JourneyPlanner\Lib\Algorithm;

use JourneyPlanner\Lib\Network\Connection;

interface JourneyPlanner {

    /**
     * @param  string $origin
     * @param  string $destination
     * @param  string $departureTime
     * @return Connection[]
     */
    public function getRoute($origin, $destination, $departureTime);
}
