<?php

namespace JourneyPlanner\App\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use JourneyPlanner\Lib\DatabaseLoader;
use JourneyPlanner\Lib\TimetableConnection;
use JourneyPlanner\Lib\ConnectionScanner;

class PlanJourney extends ConsoleCommand {
    const NAME = 'plan-journey';
    const DESCRIPTION = 'Plan a journey';

    /**
     * @var DatabaseLoader
     */
    private $loader;

    /**
     * @param DatabaseLoader $loader
     */
    public function __construct(DatabaseLoader $loader) {
        parent::__construct();
        $this->loader = $loader;
    }

    /**
     * Set up arguments
     */
    protected function configure() {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION)
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
            $date = strtotime($date);
        } else {
            $date = time();
        }

        $this->planJourney($output, $input->getArgument('origin'), $input->getArgument('destination'), $date);

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
            //return $this->loader->getTimetableConnections($targetTime, $origin);
            return $this->loader->getUnprunedTimetableConnections($targetTime);
        });

        $nonTimetableConnections = $this->outputTask($out, "Loading non timetable connections", function () {
            return $this->loader->getNonTimetableConnections();
        });

        $interchangeTimes = $this->outputTask($out, "Loading intechange", function () {
            return $this->loader->getInterchangeTimes();
        });

        $locations = $this->outputTask($out, "Loading locations", function () {
            return $this->loader->getLocations();
        });

        $scanner = new ConnectionScanner($timetableConnections, $nonTimetableConnections, $interchangeTimes);

        $route = $this->outputTask($out, "Plan journey", function () use ($scanner, $targetTime, $origin, $destination) {
            return $scanner->getRoute($origin, $destination, strtotime('1970-01-01 '.date('H:i:s', $targetTime)));
        });

        $this->displayRoute($out, $locations, $route);

        $out->writeLn("\nPeak memory usage: " . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . "Mb");
        $out->writeLn("Connections: ".count($timetableConnections));
    }

    /**
     * @param  OutputInterface $out
     * @param  array           $locations
     * @param  Connection[]    $route
     */
    private function displayRoute(OutputInterface $out, array $locations, $route) {
        $this->outputHeading($out, "Route");

        foreach ($route as $connection) {
            $origin = sprintf('%-30s', $locations[$connection->getOrigin()]);
            $destination = sprintf('%30s', $locations[$connection->getDestination()]);

            if ($connection instanceof TimetableConnection) {
                $out->writeLn(
                    date('H:i', $connection->getDepartureTime()).' '.$origin.' '.
                    $connection->getService().' '.
                    $destination.' '.date('H:i', $connection->getArrivalTime())
                );
            }
            else {
                $out->writeLn(
                    $connection->getMode().
                    " from ".$origin.
                    " to ".$destination.
                    " (".($connection->getDuration())." minutes)"
                );
            }
        }
    }
}
