<?php


namespace JourneyPlanner\Lib\Journey;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class FixedLeg extends Leg {

    private $duration;
    private $startTime;
    private $endTime;

    /**
     * @param string $origin
     * @param string $destination
     * @param string $mode
     * @param int $duration
     * @param int $startTime
     * @param int $endTime
     */
    public function __construct(string $origin, string $destination, string $mode, int $duration, int $startTime, int $endTime) {
        parent::__construct($origin, $destination, $mode);

        $this->duration = $duration;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * @return int
     */
    public function getDuration(): int {
        return $this->duration;
    }

    /**
     * @param int $time
     * @return bool
     */
    public function isAvailableAt(int $time): bool {
        return $this->startTime <= $time && $this->endTime >= $time;
    }

}