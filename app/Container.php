<?php

namespace JourneyPlanner\App;

use JourneyPlanner\App\Console\Command\PlanJourney;
use JourneyPlanner\App\Console\Console;
use JourneyPlanner\Lib\Journey\Repository\FixedLegRepository;
use JourneyPlanner\Lib\Journey\Repository\InterchangeRepository;
use JourneyPlanner\Lib\Journey\Repository\TimetableLegRepository;
use JourneyPlanner\Lib\Planner\Filter\SlowJourneyFilter;
use JourneyPlanner\Lib\Planner\GroupStationJourneyPlanner;
use JourneyPlanner\Lib\Station\Repository\StationRepository;
use JourneyPlanner\Lib\Cache\MemcachedCache;
use JourneyPlanner\Lib\Cache\RedisCache;
use JourneyPlanner\Lib\TransferPattern\Repository\TransferPatternRepository;
use Memcached;
use PDO;
use Pimple\Container as PimpleContainer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Redis;

class Container extends PimpleContainer {

    /**
     * @param array $values
     */
    public function __construct(array $values = []) {
        parent::__construct($values);

        $this['name'] = 'PHP Journey Planner';
        $this['version'] = '2.0';

        $this['console'] = function($container) {
            return new Console($container);
        };

        $this['db'] = $this->createPDO();

        $this['command.plan_journey'] = function($container) {
            return new PlanJourney($container['planner.group_station'], $container['repository.station']);
        };

        $this['repository.station'] = function($container) {
            return new StationRepository($container['db']);
        };

        $this['repository.transfer_pattern'] = function($container) {
            return new TransferPatternRepository($container['db'], $container['cache'], $container['repository.timetable_leg']);
        };

        $this['repository.timetable_leg'] = function($container) {
            return new TimetableLegRepository($container['db'], $container['cache']);
        };

        $this['repository.fixed_leg'] = function($container) {
            return new FixedLegRepository($container['db'], $container['cache']);
        };

        $this['repository.interchange'] = function($container) {
            return new InterchangeRepository($container['db'], $container['cache']);
        };

        $this['planner.group_station'] = function($container) {
            return new GroupStationJourneyPlanner(
                $container['repository.transfer_pattern'],
                $container['repository.station'],
                $container['repository.fixed_leg'],
                $container['repository.interchange'],
                [new SlowJourneyFilter()]
            );
        };


        $this['logger'] = function() {
            $stream = new StreamHandler('php://stdout');
            $logger = new Logger('php-journey-planner');
            $logger->pushHandler($stream);

            return $logger;
        };

        $this['cache.redis'] = function () {
            $redis = new Redis();
            $redis->connect('127.0.0.1');
            $redis->select(RedisCache::DB_INDEX);

            return $redis;
        };

        $this['cache.memcached'] = function () {
            $memcached = new Memcached();
            $memcached->addServer('127.0.0.1', 11211);

            return $memcached;
        };

        $this['cache'] = function ($container) {
            return new MemcachedCache($container['cache.memcached']);
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
