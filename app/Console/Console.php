<?php

namespace JourneyPlanner\App\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use JourneyPlanner\App\Container;

class Console extends Application {

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;

        parent::__construct($container['name'], $container['version']);
    }

    /**
     * @return Command[]
     */
    protected function getDefaultCommands() {
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = $this->container['command.plan_journey'];
        $defaultCommands[] = $this->container['command.create_tree'];

        return $defaultCommands;
    }
}
