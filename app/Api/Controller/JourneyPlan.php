<?php

namespace JourneyPlanner\App\Api\Controller;

use DateTime;
use JourneyPlanner\Lib\Journey\Journey;
use JourneyPlanner\Lib\Planner\GroupStationJourneyPlanner;
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
        /** @var GroupStationJourneyPlanner $planner */
        $planner = $app['planner.group_station'];

        $targetTime = new DateTime($request->get('date')." UTC");
        $journeys = $planner->getJourneys($request->get('origin'), $request->get('destination'), $targetTime);
        
        $views = array_map(function(Journey $journey) { return new JourneyView($journey); }, $journeys);

        return new JsonResponse($views);
    }
}
