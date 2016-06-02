<?php

namespace JourneyPlanner\Lib;

use InvalidArgumentException;

/**
 * @author Linus Norton <linus.norton@assertis.co.uk>
 */
class FileLoader {

    /**
     * @var String
     */
    private $root;

    const TIMETABLE_FILE = "test-sorted2.csv";
    const NON_TIMETABLE_FILE = "non-timetable.csv";
    const INTERCHANGE_FILE = "interchange.csv";
    const LOCATION_FILE = "locations.csv";

    /**
     * @param $root root path to the assets folder
     */
    public function __construct($root) {
        $this->root = $root;
    }

    /**
     * @return array of NonTimetableConnection
     */
    public function getTimetableConnections() {
        $handle = fopen($this->root . self::TIMETABLE_FILE, "r");

        if (empty($handle)) {
            throw new InvalidArgumentException("Could not open " . $this->root . self::TIMETABLE_FILE);
        }
        
        $timetable = [];

        while ((list($departureTime, $arrivalTime, $origin, $destination, $service) = fgetcsv($handle, 50, ",")) !== false) {
            $timetable[] = new TimetableConnection($origin, $destination, $departureTime, $arrivalTime, $service);
        }

        return $timetable;
    }

    /**
     * @return array of NonTimetableConnection
     */
    public function getNonTimetableConnections() {
        $handle = fopen($this->root . self::NON_TIMETABLE_FILE, "r");

        if (empty($handle)) {
            throw new InvalidArgumentException("Could not open " . $this->root . self::NON_TIMETABLE_FILE);
        }

        $connections = [];

        while ((list($origin, $destination, $duration) = fgetcsv($handle, 50, ",")) !== false) {
            $connections[$origin][] = new NonTimetableConnection($origin, $destination, $duration);
        }

        return $connections;
    }

    /**
     * @return array
     */
    public function getInterchangeTimes() {
        $handle = fopen($this->root . self::INTERCHANGE_FILE, "r");

        if (empty($handle)) {
            throw new InvalidArgumentException("Could not open " . $this->root . self::INTERCHANGE_FILE);
        }

        $interchangeTimes = [];

        while ((list($station, $duration) = fgetcsv($handle, 20, ",")) !== false) {
            $interchangeTimes[$station] = $duration * 60;
        }

        return $interchangeTimes;
    }

    /**
     * @param string $filename
     * @return array
     */
    public function getLocations() {
        $handle = fopen($this->root . self::LOCATION_FILE, "r");

        if (empty($handle)) {
            throw new InvalidArgumentException("Could not open " . $this->root . self::LOCATION_FILE);
        }
        $locations = [];

        while ((list($nlc, $name) = fgetcsv($handle, 200, ",")) !== false) {
            $locations[$nlc] = $name;
        }

        return $locations;
    }
}
