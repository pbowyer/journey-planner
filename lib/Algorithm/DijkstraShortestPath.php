<?php

namespace JourneyPlanner\Lib\Algorithm;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class DijkstraShortestPath implements MinimumSpanningTreeGenerator {

    /**
     * @var array
     */
    private $graph;

    /**
     * @var array
     */
    private $nodeConnections;

    /**
     * @var array
     */
    private $nodes;

    /**
     * @param array $graph
     */
    public function __construct(array $graph) {
        $this->graph = $graph;
        $nodes = [];

        foreach ($graph as $edge) {
            if (!isset($this->nodeConnections[$edge->getOrigin()])) {
                $this->nodeConnections[$edge->getOrigin()] = [];
            }

            $this->nodeConnections[$edge->getOrigin()][] = $edge;
            $nodes[] = $edge->getOrigin();
            $nodes[] = $edge->getDestination();
        }
        $this->nodes = array_unique($nodes);
    }

    public function getNodes() {
        return $this->nodes;
    }

    /**
     * This method will use a variant of Dijkstra's shortest path algorithm
     * to create a tree of the fastest time to each node in the graph starting
     * at the given node
     *
     * @param string $origin
     * @return array
     */
    public function getShortestPathTree($origin) {
        // HashMap of shortest distance from origin to each node
        $nodeDistance = [];
        // queue of reachable unchecked edges
        $queue = [];

        // prefill the node distance to a large int and add a
        // connection from each node to itself to the queue
        foreach ($this->nodes as $node) {
            $nodeDistance[$node] = PHP_INT_MAX;
            $queue[$node] = $node;
        }

        $nodeDistance[$origin] = 0;

        // while we have un-checked edges in the queue
        while (!empty($queue)) {
            // sort the nodes to find the shortest edges
            asort($nodeDistance);

            // if this edge is in the queue, pop it off for inspect
            foreach ($nodeDistance as $name => $distance) {
                if (isset($queue[$name])) {
                    $current = $queue[$name];
                    unset($queue[$name]);
                    break;
                }
            }

            // check the connections of the current node
            foreach ($this->getEdges($current) as $connection) {
                // calculate the total time to this connections destination
                $altDistance = $nodeDistance[$current] + $connection->getDuration();

                // if this connections time to the destination node is better than the current best
                if ($altDistance < $nodeDistance[$connection->getDestination()]) {
                    // replace the best time
                    $nodeDistance[$connection->getDestination()] = $altDistance;
                }
            }
        }

        return $nodeDistance;

    }

    private function getEdges($node) {
        return isset($this->nodeConnections[$node]) ? $this->nodeConnections[$node] : [];
    }

}
