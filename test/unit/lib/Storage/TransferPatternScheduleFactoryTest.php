<?php

use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPatternLeg;
use JourneyPlanner\Lib\Network\TransferPatternSchedule;
use JourneyPlanner\Lib\Storage\TransferPatternScheduleFactory;

class TransferPatternScheduleFactoryTest extends PHPUnit_Framework_TestCase {

    public function testGetSchedules() {
        $rows = [
            // TP 1, TL 1, SV 1
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2000,
                "service" => 3000,
                "station" => "PDW",
                "arrivalTime" => 4000,
                "departureTime" => 4000
            ],
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2000,
                "service" => 3000,
                "station" => "TON",
                "arrivalTime" => 4005,
                "departureTime" => 4005
            ],
            // SV 2
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2000,
                "service" => 3001,
                "station" => "PDW",
                "arrivalTime" => 4010,
                "departureTime" => 4010
            ],
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2000,
                "service" => 3001,
                "station" => "TON",
                "arrivalTime" => 4015,
                "departureTime" => 4015
            ],
            // TL2, SV1
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "service" => 3010,
                "station" => "TON",
                "arrivalTime" => 4000,
                "departureTime" => 4000
            ],
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "service" => 3010,
                "station" => "HIB",
                "arrivalTime" => 4005,
                "departureTime" => 4005
            ],
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "service" => 3010,
                "station" => "TBW",
                "arrivalTime" => 4010,
                "departureTime" => 4010
            ],
            // SV2
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "service" => 3011,
                "station" => "TON",
                "arrivalTime" => 4005,
                "departureTime" => 4005
            ],
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "service" => 3011,
                "station" => "HIB",
                "arrivalTime" => 4010,
                "departureTime" => 4010
            ],
            [
                "transfer_pattern" => 1000,
                "transfer_leg" => 2001,
                "service" => 3011,
                "station" => "TBW",
                "arrivalTime" => 4015,
                "departureTime" => 4015
            ],
            // TP 2, TL 1, SV 1
            [
                "transfer_pattern" => 1001,
                "transfer_leg" => 2010,
                "service" => 3100,
                "station" => "PDW",
                "arrivalTime" => 4000,
                "departureTime" => 4000
            ],
            [
                "transfer_pattern" => 1001,
                "transfer_leg" => 2010,
                "service" => 3100,
                "station" => "TBW",
                "arrivalTime" => 4005,
                "departureTime" => 4005
            ],
            // SV 2
            [
                "transfer_pattern" => 1001,
                "transfer_leg" => 2010,
                "service" => 3101,
                "station" => "PDW",
                "arrivalTime" => 4010,
                "departureTime" => 4010
            ],
            [
                "transfer_pattern" => 1001,
                "transfer_leg" => 2010,
                "service" => 3101,
                "station" => "TBW",
                "arrivalTime" => 4015,
                "departureTime" => 4015
            ]
        ];

        $expected = [
            new TransferPatternSchedule([
                new TransferPatternLeg([
                    new Leg([
                        new TimetableConnection("PDW", "TON", 4000, 4005, 3000)
                    ]),
                    new Leg([
                        new TimetableConnection("PDW", "TON", 4010, 4015, 3001)
                    ]),
                ]),
                new TransferPatternLeg([
                    new Leg([
                        new TimetableConnection("TON", "HIB", 4000, 4005, 3010),
                        new TimetableConnection("HIB", "TBW", 4005, 4010, 3010),
                    ]),
                    new Leg([
                        new TimetableConnection("TON", "HIB", 4005, 4010, 3011),
                        new TimetableConnection("HIB", "TBW", 4010, 4015, 3011),
                    ]),
                ])
            ]),
            new TransferPatternSchedule([
                new TransferPatternLeg([
                    new Leg([
                        new TimetableConnection("PDW", "TBW", 4000, 4005, 3100)
                    ]),
                    new Leg([
                        new TimetableConnection("PDW", "TBW", 4010, 4015, 3101)
                    ]),
                ]),
            ])
        ];
        
        $factory = new TransferPatternScheduleFactory();
        $actual = $factory->getSchedules($rows);
        
        $this->assertEquals($expected, $actual);
        
    }

}
