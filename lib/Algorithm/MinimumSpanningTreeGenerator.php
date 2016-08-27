<?php

namespace JourneyPlanner\Lib\Algorithm;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
interface MinimumSpanningTreeGenerator {

    /**
     * @param string $origin
     * @param int $departureTime
     * @return array
     */
    public function getShortestPathTree($origin, $departureTime);
}
