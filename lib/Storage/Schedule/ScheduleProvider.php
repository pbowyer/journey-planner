<?php

namespace JourneyPlanner\Lib\Storage\Schedule;

use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;


/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
interface ScheduleProvider {

    /**
     * Grab all connections after the target time
     *
     * @param  string $startTimestamp
     * @return TimetableConnection[]
     */
    public function getTimetableConnections($startTimestamp);

    /**
     * @param int $targetTimestamp
     * @return NonTimetableConnection[]
     */
    public function getNonTimetableConnections($targetTimestamp);

    /**
     * @return array
     */
    public function getInterchangeTimes();

    /**
     * @param $origin
     * @param $destination
     * @param $startTimestamp
     * @return TransferPatternSchedule[]
     */
    public function getTimetable($origin, $destination, $startTimestamp);
}