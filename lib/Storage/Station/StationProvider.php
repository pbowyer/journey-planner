<?php

namespace JourneyPlanner\Lib\Storage\Station;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
interface StationProvider {
    /**
     * @return array
     */
    public function getLocations();

    /**
     * @param $station
     * @return string[]
     */
    public function getRelevantStations($station);
}