<?php

namespace JourneyPlanner\Lib\Planner\Filter;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
interface JourneyFilter {

    public function filter(array $results): array;

}