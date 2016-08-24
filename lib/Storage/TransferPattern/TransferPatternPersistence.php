<?php

namespace JourneyPlanner\Lib\Storage\TransferPattern;

use JourneyPlanner\Lib\Algorithm\ConnectionScanner;
use JourneyPlanner\Lib\Algorithm\MinimumChangesConnectionScanner;
use JourneyPlanner\Lib\Algorithm\MinimumSpanningTreeGenerator;
use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Network\TransferPattern;
use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternPersistence {

    /**
     * @var array
     */
    private $timetables;

    /**
     * @var array
     */
    private $interchange;

    /**
     * @var array
     */
    private $nonTimetableConnections;

    /**
     * @param array $timetables
     * @param array $nonTimetableConnections
     * @param array $interchange
     */
    public function __construct(array $timetables, array $nonTimetableConnections, array $interchange) {
        $this->timetables = $timetables;
        $this->nonTimetableConnections = $nonTimetableConnections;
        $this->interchange = $interchange;
    }
    
    /**
     * @param PDO $db
     * @param string $station
     */
    public function calculateTransferPatternsForStation(PDO $db, $station) {
        $db->beginTransaction();

        $insertPattern = $db->prepare("INSERT INTO transfer_pattern VALUES (null, ?, ?)");
        $insertLegSQL = $db->prepare("INSERT INTO transfer_pattern_leg VALUES (null, ?, ?, ?)");
        $existingPatterns = array_flip($this->getExistingPatterns($db, $station));

        foreach ($this->timetables as $timetable) {
            foreach ($this->getTreeBuilders($timetable) as $treeBuilder) {
                $tree = $treeBuilder->getShortestPathTree($station);

                /** @var TransferPattern $pattern */
                foreach ($tree as $destination => $pattern) {
                    $hash = $pattern->getHash($station, $destination);

                    if (isset($existingPatterns[$hash]) || count($pattern->getLegs()) > 7) {
                        continue;
                    }
                    error_log("Found $hash");
                    $insertPattern->execute([$station, $destination]);
                    $patternId = $db->lastInsertId();
                    $existingPatterns[$hash] = true;

                    foreach ($pattern->getTimetableLegs() as $leg) {
                        $insertLegSQL->execute([$patternId, $leg->getOrigin(), $leg->getDestination()]);
                    }
                }
            }
        }

        $db->commit();
    }

    /**
     * @param Connection[] $timetable
     * @return MinimumSpanningTreeGenerator[]
     */
    private function getTreeBuilders(array $timetable) {
        return [
            new ConnectionScanner($timetable, $this->nonTimetableConnections, $this->interchange),
            new MinimumChangesConnectionScanner($timetable, $this->nonTimetableConnections, $this->interchange),
        ];
    }

    /**
     * @param PDO $db
     * @param string $station
     * @return string[]
     */
    private function getExistingPatterns(PDO $db, $station) {
        $stmt = $db->prepare("
          SELECT concat(tp.origin, tp.destination, group_concat(leg.origin, leg.destination separator '')) 
          FROM transfer_pattern tp 
          JOIN transfer_pattern_leg leg ON leg.transfer_pattern = tp.id
          WHERE tp.origin = ? 
          GROUP BY tp.id
        ");
        
        $stmt->execute([$station]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
