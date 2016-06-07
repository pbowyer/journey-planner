<?php

namespace JourneyPlanner\Lib\Algorithm;

use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TransferPattern;
use JourneyPlanner\Lib\Network\TimetableConnection;
use Exception;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class MultiSchedulePlanner implements JourneyPlanner {

    /**
     * @var array
     */
    private $interchangeTimes;

    /**
     * @var TransferPatternSchedule[]
     */
    private $schedules;

    /**
     * @var NonTimetableConnection[]
     */
    private $nonTimetableConnections;

    /**
     * @param array $interchangeTimes
     * @param TransferPatternSchedule[] $schedules
     * @param NonTimetableConnection[] $nonTimetableConnections
     */
    public function __construct(array $schedules, array $nonTimetableConnections, array $interchangeTimes) {
        $this->schedules = $schedules;
        $this->nonTimetableConnections = $nonTimetableConnections;
        $this->interchangeTimes = $interchangeTimes;
    }

    /**
     * @param  string $origin
     * @param  string $destination
     * @param  string $departureTime
     * @return Journey[]
     */
    public function getJourneys($origin, $destination, $departureTime) {
        $results = [];
        
        foreach ($this->schedules as $schedule) {
            $scanner = new SchedulePlanner($schedule, $this->nonTimetableConnections, $this->interchangeTimes);
            $journeys = $scanner->getJourneys($origin, $destination, $departureTime);
            $results = array_merge($results, $journeys);
        }

        usort($results, function (Journey $a, Journey $b) {
            return $a->getDepartureTime() <=> $b->getDepartureTime();
        });

        return $results;
    }
}