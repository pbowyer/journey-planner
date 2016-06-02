<?php

require_once __DIR__ . "/../vendor/autoload.php";

use JourneyPlanner\App\Api\Api;
use JourneyPlanner\App\Container;

$app = new Api(new Container());
$app->run();
