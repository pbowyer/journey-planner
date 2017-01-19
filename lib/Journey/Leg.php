<?php

namespace JourneyPlanner\Lib\Journey;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
abstract class Leg {
    const TRAIN = "train", BUS = "bus", REPLACEMENT_BUS = "replacement bus", WALK = "walk", TUBE = "tube";

    protected $origin;
    protected $destination;
    protected $mode;

    /**
     * @param $origin
     * @param $destination
     * @param $mode
     */
    public function __construct(string $origin, string $destination, string $mode) {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getOrigin(): string {
        return $this->origin;
    }

    /**
     * @return string
     */
    public function getDestination(): string {
        return $this->destination;
    }

    /**
     * @return string
     */
    public function getMode(): string {
        return $this->mode;
    }

}