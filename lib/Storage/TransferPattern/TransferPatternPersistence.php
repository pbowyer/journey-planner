<?php

namespace JourneyPlanner\Lib\Storage\TransferPattern;

use JourneyPlanner\Lib\Algorithm\ConnectionScanner;
use JourneyPlanner\Lib\Algorithm\MinimumChangesConnectionScanner;
use JourneyPlanner\Lib\Algorithm\MinimumSpanningTreeGenerator;
use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Network\Journey;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternPersistence {

    const HOURS = [
        "05:00",
        "06:00",
        "07:00",
        "08:00",
        "10:00",
        "12:00",
        "13:00",
        "16:00",
        "17:00",
        "18:00",
        "20:00",
        "21:00",
        "23:00",
    ];

    private $timetable;
    private $interchange;
    private $nonTimetableConnections;

    /**
     * @param array $timetable
     * @param array $nonTimetableConnections
     * @param array $interchange
     */
    public function __construct(array $timetable, array $nonTimetableConnections, array $interchange) {
        $this->timetable = $timetable;
        $this->nonTimetableConnections = $nonTimetableConnections;
        $this->interchange = $interchange;
    }

    /**
     * @param PDO $db
     * @param string $station
     * @param string $scanDate
     */
    public function calculateTransferPatternsForStation(PDO $db, $station, $scanDate) {
        $db->beginTransaction();

        $insertPattern = $db->prepare("INSERT INTO transfer_pattern VALUES (null, ?, ?, ?, ?, ?)");
        $insertLegSQL = $db->prepare("INSERT INTO transfer_pattern_leg VALUES (null, ?, ?, ?)");
        $existingPatterns = $this->getExistingPatterns($db, $station);

        foreach ($this->getTreeBuilders() as $treeBuilder) {
            foreach (self::HOURS as $hour) {
                error_log("Starting {$station} at ".gmdate("H:i", strtotime("1970-01-01 {$hour} UTC")));

                $tree = $treeBuilder->getShortestPathTree($station, strtotime("1970-01-01 {$hour} UTC"));

                /** @var Journey $pattern */
                foreach ($tree as $destination => $pattern) {
                    $hash = $pattern->getHash($station, $destination);
                    $legs = $pattern->getTimetableLegs();

                    if (isset($existingPatterns[$hash]) || count($legs) > 7 || count($legs) === 0) {
                        continue;
                    }

                    error_log("Found $hash");
                    $duration = $pattern->getDuration();
                    $insertPattern->execute([$station, $destination, $duration, $scanDate.' '.gmdate("H:i", $pattern->getDepartureTime()), $scanDate.' '.$hour]);
                    $patternId = $db->lastInsertId();
                    $existingPatterns[$hash] = $duration;

                    foreach ($pattern->getTimetableLegs() as $leg) {
                        $insertLegSQL->execute([$patternId, $leg->getOrigin(), $leg->getDestination()]);
                    }
                }
            }
        }

        $db->commit();
    }

    /**
     * @return MinimumSpanningTreeGenerator[]
     */
    private function getTreeBuilders() {
        return [
            new ConnectionScanner($this->timetable, $this->nonTimetableConnections, $this->interchange),
        ];
    }

    /**
     * @param PDO $db
     * @param string $station
     * @return int[]
     */
    private function getExistingPatterns(PDO $db, $station) {
        $stmt = $db->prepare("
          SELECT concat(tp.origin, tp.destination, group_concat(leg.origin, leg.destination separator '')) as hash, journey_duration
          FROM transfer_pattern tp 
          JOIN transfer_pattern_leg leg ON leg.transfer_pattern = tp.id
          WHERE tp.origin = ? 
          GROUP BY tp.id
        ");
        
        $stmt->execute([$station]);

        $existingPatterns = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingPatterns[$row["hash"]] = $row["journey_duration"];
        }

        return $existingPatterns;
    }
}
