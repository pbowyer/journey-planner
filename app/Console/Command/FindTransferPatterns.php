<?php

namespace JourneyPlanner\App\Console\Command;

use JourneyPlanner\Lib\Storage\DatabaseLoader;
use JourneyPlanner\Lib\Storage\TransferPatternPersistence;
use Spork\Batch\Strategy\AbstractStrategy;
use Spork\ProcessManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindTransferPatterns extends ConsoleCommand {
    const NAME = 'transfer-patterns';
    const DESCRIPTION = 'Find and store transfer patterns for the entire network';
    const DAYS = [
        "next monday",
        "next friday",
        "next saturday",
        "next sunday",
        "next monday + 1 month",
        "next friday + 1 month",
        "next saturday + 1 month",
        "next sunday + 1 month",
    ];

    const HOURS = [
        "05:00",
        "07:00",
        "12:00",
        "17:00",
        "22:00",
    ];

    /**
     * @var DatabaseLoader
     */
    private $loader;

    /**
     * @var callable
     */
    private $dbFactory;

    /**
     * @var AbstractStrategy
     */
    private $forkStrategy;

    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * @param DatabaseLoader $loader
     * @param ProcessManager $processManager
     * @param AbstractStrategy $forkStrategy
     * @param callable $pdoFactory
     */
    public function __construct(DatabaseLoader $loader, ProcessManager $processManager, AbstractStrategy $forkStrategy, callable $pdoFactory) {
        parent::__construct();

        $this->loader = $loader;
        $this->processManager = $processManager;
        $this->forkStrategy = $forkStrategy;
        $this->dbFactory = $pdoFactory;
    }

    /**
     * Set up arguments
     */
    protected function configure() {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $out
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $out) {
        $this->outputHeading($out, "Transfer Patterns");

        $timetables = $this->outputTask($out, "Loading timetables", function() {
            return $this->getTimetables();
        });

        $interchange = $this->outputTask($out, "Loading interchange", function() {
            return $this->loader->getInterchangeTimes();
        });

        $stations = array_keys($this->loader->getLocations());
        $persistence = new TransferPatternPersistence($timetables, $interchange);

        $this->outputTask($out, "Clearing previous patterns", function() use ($persistence) {
            $persistence->clearPreviousPatterns(call_user_func($this->dbFactory));
        });

        $this->outputTask($out, "Calculating transfer patterns", function() use ($stations, $persistence) {
            $callable = function($station) use ($persistence) {
                $persistence->calculateTransferPatternsForStation(call_user_func($this->dbFactory), $station);
            };

            $this->processManager->process($stations, $callable, $this->forkStrategy);
            $this->processManager->wait();
        });

        $this->outputMemoryUsage($out);

        return 0;
    }

    /**
     * @return Connection[]
     */
    private function getTimetables() {
        $timetables = [];

        foreach (self::DAYS as $day) {
            $nonTimetableConnections = $this->loader->getNonTimetableConnections(strtotime($day));

            foreach (self::HOURS as $hour) {
                $timetables["{$day} at {$hour}"] = [
                    "timetable" => $this->loader->getUnprunedTimetableConnections(strtotime("{$day} {$hour}")),
                    "non_timetable" => $nonTimetableConnections
                ];
            }
        }

        return $timetables;
    }
}
