<?php

namespace JourneyPlanner\Lib\Network;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPattern {

    /**
     * @var TimetableConnection[]
     */
    private $legs;

    /**
     * @param array $legs
     */
    public function __construct(array $legs) {
        $this->legs = $legs;
    }

    /**
     * @return Connection[]
     */
    public function getLegs() {
        return $this->legs;
    }

    /**
     * @param Connection[] $legs
     * @return string
     */
    public static function getHash(array $legs) {
        $hash = "";

        foreach ($legs as $leg) {
            $hash .= $leg->getOrigin().$leg->getDestination();
        }

        return $hash;
    }

}
