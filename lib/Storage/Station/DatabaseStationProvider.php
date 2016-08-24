<?php

namespace JourneyPlanner\Lib\Storage\Station;

use PDO;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class DatabaseStationProvider implements StationProvider {

    /**
     * @var PDO
     */
    private $db;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * @return array
     */
    public function getLocations() {
        $stmt = $this->db->query("SELECT stop_code, stop_name FROM stops WHERE stop_code != ''");
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['stop_code']] = $row['stop_name'];
        }

        return $results;
    }

    /**
     * @param $station
     * @return string[]
     */
    public function getRelevantStations($station) {
        if (strlen($station) === 3) {
            return [$station];
        }

        $stmt = $this->db->prepare("SELECT member_crs FROM group_station WHERE group_nlc = ?");
        $stmt->execute([$station]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
