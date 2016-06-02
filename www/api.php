<?php

require "../vendor/autoload.php";

use LJN\FileLoader;
use LJN\ConnectionScanner;

$loader = new FileLoader();
$timetable = $loader->getTimetableConnections("../assets/timetable.csv");
$nonTimetable = $loader->getNonTimetableConnections("../assets/non-timetable.csv");
$interchangeTimes = $loader->getInterchangeTimes("../assets/interchange.csv");
$scanner = new ConnectionScanner($timetable, $nonTimetable, $interchangeTimes);

$departureTime = strtotime(isset($_REQUEST['departureTime']) ? $_REQUEST['departureTime'] : 'now');
$route = $scanner->getRoute($_REQUEST['origin'], $_REQUEST['destination'], $departureTime);

header("Content-type: application/json");
echo json_encode($route);
