<?php

namespace JourneyPlanner\Lib\Loader;

use JourneyPlanner\Lib\Algorithm\DijkstraShortestPath;
use PDO;
use Spork\ProcessManager;
use Spork\Batch\Strategy\AbstractStrategy;

class TreePersistence {

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
     * @param ProcessManager   $processManager
     * @param AbstractStrategy $forkStrategy
     * @param callable         $pdoFactory
     */
    public function __construct(ProcessManager $processManager, AbstractStrategy $forkStrategy, callable $pdoFactory) {
        $this->processManager = $processManager;
        $this->forkStrategy = $forkStrategy;
        $this->dbFactory = $pdoFactory;
    }

    /**
     * Truncate the fastest_connection table and repopulate it by checking the timetable_connection
     * table for the fastest way to get from A to B
     */
    public function populateFastestConnections() {
        $db = call_user_func($this->dbFactory);

        $db->exec("TRUNCATE fastest_connection");
        $db->exec("
            INSERT INTO fastest_connection
            SELECT departureTime, arrivalTime, origin, destination, service
            FROM timetable_connection
            GROUP BY origin, destination
            HAVING MIN(arrivalTime - departureTime)
        ");
    }

    /**
     * Use the $pathFinder to store the shortest path tree for every stop in the graph
     *
     * @param  DijkstraShortestPath $pathFinder
     */
    public function populateShortestPaths(DijkstraShortestPath $pathFinder) {
        $db = call_user_func($this->dbFactory);

        $db->exec("TRUNCATE shortest_path");

        $callback = function($station) use ($pathFinder) {
            $db = call_user_func($this->dbFactory);
            $stmt = $db->prepare("INSERT INTO shortest_path VALUES (:origin, :destination, :duration)");
            $tree = $pathFinder->getShortestPathTree($station);

            foreach ($tree as $destination => $duration) {
                $stmt->execute([
                    "origin" => $station,
                    "destination" => $destination,
                    "duration" => $duration
                ]);
            }
        };

        $this->processManager->process($pathFinder->getNodes(), $callback, $this->forkStrategy);
    }
}
