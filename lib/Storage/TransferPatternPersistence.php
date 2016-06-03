<?php

namespace JourneyPlanner\Lib\Storage;

use JourneyPlanner\Lib\Algorithm\ConnectionScanner;
use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Network\TimetableConnection;
use JourneyPlanner\Lib\Network\TransferPattern;
use PDO;
use Spork\ProcessManager;
use Spork\Batch\Strategy\AbstractStrategy;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternPersistence {

    const DAYS = [
        "next monday",
        "next friday",
        "next saturday",
        "next sunday",
    ];

    const HOURS = [
        "07:00",
        "17:00",
        "22:00",
    ];

    /**
     * @var callable
     */
    private $dbFactory;

    /**
     * @var AbstractStrategy
     */
    private $forkStrategy;

    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * @var array
     */
    private $timetables;

    /**
     * @var array
     */
    private $nonTimetableConnections;

    /**
     * @var array
     */
    private $interchange;

    /**
     * @param ProcessManager   $processManager
     * @param AbstractStrategy $forkStrategy
     * @param callable         $pdoFactory
     */
    public function __construct(ProcessManager $processManager, AbstractStrategy $forkStrategy, callable $pdoFactory) {
        $this->processManager = $processManager;
        $this->forkStrategy = $forkStrategy;
        $this->dbFactory = $pdoFactory;
        $this->timetables = [];
        $this->nonTimetableConnections = [];
        $this->interchange = [];
    }

    /**
     * For each station find and store the transfer patterns at various times of day
     */
    public function calculateTransferPatterns() {
        $db = call_user_func($this->dbFactory);
        $db->exec("TRUNCATE tranfer_pattern");
        $db->exec("TRUNCATE tranfer_pattern_leg");

        $loader = new DatabaseLoader($db);
        $this->timetables = $this->getTimetables($loader);
        $this->nonTimetableConnections = $loader->getNonTimetableConnections();
        $this->interchange = $loader->getInterchangeTimes();
        $stations = array_keys($loader->getLocations());

        $this->processManager->process($stations, [$this, 'storeTransferPatternsForStation'], $this->forkStrategy);
    }

    /**
     * @param  DatabaseLoader $loader
     * @return Connection[]
     */
    private function getTimetables(DatabaseLoader $loader) {
        $timetables = [];

        foreach (self::DAYS as $day) {
            foreach (self::HOURS as $hour) {
                $timetables[] = $loader->getUnprunedTimetableConnections(strtotime("{$day} as {$hour}"));
            }
        }

        return $timetables;
    }

    /**
     * @param string $station
     */
    private function storeTransferPatternsForStation($station) {
        /** @var PDO $db */
        $db = call_user_func($this->dbFactory);
        $insertPattern = $db->prepare("INSERT INTO transfer_pattern VALUES (null, ?, ?)");
        $insertLeg = $db->prepare("INSERT INTO transfer_pattern_leg VALUES (null, ?, ?, ?)");
        $patternsFound = [];

        foreach ($this->timetables as $timetable) {
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
