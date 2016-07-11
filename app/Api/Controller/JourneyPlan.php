<?php

namespace JourneyPlanner\App\Api\Controller;

use JourneyPlanner\Lib\Algorithm\Filter\SlowJourneyFilter;
use JourneyPlanner\Lib\Algorithm\MultiSchedulePlanner;
use JourneyPlanner\Lib\Network\Journey;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JourneyPlanner\App\Api\View\JourneyView;

class JourneyPlan {
    private $loader;
    private $targetTime;
    private $nonTimetableConnections;
    private $interchangeTimes;

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Application $app, Request $request) {
        $origin = $request->get('origin');
        $destination = $request->get('destination');

        $this->loader = $app['loader.database'];
        $this->targetTime = strtotime($request->get('date'));
        $this->nonTimetableConnections = $this->loader->getNonTimetableConnections($this->targetTime);
        $this->interchangeTimes = $this->loader->getInterchangeTimes();

        $results = [];

        foreach ($this->loader->getRelevantStations($origin) as $o) {
            foreach ($this->loader->getRelevantStations($destination) as $d) {
                $results = array_merge($results, $this->getResults($o, $d));
            }
        }

        $filter = new SlowJourneyFilter();
        $journeys = $filter->filter($results);

        $views = array_map(function(Journey $journey) { return new JourneyView($journey); }, $journeys);

        return new JsonResponse($views);
    }

    private function getResults($origin, $destination) {
        $schedules = $this->loader->getScheduleFromTransferPatternTimetable($origin, $destination, $this->targetTime);
        $scanner = new MultiSchedulePlanner($schedules, $this->nonTimetableConnections, $this->interchangeTimes);

        return $scanner->getJourneys($origin, $destination, strtotime('1970-01-01 '.date('H:i:s', $this->targetTime)));
    }
}
