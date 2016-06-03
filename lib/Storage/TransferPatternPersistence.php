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
     * @var NonTimetableConnection[]
     */
    private $nonTimetableConnections;

    /**
     * @var array
     */
    private $interchange;

    /**
     * @param array $timetables
     * @param NonTimetableConnection[] $nonTimetableConnections
     * @param array $interchange
     */
    public function __construct(array $timetables, array $nonTimetableConnections, array $interchange) {
        $this->timetables = $timetables;
        $this->nonTimetableConnections = $nonTimetableConnections;
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

        foreach ($this->timetables as $time => $timetable) {
            $treeBuilder = new ConnectionScanner($timetable, $this->nonTimetableConnections, $this->interchange);
            $tree = $treeBuilder->getShortestPathTree($station);

            foreach ($tree as $destination => $legs) {
                $hash = TransferPattern::getHash($legs);

                // only store unique transfer patterns
                if (isset($patternsFound[$hash])) {
                    continue;
                }

                $insertPattern->execute([$station, $destination]);
                $patternId = $db->lastInsertId();
                $patternsFound[$hash] = true;

                foreach ($legs as $leg) {
                    // NonTimetableConnections are not stored in the transfer patterns
                    if ($leg instanceof TimetableConnection) {
                        $insertLeg->execute([$patternId, $leg->getOrigin(), $leg->getDestination()]);
                    }
                }
            }
        }
    }
}
