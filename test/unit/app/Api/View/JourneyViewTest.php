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
        $expected = '{"origin":"PDW","destination":"CST","departureTime":"02:46","arrivalTime":"03:03","legs":[{"mode":"train","service":"SE1","callingPoints":[{"station":"PDW","time":"02:46"},{"station":"TON","time":"02:49"}]},{"mode":"train","service":"SE2","callingPoints":[{"station":"TON","time":"02:49"},{"station":"SEV","time":"02:50"},{"station":"WAE","time":"02:51"}]},{"mode":"walk","origin":"WAE","destination":"LBG","duration":"00:01"},{"mode":"train","service":"SE3","callingPoints":[{"station":"LBG","time":"00:17"},{"station":"CST","time":"03:03"}]}]}';

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
        $expected = '{"origin":"PDW","destination":"LBG","departureTime":"02:46","arrivalTime":"02:53","legs":[{"mode":"train","service":"SE1","callingPoints":[{"station":"PDW","time":"02:46"},{"station":"TON","time":"02:49"}]},{"mode":"train","service":"SE2","callingPoints":[{"station":"TON","time":"02:49"},{"station":"SEV","time":"02:50"},{"station":"WAE","time":"02:51"}]},{"mode":"walk","origin":"WAE","destination":"LBG","duration":"00:01"}]}';

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
        $expected = '{"origin":"MAR","destination":"WAE","departureTime":"02:45","arrivalTime":"02:51","legs":[{"mode":"walk","origin":"MAR","destination":"PDW","duration":"00:01"},{"mode":"train","service":"SE1","callingPoints":[{"station":"PDW","time":"02:46"},{"station":"TON","time":"02:49"}]},{"mode":"train","service":"SE2","callingPoints":[{"station":"TON","time":"02:49"},{"station":"SEV","time":"02:50"},{"station":"WAE","time":"02:51"}]}]}';

        $this->assertEquals($expected, $actual);
    }

    public function testOnlyNonTimetableConnection() {
        $jp = new JourneyView(
            new Journey([
                new Leg([new NonTimetableConnection("MAR", "PDW", 100)]),
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"MAR","destination":"PDW","departureTime":"00:00","arrivalTime":"00:00","legs":[{"mode":"walk","origin":"MAR","destination":"PDW","duration":"00:01"}]}';

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
        $expected = '{"origin":"MAR","destination":"WAE","departureTime":"22:11","arrivalTime":"03:51","legs":[{"mode":"walk","origin":"MAR","destination":"PDW","duration":"00:01"},{"mode":"train","service":"SE1","callingPoints":[{"station":"PDW","time":"22:13"},{"station":"TON","time":"22:15"}]},{"mode":"train","service":"SE2","callingPoints":[{"station":"TON","time":"22:15"},{"station":"SEV","time":"22:17"},{"station":"WAE","time":"03:51"}]}]}';

        $this->assertEquals($expected, $actual);

    }
}
