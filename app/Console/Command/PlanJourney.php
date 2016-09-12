<?php

namespace JourneyPlanner\App\Console\Command;

use JourneyPlanner\Lib\Algorithm\Filter\SlowJourneyFilter;
use JourneyPlanner\Lib\Algorithm\MinimumChangesConnectionScanner;
use JourneyPlanner\Lib\Algorithm\MultiSchedulePlanner;
use JourneyPlanner\Lib\Network\Journey;
use JourneyPlanner\Lib\Storage\Schedule\ScheduleProvider;
use JourneyPlanner\Lib\Storage\Station\StationProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use JourneyPlanner\Lib\Storage\Station\DatabaseStationProvider;
use JourneyPlanner\Lib\Algorithm\ConnectionScanner;

class PlanJourney extends ConsoleCommand {
    const NAME = 'plan-journey';
    const DESCRIPTION = 'Plan a journey';

    /**
     * @var StationProvider
     */
    private $stationProvider;

    /**
     * @var ScheduleProvider
     */
    private $scheduleProvider;

    /**
     * @param StationProvider $stationProvider
     * @param ScheduleProvider $scheduleProvider
     */
    public function __construct(StationProvider $stationProvider, ScheduleProvider $scheduleProvider) {
        parent::__construct();

        $this->stationProvider = $stationProvider;
        $this->scheduleProvider = $scheduleProvider;
    }

    /**
     * Set up arguments
     */
    protected function configure() {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION)
            ->addOption("csa", "--csa", InputOption::VALUE_NONE, false)
            ->addArgument(
                'origin',
                InputArgument::REQUIRED,
                'Origin station CRS code e.g. CBW, HIB, LBG'
            )
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

        if ($input->getOption('csa')) {
            $this->planJourney($output, $input->getArgument('origin'), $input->getArgument('destination'), $date);
        }
        else {
            $this->planMutlipleJourneys($output, $input->getArgument('origin'), $input->getArgument('destination'), $date);
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
        $this->outputHeading($out, "Route");
        $out->writeln("Duration ".$journey->getDuration());
        foreach ($journey->getLegs() as $leg) {

            if (!$leg->isTransfer()) {
                foreach ($leg->getConnections() as $connection) {
                    $origin = sprintf('%-30s', $locations[$connection->getOrigin()]);
                    $destination = sprintf('%30s', $locations[$connection->getDestination()]);
                    $out->writeln(
                        gmdate('H:i', $connection->getDepartureTime()).' '.$origin.' '.
                        sprintf('%-6s', $connection->getService()).' '.
                        $destination.' '.gmdate('H:i', $connection->getArrivalTime())
                    );
                }
            }
            else {
                $origin = sprintf('%-30s', $locations[$leg->getOrigin()]);
                $destination = sprintf('%30s', $locations[$leg->getDestination()]);
                $out->writeln(
                    sprintf('%-6s', $leg->getMode()).
                    $origin.
                    '   to'.
                    $destination.
                    " (".($leg->getDuration() / 60)."mins)"
                );
            }
        }
    }
}
