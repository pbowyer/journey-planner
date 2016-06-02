<?php

namespace JourneyPlanner\Lib\Algorithm;
use JourneyPlanner\Lib\Network\Connection;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class SchedulePlanner implements JourneyPlanner {

    /**
     * Stores the list of connections. Please note that this timetable must be time ordered
     *
     * @var array
     */
    private $transferPatterns;

    /**
     * Stores the list of non timetabled connections
     *
     * @var array
     */
    private $nonTimetable;

    /**
     * HashMap of station => interchange time required at that station when changing service
     *
     * @var array
     */
    private $interchangeTimes;

    /**
     * @param array $transferPatterns
     * @param array $nonTimetable
     * @param array $interchangeTimes
     */
    public function __construct(array $transferPatterns, array $nonTimetable, array $interchangeTimes) {
        $this->transferPatterns = $transferPatterns;
        $this->nonTimetable = $nonTimetable;
        $this->interchangeTimes = $interchangeTimes;
    }

    /**
     * @param  string $origin
     * @param  string $destination
     * @param  string $departureTime
     * @return Connection[]
     */
    public function getRoute($origin, $destination, $departureTime) {

    }
}
