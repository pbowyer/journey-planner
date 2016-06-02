<?php

namespace JourneyPlanner\Lib\Network;

/**
 * @author Linus Norton <linus.norton@assertis.co.uk>
 */
class TransferPattern {

    /**
     * @var array
     */
    private $legs;

    /**
     * @param array $legs
     */
    public function __construct(array $legs) {
        $this->legs = $legs;
    }

    /**
     * @return array
     */
    public function getLegs() {
        return $this->legs;
    }

}
