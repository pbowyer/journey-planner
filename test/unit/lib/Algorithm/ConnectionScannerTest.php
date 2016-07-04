<?php

use JourneyPlanner\Lib\Algorithm\ConnectionScanner;
use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TransferPattern;
use JourneyPlanner\Lib\Network\Journey;


class ConnectionScannerTest extends PHPUnit_Framework_TestCase {

    public function testBasicJourney() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
        ];

        $scanner = new ConnectionScanner($timetable, [], []);
        $actual = $scanner->getJourneys("A", "D", 900);
        $expected = [new Journey([new Leg($timetable)])];

        $this->assertEquals($expected, $actual);
    }

    public function testJourneyWithEarlyTermination() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
            new TimetableConnection("D", "E", 1120, 1135, "CS1234", "LN"),
        ];

        $scanner = new ConnectionScanner($timetable, [], []);
        $actual = $scanner->getJourneys("A", "D", 900);
        $expectedLeg = new Leg([
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
        ]);

        $expected = [new Journey([$expectedLeg])];

        $this->assertEquals($expected, $actual);
    }

    public function testMultipleRoutes() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("A", "C", 1005, 1025, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1030, 1100, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
            new TimetableConnection("D", "E", 1105, 1125, "CS1234", "LN"),
            new TimetableConnection("D", "E", 1120, 1135, "CS1234", "LN"),
        ];

        $scanner = new ConnectionScanner($timetable, [], []);
        $actual = $scanner->getJourneys("A", "E", 900);
        $expectedLeg = new Leg([
            new TimetableConnection("A", "C", 1005, 1025, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1030, 1100, "CS1234", "LN"),
            new TimetableConnection("D", "E", 1105, 1125, "CS1234", "LN"),
        ]);

        $expected = [new Journey([$expectedLeg])];
        $this->assertEquals($expected, $actual);
    }

    public function testNoRoute() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
            new TimetableConnection("D", "E", 1105, 1125, "CS1234", "LN"),
            new TimetableConnection("D", "E", 1120, 1135, "CS1234", "LN"),
        ];

        $scanner = new ConnectionScanner($timetable, [], []);
        $actual = $scanner->getJourneys("A", "E", 900);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }

    public function testNoRouteBecauseOfMissedConnection() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1001, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
            new TimetableConnection("D", "E", 1105, 1125, "CS1234", "LN"),
            new TimetableConnection("D", "E", 1120, 1135, "CS1234", "LN"),
        ];

        $scanner = new ConnectionScanner($timetable, [], []);
        $actual = $scanner->getJourneys("A", "E", 900);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }

    public function testRouteWithNonTimetabledConnection() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1030, 1100, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
        ];

        $nonTimetable = [
            "B" => [
                new NonTimetableConnection("B", "C", 5),
                new NonTimetableConnection("B", "E", 5),
            ]
        ];

        $scanner = new ConnectionScanner($timetable, $nonTimetable, []);
        $actual = $scanner->getJourneys("A", "D", 900);
        $legs = [
            new Leg([new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN")]),
            new Leg([new NonTimetableConnection("B", "C", 5)]),
            new Leg([new TimetableConnection("C", "D", 1030, 1100, "CS1234", "LN")])
        ];

        $expected = [new Journey($legs)];
        $this->assertEquals($expected, $actual);
    }

    public function testRouteWithNonTimetabledConnectionThatCantBeUsed() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1030, 1100, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
        ];

        $nonTimetable = [
            "B" => [
                new NonTimetableConnection("B", "C", 5, Connection::TUBE, 100, 200),
                new NonTimetableConnection("B", "E", 5),
            ]
        ];

        $scanner = new ConnectionScanner($timetable, $nonTimetable, []);
        $actual = $scanner->getJourneys("A", "D", 900);
        $expectedLeg = new Leg([
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN")
        ]);

        $expected = [new Journey([$expectedLeg])];
        $this->assertEquals($expected, $actual);
    }    
    
    
    public function testRouteWithNonTimetabledConnectionThatShouldntBeUsed() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1030, 1100, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
        ];

        $nonTimetable = [
            "B" => [
                new NonTimetableConnection("B", "C", 500),
                new NonTimetableConnection("B", "E", 5),
            ]
        ];

        $scanner = new ConnectionScanner($timetable, $nonTimetable, []);
        $actual = $scanner->getJourneys("A", "D", 900);
        $expectedLeg = new Leg([
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN")
        ]);

        $expected = [new Journey([$expectedLeg])];
        $this->assertEquals($expected, $actual);
    }

    public function testRouteStartingInNonTimetabledConnection() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1030, 1100, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
        ];

        $nonTimetable = [
            "A" => [
                new NonTimetableConnection("A", "B", 5),
            ]
        ];

        $scanner = new ConnectionScanner($timetable, $nonTimetable, []);
        $actual = $scanner->getJourneys("A", "D", 900);
        $expectedLegs = [
            new Leg([new NonTimetableConnection("A", "B", 5)]),
            new Leg([new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
                     new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN")]),
        ];

        $expected = [new Journey($expectedLegs)];
        $this->assertEquals($expected, $actual);
    }

    public function testNonTimetableOnly() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1234", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1030, 1100, "CS1234", "LN"),
            new TimetableConnection("C", "D", 1100, 1115, "CS1234", "LN"),
        ];

        $nonTimetable = [
            "A" => [
                new NonTimetableConnection("A", "D", 5),
            ]
        ];

        $scanner = new ConnectionScanner($timetable, $nonTimetable, []);
        $actual = $scanner->getJourneys("A", "D", 900);
        $expectedLeg = new Leg([
            new NonTimetableConnection("A", "D", 5)
        ]);

        $expected = [new Journey([$expectedLeg])];
        $this->assertEquals($expected, $actual);
    }

    public function testChangeOfService() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1000", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS2000", "LN"),
            new TimetableConnection("C", "D", 1045, 1115, "CS2000", "LN"),
        ];

        $interchangeTimes = [
            "A" => 5,
            "B" => 5,
            "C" => 5
        ];

        $scanner = new ConnectionScanner($timetable, [], $interchangeTimes);
        $actual = $scanner->getJourneys("A", "D", 900);
        $expectedLegs = [
            new Leg([new TimetableConnection("A", "B", 1000, 1015, "CS1000", "LN")]),
            new Leg([
                new TimetableConnection("B", "C", 1020, 1045, "CS2000", "LN"),
                new TimetableConnection("C", "D", 1045, 1115, "CS2000", "LN"),
            ])
        ];
        
        $expected = [new Journey($expectedLegs)];
        $this->assertEquals($expected, $actual);
    }

    public function testCantMakeConnectionBecauseOfInterchangeTime() {
        $timetable = [
            new TimetableConnection("A", "B", 1000, 1015, "CS1000", "LN"),
            new TimetableConnection("B", "C", 1020, 1045, "CS2000", "LN"),
            new TimetableConnection("C", "D", 1045, 1115, "CS2000", "LN"),
        ];

        $interchangeTimes = [
            "A" => 6,
            "B" => 6,
            "C" => 6
        ];

        $scanner = new ConnectionScanner($timetable, [], $interchangeTimes);
        $route = $scanner->getJourneys("A", "D", 900);
        $this->assertEquals([], $route);
    }

    public function testWalkIsFasterThanChangeOfService() {
        $timetable = [
            new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
            new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN"),
            new TimetableConnection("CHX", "LBG", 1050, 1055, "SE2000", "LN"),
            new TimetableConnection("CHX", "LBG", 1105, 1110, "SE2000", "LN"),
        ];

        $nonTimetable = [
            "WAE" => [
                new NonTimetableConnection("WAE", "LBG", 20),
            ]
        ];

        $interchangeTimes = [
            "CHX" => 10
        ];

        $scanner = new ConnectionScanner($timetable, $nonTimetable, $interchangeTimes);
        $actual = $scanner->getJourneys("ORP", "LBG", 900);
        $expectedLegs = [
            new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN")]),
            new Leg([new NonTimetableConnection("WAE", "LBG", 20)]),
        ];

        $expected = [new Journey($expectedLegs)];
        $this->assertEquals($expected, $actual);
    }

    public function testChangeIsFasterThanWalking() {
        $timetable = [
            new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
            new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN"),
            new TimetableConnection("CHX", "LBG", 1050, 1055, "SE2000", "LN"),
        ];

        $nonTimetable = [
            "WAE" => [
                new NonTimetableConnection("WAE", "LBG", 20),
            ]
        ];

        $interchangeTimes = [
            "CHX" => 5
        ];

        $scanner = new ConnectionScanner($timetable, $nonTimetable, $interchangeTimes);
        $actual = $scanner->getJourneys("ORP", "LBG", 900);
        $expectedLegs = [
            new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
                     new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN")]),
            new Leg([new TimetableConnection("CHX", "LBG", 1050, 1055, "SE2000", "LN")]),
        ];

        $expected = [new Journey($expectedLegs)];
        $this->assertEquals($expected, $actual);    }

    public function testGetShortestPathTree() {
        $timetable = [
            new TimetableConnection("SEV", "ORP", 900, 940, "SE1000", "LN"),
            new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN")
        ];

        $nonTimetable = [];
        $interchangeTimes = [];

        $scanner = new ConnectionScanner($timetable, $nonTimetable, $interchangeTimes);
        $tree = $scanner->getShortestPathTree("SEV");
        $expectedTree = [
            "ORP" => new TransferPattern([
                new Leg([new TimetableConnection("SEV", "ORP", 900, 940, "SE1000", "LN")])
            ]),
            "WAE" => new TransferPattern([
                new Leg([new TimetableConnection("SEV", "ORP", 900, 940, "SE1000", "LN"),
                         new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN")])
            ])
        ];

        $this->assertEquals($expectedTree, $tree);
    }

    public function testGetShortestPathTreeWithChange() {
        $timetable = [
            new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
            new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN"),
            new TimetableConnection("CHX", "LBG", 1050, 1055, "SE2000", "LN"),
            new TimetableConnection("CHX", "LBG", 1052, 1053, "SE2500", "LN"),
            new TimetableConnection("ORP", "WAE", 1100, 1140, "SE3000", "LN"),
        ];

        $nonTimetable = [
            "WAE" => [
                new NonTimetableConnection("WAE", "LBG", 20),
            ]
        ];

        $interchangeTimes = [
            "CHX" => 5
        ];

        $scanner = new ConnectionScanner($timetable, $nonTimetable, $interchangeTimes);
        $tree = $scanner->getShortestPathTree("ORP");
        $expectedTree = [
            "WAE" => new TransferPattern([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN")])
            ]),
            "LBG" => new TransferPattern([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
                    new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN")]),
                new Leg([new TimetableConnection("CHX", "LBG", 1052, 1053, "SE2500", "LN")]),
            ]),
            "CHX" => new TransferPattern([
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

        $scanner = new ConnectionScanner($timetable, $nonTimetable, $interchangeTimes);
        $tree = $scanner->getShortestPathTree("ORP");
        $expectedTree = [
            "WAE" => new TransferPattern([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN")])
            ]),
            "LBG" => new TransferPattern([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN")]),
                new Leg([new NonTimetableConnection("WAE", "LBG", 5)]),
            ]),
            "CHX" => new TransferPattern([
                new Leg([new TimetableConnection("ORP", "WAE", 1000, 1040, "SE1000", "LN"),
                         new TimetableConnection("WAE", "CHX", 1040, 1045, "SE1000", "LN")])
            ])
        ];

        $this->assertEquals($expectedTree, $tree);
    }
}
