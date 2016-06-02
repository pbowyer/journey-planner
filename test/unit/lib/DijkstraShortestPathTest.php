<?php

use JourneyPlanner\Lib\DijkstraShortestPath;
use JourneyPlanner\Lib\NonTimetableConnection;
use JourneyPlanner\Lib\Route;

class DijkstraShortestPathTest extends PHPUnit_Framework_TestCase {

    public function testBasicPath() {
        $graph = [
            new NonTimetableConnection("A", "B", 10),
            new NonTimetableConnection("B", "C", 10),
            new NonTimetableConnection("B", "C", 5),
            new NonTimetableConnection("C", "D", 11),
        ];

        $pathFinder = new DijkstraShortestPath($graph);
        $tree = $pathFinder->getShortestPathTree("A");

        $expected = [
            "A" => 0,
            "B" => 10,
            "C" => 15,
            "D" => 26
        ];

        $this->assertEquals($expected, $tree);
    }
}