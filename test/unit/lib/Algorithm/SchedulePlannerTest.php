<?php

use JourneyPlanner\Lib\Algorithm\SchedulePlanner;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TransferPattern;
use JourneyPlanner\Lib\Network\TransferPatternLeg;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;

class SchedulePlannerTest extends PHPUnit_Framework_TestCase {

    public function testBasicJourney() {
        $schedule = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122")]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133")]),
            ])
        ]);

        $scanner = new SchedulePlanner($schedule, [], []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121")]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113")]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133")]),
            ])
        ], $journeys);
    }

    public function testJourneyWithNonTimetableConnection() {
        $schedule = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133")]),
            ])
        ]);

        $nonTimetable = [
            "B" => [
                new NonTimetableConnection("B", "C", 5),
                new NonTimetableConnection("B", "E", 5),
            ]
        ];

        $scanner = new SchedulePlanner($schedule, $nonTimetable, []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([new NonTimetableConnection("B", "C", 5)]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new NonTimetableConnection("B", "C", 5)]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113")]),
                new Leg([new NonTimetableConnection("B", "C", 5)]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
            ]),
        ], $journeys);
    }

    public function testCantMakeUnreachableConnectionsWithTransfer() {
        $schedule = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133")]),
            ])
        ]);

        $nonTimetable = [
            "B" => [
                new NonTimetableConnection("B", "C", 15),
                new NonTimetableConnection("B", "E", 5),
            ]
        ];

        $scanner = new SchedulePlanner($schedule, $nonTimetable, []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([new NonTimetableConnection("B", "C", 15)]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new NonTimetableConnection("B", "C", 15)]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113")]),
                new Leg([new NonTimetableConnection("B", "C", 15)]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
            ]),
        ], $journeys);
    }

    public function testJourneyWithUnreachableLegs() {
        $schedule = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new TimetableConnection("A", "B", 1500, 1515, "LN1113")]),
                new Leg([new TimetableConnection("A", "B", 1700, 1715, "LN1114")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122")]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133")]),
            ])
        ]);

        $scanner = new SchedulePlanner($schedule, [], []);
        $journeys = $scanner->getJourneys("A", "D", 1005);

        $this->assertEquals([
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
            ])
        ], $journeys);
    }

    public function testJourneyWithFirstLegUnreachable() {
        $schedule = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("A", "B", 1400, 1415, "LN1111")]),
                new Leg([new TimetableConnection("A", "B", 1520, 1545, "LN1112")]),
                new Leg([new TimetableConnection("A", "B", 1600, 1615, "LN1113")]),
                new Leg([new TimetableConnection("A", "B", 1700, 1715, "LN1114")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122")]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133")]),
            ])
        ]);

        $scanner = new SchedulePlanner($schedule, [], []);
        $journeys = $scanner->getJourneys("A", "D", 1005);

        $this->assertEquals([], $journeys);
    }

    public function testJourneyWithTransferForFirstLeg() {
        $schedule = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
            ])
        ]);

        $nonTimetable = [
            "A" => [
                new NonTimetableConnection("A", "B", 5),
            ]
        ];

        $scanner = new SchedulePlanner($schedule, $nonTimetable, []);
        $journeys = $scanner->getJourneys("A", "D", 1005);

        $this->assertEquals([
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 5)]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
            ])
        ], $journeys);
    }

    public function testJourneyWithCallingPoints() {
        $schedule = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113")]),
            ]),
            new TransferPatternLeg([
                new Leg([
                    new TimetableConnection("B", "C", 1020, 1025, "LN1121"),
                    new TimetableConnection("C", "D", 1025, 1045, "LN1121")
                ]),
                new Leg([
                    new TimetableConnection("B", "C", 1100, 1125, "LN1122"),
                    new TimetableConnection("C", "D", 1125, 1145, "LN1122")
                ]),
                new Leg([
                    new TimetableConnection("B", "D", 1200, 1215, "LN1123")
                ]),
            ])
        ]);

        $scanner = new SchedulePlanner($schedule, [], []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([
                    new TimetableConnection("B", "C", 1020, 1025, "LN1121"),
                    new TimetableConnection("C", "D", 1025, 1045, "LN1121")
                ]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([
                    new TimetableConnection("B", "C", 1100, 1125, "LN1122"),
                    new TimetableConnection("C", "D", 1125, 1145, "LN1122")
                ]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113")]),
                new Leg([
                    new TimetableConnection("B", "D", 1200, 1215, "LN1123")
                ]),
            ])
        ], $journeys);
    }
}
