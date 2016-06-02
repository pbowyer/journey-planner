<?php

namespace JourneyPlanner\App\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use JourneyPlanner\Lib\DatabaseLoader;
use JourneyPlanner\Lib\TreePersistence;
use JourneyPlanner\Lib\DijkstraShortestPath;
use JourneyPlanner\Lib\ConnectionScanner;

class CreateShortestPathTree extends ConsoleCommand {
    const NAME = 'create-tree';
    const DESCRIPTION = 'Populate the shortest path tree for every station';

    /**
     * @var DatabaseLoader
     */
    private $loader;

    /**
     * @var TreePersistence
     */
    private $treePersistence;

    /**
     * @param DatabaseLoader $loader
     */
    public function __construct(DatabaseLoader $loader, TreePersistence $treePersistence) {
        parent::__construct();

        $this->loader = $loader;
        $this->treePersistence = $treePersistence;
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
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $out) {
        $this->outputHeading($out, "Shortest Path Tree");

        $this->outputTask($out, "Populating fastest connections", function() {
            $this->treePersistence->populateFastestConnections();
        });

        $timetable = $this->outputTask($out, "Calculating fastest connections", function() {
            return $this->loader->getFastestConnections();
        });

        $pathFinder = $this->outputTask($out, "Creating path finder", function() use ($timetable) {
            return new DijkstraShortestPath($timetable);
        });

        $this->outputTask($out, "Populating shortest path trees", function() use ($pathFinder) {
            $this->treePersistence->populateShortestPaths($pathFinder);
        });

        return 0;
    }
}
