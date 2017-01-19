<?php

namespace JourneyPlanner\Lib\TransferPattern;

use JourneyPlanner\Lib\Journey\TimetableLeg;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class PatternSegment {

    private $legs;

    /**
     * @param TimetableLeg[] $legs
     */
    public function __construct(array $legs) {
        $this->legs = $legs;
    }

    /**
     * @return TimetableLeg[]
     */
    public function getLegs(): array {
        return $this->legs;
    }

    /**
     * @return string
     */
    public function getOrigin(): string {
        return $this->legs[0]->getOrigin();
    }

}