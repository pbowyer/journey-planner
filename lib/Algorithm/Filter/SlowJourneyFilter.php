<?php

namespace JourneyPlanner\Lib\Algorithm\Filter;

use JourneyPlanner\Lib\Network\Journey;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class SlowJourneyFilter implements JourneyFilter {

    /**
     * For every journey departing at the same time, only take the one that arrives first
     *
     * For every journey arriving at the same time, only take the one that leaves last
     *
     * @param Journey[] $results
     * @return Journey[]
     */
    public function filter(array $results) {
        $earliestArrivals = [];
        $latestDepartures = [];

        foreach ($results as $j) {
            if (isset($earliestArrivals[$j->getDepartureTime()])) {
                $earliestArrivals[$j->getDepartureTime()] = min($earliestArrivals[$j->getDepartureTime()], $j->getArrivalTime());
            }
            else {
                $earliestArrivals[$j->getDepartureTime()] = $j->getArrivalTime();
            }
            if (isset($latestDepartures[$j->getArrivalTime()])) {
                $latestDepartures[$j->getArrivalTime()] = max($latestDepartures[$j->getArrivalTime()], $j->getDepartureTime());
            }
            else {
                $latestDepartures[$j->getArrivalTime()] = $j->getDepartureTime();
            }
        }

        $journeys = [];

        foreach ($results as $j) {
            $hash = $j->getDepartureTime().$j->getArrivalTime();

            if (!isset($journeys[$hash]) &&
                $earliestArrivals[$j->getDepartureTime()] === $j->getArrivalTime() &&
                $latestDepartures[$j->getArrivalTime()] === $j->getDepartureTime()) {
                $journeys[$hash] = $j;
            }
        }

        return array_values($journeys);
    }

}