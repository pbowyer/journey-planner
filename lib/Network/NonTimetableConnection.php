<?php

namespace JourneyPlanner\Lib\Network;

class NonTimetableConnection extends Connection {

    private $duration;
    private $startTime;
    private $endTime;

    /**
     * @param string $origin
     * @param string $destination
     * @param int $duration
     * @param string $mode
     * @param int $startTime
     * @param int $endTime
     */
    public function __construct($origin, $destination, $duration, $mode = parent::WALK, $startTime = 0, $endTime = PHP_INT_MAX) {
        parent::__construct($origin, $destination, $mode);

        $this->duration = $duration;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * @return int
     */
    public function getDuration() {
        return $this->duration;
    }

    /**
     * A transfer always needs interchange.
     *
     * @param  Connection $connection
     * @return boolean
     */
    public function requiresInterchangeWith(Connection $connection) {
        return true;
    }

    /**
     * @param $time
     * @return bool
     */
    public function isAvailableAt($time) {
        return $this->startTime <= $time && $this->endTime >= $time;
    }
}
