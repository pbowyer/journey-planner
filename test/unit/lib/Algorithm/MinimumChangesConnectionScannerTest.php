<?php

use JourneyPlanner\Lib\Algorithm\MinimumChangesConnectionScanner;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;

class MinimumChangesConnectionScannerTest extends PHPUnit_Framework_TestCase {

    public function testSelectJourneyWithLeastChangesIfTimeEqual() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1000", "LN"),
            new TimetableConnection("A", "D", 1000, 1115, "CS1001", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS2000", "LN"),
            new TimetableConnection("C", "D", 1045, 1110, "CS2000", "LN"),
            new TimetableConnection("D", "E", 1120, 1125, "CS3000", "LN"),
        ];

        $interchangeTimes = [
            "A" => 1,
            "B" => 1,
            "C" => 1,
            "D" => 1
        ];

        $expected = [new Journey([
            new Leg([new TimetableConnection("A", "D", 1000, 1115, "CS1001", "LN")]),
            new Leg([new TimetableConnection("D", "E", 1120, 1125, "CS3000", "LN")]),
        ])];

        $scanner = new MinimumChangesConnectionScanner($timetable, [], $interchangeTimes);
        $route = $scanner->getJourneys("A", "E", 900);
        $this->assertEquals($expected, $route);
    }

    public function testGetShortestPathTreeWithChange() {
        $timetable = [
            new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
            new TimetableConnection("ORP", "LBG", 1000, 1240, "SE1001", "LN"),
            new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN"),
            new TimetableConnection("CHX", "LBG", 1050, 1055, "SE2000", "LN"),
            new TimetableConnection("CHX", "LBG", 1052, 1053, "SE2500", "LN"),
            new TimetableConnection("ORP", "WAE", 1100, 1120, "SE3000", "LN"),
        ];

        $nonTimetable = [
            "WAE" => [
                new NonTimetableConnection("WAE", "LBG", 20),
            ]
        ];

        $interchangeTimes = [
            "CHX" => 5
        ];

        $scanner = new MinimumChangesConnectionScanner($timetable, $nonTimetable, $interchangeTimes);
        $tree = $scanner->getShortestPathTree("ORP");
        $expectedTree = [
            "WAE" => new Journey([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN")])
            ]),
            "LBG" => new Journey([
                new Leg([new TimetableConnection("ORP", "LBG", 1000, 1240, "SE1001", "LN")]),
            ]),
            "CHX" => new Journey([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
                         new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN")])
            ])
        ];

        $this->assertEquals($expectedTree, $tree);
    }

    public function testTransferPatternsWithTransfer() {
        $timetable = [
            new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
            new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN"),
            new TimetableConnection("CHX", "LBG", 1050, 1055, "SE2000", "LN"),
            new TimetableConnection("CHX", "LBG", 1052, 1053, "SE2500", "LN"),
            new TimetableConnection("ORP", "WAE", 1100, 1140, "SE3000", "LN"),
        ];

        $nonTimetable = [
            "WAE" => [
                new NonTimetableConnection("WAE", "LBG", 5),
            ]
        ];

        $interchangeTimes = [
            "CHX" => 5
        ];

        $scanner = new MinimumChangesConnectionScanner($timetable, $nonTimetable, $interchangeTimes);
        $tree = $scanner->getShortestPathTree("ORP");
        $expectedTree = [
            "WAE" => new Journey([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN")])
            ]),
            "LBG" => new Journey([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN")]),
                new Leg([new NonTimetableConnection("WAE", "LBG", 5)]),
            ]),
            "CHX" => new Journey([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
                    new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN")])
            ])
        ];

        $this->assertEquals($expectedTree, $tree);
    }


}