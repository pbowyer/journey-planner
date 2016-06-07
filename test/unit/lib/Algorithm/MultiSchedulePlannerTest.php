<?php

use JourneyPlanner\Lib\Algorithm\MultiSchedulePlanner;
use JourneyPlanner\Lib\Algorithm\SchedulePlanner;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;

class MultiSchedulePlannerTest extends PHPUnit_Framework_TestCase {

    public function testSortJourneys() {
        $schedule = new TransferPatternSchedule([
            [
                new TimetableConnection("A", "B", 1000, 1015, "LN1111"),
                new TimetableConnection("A", "B", 1020, 1045, "LN1112"),
                new TimetableConnection("A", "B", 1100, 1115, "LN1113"),
            ],
            [
                new TimetableConnection("B", "C", 1020, 1045, "LN1121"),
                new TimetableConnection("B", "C", 1100, 1145, "LN1122"),
                new TimetableConnection("B", "C", 1200, 1215, "LN1123"),
            ],
            [
                new TimetableConnection("C", "D", 1120, 1145, "LN1131"),
                new TimetableConnection("C", "D", 1200, 1245, "LN1132"),
                new TimetableConnection("C", "D", 1300, 1315, "LN1133"),
            ]
        ]);

        $schedule2 = new TransferPatternSchedule([
            [
                new TimetableConnection("A", "B", 1005, 1015, "LN1111"),
                new TimetableConnection("A", "B", 1025, 1045, "LN1112"),
                new TimetableConnection("A", "B", 1105, 1115, "LN1113"),
            ],
            [
                new TimetableConnection("B", "C", 1025, 1045, "LN1121"),
                new TimetableConnection("B", "C", 1105, 1145, "LN1122"),
                new TimetableConnection("B", "C", 1205, 1215, "LN1123"),
            ],
            [
                new TimetableConnection("C", "D", 1125, 1145, "LN1131"),
                new TimetableConnection("C", "D", 1205, 1245, "LN1132"),
                new TimetableConnection("C", "D", 1305, 1315, "LN1133"),
            ]
        ]);

        $scanner = new MultiSchedulePlanner([$schedule, $schedule2], [], []);
        $journeys = $scanner->getJourneys("A", "D", 900);

        $this->assertEquals([
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111")]),
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121")]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1005, 1015, "LN1111")]),
                new Leg([new TimetableConnection("B", "C", 1025, 1045, "LN1121")]),
                new Leg([new TimetableConnection("C", "D", 1125, 1145, "LN1131")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1025, 1045, "LN1112")]),
                new Leg([new TimetableConnection("B", "C", 1105, 1145, "LN1122")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1245, "LN1132")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113")]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1105, 1115, "LN1113")]),
                new Leg([new TimetableConnection("B", "C", 1205, 1215, "LN1123")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1315, "LN1133")]),
            ])
        ], $journeys);
    }
    public function testSortJourneysWithTransferAtStart() {
        $schedule = new TransferPatternSchedule([
            [
                new TimetableConnection("B", "C", 1020, 1045, "LN1121"),
                new TimetableConnection("B", "C", 1100, 1145, "LN1122"),
                new TimetableConnection("B", "C", 1200, 1215, "LN1123"),
            ],
            [
                new TimetableConnection("C", "D", 1120, 1145, "LN1131"),
                new TimetableConnection("C", "D", 1200, 1245, "LN1132"),
                new TimetableConnection("C", "D", 1300, 1315, "LN1133"),
            ]
        ]);

        $schedule2 = new TransferPatternSchedule([
            [
                new TimetableConnection("B", "C", 1025, 1045, "LN1121"),
                new TimetableConnection("B", "C", 1105, 1145, "LN1122"),
                new TimetableConnection("B", "C", 1205, 1215, "LN1123"),
            ],
            [
                new TimetableConnection("C", "D", 1125, 1145, "LN1131"),
                new TimetableConnection("C", "D", 1205, 1245, "LN1132"),
                new TimetableConnection("C", "D", 1305, 1315, "LN1133"),
            ]
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
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121")]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1025, 1045, "LN1121")]),
                new Leg([new TimetableConnection("C", "D", 1125, 1145, "LN1131")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1105, 1145, "LN1122")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1245, "LN1132")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1205, 1215, "LN1123")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1315, "LN1133")]),
            ])
        ], $journeys);

    }


}
