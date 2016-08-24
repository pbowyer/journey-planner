<?php

use JourneyPlanner\Lib\Algorithm\Filter\SlowJourneyFilter;
use JourneyPlanner\Lib\Algorithm\MultiSchedulePlanner;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternLeg;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;
use JourneyPlanner\Lib\Storage\Schedule\ScheduleProvider;
use PHPUnit\Framework\TestCase;

class MultiSchedulePlannerTest extends TestCase {

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
                new Leg([new TimetableConnection("A", "B", 1005, 1020, "LN1111", "LN")]),
                new Leg([new TimetableConnection("A", "B", 1025, 1050, "LN1112", "LN")]),
                new Leg([new TimetableConnection("A", "B", 1105, 1120, "LN1113", "LN")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("B", "C", 1025, 1050, "LN1121", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1105, 1150, "LN1122", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1205, 1220, "LN1123", "LN")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1125, 1150, "LN1131", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1250, "LN1132", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1320, "LN1133", "LN")]),
            ])
        ]);

        $mock = $this->createMock(ScheduleProvider::class);
        $mock->method('getInterchangeTimes')->willReturn([]);
        $mock->method('getNonTimetableConnections')->willReturn([]);
        $mock->method('getTimetable')->willReturn([$schedule, $schedule2]);

        $scanner = new MultiSchedulePlanner($mock, [new SlowJourneyFilter()]);
        $journeys = $scanner->getJourneys(["A"], ["D"], 900);

        $this->assertEquals([
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1000, 1015, "LN1111", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1005, 1020, "LN1111", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1025, 1050, "LN1121", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1125, 1150, "LN1131", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1020, 1045, "LN1112", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1025, 1050, "LN1112", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1105, 1150, "LN1122", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1250, "LN1132", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1100, 1115, "LN1113", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133", "LN")]),
            ]),
            new Journey([
                new Leg([new TimetableConnection("A", "B", 1105, 1120, "LN1113", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1205, 1220, "LN1123", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1320, "LN1133", "LN")]),
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
                new Leg([new TimetableConnection("B", "C", 1025, 1050, "LN1121", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1105, 1150, "LN1122", "LN")]),
                new Leg([new TimetableConnection("B", "C", 1205, 1250, "LN1123", "LN")]),
            ]),
            new TransferPatternLeg([
                new Leg([new TimetableConnection("C", "D", 1125, 1150, "LN1131", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1250, "LN1132", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1320, "LN1133", "LN")]),
            ])
        ]);

        $nonTimetable = [
            "A" => [
                new NonTimetableConnection("A", "B", 10)
            ]
        ];

        $mock = $this->createMock(ScheduleProvider::class);
        $mock->method('getInterchangeTimes')->willReturn([]);
        $mock->method('getNonTimetableConnections')->willReturn($nonTimetable);
        $mock->method('getTimetable')->willReturn([$schedule, $schedule2]);

        $scanner = new MultiSchedulePlanner($mock, [new SlowJourneyFilter()]);
        $journeys = $scanner->getJourneys(["A"], ["D"], 900);

        $this->assertEquals([
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1020, 1045, "LN1121", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1120, 1145, "LN1131", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1025, 1050, "LN1121", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1125, 1150, "LN1131", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1100, 1145, "LN1122", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1200, 1245, "LN1132", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1105, 1150, "LN1122", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1205, 1250, "LN1132", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1200, 1215, "LN1123", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1300, 1315, "LN1133", "LN")]),
            ]),
            new Journey([
                new Leg([new NonTimetableConnection("A", "B", 10)]),
                new Leg([new TimetableConnection("B", "C", 1205, 1250, "LN1123", "LN")]),
                new Leg([new TimetableConnection("C", "D", 1305, 1320, "LN1133", "LN")]),
            ])
        ], $journeys);

    }


}
