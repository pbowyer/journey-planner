<?php

namespace JourneyPlanner\App\Console\Command;

use DateTime;
use JourneyPlanner\Lib\Journey\FixedLeg;
use JourneyPlanner\Lib\Journey\Journey;
use JourneyPlanner\Lib\Journey\TimetableLeg;
use JourneyPlanner\Lib\Planner\GroupStationPlanner;
use JourneyPlanner\Lib\Station\Repository\StationRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class PlanJourney extends ConsoleCommand {
    const NAME = 'plan-journey';
    const DESCRIPTION = 'Plan a journey';

    private $journeyPlanner;
    private $stationRepository;

    /**
     * @param GroupStationPlanner $journeyPlanner
     * @param StationRepository $stationRepository
     */
    public function __construct(GroupStationPlanner $journeyPlanner, StationRepository $stationRepository) {
        parent::__construct();

        $this->journeyPlanner = $journeyPlanner;
        $this->stationRepository = $stationRepository;
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
            $date = new DateTime($date. ' UTC');
        } else {
            $date = new DateTime();
        }

        $this->planMutlipleJourneys($output, $input->getArgument('origin'), $input->getArgument('destination'), $date);

        return 0;
    }

    private function planMutlipleJourneys(OutputInterface $out, string $origin, string $destination, DateTime $date) {
        $this->outputHeading($out, "Journey Planner");

        $locations = $this->outputTask($out, "Loading locations", function () {
            return $this->stationRepository->getLocations();
        });

        $results = $this->outputTask($out, "Plan journeys", function () use ($date, $origin, $destination) {
            return $this->journeyPlanner->getJourneys($origin, $destination, $date);
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

        $out->writeln("Duration " . $journey->getDuration());

        foreach ($journey->getLegs() as $leg) {
            if ($leg instanceof TimetableLeg) {
                $origin = sprintf('%-30s', $locations[$leg->getOrigin()]);
                $destination = sprintf('%30s', $locations[$leg->getDestination()]);
                $out->writeln(
                    gmdate('H:i', $leg->getDepartureTime()).' '.$origin.' '.
                    sprintf('%-6s', $leg->getService()).' '.
                    $destination.' '.gmdate('H:i', $leg->getArrivalTime())
                );
            }
            else if ($leg instanceof FixedLeg) {
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
