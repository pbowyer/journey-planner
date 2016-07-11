<?php

namespace JourneyPlanner\Lib\Network;
use Exception;

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
     * @throws Exception
     */
    public function __construct(array $legs) {
        if (count($legs) === 0) {
            throw new Exception("A transfer pattern leg must contain at least one service.");
        }
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