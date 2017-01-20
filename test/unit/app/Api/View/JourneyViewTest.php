<?php

use JourneyPlanner\Lib\Journey\CallingPoint;
use JourneyPlanner\Lib\Journey\FixedLeg;
use JourneyPlanner\Lib\Journey\Journey;
use JourneyPlanner\Lib\Journey\Leg;
use JourneyPlanner\Lib\Journey\TimetableLeg;
use JourneyPlanner\App\Api\View\JourneyView;

class JourneyViewTest extends PHPUnit_Framework_TestCase {

    public function testNormalJourney() {
        $jp = new JourneyView(
            new Journey([
                new TimetableLeg("PDW", "TON", Leg::TRAIN, 10000, 10150, [new CallingPoint("PDW", null, 10000), new CallingPoint("TON", 10150)], "SE1", "LN"),
                new TimetableLeg("TON", "WAE", Leg::TRAIN, 10150, 10280, [new CallingPoint("TON", null, 10150), new CallingPoint("SEV", 10250, 10250), new CallingPoint("WAE", 10280)], "SE2", "LN"),
                new FixedLeg("WAE", "LBG", Leg::WALK, 100, 0 , 99999),
                new TimetableLeg("LBG", "CST", Leg::TRAIN, 10400, 11000, [new CallingPoint("LBG", null, 10400), new CallingPoint("CST", 11000)], "SE3", "LN")
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"PDW","destination":"CST","departureTime":"02:46","arrivalTime":"03:03","legs":[{"mode":"train","service":"SE1","operator":"LN","callingPoints":[{"station":"PDW","time":"02:46"},{"station":"TON","time":"02:49"}]},{"mode":"train","service":"SE2","operator":"LN","callingPoints":[{"station":"TON","time":"02:49"},{"station":"SEV","time":"02:50"},{"station":"WAE","time":"02:51"}]},{"mode":"walk","origin":"WAE","destination":"LBG","duration":"00:01"},{"mode":"train","service":"SE3","operator":"LN","callingPoints":[{"station":"LBG","time":"02:53"},{"station":"CST","time":"03:03"}]}]}';

        $this->assertEquals($expected, $actual);
    }

    public function testNonTimetableConnectionAtEnd() {
        $jp = new JourneyView(
            new Journey([
                new TimetableLeg("PDW", "TON", Leg::TRAIN, 10000, 10150, [new CallingPoint("PDW", null, 10000), new CallingPoint("TON", 10150)], "SE1", "LN"),
                new TimetableLeg("TON", "WAE", Leg::TRAIN, 10150, 10280, [new CallingPoint("TON", null, 10150), new CallingPoint("SEV", 10250, 10250), new CallingPoint("WAE", 10280)], "SE2", "LN"),
                new FixedLeg("WAE", "LBG", Leg::WALK, 100, 0 , 99999),
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"PDW","destination":"LBG","departureTime":"02:46","arrivalTime":"02:53","legs":[{"mode":"train","service":"SE1","operator":"LN","callingPoints":[{"station":"PDW","time":"02:46"},{"station":"TON","time":"02:49"}]},{"mode":"train","service":"SE2","operator":"LN","callingPoints":[{"station":"TON","time":"02:49"},{"station":"SEV","time":"02:50"},{"station":"WAE","time":"02:51"}]},{"mode":"walk","origin":"WAE","destination":"LBG","duration":"00:01"}]}';

        $this->assertEquals($expected, $actual);
    }

    public function testNonTimetableConnectionAtBeginning() {
        $jp = new JourneyView(
            new Journey([
                new FixedLeg("MAR", "PDW", Leg::WALK, 100, 0 , 99999),
                new TimetableLeg("PDW", "TON", Leg::TRAIN, 10000, 10150, [new CallingPoint("PDW", null, 10000), new CallingPoint("TON", 10150)], "SE1", "LN"),
                new TimetableLeg("TON", "WAE", Leg::TRAIN, 10150, 10280, [new CallingPoint("TON", null, 10150), new CallingPoint("SEV", 10250, 10250), new CallingPoint("WAE", 10280)], "SE2", "LN"),
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"MAR","destination":"WAE","departureTime":"02:45","arrivalTime":"02:51","legs":[{"mode":"walk","origin":"MAR","destination":"PDW","duration":"00:01"},{"mode":"train","service":"SE1","operator":"LN","callingPoints":[{"station":"PDW","time":"02:46"},{"station":"TON","time":"02:49"}]},{"mode":"train","service":"SE2","operator":"LN","callingPoints":[{"station":"TON","time":"02:49"},{"station":"SEV","time":"02:50"},{"station":"WAE","time":"02:51"}]}]}';

        $this->assertEquals($expected, $actual);
    }

    public function testOnlyNonTimetableConnection() {
        $jp = new JourneyView(
            new Journey([
                new FixedLeg("MAR", "PDW", Leg::WALK, 100, 0 , 99999),
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"MAR","destination":"PDW","departureTime":"00:00","arrivalTime":"00:00","legs":[{"mode":"walk","origin":"MAR","destination":"PDW","duration":"00:01"}]}';

        $this->assertEquals($expected, $actual);

    }

    public function testArrivalAfterMidnight() {
        $jp = new JourneyView(
            new Journey([
                new FixedLeg("MAR", "PDW", Leg::WALK, 100, 0 , 99999),
                new TimetableLeg("PDW", "TON", Leg::TRAIN, 80000, 80150, [new CallingPoint("PDW", null, 80000), new CallingPoint("TON", 80150)], "SE1", "LN"),
                new TimetableLeg("TON", "WAE", Leg::TRAIN, 80150, 100280, [new CallingPoint("TON", null, 80150), new CallingPoint("SEV", 80250, 80250), new CallingPoint("WAE", 100280)], "SE2", "LN"),
            ])
        );

        $actual = json_encode($jp);
        $expected = '{"origin":"MAR","destination":"WAE","departureTime":"22:11","arrivalTime":"03:51","legs":[{"mode":"walk","origin":"MAR","destination":"PDW","duration":"00:01"},{"mode":"train","service":"SE1","operator":"LN","callingPoints":[{"station":"PDW","time":"22:13"},{"station":"TON","time":"22:15"}]},{"mode":"train","service":"SE2","operator":"LN","callingPoints":[{"station":"TON","time":"22:15"},{"station":"SEV","time":"22:17"},{"station":"WAE","time":"03:51"}]}]}';

        $this->assertEquals($expected, $actual);

    }
}
