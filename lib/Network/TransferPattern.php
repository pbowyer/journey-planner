<?php

namespace JourneyPlanner\Lib\Network;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPattern {

    /**
     * @var Leg[]
     */
    private $legs;

    /**
     * @param Leg[] $legs
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
    public function getHash() {
        $hash = "";

        foreach ($this->legs as $leg) {
            $hash .= $leg->getOrigin().$leg->getDestination();
        }

        return $hash;
    }

}
