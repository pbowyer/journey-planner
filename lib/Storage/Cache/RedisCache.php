<?php

namespace JourneyPlanner\Lib\Storage\Cache;

use Redis;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class RedisCache implements Cache {

    const DB_INDEX = 1;

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @param Redis $redis
     */
    public function __construct(Redis $redis) {
        $this->redis = $redis;
    }

    /**
     * @param string $key
     * @return bool|string
     */
    public function get(string $key) {
        return $this->redis->get($key);
    }

    /**
     * @param string $key
     * @return bool|string
     */
    public function getObject(string $key) {
        $value = $this->redis->get($key);

        return $value ? unserialize($value) : false;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function set(string $key, string $value) {
        $this->redis->set($key, $value);
    }

    /**
     * @param string $key
     * @param $value
     */
    public function setObject(string $key, $value) {
        $this->redis->set($key, serialize($value));
    }

}