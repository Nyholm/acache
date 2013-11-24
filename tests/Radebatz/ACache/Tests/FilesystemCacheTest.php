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

use Radebatz\ACache\FilesystemCache;

/**
 * FilesystemCache tests.
 */
class FilesystemCacheTest extends NamespaceCacheTest
{

    /**
     * Get a temp directory.
     *
     * @param  int    $perms File permissions.
     * @return string The directory name.
     */
    protected function getTempDir($perms = 0777)
    {
        $tempfile = tempnam(sys_get_temp_dir(), '');
        if (file_exists($tempfile)) {
            unlink($tempfile);
        }
        mkdir($tempfile);
        chmod($tempfile, $perms);

        return $tempfile;
    }

    /**
     * Cache provider.
     */
    public function cacheProvider()
    {
        return array(
            array(new FilesystemCache($this->getTempDir()))
        );
    }

    /**
     * Test not writeable folder.
     *
     * @expectedException InvalidArgumentException
     */
    public function testNotWriteable()
    {
        $dir = $this->getTempDir(0000);
        if (is_writeable($dir)) {
            $this->markTestSkipped('Seems chmod is not supported here.');
        }

        new FilesystemCache($dir);
    }

    /**
     * Test invalid directory.
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalid()
    {
        new FilesystemCache(tempnam(sys_get_temp_dir(), 'acache_'));
    }

    /**
     * Test directory.
     */
    public function testDirectory()
    {
        $dir = $this->getTempDir();
        $cache = new FilesystemCache($dir);
        $this->assertEquals($dir, $cache->getDirectory());
    }

}