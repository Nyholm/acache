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

use Radebatz\ACache\WincacheCache;

/**
 * WincacheCache tests.
 */
class WincacheCacheTest extends NamespaceCacheTest
{

    /**
     * Check if wincache is available.
     */
    protected function hasWincache()
    {
        return function_exists('wincache_ucache_exists');
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        if (!$this->hasWincache()) {
            $this->markTestSkipped('Skipping Wincache');
        }
    }

    /**
     * Cache provider.
     */
    public function cacheProvider()
    {
        if (!$this->hasWincache()) {
            return null;
        }

        return array(
            array(new WincacheCache())
        );
    }

}