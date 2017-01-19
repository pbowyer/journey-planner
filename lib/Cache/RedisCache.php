<?php

namespace JourneyPlanner\Lib\Cache;

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

    /**
     * Cache the return value of a method
     *
     * @param string $key
     * @param callable $method
     * @param array $args
     * @return mixed
     */
    public function cacheMethod(string $key, callable $method, ...$args) {
        $cachedValue = $this->getObject($key);

        if ($cachedValue !== false) {
            return $cachedValue;
        }

        $result = call_user_func_array($method, $args);

        $this->setObject($key, $result);

        return $result;
    }

}