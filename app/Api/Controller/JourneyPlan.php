<?php

namespace JourneyPlanner\App\Api\Controller;

use JourneyPlanner\Lib\Algorithm\Filter\SlowJourneyFilter;
use JourneyPlanner\Lib\Algorithm\MultiSchedulePlanner;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Storage\DatabaseLoader;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JourneyPlanner\App\Api\View\JourneyView;

class JourneyPlan {
    
    /**
     * @param Application $app
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Application $app, Request $request) {
        /** @var DatabaseLoader $loader */
        $loader = $app['loader.database'];
        $targetTime = strtotime($request->get('date'));

        $origins = $loader->getRelevantStations($request->get('origin'));
        $destinations = $loader->getRelevantStations($request->get('destination'));
        $planner = new MultiSchedulePlanner($loader, [new SlowJourneyFilter()]);
        $journeys = $planner->getJourneys($origins, $destinations, $targetTime);
        
        $views = array_map(function(Journey $journey) { return new JourneyView($journey); }, $journeys);

        return new JsonResponse($views);
    }
}
