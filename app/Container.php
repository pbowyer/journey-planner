<?php

namespace JourneyPlanner\App;

use JourneyPlanner\App\Console\Command\PlanJourney;
use JourneyPlanner\App\Console\Command\CreateShortestPathTree;
use JourneyPlanner\Lib\DatabaseLoader;
use JourneyPlanner\Lib\TreePersistence;
use PDO;
use Pimple\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Spork\ProcessManager;
use Spork\Batch\Strategy\ChunkStrategy;

class Container extends Container {

    /**
     * @param array $values
     */
    public function __construct(array $values = []) {
        parent::__construct($values);

        $this['name'] = 'PHP Journey Planner';
        $this['version'] = '1.1';

        $this['console'] = function(Container $container) {
            return new Console($container);
        };

        $this['db'] = $this->createPDO();

        $this['command.plan_journey'] = function(Container $container) {
            return new PlanJourney($container['loader.database']);
        };

        $this['command.create_tree'] = function(Container $container) {
            return new CreateShortestPathTree($container['loader.database'], $container['persistence.tree']);
        };

        $this['loader.database'] = function(Container $container) {
            return new DatabaseLoader($container['db']);
        };

        $this['persistence.tree'] = function(Container $container) {
            $manager = new ProcessManager();
            $strategy = new ChunkStrategy(32);

            return new TreePersistence($manager, $strategy, [$this, 'createPDO']);
        };

        $this['logger'] = function() {
            $stream = new StreamHandler('php://stdout');
            $logger = new Logger('php-journey-planner');
            $logger->pushHandler($stream);

            return $logger;
        };
    }

    /**
     * @return PDO
     */
    public function createPDO() {
        $pdo = new PDO('mysql:host=localhost;dbname=ojp', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * @return Console
     */
    public function getConsole() {
        return $this['console'];
    }
}
