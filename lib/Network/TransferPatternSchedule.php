<?php

namespace JourneyPlanner\Lib\Network;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternSchedule {

    /**
     * @var TimetableConnection[]
     */
    private $legs;

    /**
     * @param TimetableConnection[] $legs
     */
    public function __construct(array $legs) {
        $this->legs = $legs;
    }

    /**
     * @return TimetableConnection[]
     */
    public function getLegs() {
        return $this->legs;
    }
        

}