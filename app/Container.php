<?php

namespace JourneyPlanner\App;

use JourneyPlanner\App\Console\Command\AssignStationClusters;
use JourneyPlanner\App\Console\Command\FindTransferPatterns;
use JourneyPlanner\App\Console\Command\PlanJourney;
use JourneyPlanner\App\Console\Console;
use JourneyPlanner\Lib\Storage\Schedule\CachedProvider;
use JourneyPlanner\Lib\Storage\Station\DatabaseStationProvider;
use JourneyPlanner\Lib\Storage\Cache\RedisCache;
use PDO;
use Pimple\Container as PimpleContainer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Redis;
use Spork\ProcessManager;
use Spork\Batch\Strategy\ChunkStrategy;

class Container extends PimpleContainer {

    /**
     * @param array $values
     */
    public function __construct(array $values = []) {
        parent::__construct($values);

        $this['name'] = 'PHP Journey Planner';
        $this['version'] = '1.1';

        $this['console'] = function($container) {
            return new Console($container);
        };

        $this['db'] = $this->createPDO();

        $this['command.plan_journey'] = function($container) {
            return new PlanJourney($container['provider.station'], $container['provider.schedule']);
        };

        $this['command.assign_clusters'] = function($container) {
            return new AssignStationClusters($container['db']);
        };
        
        $this['command.transfer_pattern'] = function($container) {
            return new FindTransferPatterns(
                $container['provider.station'],
                $container['provider.schedule'],
                new ProcessManager(),
                new ChunkStrategy($container['cpu.cores']),
                [$this, 'createPDO']
            );
        };

        $this['provider.station'] = function($container) {
            return new DatabaseStationProvider($container['db']);
        };

        $this['provider.schedule'] = function($container) {
            return new CachedProvider($container['db'], $container['cache']);
        };

        $this['logger'] = function() {
            $stream = new StreamHandler('php://stdout');
            $logger = new Logger('php-journey-planner');
            $logger->pushHandler($stream);

            return $logger;
        };

        $this['cpu.cores'] = function () {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            return count($matches[0]);
        };

        $this['cache.redis'] = function () {
            $redis = new Redis();
            $redis->connect('127.0.0.1');

            return $redis;
        };

        $this['cache'] = function ($container) {
            return new RedisCache($container['cache.redis']);
        };
    }

    /**
     * @return PDO
     */
    public function createPDO() {
        $user = $_SERVER["DATABASE_USERNAME"] ?? "root";
        $pass = $_SERVER["DATABASE_PASSWORD"] ?? "";
        $host = $_SERVER["DATABASE_HOSTNAME"] ?? "localhost";

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
