<?php

namespace JourneyPlanner\Lib\Storage;

use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;


/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
interface ScheduleProvider {
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