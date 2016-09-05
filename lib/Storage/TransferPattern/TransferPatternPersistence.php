<?php

namespace JourneyPlanner\Lib\Storage\TransferPattern;

use JourneyPlanner\Lib\Algorithm\ConnectionScanner;
use JourneyPlanner\Lib\Network\Journey;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternPersistence {

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
        $treeBuilder = new ConnectionScanner($this->timetable, $this->nonTimetableConnections, $this->interchange);
        
        $tree = $treeBuilder->getShortestPathTree($station, 18000);

        /** @var Journey $pattern */
        foreach ($tree as $destination => $patterns) {
            foreach ($patterns as $pattern) {
                $hash = $pattern->getHash($station, $destination);
                $legs = $pattern->getTimetableLegs();

                if (isset($existingPatterns[$hash]) || count($legs) > 7 || count($legs) === 0) {
                    continue;
                }

                error_log("Found {$hash}");
                $duration = $pattern->getDuration();
                $insertPattern->execute([$station, $destination, $duration, $scanDate . ' ' . gmdate("H:i", $pattern->getDepartureTime()), $scanDate]);
                $patternId = $db->lastInsertId();
                $existingPatterns[$hash] = $duration;

                foreach ($pattern->getTimetableLegs() as $leg) {
                    $insertLegSQL->execute([$patternId, $leg->getOrigin(), $leg->getDestination()]);
                }
            }
        }

        $db->commit();
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
