<?php

use JourneyPlanner\Lib\Journey\FixedLeg;
use JourneyPlanner\Lib\Journey\Journey;
use JourneyPlanner\Lib\Journey\Leg;
use JourneyPlanner\Lib\Journey\Repository\FixedLegRepository;
use JourneyPlanner\Lib\Station\Repository\InterchangeRepository;
use JourneyPlanner\Lib\Journey\TimetableLeg;
use JourneyPlanner\Lib\Planner\Filter\SlowJourneyFilter;
use JourneyPlanner\Lib\Planner\GroupStationPlanner;
use JourneyPlanner\Lib\Station\Repository\StationRepository;
use JourneyPlanner\Lib\TransferPattern\PatternSegment;
use JourneyPlanner\Lib\TransferPattern\Repository\TransferPatternRepository;
use JourneyPlanner\Lib\TransferPattern\TransferPattern;
use PHPUnit\Framework\TestCase;

class GroupStationPlannerTest extends TestCase {

    public function testSortJourneys() {
        $schedule = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [], "LN1111", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [], "LN1112", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [], "LN1113", "LN"),
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

        $schedule2 = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("A", "B", Leg::TRAIN, 1005, 1020, [], "LN1111", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1025, 1050, [], "LN1112", "LN"),
                new TimetableLeg("A", "B", Leg::TRAIN, 1105, 1120, [], "LN1113", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("B", "C", Leg::TRAIN, 1025, 1050, [], "LN1121", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1105, 1150, [], "LN1122", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1205, 1220, [], "LN1123", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("C", "D", Leg::TRAIN, 1125, 1150, [], "LN1131", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1205, 1250, [], "LN1132", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1305, 1320, [], "LN1133", "LN"),
            ])
        ]);

        $tp = $this->createMock(TransferPatternRepository::class);
        $tp->method('getTransferPatterns')->willReturn([$schedule, $schedule2]);
        $int = $this->createMock(InterchangeRepository::class);
        $int->method('getInterchange')->willReturn([]);
        $fx = $this->createMock(FixedLegRepository::class);
        $fx->method('getFixedLegs')->willReturn([]);
        $st = $this->createMock(StationRepository::class);
        $st->method('getRelevantStations')->willReturn(["A"], ["D"]);

        $scanner = new GroupStationPlanner($tp, $st, $fx, $int, [new SlowJourneyFilter()]);
        $journeys = $scanner->getJourneys("A", "D", new DateTime("00:10 UTC"));

        $this->assertEquals([
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1000, 1015, [], "LN1111", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1020, 1045, [], "LN1121", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1005, 1020, [], "LN1111", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1025, 1050, [], "LN1121", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1125, 1150, [], "LN1131", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1020, 1045, [], "LN1112", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1145, [], "LN1122", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1025, 1050, [], "LN1112", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1105, 1150, [], "LN1122", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1205, 1250, [], "LN1132", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1100, 1115, [], "LN1113", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1200, 1215, [], "LN1123", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1300, 1315, [], "LN1133", "LN"),
            ]),
            new Journey([
                new TimetableLeg("A", "B", Leg::TRAIN, 1105, 1120, [], "LN1113", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1205, 1220, [], "LN1123", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1305, 1320, [], "LN1133", "LN"),
            ])
        ], $journeys);
    }
    public function testSortJourneysWithTransferAtStart() {
        $schedule = new TransferPattern([
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

        $schedule2 = new TransferPattern([
            new PatternSegment([
                new TimetableLeg("B", "C", Leg::TRAIN, 1025, 1050, [], "LN1121", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1105, 1150, [], "LN1122", "LN"),
                new TimetableLeg("B", "C", Leg::TRAIN, 1205, 1250, [], "LN1123", "LN"),
            ]),
            new PatternSegment([
                new TimetableLeg("C", "D", Leg::TRAIN, 1125, 1150, [], "LN1131", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1205, 1250, [], "LN1132", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1305, 1320, [], "LN1133", "LN"),
            ])
        ]);

        $nonTimetable = [
            "A" => [
                new FixedLeg("A", "B", Leg::TRAIN, 10, 0, 999999)
            ]
        ];

        $tp = $this->createMock(TransferPatternRepository::class);
        $tp->method('getTransferPatterns')->willReturn([$schedule, $schedule2]);
        $int = $this->createMock(InterchangeRepository::class);
        $int->method('getInterchange')->willReturn([]);
        $fx = $this->createMock(FixedLegRepository::class);
        $fx->method('getFixedLegs')->willReturn($nonTimetable);
        $st = $this->createMock(StationRepository::class);
        $st->method('getRelevantStations')->willReturn(["A"], ["D"]);

        $scanner = new GroupStationPlanner($tp, $st, $fx, $int, [new SlowJourneyFilter()]);
        $journeys = $scanner->getJourneys("A", "D", new DateTime("00:10 UTC"));

        $this->assertEquals([
            new Journey([
                new FixedLeg("A", "B", Leg::TRAIN, 10, 0, 999999),
                new TimetableLeg("B", "C", Leg::TRAIN, 1020, 1045, [], "LN1121", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1120, 1145, [], "LN1131", "LN"),
            ]),
            new Journey([
                new FixedLeg("A", "B", Leg::TRAIN, 10, 0, 999999),
                new TimetableLeg("B", "C", Leg::TRAIN, 1025, 1050, [], "LN1121", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1125, 1150, [], "LN1131", "LN"),
            ]),
            new Journey([
                new FixedLeg("A", "B", Leg::TRAIN, 10, 0, 999999),
                new TimetableLeg("B", "C", Leg::TRAIN, 1100, 1145, [], "LN1122", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1200, 1245, [], "LN1132", "LN"),
            ]),
            new Journey([
                new FixedLeg("A", "B", Leg::TRAIN, 10, 0, 999999),
                new TimetableLeg("B", "C", Leg::TRAIN, 1105, 1150, [], "LN1122", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1205, 1250, [], "LN1132", "LN"),
            ]),
            new Journey([
                new FixedLeg("A", "B", Leg::TRAIN, 10, 0, 999999),
                new TimetableLeg("B", "C", Leg::TRAIN, 1200, 1215, [], "LN1123", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1300, 1315, [], "LN1133", "LN"),
            ]),
            new Journey([
                new FixedLeg("A", "B", Leg::TRAIN, 10, 0, 999999),
                new TimetableLeg("B", "C", Leg::TRAIN, 1205, 1250, [], "LN1123", "LN"),
                new TimetableLeg("C", "D", Leg::TRAIN, 1305, 1320, [], "LN1133", "LN"),
            ])
        ], $journeys);

    }


}
