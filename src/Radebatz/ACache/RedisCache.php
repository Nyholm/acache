<?php

/*
* This file is part of the ACache library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ACache;

use Redis;

/**
 * Redis cache.
 *
 * @author Martin Rademacher <mano@radebatz.net>
 */
class RedisCache extends AbstractPathKeyCache
{
    protected $redis;

    /**
     * Create instance.
     *
     * @param \Redis $redis The <code>Redis</code> instance to be used.
     */
    public function __construct(Redis $redis)
    {
        parent::__construct();

        // need serialization
        if (Redis::SERIALIZER_NONE == $redis->getOption(Redis::OPT_SERIALIZER)) {
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        }

        $this->redis = $redis;
    }

    /**
     * {@inheritDoc}
     */
    protected function fetchEntry($id)
    {
        return $this->redis->get($id);
    }

    /**
     * {@inheritDoc}
     */
    protected function containsEntry($id)
    {
        return $this->redis->exists($id);
    }

    /**
     * {@inheritDoc}
     */
    protected function saveEntry($id, $entry, $lifeTime = 0)
    {
        if (!$lifeTime) {
            return $this->redis->set($id, $entry);
        }

        // ttl is in ms
        return $this->redis->setex($id, (int) $lifeTime * 1000, $entry);
    }

    /**
     * {@inheritDoc}
     */
    protected function deleteEntry($id)
    {
        return 1 === $this->redis->delete($id);
    }

    /**
     * {@inheritDoc}
     */
    public function flush($namespace = null)
    {
        if (!$namespace) {
            return $this->redis->flushDB();
        } else {
            $namespace = implode($this->getNamespaceDelimiter(), (array) $namespace);
            // iterate over all entries and delete matching
            foreach ($this->redis->getKeys($namespace.'*') as $key) {
                $this->redis->delete($key);
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getStats()
    {
        $info = $this->redis->info();

        return array(
            CacheInterface::STATS_SIZE => $this->redis->dbSize(),
            CacheInterface::STATS_UPTIME => $info['uptime_in_seconds'],
            CacheInterface::STATS_MEMORY_USAGE => $info['used_memory'],
        );
    }

}