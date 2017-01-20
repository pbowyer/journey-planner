<?php

use JourneyPlanner\Lib\Journey\CallingPoint;
use JourneyPlanner\Lib\Journey\FixedLeg;
use JourneyPlanner\Lib\Journey\Journey;
use JourneyPlanner\Lib\Journey\Leg;
use JourneyPlanner\Lib\Journey\TimetableLeg;
use JourneyPlanner\Lib\Planner\TransferPatternPlanner;
use JourneyPlanner\Lib\TransferPattern\PatternSegment;
use JourneyPlanner\Lib\TransferPattern\TransferPattern;

class TransferPatternPlannerTest extends PHPUnit_Framework_TestCase {

    public function testBasicJourney() {
        $schedule = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("B", "C", Leg::TRAIN, 1020, 1045, [], "LN1121", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1145, [], "LN1122", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1200, 1215, [], "LN1123", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1300, 1315, [], "LN1133", "LN"),
            ])
        ]);

        $scanner = new TransferPatternPlanner($schedule, [], []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1020, 1045, [], "LN1121", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1145, [], "LN1122", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1200, 1215, [], "LN1123", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1300, 1315, [], "LN1133", "LN"),
            ])
        ], $journeys);
    }

    public function testJourneyWithFixedLeg() {
        $schedule = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1300, 1315, [], "LN1133", "LN"),
            ])
        ]);

        $nonTimetable = [
            "B" => [
                new FixedLeg("B", "C", Leg::WALK, 5, 0, 999999),
                new FixedLeg("B", "E", Leg::WALK, 5, 0, 999999),
            ]
        ];

        $scanner = new TransferPatternPlanner($schedule, $nonTimetable, []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new FixedLeg("B", "C", Leg::WALK, 5, 0, 999999),
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new FixedLeg("B", "C", Leg::WALK, 5, 0, 999999),
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
                new FixedLeg("B", "C", Leg::WALK, 5, 0, 999999),
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
            ]),
        ], $journeys);
    }

    public function testCantMakeUnreachableConnectionsWithTransfer() {
        $schedule = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1300, 1315, [], "LN1133", "LN"),
            ])
        ]);

        $nonTimetable = [
            "B" => [
                new FixedLeg("B", "C", Leg::WALK, 15, 0, 999999),
                new FixedLeg("B", "E", Leg::WALK, 5, 0, 999999),
            ]
        ];

        $scanner = new TransferPatternPlanner($schedule, $nonTimetable, []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new FixedLeg("B", "C", Leg::WALK, 15, 0, 999999),
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new FixedLeg("B", "C", Leg::WALK, 15, 0, 999999),
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
                new FixedLeg("B", "C", Leg::WALK, 15, 0, 999999),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
            ]),
        ], $journeys);
    }

    public function testJourneyWithUnreachableTimetableLegs() {
        $schedule = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1500, 1515, [], "LN1113", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1700, 1715, [], "LN1114", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("B", "C", Leg::TRAIN, 1020, 1045, [], "LN1121", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1145, [], "LN1122", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1200, 1215, [], "LN1123", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1300, 1315, [], "LN1133", "LN"),
            ])
        ]);

        $scanner = new TransferPatternPlanner($schedule, [], []);
        $journeys = $scanner->getJourneys("A", "D", 1005);

        $this->assertEquals([
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1145, [], "LN1122", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
            ])
        ], $journeys);
    }

    public function testJourneyWithFirstTimetableLegUnreachable() {
        $schedule = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("A", "B", Leg::TRAIN, 1400, 1415, [], "LN1111", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1520, 1545, [], "LN1112", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1600, 1615, [], "LN1113", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1700, 1715, [], "LN1114", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("B", "C", Leg::TRAIN, 1020, 1045, [], "LN1121", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1145, [], "LN1122", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1200, 1215, [], "LN1123", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1300, 1315, [], "LN1133", "LN"),
            ])
        ]);

        $scanner = new TransferPatternPlanner($schedule, [], []);
        $journeys = $scanner->getJourneys("A", "D", 1005);

        $this->assertEquals([], $journeys);
    }

    public function testJourneyWithTransferForFirstTimetableLeg() {
        $schedule = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1145, [], "LN1122", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
            ])
        ]);

        $nonTimetable = [
            "A" => [
                new FixedLeg("A", "B", Leg::WALK, 5, 0, 999999),
            ]
        ];

        $scanner = new TransferPatternPlanner($schedule, $nonTimetable, []);
        $journeys = $scanner->getJourneys("A", "D", 1005);

        $this->assertEquals([
            new Journey([
                new FixedLeg("A", "B", Leg::WALK, 5, 0, 999999),
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1145, [], "LN1122", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
            ])
        ], $journeys);
    }

    public function testJourneyWithCallingPoints() {
        $schedule = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("B", "D", Leg::TRAIN, 1020, 1045, [new CallingPoint("B", null, 1020), new CallingPoint("C", 1025, 1025), new CallingPoint("D", 1045)], "LN1121", "LN"),
                new TimetableLeg("B", "D", Leg::TRAIN, 1100, 1145, [new CallingPoint("B", null, 1100), new CallingPoint("C", 1125, 1125), new CallingPoint("D", 1145)], "LN1122", "LN"),
                new TimetableLeg("B", "D", Leg::TRAIN, 1200, 1215, [new CallingPoint("B", null, 1200), new CallingPoint("D", 1215)], "LN1123", "LN"),
            ])
        ]);

        $scanner = new TransferPatternPlanner($schedule, [], []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new TimetableLeg("B", "D", Leg::TRAIN, 1020, 1045, [new CallingPoint("B", null, 1020), new CallingPoint("C", 1025, 1025), new CallingPoint("D", 1045)], "LN1121", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("B", "D", Leg::TRAIN, 1100, 1145, [new CallingPoint("B", null, 1100), new CallingPoint("C", 1125, 1125), new CallingPoint("D", 1145)], "LN1122", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
                new TimetableLeg("B", "D", Leg::TRAIN, 1200, 1215, [new CallingPoint("B", null, 1200), new CallingPoint("D", 1215)], "LN1123", "LN"),
            ])
        ], $journeys);
    }

    public function testJourneyWithOvertakenTimetableLeg() {
        $schedule = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("B", "C", Leg::TRAIN, 1020, 1045, [], "LN1121", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1130, 1155, [], "LN1123", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1205, [], "LN1122", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1300, 1315, [], "LN1133", "LN"),
            ])
        ]);

        $scanner = new TransferPatternPlanner($schedule, [], []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [new CallingPoint("A", null, 1000), new CallingPoint("B", 1015)], "LN1111", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1020, 1045, [], "LN1121", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [new CallingPoint("A", null, 1020), new CallingPoint("B", 1045)], "LN1112", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1130, 1155, [], "LN1123", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [new CallingPoint("A", null, 1100), new CallingPoint("B", 1115)], "LN1113", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1130, 1155, [], "LN1123", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
            ])
        ], $journeys);
    }

}
