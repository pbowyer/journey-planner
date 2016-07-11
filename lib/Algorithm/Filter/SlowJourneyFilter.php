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
            $arrivesFirst = $earliestArrivals[$j->getDepartureTime()] === $j->getArrivalTime();
            $departsLast = $latestDepartures[$j->getArrivalTime()] === $j->getDepartureTime();

            // if it's the fastest service and has the least changes
            if ($arrivesFirst && $departsLast && (!isset($journeys[$hash]) || count($j->getLegs()) < count($journeys[$hash]->getLegs()))) {
                $journeys[$hash] = $j;
            }
        }

        ksort($journeys);
        
        return array_values($journeys);
    }

}