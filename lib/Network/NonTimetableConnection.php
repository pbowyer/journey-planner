<?php

namespace JourneyPlanner\Lib\Network;

class NonTimetableConnection extends Connection {

    private $duration;

    /**
     * @param string $origin
     * @param string $destination
     * @param int $duration
     * @param string $mode
     */
    public function __construct($origin, $destination, $duration, $mode = parent::WALK) {
        parent::__construct($origin, $destination, $mode);

        $this->duration = $duration;
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
     * @param  TimetableConnection $connection
     * @return boolean
     */
    public function requiresInterchangeWith(TimetableConnection $connection) {
        return true;
    }
}
