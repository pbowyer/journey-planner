<?php

namespace JourneyPlanner\App\Console\Command;

use JourneyPlanner\Lib\Network\Connection;
use JourneyPlanner\Lib\Storage\DatabaseLoader;
use JourneyPlanner\Lib\Storage\TransferPatternPersistence;
use PDO;
use Spork\Batch\Strategy\AbstractStrategy;
use Spork\ProcessManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindTransferPatterns extends ConsoleCommand {
    const NAME = 'transfer-patterns';
    const DESCRIPTION = 'Find and store transfer patterns for the entire network';

    const HOURS = [
        "01:00",
        "05:00",
        "06:00",
        "07:00",
        "08:00",
        "10:00",
        "12:00",
        "13:00",
        "16:00",
        "17:00",
        "18:00",
        "20:00",
        "21:00",
        "23:00",
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
        $scanDate = $this->getNextScanDate();

        $nonTimetableConnections = $this->outputTask($out, "Loading non-timetable connections", function() use ($scanDate) {
            return $this->loader->getNonTimetableConnections(strtotime($scanDate));
        });
            
        $timetables = $this->outputTask($out, "Loading timetables", function() use ($scanDate) {
            return $this->getTimetables($scanDate);
        });

        $interchange = $this->outputTask($out, "Loading interchange", function() {
            return $this->loader->getInterchangeTimes();
        });

        $stations = array_keys($this->loader->getLocations());
        $persistence = new TransferPatternPersistence($timetables, $nonTimetableConnections, $interchange);
        
        $this->outputTask($out, "Calculating transfer patterns", function() use ($stations, $persistence) {
            $callable = function($station) use ($persistence) {
                $persistence->calculateTransferPatternsForStation(call_user_func($this->dbFactory), $station);
            };

            $this->processManager->process($stations, $callable, $this->forkStrategy);
            $this->processManager->wait();
        });

        $this->setLastScanDate($scanDate);
        $this->outputMemoryUsage($out);

        return 0;
    }

    /**
     * @return string
     */
    public function getNextScanDate() {
        $db = call_user_func($this->dbFactory);

        return $db->query("SELECT date + INTERVAL 1 DAY FROM last_transfer_pattern_scan")->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * @param $date
     */
    public function setLastScanDate($date) {
        $db = call_user_func($this->dbFactory);

        $db->prepare("UPDATE last_transfer_pattern_scan SET date = ?")->execute([$date]);
    }

    /**
     * @param $day
     * @return Connection[]
     */
    private function getTimetables($day) {
        $timetables = [];

        foreach (self::HOURS as $hour) {
            $timetables[] = $this->loader->getTimetableConnections(strtotime("{$day} {$hour}"));
        }

        return $timetables;
    }
}
