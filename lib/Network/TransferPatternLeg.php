<?php

namespace JourneyPlanner\Lib\Network;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPatternLeg {
    /**
     * @var Leg[]
     */
    private $legs;

    /**
     * @param array $legs
     */
    public function __construct(array $legs) {
        $this->legs = $legs;
    }

    /**
     * @return Leg[]
     */
    public function getLegs() {
        return $this->legs;
    }

    /**
     * @return string
     */
    public function getOrigin() {
        return $this->legs[0]->getOrigin();
    }
    
}