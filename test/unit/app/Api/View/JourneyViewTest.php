<?php

use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\App\Api\View\JourneyView;

class JourneyViewTest extends PHPUnit_Framework_TestCase {

    public function testNormalJourney() {
        $jp = new JourneyView(
            new Journey([
                new Leg([new TimetableConnection("PDW", "TON", 10000, 10150, "SE1")]),
                new Leg([new TimetableConnection("TON", "SEV", 10150, 10250, "SE2"),
                         new TimetableConnection("SEV", "WAE", 10250, 10280, "SE2")]),
                new Leg([new NonTimetableConnection("WAE", "LBG", 100)]),
                new Leg([new TimetableConnection("LBG", "CST", 1040, 11000, "SE3")])
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"PDW","destination":"CST","departureTime":"03:46:40","arrivalTime":"04:03:20","legs":[{"mode":"Train","service":"SE1","callingPoints":[{"station":"PDW","time":"03:46:40"},{"station":"TON","time":"03:49:10"}]},{"mode":"Train","service":"SE2","callingPoints":[{"station":"TON","time":"03:49:10"},{"station":"SEV","time":"03:50:50"},{"station":"WAE","time":"03:51:20"}]},{"mode":"Walk","origin":"WAE","destination":"LBG","duration":"1 mins"},{"mode":"Train","service":"SE3","callingPoints":[{"station":"LBG","time":"01:17:20"},{"station":"CST","time":"04:03:20"}]}]}';

        $this->assertEquals($expected, $actual);
    }

    public function testNonTimetableConnectionAtEnd() {
        $jp = new JourneyView(
            new Journey([
                new Leg([new TimetableConnection("PDW", "TON", 10000, 10150, "SE1")]),
                new Leg([new TimetableConnection("TON", "SEV", 10150, 10250, "SE2"),
                         new TimetableConnection("SEV", "WAE", 10250, 10280, "SE2")]),
                new Leg([new NonTimetableConnection("WAE", "LBG", 100)]),
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"PDW","destination":"LBG","departureTime":"03:46:40","arrivalTime":"03:53:00","legs":[{"mode":"Train","service":"SE1","callingPoints":[{"station":"PDW","time":"03:46:40"},{"station":"TON","time":"03:49:10"}]},{"mode":"Train","service":"SE2","callingPoints":[{"station":"TON","time":"03:49:10"},{"station":"SEV","time":"03:50:50"},{"station":"WAE","time":"03:51:20"}]},{"mode":"Walk","origin":"WAE","destination":"LBG","duration":"1 mins"}]}';

        $this->assertEquals($expected, $actual);
    }

    public function testNonTimetableConnectionAtBeginning() {
        $jp = new JourneyView(
            new Journey([
                new Leg([new NonTimetableConnection("MAR", "PDW", 100)]),
                new Leg([new TimetableConnection("PDW", "TON", 10000, 10150, "SE1")]),
                new Leg([new TimetableConnection("TON", "SEV", 10150, 10250, "SE2"),
                         new TimetableConnection("SEV", "WAE", 10250, 10280, "SE2")]),
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"MAR","destination":"WAE","departureTime":"03:45:00","arrivalTime":"03:51:20","legs":[{"mode":"Walk","origin":"MAR","destination":"PDW","duration":"1 mins"},{"mode":"Train","service":"SE1","callingPoints":[{"station":"PDW","time":"03:46:40"},{"station":"TON","time":"03:49:10"}]},{"mode":"Train","service":"SE2","callingPoints":[{"station":"TON","time":"03:49:10"},{"station":"SEV","time":"03:50:50"},{"station":"WAE","time":"03:51:20"}]}]}';

        $this->assertEquals($expected, $actual);
    }

    public function testOnlyNonTimetableConnection() {
        $jp = new JourneyView(
            new Journey([
                new Leg([new NonTimetableConnection("MAR", "PDW", 100)]),
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"MAR","destination":"PDW","departureTime":"01:00:00","arrivalTime":"01:00:00","legs":[{"mode":"Walk","origin":"MAR","destination":"PDW","duration":"1 mins"}]}';

        $this->assertEquals($expected, $actual);

    }

    public function testArrivalAfterMidnight() {
        $jp = new JourneyView(
            new Journey([
                new Leg([new NonTimetableConnection("MAR", "PDW", 100)]),
                new Leg([new TimetableConnection("PDW", "TON", 80000, 80150, "SE1")]),
                new Leg([new TimetableConnection("TON", "SEV", 80150, 80250, "SE2"),
                         new TimetableConnection("SEV", "WAE", 80250, 100280, "SE2")]),
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"MAR","destination":"WAE","departureTime":"23:11:40","arrivalTime":"04:51:20","legs":[{"mode":"Walk","origin":"MAR","destination":"PDW","duration":"1 mins"},{"mode":"Train","service":"SE1","callingPoints":[{"station":"PDW","time":"23:13:20"},{"station":"TON","time":"23:15:50"}]},{"mode":"Train","service":"SE2","callingPoints":[{"station":"TON","time":"23:15:50"},{"station":"SEV","time":"23:17:30"},{"station":"WAE","time":"04:51:20"}]}]}';

        $this->assertEquals($expected, $actual);

    }
}
