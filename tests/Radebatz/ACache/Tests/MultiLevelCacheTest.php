<?php

/*
* This file is part of the ACache library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ACache\Tests;

use Radebatz\ACache\CacheInterface;
use Radebatz\ACache\ArrayCache;
use Radebatz\ACache\MultiLevelCache;

/**
 * MultiLevelCache tests
 *
 * @author Martin Rademacher <mano@radebatz.net>
 */
class MultiLevelCacheTest extends CacheTest
{

    /**
     * Cache provider.
     */
    public function cacheProvider()
    {
        return array(
            array(new MultiLevelCache(array(new ArrayCache()), false)),
            array(new MultiLevelCache(array(new ArrayCache(), new ArrayCache()), false)),
            array(new MultiLevelCache(array(new ArrayCache()), true)),
            array(new MultiLevelCache(array(new ArrayCache(), new ArrayCache()), true)),
        );
    }

    /**
     * Test default stuff.
     */
    public function testDefaults()
    {
        $cache = new MultiLevelCache(array(new ArrayCache(), new ArrayCache()));
        $this->assertFalse($cache->contains('yin'));
        $this->assertNull($cache->fetch('yin'));

        $this->assertTrue($cache->save('yin', 'yang'));
        $this->assertTrue($cache->contains('yin'));
        $this->assertEquals('yang', $cache->fetch('yin'));

        $cache->flush();
        $this->assertFalse($cache->contains('foo'));
        foreach ($cache->getStack() as $sc) {
            $stats = $sc->getStats();
            $this->assertEquals(0, $stats[CacheInterface::STATS_SIZE]);
        }
    }

    /**
     * Test no bubbles.
     */
    public function testNoBubbles()
    {
        // no bubbles :{
        $cache = new MultiLevelCache(array(new ArrayCache(), new ArrayCache()), false);

        $this->assertFalse($cache->isBubbleOnFetch());

        // save
        $this->assertTrue($cache->save('yin', 'yang'));
        // ensure we have populated all caches in the stack
        foreach ($cache->getStack() as $sc) {
            $stats = $sc->getStats();
            $this->assertEquals(1, $stats[CacheInterface::STATS_SIZE]);
        }

        // flush 1st level
        $stack = $cache->getStack();
        $stack[0]->flush();

        // fetch
        $this->assertEquals('yang', $cache->fetch('yin'));
        // check that fetch hasn't triggered any bubbles
        foreach ($cache->getStack() as $ii => $sc) {
            $stats = $sc->getStats();
            $this->assertEquals($ii, $stats[CacheInterface::STATS_SIZE]);
        }
    }


    /**
     * Test bubbles.
     */
    public function testBubbles()
    {
        // bubbles :}
        $cache = new MultiLevelCache(array(new ArrayCache(), new ArrayCache()), true);

        $this->assertTrue($cache->isBubbleOnFetch());

        // save
        $this->assertTrue($cache->save('yin', 'yang'));
        // ensure we have populated all caches in the stack
        foreach ($cache->getStack() as $sc) {
            $stats = $sc->getStats();
            $this->assertEquals(1, $stats[CacheInterface::STATS_SIZE]);
        }

        // flush 1st level
        $stack = $cache->getStack();
        $stack[0]->flush();

        // fetch
        $this->assertEquals('yang', $cache->fetch('yin'));
        // check that fetch has triggered bubbles
        foreach ($cache->getStack() as $sc) {
            $stats = $sc->getStats();
            $this->assertEquals(1, $stats[CacheInterface::STATS_SIZE]);
        }
    }

}
