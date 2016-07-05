<?php

namespace JourneyPlanner\App;

use JourneyPlanner\App\Console\Command\FindTransferPatterns;
use JourneyPlanner\App\Console\Command\PlanJourney;
use JourneyPlanner\App\Console\Command\CreateShortestPathTree;
use JourneyPlanner\Lib\Storage\DatabaseLoader;
use JourneyPlanner\Lib\Storage\TransferPatternPersistence;
use JourneyPlanner\Lib\Storage\TreePersistence;
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
        
        $this['command.transfer_pattern'] = function(Container $container) {
            return new FindTransferPatterns(
                $container['loader.database'],
                new ProcessManager(),
                new ChunkStrategy(8),
                [$this, 'createPDO']
            );
        };

        $this['loader.database'] = function(Container $container) {
            return new DatabaseLoader($container['db']);
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
        $user = $_SERVER["DATABASE_USERNAME"];
        $pass = $_SERVER["DATABASE_PASSWORD"];
        $host = $_SERVER["DATABASE_HOSTNAME"];

        $pdo = new PDO("mysql:host={$host};dbname=ojp", $user, $pass);
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
