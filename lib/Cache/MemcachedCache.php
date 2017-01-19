<?php

namespace JourneyPlanner\Lib\Cache;

use Memcached;

/**
 * @author Linus Norton <linusnorton@gmail.com>
 */
class MemcachedCache implements Cache {

    private $memcached;

    /**
     * @param Memcached $memcached
     */
    public function __construct(Memcached $memcached) {
        $this->memcached = $memcached;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key) {
        return $this->memcached->get($key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getObject(string $key) {
        return $this->memcached->get($key);
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function set(string $key, string $value) {
        return $this->memcached->set($key, $value);
    }

    /**
     * @param string $key
     * @param $value
     * @return bool
     */
    public function setObject(string $key, $value) {
        return $this->memcached->set($key, $value);
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

