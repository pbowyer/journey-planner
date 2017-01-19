<?php

namespace JourneyPlanner\Lib\Planner;

use DateTime;
use JourneyPlanner\Lib\Journey\FixedLeg;
use JourneyPlanner\Lib\Journey\Journey;
use JourneyPlanner\Lib\Journey\Repository\FixedLegRepository;
use JourneyPlanner\Lib\Journey\Repository\InterchangeRepository;
use JourneyPlanner\Lib\Planner\Filter\JourneyFilter;
use JourneyPlanner\Lib\Station\Repository\StationRepository;
use JourneyPlanner\Lib\TransferPattern\Repository\TransferPatternRepository;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class GroupStationJourneyPlanner {

    private $patternRepository;
    private $stationRepository;
    private $fixedLegRepository;
    private $filters;
    private $interchangeRepository;

    /**
     * @param TransferPatternRepository $scheduleProvider
     * @param StationRepository $stationRepository
     * @param FixedLegRepository $fixedLegRepository
     * @param InterchangeRepository $interchangeRepository
     * @param JourneyFilter[] $filters
     */
    public function __construct(TransferPatternRepository $scheduleProvider,
                                StationRepository $stationRepository,
                                FixedLegRepository $fixedLegRepository,
                                InterchangeRepository $interchangeRepository,
                                array $filters) {

        $this->patternRepository = $scheduleProvider;
        $this->filters = $filters;
        $this->stationRepository = $stationRepository;
        $this->fixedLegRepository = $fixedLegRepository;
        $this->interchangeRepository = $interchangeRepository;
    }

    /**
     * @param string $origin
     * @param string $destination
     * @param DateTime $dateTime
     * @return Journey[]
     */
    public function getJourneys(string $origin, string $destination, DateTime $dateTime): array {
        $interchange = $this->interchangeRepository->getInterchange();
        $nonTimetable = $this->fixedLegRepository->getFixedLegs($dateTime);
        $departureTime = intval($dateTime->format("H")) * 3600;
        $results = [];

        foreach ($this->stationRepository->getRelevantStations($origin) as $o) {
            foreach ($this->stationRepository->getRelevantStations($destination) as $d) {
                foreach ($this->patternRepository->getTransferPatterns($o, $d, $dateTime) as $pattern) {
                    $scanner = new TransferPatternPlanner($pattern, $nonTimetable, $interchange);

                    $results = array_merge($results, $scanner->getJourneys($o, $d, $departureTime));
                }
            }
        }

        foreach ($this->filters as $filter) {
            $results = $filter->filter($results);
        }

        return $results;
    }
}