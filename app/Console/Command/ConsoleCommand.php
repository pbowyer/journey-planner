<?php

namespace JourneyPlanner\App\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends Command {

    protected function outputTask(OutputInterface $out, $name, $fn) {
        $out->write("# {$name}");
        $timeStart = microtime(true);
        $result = $fn();
        $timeEnd = microtime(true);
        $time = sprintf("%8s", round($timeEnd - $timeStart, 4));

        $out->writeLn(str_pad(" finished {$time}s #", 78 - strlen($name), ".", STR_PAD_LEFT));

        return $result;
    }

    protected function outputHeading(OutputInterface $out, $heading) {
        $out->writeLn(str_pad(" {$heading} ", 80, "#", STR_PAD_BOTH));
    }

}
