<?php

namespace JourneyPlanner\App\Api;

use Silex\Application;
use JourneyPlanner\App\Api\Controller\JourneyPlan;
use JourneyPlanner\App\Container;

class Api extends Application {

    /**
     * @param Container $container
     */
    public function __construct(Container $container) {
        parent::__construct();

        foreach ($container->keys() as $key) {
            $this[$key] = $container->raw($key);
        }

        $this["debug"] = true;
        $this->get('/journey-plan', JourneyPlan::class);
    }

}
