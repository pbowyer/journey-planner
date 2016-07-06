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
     * @param string $origin
     * @param string $destination
     * @return string
     */
    public function getHash($origin, $destination) {
        $hash = $origin.$destination;

        foreach ($this->getTimetableLegs() as $leg) {
            $hash .= $leg->getOrigin().$leg->getDestination();
        }

        return $hash;
    }

    /**
     * @return Leg[]
     */
    public function getTimetableLegs() {
        return array_filter($this->legs, function(Leg $leg) { return !$leg->isTransfer(); });
    }

}
