<?php

namespace JourneyPlanner\App\Api\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JourneyPlan\App\Api\View\JourneyPlan as JourneyPlanView;
use JourneyPlan\Lib\ConnectionScanner;

class JourneyPlan {

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Application $app, Request $request) {
        $origin = $request->get('origin');
        $destination = $request->get('destination');
        $targetTime = strtotime($request->get('date'));

        $timetableConnections = $app['loader.database']->getUnprunedTimetableConnections($targetTime);
        $nonTimetableConnections = $app['loader.database']->getNonTimetableConnections();
        $interchangeTimes = $app['loader.database']->getInterchangeTimes();
        $locations = $app['loader.database']->getLocations();

        $scanner = new ConnectionScanner($timetableConnections, $nonTimetableConnections, $interchangeTimes);
        $route = $scanner->getRoute($origin, $destination, strtotime('1970-01-01 '.date('H:i:s', $targetTime)));
        $view = new JourneyPlanView($route);

        return new JsonResponse($view);
    }
}
