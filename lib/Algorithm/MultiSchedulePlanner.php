<?php

namespace JourneyPlanner\Lib\Algorithm;

use JourneyPlanner\Lib\Algorithm\Filter\JourneyFilter;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Storage\ScheduleProvider;

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
     * @param  string $departureTime
     * @return Journey[]
     */
    public function getJourneys($origins, $destinations, $departureTime) {
        $interchange = $this->scheduleProvider->getInterchangeTimes();
        $nonTimetable = $this->scheduleProvider->getNonTimetableConnections($departureTime);
        $results = [];

        foreach ($origins as $o) {
            foreach ($destinations as $d) {
                $schedules = $this->scheduleProvider->getScheduleFromTransferPatternTimetable($o, $d, $departureTime);
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