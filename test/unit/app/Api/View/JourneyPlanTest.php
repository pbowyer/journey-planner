<?php

use JourneyPlanner\Lib\TimetableConnection;
use JourneyPlanner\Lib\NonTimetableConnection;
use JourneyPlanner\App\Api\View\JourneyPlan;

class JourneyPlanTest extends PHPUnit_Framework_TestCase {

    public function testNormalJourney() {
        $jp = new JourneyPlan([
            new TimetableConnection("PDW", "TON", 10000, 10150, "SE1"),
            new TimetableConnection("TON", "SEV", 10150, 10250, "SE2"),
            new TimetableConnection("SEV", "WAE", 10250, 10280, "SE2"),
            new NonTimetableConnection("WAE", "LBG", 100),
            new TimetableConnection("LBG", "CST", 1040, 11000, "SE3")
        ]);

        $actual = json_encode($jp);
        $expected = '[{"origin":"PDW","destination":"CST","departureTime":"02:46:40","arrivalTime":"03:03:20","legs":[{"mode":"Train","service":"SE1","callingPoints":[{"station":"PDW","time":"02:46:40"},{"station":"TON","time":"02:49:10"}]},{"mode":"Train","service":"SE2","callingPoints":[{"station":"TON","time":"02:49:10"},{"station":"SEV","time":"02:50:50"},{"station":"WAE","time":"02:51:20"}]},{"mode":"Walk","origin":"WAE","destination":"LBG","duration":"1 mins"},{"mode":"Train","service":"SE3","callingPoints":[{"station":"LBG","time":"00:17:20"},{"station":"CST","time":"03:03:20"}]}]}]';

        $this->assertEquals($expected, $actual);
    }

    public function testNonTimetableConnectionAtEnd() {
        $jp = new JourneyPlan([
            new TimetableConnection("PDW", "TON", 10000, 10150, "SE1"),
            new TimetableConnection("TON", "SEV", 10150, 10250, "SE2"),
            new TimetableConnection("SEV", "WAE", 10250, 10280, "SE2"),
            new NonTimetableConnection("WAE", "LBG", 100)
        ]);

        $actual = json_encode($jp);
        $expected = '[{"origin":"PDW","destination":"LBG","departureTime":"02:46:40","arrivalTime":"02:53:00","legs":[{"mode":"Train","service":"SE1","callingPoints":[{"station":"PDW","time":"02:46:40"},{"station":"TON","time":"02:49:10"}]},{"mode":"Train","service":"SE2","callingPoints":[{"station":"TON","time":"02:49:10"},{"station":"SEV","time":"02:50:50"},{"station":"WAE","time":"02:51:20"}]},{"mode":"Walk","origin":"WAE","destination":"LBG","duration":"1 mins"}]}]';

        $this->assertEquals($expected, $actual);
    }

    public function testNonTimetableConnectionAtBeginning() {
        $jp = new JourneyPlan([
            new NonTimetableConnection("MAR", "PDW", 100),
            new TimetableConnection("PDW", "TON", 10000, 10150, "SE1"),
            new TimetableConnection("TON", "SEV", 10150, 10250, "SE2"),
            new TimetableConnection("SEV", "WAE", 10250, 10280, "SE2")
        ]);

        $actual = json_encode($jp);
        $expected = '[{"origin":"MAR","destination":"WAE","departureTime":"02:45:00","arrivalTime":"02:51:20","legs":[{"mode":"Walk","origin":"MAR","destination":"PDW","duration":"1 mins"},{"mode":"Train","service":"SE1","callingPoints":[{"station":"PDW","time":"02:46:40"},{"station":"TON","time":"02:49:10"}]},{"mode":"Train","service":"SE2","callingPoints":[{"station":"TON","time":"02:49:10"},{"station":"SEV","time":"02:50:50"},{"station":"WAE","time":"02:51:20"}]}]}]';

        $this->assertEquals($expected, $actual);
    }

    public function testOnlyNonTimetableConnection() {
        $jp = new JourneyPlan([
            new NonTimetableConnection("MAR", "PDW", 100),
        ]);

        $actual = json_encode($jp);
        $expected = '[{"origin":"MAR","destination":"PDW","departureTime":"00:00:00","arrivalTime":"00:00:00","legs":[{"mode":"Walk","origin":"MAR","destination":"PDW","duration":"1 mins"}]}]';

        $this->assertEquals($expected, $actual);

    }

    public function testArrivalAfterMidnight() {
        $jp = new JourneyPlan([
            new NonTimetableConnection("MAR", "PDW", 100),
            new TimetableConnection("PDW", "TON", 80000, 80150, "SE1"),
            new TimetableConnection("TON", "SEV", 80150, 80250, "SE2"),
            new TimetableConnection("SEV", "WAE", 80250, 100280, "SE2")
        ]);

        $actual = json_encode($jp);
        $expected = '[{"origin":"MAR","destination":"WAE","departureTime":"22:11:40","arrivalTime":"03:51:20","legs":[{"mode":"Walk","origin":"MAR","destination":"PDW","duration":"1 mins"},{"mode":"Train","service":"SE1","callingPoints":[{"station":"PDW","time":"22:13:20"},{"station":"TON","time":"22:15:50"}]},{"mode":"Train","service":"SE2","callingPoints":[{"station":"TON","time":"22:15:50"},{"station":"SEV","time":"22:17:30"},{"station":"WAE","time":"03:51:20"}]}]}]';

        $this->assertEquals($expected, $actual);

    }
}
