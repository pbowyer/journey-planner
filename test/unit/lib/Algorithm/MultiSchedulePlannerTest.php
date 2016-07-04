<?php

use JourneyPlanner\Lib\Algorithm\MultiSchedulePlanner;
use JourneyPlanner\Lib\Algorithm\SchedulePlanner;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternLeg;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;

class MultiSchedulePlannerTest extends PHPUnit_Framework_TestCase {

    public function testSortJourneys() {
        $schedule = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111", "LN")]),
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112", "LN")]),
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113", "LN")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123", "LN")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133", "LN")]),
            ])
        ]);

        $schedule2 = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("A", "B", 1005, 1015, "LN1111", "LN")]),
                new Leg([new TimetableConnection("A", "B", 1025, 1045, "LN1112", "LN")]),
                new Leg([new TimetableConnection("A", "B", 1105, 1115, "LN1113", "LN")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("B", "C", 1025, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1105, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1205, 1215, "LN1123", "LN")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1125, 1145, "LN1131", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1245, "LN1132", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1315, "LN1133", "LN")]),
            ])
        ]);

        $scanner = new MultiSchedulePlanner([$schedule, $schedule2], [], []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1005, 1015, "LN1111", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1025, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1125, 1145, "LN1131", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1025, 1045, "LN1112", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1105, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1245, "LN1132", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1105, 1115, "LN1113", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1205, 1215, "LN1123", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1315, "LN1133", "LN")]),
            ])
        ], $journeys);
    }
    public function testSortJourneysWithTransferAtStart() {
        $schedule = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123", "LN")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133", "LN")]),
            ])
        ]);

        $schedule2 = new TransferPatternSchedule([
            new TransferPatternLeg([
                new Leg([new TimetableConnection("B", "C", 1025, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1105, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1205, 1215, "LN1123", "LN")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1125, 1145, "LN1131", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1245, "LN1132", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1315, "LN1133", "LN")]),
            ])
        ]);

        $nonTimetable = [
            "A" => [
                new NonTimetableConnection("A", "B", 10)
            ]
        ];

        $scanner = new MultiSchedulePlanner([$schedule, $schedule2], $nonTimetable, []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1025, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1125, 1145, "LN1131", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1105, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1245, "LN1132", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1205, 1215, "LN1123", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1315, "LN1133", "LN")]),
            ])
        ], $journeys);

    }


}
