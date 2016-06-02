<?php

use JourneyPlanner\Lib\Algorithm\SchedulePlanner;
use JourneyPlanner\Lib\Network\TimetableConnection;


class SchedulePlannerTest extends PHPUnit_Framework_TestCase {

    public function testBasicJourney() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234"),
        ];

        $scanner = new SchedulePlanner($timetable, [], []);
        $route = $scanner->getRoute("A", "D", 900);
        $this->assertEquals($timetable, $route);
    }

}
