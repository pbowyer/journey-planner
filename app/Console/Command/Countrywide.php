<?php

namespace JourneyPlanner\App\Console\Command;

use JourneyPlanner\Lib\Algorithm\Filter\SlowJourneyFilter;
use JourneyPlanner\Lib\Algorithm\MinimumChangesConnectionScanner;
use JourneyPlanner\Lib\Algorithm\MultiSchedulePlanner;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Network\Leg;
use JourneyPlanner\Lib\Storage\Schedule\ScheduleProvider;
use JourneyPlanner\Lib\Storage\Station\StationProvider;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use JourneyPlanner\Lib\Storage\Station\DatabaseStationProvider;
use JourneyPlanner\Lib\Algorithm\ConnectionScanner;

class Countrywide extends ConsoleCommand {
    const NAME = 'countrywide';
    const DESCRIPTION = 'Find journeys from all stations to the given destination';

    /**
     * @var StationProvider
     */
    private $stationProvider;

    /**
     * @var ScheduleProvider
     */
    private $scheduleProvider;

    /**
     * @var \PDO
     */
    private $db;
    /** @var  \PDOStatement */
    private $stmt;

    /**
     * @param StationProvider $stationProvider
     * @param ScheduleProvider $scheduleProvider
     * @param \PDO $db
     */
    public function __construct(StationProvider $stationProvider, ScheduleProvider $scheduleProvider, \PDO $db) {
        parent::__construct();

        $this->stationProvider = $stationProvider;
        $this->scheduleProvider = $scheduleProvider;
        $this->db = $db;
    }

    /**
     * Set up arguments
     */
    protected function configure() {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION)
            ->addArgument(
                'destination',
                InputArgument::REQUIRED,
                'Destination station CRS code e.g. CBW, HIB, LBG'
            )
            ->addArgument(
               'date',
               InputArgument::OPTIONAL,
               'Journey date e.g. 2016-06-20T07:40:00'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $date = $input->getArgument('date');
        if ($date) {
            $date = strtotime($date. ' UTC');
        } else {
            $date = time();
        }

        $this->stmt = $this->db->prepare("INSERT INTO peter SET origin = :origin, destination = :destination, depart = :depart, arrive = :arrive, duration = :duration, changes = :changes, legs = :legs");

        $dbquery = $this->db->query("SELECT stop_code FROM stops WHERE stop_code != ''");
        while ($origin = $dbquery->fetchColumn()) {
            $this->planMutlipleJourneys($output, $origin, $input->getArgument('destination'), $date);
        }

        return 0;
    }

    /**
     * @param  OutputInterface $out
     * @param  string          $origin
     * @param  string          $destination
     * @param  int             $targetTime
     */
    private function planJourney(OutputInterface $out, $origin, $destination, $targetTime) {
        $this->outputHeading($out, "Journey Planner");

        $timetableConnections = $this->outputTask($out, "Loading timetable", function () use ($targetTime, $origin) {
            return $this->scheduleProvider->getTimetableConnections($targetTime);
        });

        $nonTimetableConnections = $this->outputTask($out, "Loading non timetable connections", function () use ($targetTime) {
            return $this->scheduleProvider->getNonTimetableConnections($targetTime);
        });

        $interchangeTimes = $this->outputTask($out, "Loading interchange", function () {
            return $this->scheduleProvider->getInterchangeTimes();
        });

        $locations = $this->outputTask($out, "Loading locations", function () {
            return $this->stationProvider->getLocations();
        });

        $scanner = new ConnectionScanner($timetableConnections, $nonTimetableConnections, $interchangeTimes);

        $route = $this->outputTask($out, "Plan journey", function () use ($scanner, $targetTime, $origin, $destination) {
            return $scanner->getJourneys($origin, $destination, strtotime('1970-01-01 '.gmdate('H:i:s', $targetTime).' UTC'));
        });

        if (count($route) === 0) {
            $out->writeln("No route found.");
        }
        else {
            $this->displayRoute($out, $locations, $route[0]);
        }

        $this->outputMemoryUsage($out);
        $out->writeln("Connections: ".count($timetableConnections));
    }
    
    private function planMutlipleJourneys(OutputInterface $out, $origin, $destination, $targetTime) {
        $this->outputHeading($out, "Journey Planner");


        $locations = $this->outputTask($out, "Loading locations", function () {
            return $this->stationProvider->getLocations();
        });

        $results = $this->outputTask($out, "Plan journeys", function () use ($targetTime, $origin, $destination) {
            $origins = $this->stationProvider->getRelevantStations($origin);
            $destinations = $this->stationProvider->getRelevantStations($destination);
            $scanner = new MultiSchedulePlanner($this->scheduleProvider, [new SlowJourneyFilter()]);

            return $scanner->getJourneys($origins, $destinations, $targetTime);
        });

        foreach ($results as $journey) {
            $this->displayRoute($out, $locations, $journey);
        }

        $this->outputMemoryUsage($out);
    }

    /**
     * @param  OutputInterface $out
     * @param  array           $locations
     * @param  Journey         $journey
     */
    private function displayRoute(OutputInterface $out, array $locations, Journey $journey) {
		// @TODO Store the given CLI destination (e.g. 1072) so we can use the database table for multiple searches
        $data = [
            'origin' => $journey->getOrigin(),
            'destination' => $journey->getDestination(),
            'depart' => $this->getTime($journey->getDepartureTime()),
            'arrive' => $this->getTime($journey->getArrivalTime()),
            'duration' => $journey->getDuration(),
            'changes' => count($journey->getLegs()) - 1,
            'legs' => json_encode(array_map([$this, 'getLeg'], $journey->getLegs()), JSON_PRETTY_PRINT),
        ];
        #dump($data);
        $out->writeln("{$data['origin']}\t{$data['destination']}\t" . $this->getTime($data['duration']));
        $this->stmt->execute($data);
    }

    /**
     * @param Leg $leg
     * @return stdClass
     */
    private function getLeg(Leg $leg) {
        $json = new stdClass;
        $json->mode = strtolower($leg->getMode());

        if ($leg->isTransfer()) {
            $json->origin = $leg->getOrigin();
            $json->destination = $leg->getDestination();
            $json->duration = $this->getTime($leg->getDuration());

            return $json;
        }

        $json->service = $leg->getService();
        $json->operator = $leg->getOperator();
        $json->callingPoints = [
            $this->getCallingPoint($leg->getOrigin(), $leg->getDepartureTime())
        ];

        foreach ($leg->getConnections() as $c) {
            $json->callingPoints[] = $this->getCallingPoint($c->getDestination(), $c->getArrivalTime());
        }

        return $json;
    }

    private function getCallingPoint($station, $time) {
        $point = new stdClass;
        $point->station = $station;
        $point->time = $this->getTime($time);

        return $point;
    }

    /**
     * @param  int $time
     * @return string
     */
    private function getTime($time) {
        return gmdate("H:i", $time % 86400);
    }
}
