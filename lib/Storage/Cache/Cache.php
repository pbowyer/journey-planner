<?php

namespace JourneyPlanner\Lib\Storage\Cache;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
interface Cache {

    public function get(string $key);

    public function getObject(string $key);

    public function set(string $key, string $value);

    public function setObject(string $string, $value);

}