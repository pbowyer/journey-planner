<?php

namespace JourneyPlanner\Lib\Algorithm;

use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Network\Journey;

/**
 * This class overrides the fitness measure of the ConnectionScanner in order
 * to choose journeys involving the least number of changes
 *
 * @author Linus Norton <linusnorton@gmail.com>
 */
class MinimumChangesConnectionScanner extends ConnectionScanner {
    /**
     * @var array
     */
    private $changes;

    /**
     * @param string $origin
     * @param string $destination
     * @param string $departureTime
     * @return Journey|null
     */
    protected function getJourney($origin, $destination, $departureTime) {
        $this->changes = [$origin => 0];

        return parent::getJourney($origin, $destination, $departureTime);
    }

    /**
     * @param string $origin
     * @param int $departureTime
     * @return Journey[]
     */
    public function getEarliestArrivalTree($origin, $departureTime) {
        $this->changes = [$origin => 0];

        return parent::getEarliestArrivalTree($origin, $departureTime);
    }

    /**
     * Try to use the connection that involves the least number of changes.
     *
     * @param Connection $connection
     * @return bool
     * @throws PlanningException
     */
    protected function thisConnectionIsBetter(Connection $connection) {
        $firstVisit = !isset($this->arrivals[$connection->getDestination()]);
        $requiresChange = !isset($this->connections[$connection->getOrigin()]) || $connection->requiresInterchangeWith($this->connections[$connection->getOrigin()]);
        $numChanges = $this->changes[$connection->getOrigin()] + intval($requiresChange);
        
        if ($firstVisit ||
            $numChanges < $this->changes[$connection->getDestination()] ||
            $numChanges === $this->changes[$connection->getDestination()] && parent::thisConnectionIsBetter($connection)
        ) {
            $this->changes[$connection->getDestination()] = $numChanges;
            
            return true;
        }

        return false;
    }

}