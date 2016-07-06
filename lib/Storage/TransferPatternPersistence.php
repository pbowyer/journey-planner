<?php

namespace JourneyPlanner\Lib\Storage;

use JourneyPlanner\Lib\Algorithm\ConnectionScanner;
use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Network\NonTimetableConnection;
use JourneyPlanner\Lib\Network\TimetableConnection;
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
     * @param array $timetables
     * @param array $interchange
     */
    public function __construct(array $timetables, array $interchange) {
        $this->timetables = $timetables;
        $this->interchange = $interchange;
    }

    /**
     * Truncate the tables before use
     * @param PDO $db
     */
    public function clearPreviousPatterns(PDO $db) {
        $db->exec("TRUNCATE transfer_pattern");
        $db->exec("TRUNCATE transfer_pattern_leg");
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

        foreach ($this->timetables as $time => $timetables) {
            $treeBuilder = new ConnectionScanner($timetables["timetable"], $timetables["non_timetable"], $this->interchange);
            $tree = $treeBuilder->getShortestPathTree($station);

            /** @var TransferPattern $pattern */
            foreach ($tree as $destination => $pattern) {
                $hash = $pattern->getHash($station, $destination);
error_log("Hash {$hash}");
                if (isset($existingPatterns[$hash])) {
                    continue;
                }
error_log("Saving {$hash}");
                $insertPattern->execute([$station, $destination]);
                $patternId = $db->lastInsertId();
                $existingPatterns[$hash] = true;

                foreach ($pattern->getTimetableLegs() as $leg) {
                    $insertLegSQL->execute($patternId, $leg->getOrigin(), $leg->getDestination());
                }
            }
        }

        $db->commit();
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
