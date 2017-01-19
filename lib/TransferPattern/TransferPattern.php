<?php

namespace JourneyPlanner\Lib\TransferPattern;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TransferPattern {

    private $segments;

    /**
     * @param PatternSegment[] $segments
     */
    public function __construct(array $segments) {
        $this->segments = $segments;
    }

    /**
     * @return PatternSegment[]
     */
    public function getSegments(): array {
        return $this->segments;
    }

}