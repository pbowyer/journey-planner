<?php

use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternLeg;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;
use JourneyPlanner\Lib\Storage\Schedule\TransferPatternScheduleFactory;

class TransferPatternScheduleFactoryTest extends PHPUnit_Framework_TestCase {

    public function testGetSchedules() {
        $rows = [
            // TP 1, TL 1, SV 1
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2000,
                "service" => 3000,
                "origin" => "PDW",
                "destination" => "TON",
                "departureTime" => 4000,
                "arrivalTime" => 4005,
                "operator" => "LN",
                "type" => "train",
            ],
            // SV 2
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2000,
                "service" => 3001,
                "origin" => "PDW",
                "destination" => "TON",
                "departureTime" => 4010,
                "arrivalTime" => 4015,
                "operator" => "LN",
                "type" => "train",
            ],
            // TL2, SV1
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "service" => 3010,
                "origin" => "TON",
                "destination" => "HIB",
                "departureTime" => 4000,
                "arrivalTime" => 4005,
                "operator" => "LN",
                "type" => "train",
            ],
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "origin" => "HIB",
                "destination" => "TBW",
                "service" => 3010,
                "departureTime" => 4005,
                "arrivalTime" => 4010,
                "operator" => "LN",
                "type" => "train",
            ],
            // SV2
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "service" => 3011,
                "origin" => "TON",
                "destination" => "HIB",
                "departureTime" => 4005,
                "arrivalTime" => 4010,
                "operator" => "LN",
                "type" => "train",
            ],
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "service" => 3011,
                "origin" => "HIB",
                "destination" => "TBW",
                "departureTime" => 4010,
                "arrivalTime" => 4015,
                "operator" => "LN",
                "type" => "train",
            ],
            // TP 2, TL 1, SV 1
            [
                "transfer_pattern" => 1001,
                "transfer_leg" => 2010,
                "service" => 3100,
                "origin" => "PDW",
                "destination" => "TBW",
                "departureTime" => 4000,
                "arrivalTime" => 4005,
                "operator" => "LN",
                "type" => "train",
            ],
            // SV 2
            [
                "transfer_pattern" => 1001,
                "transfer_leg" => 2010,
                "service" => 3101,
                "origin" => "PDW",
                "destination" => "TBW",
                "departureTime" => 4010,
                "arrivalTime" => 4015,
                "operator" => "LN",
                "type" => "train",
            ],
        ];

        $expected = [
            new TransferPatternSchedule([
                new TransferPatternLeg([
                    new Leg([
                        new TimetableConnection("PDW", "TON", 4000, 4005, 3000, "LN")
                    ]),
                    new Leg([
                        new TimetableConnection("PDW", "TON", 4010, 4015, 3001, "LN")
                    ]),
                ]),
                new TransferPatternLeg([
                    new Leg([
                        new TimetableConnection("TON", "HIB", 4000, 4005, 3010, "LN"),
                        new TimetableConnection("HIB", "TBW", 4005, 4010, 3010, "LN"),
                    ]),
                    new Leg([
                        new TimetableConnection("TON", "HIB", 4005, 4010, 3011, "LN"),
                        new TimetableConnection("HIB", "TBW", 4010, 4015, 3011, "LN"),
                    ]),
                ])
            ]),
            new TransferPatternSchedule([
                new TransferPatternLeg([
                    new Leg([
                        new TimetableConnection("PDW", "TBW", 4000, 4005, 3100, "LN")
                    ]),
                    new Leg([
                        new TimetableConnection("PDW", "TBW", 4010, 4015, 3101, "LN")
                    ]),
                ]),
            ])
        ];
        
        $factory = new TransferPatternScheduleFactory();
        $actual = $factory->getSchedulesFromTimetable($rows);
        
        $this->assertEquals($expected, $actual);
        
    }

}
