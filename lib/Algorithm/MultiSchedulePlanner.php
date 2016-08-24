<?php

namespace JourneyPlanner\Lib\Algorithm;

use JourneyPlanner\Lib\Algorithm\Filter\JourneyFilter;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Storage\Schedule\ScheduleProvider;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class MultiSchedulePlanner implements JourneyPlanner {

    /**
     * @var ScheduleProvider
     */
    private $scheduleProvider;
    /**
     * @var JourneyFilter[]
     */
    private $filters;

    /**
     * @param ScheduleProvider $scheduleProvider
     * @param JourneyFilter[] $filters
     */
    public function __construct(ScheduleProvider $scheduleProvider, array $filters) {
        $this->scheduleProvider = $scheduleProvider;
        $this->filters = $filters;
    }

    /**
     * @param  string[] $origins
     * @param  string[] $destinations
     * @param  int $departureDateTime
     * @return Journey[]
     */
    public function getJourneys($origins, $destinations, $departureDateTime) {
        $interchange = $this->scheduleProvider->getInterchangeTimes();
        $nonTimetable = $this->scheduleProvider->getNonTimetableConnections($departureDateTime);
        $departureTime = strtotime('1970-01-01 '.date('H:i:s', $departureDateTime));
        $results = [];

        foreach ($origins as $o) {
            foreach ($destinations as $d) {
                $schedules = $this->scheduleProvider->getTimetable($o, $d, $departureDateTime);
                foreach ($schedules as $schedule) {
                    $scanner = new SchedulePlanner($schedule, $nonTimetable, $interchange);

                    $results = array_merge($results, $scanner->getJourneys($o, $d, $departureTime));
                }
            }
        }

        foreach ($this->filters as $filter) {
            $results = $filter->filter($results);
        }

        return $results;
    }
}