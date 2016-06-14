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
        $insertPattern = $db->prepare("INSERT INTO transfer_pattern VALUES (null, ?, ?)");
        $insertLeg = $db->prepare("INSERT INTO transfer_pattern_leg VALUES (null, ?, ?, ?)");
        $patternsFound = [];

        foreach ($this->timetables as $time => $timetables) {
            $treeBuilder = new ConnectionScanner($timetables["timetable"], $timetables["non_timetable"], $this->interchange);
            $tree = $treeBuilder->getShortestPathTree($station);

            /** @var TransferPattern $pattern */
            foreach ($tree as $destination => $pattern) {
                $hash = $pattern->getHash();

                // only store unique transfer patterns
                if (isset($patternsFound[$hash])) {
                    continue;
                }

                $insertPattern->execute([$station, $destination]);
                $patternId = $db->lastInsertId();
                $patternsFound[$hash] = true;

                foreach ($pattern->getLegs() as $leg) {
                    // NonTimetableConnections are not stored in the transfer patterns
                    if (!$leg->isTransfer()) {
                        $insertLeg->execute([$patternId, $leg->getOrigin(), $leg->getDestination()]);
                    }
                }
            }
        }
    }
}
